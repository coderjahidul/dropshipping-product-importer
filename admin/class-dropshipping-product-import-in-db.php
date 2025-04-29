<?php
function dropshipping_product_import_in_db() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sync_dropshipping_product';
    $dpi_import_limit = get_option('dpi_import_limit', 10);
    $results = [
        'success' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'messages' => []
    ];

    $products = $wpdb->get_results(
        "SELECT id, value FROM $table_name WHERE status = 'pending' ORDER BY id ASC LIMIT $dpi_import_limit"
    );

    if (empty($products)) {
        $results['messages'][] = "No pending products found.";
        return $results;
    }

    foreach ($products as $product) {
        try {
            $product_data = json_decode($product->value, true);

            if (!$product_data) {
                $results['skipped']++;
                $results['messages'][] = "Product ID {$product->id}: Invalid JSON data";
                continue;
            }

            // Extract product data
            $external_id = $product_data['id'];
            $product_name = $product_data['name'];
            $product_code = $product_data['product_code'];
            $product_category = $product_data['category'];
            $product_slug = $product_data['slug'];
            $product_merchant_price = $product_data['sale_price'];
            $product_price = $product_data['price'];
            $product_description = $product_data['details'];
            $product_stock = ($product_data['status'] === 'active') ? 'instock' : 'outofstock';
            $product_variants = isset($product_data['product_variants']) ? $product_data['product_variants'] : [];
            $product_image_url = $product_data['thumbnail_img'];
            $product_gallery_images = isset($product_data['product_images']) ? $product_data['product_images'] : [];

            // Check if product exists by SKU
            $existing_product_id = wc_get_product_id_by_sku($product_code);
            
            if ($existing_product_id) {
                // Update existing product (same as before)
                $wc_product = wc_get_product($existing_product_id);
                $wc_product->set_regular_price($product_price);
                $wc_product->set_sale_price($product_merchant_price);
                $wc_product->set_stock_status($product_stock);
                $product_id = $wc_product->save();
                $results['updated']++;
                $results['messages'][] = "Product {$product_code}: Updated successfully";
            } else {
                // Create new product
                if (!empty($product_variants)) {
                    // Create variable product
                    $wc_product = new WC_Product_Variable();
                    
                    // Extract all unique attributes
                    $size_options = [];
                    $color_options = [];
                    
                    foreach ($product_variants as $variant) {
                        if ($variant['attribute'] === 'Size') {
                            $size_options[] = $variant['variant'];
                        } elseif ($variant['attribute'] === 'Color') {
                            $color_options[] = $variant['variant'];
                        }
                    }
                    
                    // Create Size attribute
                    if (!empty($size_options)) {
                        $size_attribute = new WC_Product_Attribute();
                        $size_attribute->set_name('Size');
                        $size_attribute->set_options(array_unique($size_options));
                        $size_attribute->set_visible(true);
                        $size_attribute->set_variation(true);
                    }
                    
                    // Create Color attribute
                    if (!empty($color_options)) {
                        $color_attribute = new WC_Product_Attribute();
                        $color_attribute->set_name('Color');
                        $color_attribute->set_options(array_unique($color_options));
                        $color_attribute->set_visible(true);
                        $color_attribute->set_variation(true);
                    }
                    
                    // Set attributes to product
                    $attributes = [];
                    if (!empty($size_options)) $attributes[] = $size_attribute;
                    if (!empty($color_options)) $attributes[] = $color_attribute;
                    $wc_product->set_attributes($attributes);
                    
                    // Save product to get ID
                    $product_id = $wc_product->save();
                    
                    // Create variations for each combination
                    foreach ($product_variants as $variant) {
                        $variation = new WC_Product_Variation();
                        $variation->set_parent_id($product_id);
                        
                        // Set attributes
                        $variation_attributes = [];
                        if ($variant['attribute'] === 'Size') {
                            $variation_attributes['pa_size'] = sanitize_title($variant['variant']);
                        } elseif ($variant['attribute'] === 'Color') {
                            $variation_attributes['pa_color'] = sanitize_title($variant['variant']);
                        }
                        
                        $variation->set_attributes($variation_attributes);
                        $variation->set_regular_price($product_price);
                        $variation->set_sale_price($product_merchant_price);
                        $variation->set_stock_status($product_stock);
                        $variation->set_sku($product_code . '-' . strtolower($variant['variant']));
                        
                        // Set variation image if available
                        if (isset($variant['image'])) {
                            $image_id = upload_image_from_url($variant['image'], 
                                "{$product_name} - {$variant['variant']}");
                            if ($image_id) {
                                $variation->set_image_id($image_id);
                            }
                        }
                        
                        $variation->save();
                    }
                    
                    $wc_product->variable_product_sync();
                } else {
                    // Create simple product (same as before)
                    $wc_product = new WC_Product_Simple();
                }

                // Set common product properties
                $wc_product->set_name($product_name);
                $wc_product->set_sku($product_code);
                $wc_product->set_slug(sanitize_title($product_slug));
                $wc_product->set_description($product_description);
                $wc_product->set_regular_price($product_price);
                $wc_product->set_sale_price($product_merchant_price);
                $wc_product->set_status('publish');
                $wc_product->set_catalog_visibility('visible');
                $wc_product->set_stock_status($product_stock);

                // Handle category
                $category_ids = [];
                $category = get_term_by('name', $product_category, 'product_cat');
                if (!$category) {
                    $category = wp_insert_term($product_category, 'product_cat');
                    if (!is_wp_error($category)) {
                        $category_ids[] = $category['term_id'];
                    }
                } else {
                    $category_ids[] = $category->term_id;
                }
                $wc_product->set_category_ids($category_ids);

                // Handle images
                $image_ids = [];
                if (!empty($product_image_url)) {
                    $image_id = upload_image_from_url($product_image_url, $product_name);
                    if ($image_id) {
                        $image_ids[] = $image_id;
                        $wc_product->set_image_id($image_id);
                    }
                }
                
                if (!empty($product_gallery_images)) {
                    foreach ($product_gallery_images as $gallery_image) {
                        $gallery_image_id = upload_image_from_url($gallery_image['product_image'], 
                            "{$product_name} - Gallery");
                        if ($gallery_image_id) {
                            $image_ids[] = $gallery_image_id;
                        }
                    }
                    $wc_product->set_gallery_image_ids($image_ids);
                }

                $product_id = $wc_product->save();
                $results['success']++;
            }

            // Mark as complete
            $wpdb->update(
                $table_name,
                ['status' => 'complete'],
                ['id' => $product->id],
                ['%s'],
                ['%d']
            );

        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "Product ID {$product->id}: Error - " . $e->getMessage();
            continue;
        }
    }

    return $results;
}

/**
 * Upload image from URL helper function
 */
function upload_image_from_url($image_url, $image_name) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $image_name = sanitize_file_name($image_name);
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        error_log("Image download failed: " . $tmp->get_error_message());
        return false;
    }
    
    $file_array = [
        'name' => $image_name . '.jpg',
        'tmp_name' => $tmp
    ];
    
    $id = media_handle_sideload($file_array, 0);
    
    @unlink($file_array['tmp_name']);
    
    if (is_wp_error($id)) {
        error_log("Image upload failed: " . $id->get_error_message());
        return false;
    }
    
    return $id;
}