<?php
/**
 * Cron-friendly Sync Script for M. Pietro
 * Can be run via CLI: php sync_products.php
 * Or via Web: proxy.php?endpoint=sync
 */

require_once(__DIR__ . "/../../Stricker_REST/PHP_StrickerBRRestClient_1.1/StrickerRestClient.php");

class StrickerSyncer {
    private $api;
    private $cacheDir;

    public function __construct() {
        $this->cacheDir = __DIR__ . '/cache_data';
        if (!is_dir($this->cacheDir)) mkdir($this->cacheDir, 0777, true);
        
        ob_start();
        $this->api = new StrickerRestClient("REMOVED_KEY", "https");
        ob_end_clean();
    }

    public function run() {
        echo "[INFO] Starting full sync at " . date('Y-m-d H:i:s') . "\n";

        // 1. Fetch Categories properly
        echo "[INFO] Fetching Categories Tree...\n";
        $treeJson = $this->api->GetProductsTree();
        $categories = [];
        if ($treeJson) {
            $tree = json_decode($treeJson);
            // The tree might have a key like "Categories" or be the array itself
            $root = $tree->Categories ?? $tree->ProductTree ?? [];
            
            // If it's the "ProductsTree" from Stricker, it's actually products grouped by cat?
            // Let's also look for a flatter category list if available or use product types
        }

        // 2. Fetch Products
        echo "[INFO] Fetching Products...\n";
        $productsJson = $this->api->GetProducts();
        $productsData = json_decode($productsJson);
        if (!isset($productsData->Products)) {
            echo "[ERROR] No products found in API response.\n";
            return;
        }

        // 3. Fetch Stocks
        echo "[INFO] Fetching Stocks...\n";
        $stocksJson = $this->api->GetStocks();
        $stocksData = json_decode($stocksJson);
        $stocksMap = [];
        if (isset($stocksData->Stocks)) {
            foreach ($stocksData->Stocks as $s) {
                $ref = explode('-', $s->Sku)[0];
                $stocksMap[$ref] = ($stocksMap[$ref] ?? 0) + (int)$s->Quantity;
            }
        }

        // 4. Enrich and save
        $allProducts = [];
        $seen = [];
        $extractedCats = [];

        foreach ($productsData->Products as $p) {
            $sku = (string)$p->ProdReference;
            if (isset($seen[$sku])) continue;
            $seen[$sku] = true;

            $type = (string)($p->Type ?? '');
            $subType = (string)($p->SubType ?? '');
            if ($type) $extractedCats[] = $type;
            if ($subType) $extractedCats[] = $subType;

            $enriched = [
                'prod_reference' => $sku,
                'name' => (string)($p->Name ?? ''),
                'main_image' => (string)($p->MainImage ?? ''),
                'brand' => (string)($p->Brand ?? 'Premium'),
                'type' => $type,
                'sub_type' => $subType,
                'stock_total' => (int)($stocksMap[$sku] ?? 0),
                'short_description' => (string)($p->ShortDescription ?? ''),
                'description' => (string)($p->Description ?? ''),
                'colors' => (string)($p->Colors ?? ''),
                'all_images' => (string)($p->AllImageList ?? $p->MainImage)
            ];

            $allProducts[] = $enriched;
            file_put_contents($this->cacheDir . '/p_' . $sku . '.json', json_encode($enriched));
        }

        // Save real categories found in products
        $finalCats = array_values(array_unique(array_filter($extractedCats)));
        sort($finalCats);
        file_put_contents($this->cacheDir . '/categories_list.json', json_encode($finalCats));

        file_put_contents($this->cacheDir . '/products_all.json', json_encode($allProducts));
        file_put_contents($this->cacheDir . '/last_sync.txt', time());

        echo "[SUCCESS] Sync complete. Products: " . count($allProducts) . ", Categories: " . count($finalCats) . "\n";
    }
}

if (php_sapi_name() === 'cli') {
    $syncer = new StrickerSyncer();
    $syncer->run();
}
