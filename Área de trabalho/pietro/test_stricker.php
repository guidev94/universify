<?php

require_once("Stricker_REST/PHP_StrickerBRRestClient_1.1/StrickerRestClient.php");

$key = "REMOVED_KEY";
$protocol = "https";
$webservice = new StrickerRestClient($key, $protocol);

echo "--- Validating Token ---\n";
echo $webservice->ValidateToken() . "\n\n";

echo "--- Getting Products (First 5) ---\n";
$productsJson = $webservice->GetProducts();
$productsData = json_decode($productsJson);
$products = $productsData->Products ?? null;

if (isset($products) && is_array($products)) {
    echo "Total products found: " . count($products) . "\n";
    foreach (array_slice($products, 0, 5) as $product) {
        echo "Product: " . ($product->Name ?? 'N/A') . " - SKU: " . ($product->ProdReference ?? 'N/A') . "\n";
    }
} else {
    echo "Failed to get products.\n";
}

echo "\n--- Getting Catalog Prices ---\n";
$pricesJson = $webservice->GetCatalogPrices();
$pricesData = json_decode($pricesJson);
if (isset($pricesData->CatalogPrices) && count($pricesData->CatalogPrices) > 0) {
    echo "Total prices found: " . count($pricesData->CatalogPrices) . "\n";
} else {
    echo "No catalog prices found for this account.\n";
}

echo "\n--- Getting Customization Options (First item) ---\n";
$custJson = $webservice->GetCustomizationsOptions();
$custData = json_decode($custJson);
$cust = $custData->CustomizationOptions ?? null;

if (isset($cust) && is_array($cust) && count($cust) > 0) {
    echo "Total customization options: " . count($cust) . "\n";
    echo "Sample Item:\n";
    print_r($cust[0]);
} else {
    echo "No customization options found.\n";
}

echo "\n--- Getting Products Tree (Raw check) ---\n";
$treeJson = $webservice->GetProductsTree();
echo "Raw Tree Response (first 200 chars): " . substr($treeJson, 0, 200) . "\n";
$treeData = json_decode($treeJson);
// ... existing logic ...

echo "\n--- Getting Colors (First 5) ---\n";
$colorsJson = $webservice->GetColors();
$colorsData = json_decode($colorsJson);
$colors = $colorsData->Colors ?? null;
if (isset($colors) && is_array($colors)) {
    echo "Total colors found: " . count($colors) . "\n";
    foreach (array_slice($colors, 0, 5) as $color) {
        echo "Color: " . ($color->Name ?? 'N/A') . " - ID: " . ($color->ColorID ?? 'N/A') . "\n";
    }
}

echo "\n--- Getting Product Types (First 5) ---\n";
$typesJson = $webservice->GetProductTypes();
$typesData = json_decode($typesJson);
$types = $typesData->ProductTypes ?? null;
if (isset($types) && is_array($types)) {
    echo "Total types found: " . count($types) . "\n";
    foreach (array_slice($types, 0, 5) as $type) {
        echo "Type: " . ($type->TypeName ?? 'N/A') . "\n";
    }
}

echo "\n--- Getting Stocks (First 5) ---\n";
$stocksJson = $webservice->GetStocks();
$stocksData = json_decode($stocksJson);
$stocks = $stocksData->Stocks ?? null;

if (isset($stocks) && is_array($stocks)) {
    echo "Total stocks records found: " . count($stocks) . "\n";
    foreach (array_slice($stocks, 0, 5) as $stock) {
        echo "SKU: " . ($stock->Sku ?? 'N/A') . " - Stock: " . ($stock->Quantity ?? 'N/A') . "\n";
    }
} else {
    echo "Failed to get stocks.\n";
}
