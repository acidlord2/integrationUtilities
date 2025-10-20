<?php
// Extract real product IDs from MoySklad system
require_once(__DIR__ . '/docker-config.php');
require_once(__DIR__ . '/classes/MS/v2/Api.php');

echo "🔍 Extracting real product IDs from MoySklad system...\n";

try {
    $api = new \MS\v2\Api();
    
    // Get products from assortment API
    $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product?limit=20';
    $response = $api->getData($url);
    
    if ($response === false) {
        echo "❌ Failed to fetch products\n";
        exit(1);
    }
    
    if (is_string($response)) {
        $response = json_decode($response, true);
    }
    
    if (!$response || !isset($response['rows'])) {
        echo "❌ Invalid response format\n";
        exit(1);
    }
    
    $products = $response['rows'];
    
    if (empty($products)) {
        echo "❌ No products found\n";
        exit(1);
    }
    
    echo "✅ Found " . count($products) . " products\n\n";
    echo "📦 Real Product Data:\n";
    echo str_repeat("-", 60) . "\n";
    
    $productData = [];
    foreach ($products as $index => $product) {
        if ($index >= 10) break; // Limit to first 10 products
        
        $productId = basename($product['meta']['href']);
        $productData[] = [
            'id' => $productId,
            'name' => $product['name'],
            'href' => $product['meta']['href'],
            'buyPrice' => $product['buyPrice']['value'] ?? 0,
            'salePrices' => $product['salePrices'] ?? []
        ];
        
        echo "Product " . ($index + 1) . ":\n";
        echo "  ID: " . $productId . "\n";
        echo "  Name: " . $product['name'] . "\n";
        echo "  Buy Price: " . number_format(($product['buyPrice']['value'] ?? 0) / 100, 2) . " RUB\n";
        echo "  Sale Prices: " . count($product['salePrices'] ?? []) . "\n";
        echo "  Href: " . $product['meta']['href'] . "\n";
        echo "\n";
    }
    
    // Output as PHP array for easy copying
    echo str_repeat("=", 60) . "\n";
    echo "PHP Array for test usage:\n";
    echo str_repeat("=", 60) . "\n";
    echo "\$realProducts = " . var_export($productData, true) . ";\n";
    
} catch (Exception $e) {
    echo "🚨 Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>