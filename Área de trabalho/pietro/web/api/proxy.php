<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$cacheDir = __DIR__ . '/cache_data';
$endpoint = $_GET['endpoint'] ?? 'search';

if ($endpoint === 'sync') {
    require_once('sync_products.php');
    $syncer = new StrickerSyncer();
    $syncer->run();
    echo json_encode(['success' => true]);
    exit;
}

if ($endpoint === 'categories') {
    $file = $cacheDir . '/categories_list.json';
    if (file_exists($file)) readfile($file);
    else echo "[]";
    exit;
}

if ($endpoint === 'all_products') {
    $file = $cacheDir . '/products_all.json';
    if (file_exists($file)) readfile($file);
    else echo "[]";
    exit;
}

if ($endpoint === 'product') {
    $sku = $_GET['sku'] ?? '';
    $file = $cacheDir . '/p_' . $sku . '.json';
    if (file_exists($file)) readfile($file);
    else echo json_encode(['error' => 'Product not found']);
    exit;
}

if ($endpoint === 'search') {
    $file = $cacheDir . '/products_all.json';
    if (!file_exists($file)) { echo "[]"; exit; }
    
    $all = json_decode(file_get_contents($file));
    $q = mb_strtolower($_GET['q'] ?? '', 'UTF-8');
    $cat = $_GET['cat'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $filtered = [];
    foreach ($all as $p) {
        $matchCat = !$cat || $p->type === $cat || $p->sub_type === $cat;
        $matchQuery = !$q || 
                      mb_strpos(mb_strtolower($p->name, 'UTF-8'), $q) !== false || 
                      mb_strpos(mb_strtolower($p->prod_reference, 'UTF-8'), $q) !== false;

        if ($matchCat && $matchQuery) {
            $filtered[] = $p;
        }
    }
    echo json_encode(array_slice($filtered, $offset, $limit));
    exit;
}
