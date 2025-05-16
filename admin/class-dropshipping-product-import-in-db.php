<?php
function dropshipping_product_import_in_db() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sync_dropshipping_product';
    $dpi_import_limit = get_option('dpi_import_limit', 1);
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

                // Set sale price if discount
                if ((float) get_option('dpi_discount_percentage', 0) > 0) {
                    $product_discount_price = get_discounted_price($product_price);
                    $wc_product->set_sale_price($product_discount_price);
                }

                $wc_product->set_stock_status($product_stock);
                $product_id = $wc_product->save();
                $results['updated']++;
                $results['messages'][] = "Product {$product_code}: Updated successfully";
            } else {
                // Create new product
                if (!empty($product_variants)) {
                    // Create variable product
                    $wc_product = new WC_Product_Variable();
                    $wc_product->set_name($product_name); // Set name or title
                    $wc_product->set_status('publish'); // Optional: set product status

                    // Extract all unique attributes
                    $size_options = [];
                    $color_options = [];

                    foreach ($product_variants as $variant) {
                        if ($variant['attribute'] === 'Size') {
                            $size_options[] = sanitize_title($variant['variant']); // Use slug
                        } elseif ($variant['attribute'] === 'Color') {
                            $color_options[] = sanitize_title($variant['variant']); // Use slug
                        }
                    }

                    $attributes = [];

                    // Create Size attribute
                    if (!empty($size_options)) {
                        $size_attribute = new WC_Product_Attribute();
                        $size_attribute->set_name('Size'); // Must use taxonomy slug
                        $size_attribute->set_options(array_unique($size_options));
                        $size_attribute->set_visible(true);
                        $size_attribute->set_variation(true);
                        $attributes[] = $size_attribute;
                    }

                    // Create Color attribute
                    if (!empty($color_options)) {
                        $color_attribute = new WC_Product_Attribute();
                        $color_attribute->set_name('Color'); // Must use taxonomy slug
                        $color_attribute->set_options(array_unique($color_options));
                        $color_attribute->set_visible(true);
                        $color_attribute->set_variation(true);
                        $attributes[] = $color_attribute;
                    }

                    // Set attributes and save product
                    $wc_product->set_attributes($attributes);
                    $product_id = $wc_product->save();

                    // Make sure terms exist in taxonomy
                    foreach ($size_options as $size_slug) {
                        if (!term_exists($size_slug, 'size')) {
                            wp_insert_term(ucwords(str_replace('-', ' ', $size_slug)), 'size', ['slug' => $size_slug]);
                        }
                    }
                    foreach ($color_options as $color_slug) {
                        if (!term_exists($color_slug, 'color')) {
                            wp_insert_term(ucwords(str_replace('-', ' ', $color_slug)), 'color', ['slug' => $color_slug]);
                        }
                    }

                    // Create variations
                    foreach ($product_variants as $variant) {
                        $variation = new WC_Product_Variation();
                        $variation->set_parent_id($product_id);

                        // Set correct attribute keys
                        $variation_attributes = [];

                        if ($variant['attribute'] === 'Size') {
                            $variation_attributes['size'] = sanitize_title($variant['variant']);
                        } elseif ($variant['attribute'] === 'Color') {
                            $variation_attributes['size'] = sanitize_title($variant['variant']);
                        }

                        $variation->set_attributes($variation_attributes);
                        $variation->set_regular_price($product_price);

                        // Set sale price if discount
                        if ((float) get_option('dpi_discount_percentage', 0) > 0) {
                            $product_discount_price = get_discounted_price($product_price);
                            $variation->set_sale_price($product_discount_price);
                        }


                        $variation->set_stock_status($product_stock);
                        $variation->set_sku($product_code . '-' . strtolower($variant['variant']));

                        // Set variation image
                        if (!empty($variant['image'])) {
                            $image_id = upload_image_from_url($variant['image'], "{$product_name} - {$variant['variant']}");
                            if ($image_id) {
                                $variation->set_image_id($image_id);
                            }
                        }

                        $variation->save();
                    }

                    // Sync variations
                    WC_Product_Variable::sync($product_id);


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

                // Set sale price if discount
                if ((float) get_option('dpi_discount_percentage', 0) > 0) {
                    $product_discount_price = get_discounted_price($product_price);
                    $wc_product->set_sale_price($product_discount_price);
                }

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

                // Get watermark logo URL from settings
                $watermark_url = get_option('dpi_product_watermark_logo');

                // Handle featured image
                if (!empty($product_image_url)) {
                    $image_id = !empty($watermark_url)
                        ? upload_image_with_watermark_url($product_image_url, $product_name, $watermark_url)
                        : upload_image_from_url($product_image_url, $product_name);

                    if ($image_id) {
                        $wc_product->set_image_id($image_id); // Set as thumbnail only
                    }
                }

                // Handle gallery images
                if (!empty($product_gallery_images)) {
                    $gallery_ids = [];

                    foreach ($product_gallery_images as $gallery_image) {
                        $gallery_image_id = !empty($watermark_url)
                            ? upload_image_with_watermark_url($gallery_image['product_image'], "{$product_name} - Gallery", $watermark_url)
                            : upload_image_from_url($gallery_image['product_image'], "{$product_name} - Gallery");

                        if ($gallery_image_id) {
                            $gallery_ids[] = $gallery_image_id;
                        }
                    }

                    $wc_product->set_gallery_image_ids($gallery_ids);
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

// Get discounted price
function get_discounted_price($price) {
    $discount_percentage = (float) get_option('dpi_discount_percentage', 0);
    $price = (float) $price;

    // Clamp discount percentage between 0 and 100
    $discount_percentage = max(0, min(100, $discount_percentage));

    return round($price - ($price * $discount_percentage / 100), 2);
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

function upload_image_with_watermark_url($image_url, $image_name, $watermark_url) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $image_name = sanitize_file_name($image_name);
    $tmp = download_url($image_url);

    if (is_wp_error($tmp)) {
        error_log("Image download failed: " . $tmp->get_error_message());
        return false;
    }

    $source = imagecreatefromstring(file_get_contents($tmp));
    if (!$source) {
        @unlink($tmp);
        error_log("Failed to create image from product image URL.");
        return false;
    }

    // Download watermark logo from URL
    $watermark_tmp = download_url($watermark_url);
    if (is_wp_error($watermark_tmp)) {
        @unlink($tmp);
        error_log("Failed to download watermark image: " . $watermark_tmp->get_error_message());
        return false;
    }

    $watermark = imagecreatefromstring(file_get_contents($watermark_tmp));
    if (!$watermark) {
        @unlink($tmp);
        @unlink($watermark_tmp);
        error_log("Failed to create image from watermark URL.");
        return false;
    }

    imagealphablending($watermark, true);
    imagesavealpha($watermark, true);

    // Get sizes
    $source_width = imagesx($source);
    $source_height = imagesy($source);
    $wm_width = imagesx($watermark);
    $wm_height = imagesy($watermark);

    // Position watermark (Top right)
    $dest_x = $source_width - $wm_width - 20;
    $dest_y = 20;

    // Merge
    imagecopy($source, $watermark, $dest_x, $dest_y, 0, 0, $wm_width, $wm_height);

    // Save to temp file
    $watermarked_tmp = tempnam(sys_get_temp_dir(), 'wm_') . '.jpg';
    imagejpeg($source, $watermarked_tmp, 90);

    // Clean up
    imagedestroy($source);
    imagedestroy($watermark);
    @unlink($tmp);
    @unlink($watermark_tmp);

    // Prepare for sideload
    $file_array = [
        'name' => $image_name . '.jpg',
        'tmp_name' => $watermarked_tmp,
    ];

    // Upload
    $id = media_handle_sideload($file_array, 0);

    @unlink($file_array['tmp_name']);

    if (is_wp_error($id)) {
        error_log("Image upload failed: " . $id->get_error_message());
        return false;
    }

    return $id;
}

