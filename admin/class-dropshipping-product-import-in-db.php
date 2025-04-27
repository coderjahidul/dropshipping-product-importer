<?php
function dropshipping_product_import_in_db() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sync_dropshipping_product';
    $dpi_import_limit = get_option('dpi_import_limit', 10);

    $products = $wpdb->get_results(
        "SELECT id, value FROM $table_name WHERE status = 'pending' ORDER BY id ASC LIMIT $dpi_import_limit"
    );

    if (empty($products)) {
        return "No pending products found.";
    }

    foreach ($products as $product) {
        $product_data = json_decode($product->value, true);

        if (!$product_data) {
            continue;
        }

        // Product information
        $product_id = $product_data['id'];
        // Product Name
        $product_name = $product_data['name'];
        // Product Code
        $product_code = $product_data['product_code'];
        // Product Category
        $product_category = $product_data['category'];
        // Product Slug
        $product_slug = $product_data['slug'];
        // Product Merchant Price
        $product_merchant_price = $product_data['sale_price'];
        // Product Price
        $product_price = $product_data['price'];
        // Product Description
        $product_description = $product_data['details'];
        // if product is active set stock to in stock otherwise out of stock
        $product_stock = ($product_data['status'] === 'active') ? 'instock' : 'outofstock';

        // Product Variants color or size
        $product_variants = isset($product_data['product_variants']) ? $product_data['product_variants'] : [];
        // Product Thumbnail image
        $product_image_url = $product_data['thumbnail_img'];
        // Product Gallery images
        $product_gallery_images = isset($product_data['product_images']) ? $product_data['product_images'] : [];
    }

    return "Products imported or updated successfully.";
}
