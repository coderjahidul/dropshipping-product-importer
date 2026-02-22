<?php
<<<<<<< HEAD
function dropshipping_product_import_in_db() {
=======
function dropshipping_product_import_in_db()
{
>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
    global $wpdb;

    $table_name = $wpdb->prefix . 'sync_dropshipping_product';
    $dpi_import_limit = get_option('dpi_import_limit', 1);
    $selected_category = get_option('dpi_selected_category', '');
    $results = [
        'success' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'messages' => []
    ];

    $query = "SELECT id, value FROM $table_name WHERE status = 'pending'";
    $query_args = [];

    if (!empty($selected_category)) {
        if (is_array($selected_category)) {
            if (!in_array('all', $selected_category)) {
                $placeholders = array_fill(0, count($selected_category), '%s');
                $query .= " AND category_name IN (" . implode(', ', $placeholders) . ")";
                $query_args = array_merge($query_args, $selected_category);
            }
        } else if ($selected_category !== 'all') {
            $query .= " AND category_name = %s";
            $query_args[] = $selected_category;
        }
    }

    $query .= " ORDER BY id ASC LIMIT %d";
    $query_args[] = (int) $dpi_import_limit;

    $products = $wpdb->get_results($wpdb->prepare($query, $query_args));

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
<<<<<<< HEAD

            // Check if product exists by SKU
            $existing_product_id = wc_get_product_id_by_sku($product_code);
            
            if ($existing_product_id) {
                // Get the product object
                $wc_product = wc_get_product($existing_product_id);
            
                // Global product update (for simple products)
                $wc_product->set_regular_price($product_price);
            
                if ((float) get_option('dpi_discount_percentage', 0) > 0) {
                    $product_discount_price = get_discounted_price($product_price);
                    $wc_product->set_sale_price($product_discount_price);
                }else{
                    // delete sale price
                    $wc_product->set_sale_price('');
                }
            
                $wc_product->set_stock_status($product_stock);
            
=======
            $product_tags = isset($product_data['tags']) ? $product_data['tags'] : '';
            $meta_description = isset($product_data['meta_description']) ? $product_data['meta_description'] : (isset($product_data['seo_description']) ? $product_data['seo_description'] : '');
            $focus_keyphrase = isset($product_data['meta_keywords']) ? $product_data['meta_keywords'] : (isset($product_data['focus_keyphrase']) ? $product_data['focus_keyphrase'] : '');

            // Get Gemini settings
            $gemini_api_key = get_option('dpi_gemini_api_key');
            $enable_focus = get_option('dpi_gemini_enable_focus_keyphrase', '1') === '1';
            $enable_meta = get_option('dpi_gemini_enable_meta_description', '1') === '1';
            $enable_tags = get_option('dpi_gemini_enable_tags', '1') === '1';

            // Use Gemini AI to generate SEO data if API key is present and any SEO field is enabled
            if (!empty($gemini_api_key) && ($enable_focus || $enable_meta || $enable_tags)) {
                $gemini_seo = generate_seo_data_with_gemini($product_name, $product_description);

                if ($gemini_seo) {
                    if ($enable_focus && (empty($focus_keyphrase) || $focus_keyphrase === $product_name)) {
                        $focus_keyphrase = $gemini_seo['focus_keyphrase'];
                    }
                    if ($enable_meta && (empty($meta_description) || strlen($meta_description) < 20)) {
                        $meta_description = $gemini_seo['meta_description'];
                    }
                    if ($enable_tags && (empty($product_tags) || count(explode(',', $product_tags)) < 3)) {
                        $product_tags = $gemini_seo['tags'];
                    }
                }
            }

            // Fallbacks for disabled or missing data
            $plain_description = wp_strip_all_tags($product_description);
            if ($enable_meta && empty($meta_description) && !empty($plain_description)) {
                $meta_description = mb_substr($plain_description, 0, 160);
            }
            if ($enable_focus && empty($focus_keyphrase)) {
                $focus_keyphrase = $product_name;
            }

            // Clear fields if explicitly disabled
            if (!$enable_focus)
                $focus_keyphrase = '';
            if (!$enable_meta)
                $meta_description = '';
            if (!$enable_tags)
                $product_tags = '';

            // Check if product exists by SKU
            $existing_product_id = wc_get_product_id_by_sku($product_code);

            if ($existing_product_id) {
                // Get the product object
                $wc_product = wc_get_product($existing_product_id);

                // Global product update (for simple products)
                $wc_product->set_regular_price($product_price);

                if ((float) get_option('dpi_discount_percentage', 0) > 0) {
                    $product_discount_price = get_discounted_price($product_price);
                    $wc_product->set_sale_price($product_discount_price);
                } else {
                    // delete sale price
                    $wc_product->set_sale_price('');
                }

                $wc_product->set_stock_status($product_stock);

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
                // Check if it's a variable product
                if ($wc_product instanceof WC_Product_Variable) {
                    // Get all variation IDs
                    $variation_ids = $wc_product->get_children();
<<<<<<< HEAD
            
                    foreach ($variation_ids as $variation_id) {
                        $variation = new WC_Product_Variation($variation_id);
            
                        // Update variation regular price
                        $variation->set_regular_price($product_price);
            
=======

                    foreach ($variation_ids as $variation_id) {
                        $variation = new WC_Product_Variation($variation_id);

                        // Update variation regular price
                        $variation->set_regular_price($product_price);

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
                        // Update sale price if discount applies
                        if ((float) get_option('dpi_discount_percentage', 0) > 0) {
                            $variation_discount_price = get_discounted_price($product_price);
                            $variation->set_sale_price($variation_discount_price);
<<<<<<< HEAD
                        }else{
                            // delete sale price
                            $variation->set_sale_price('');
                        }
            
                        // Optional: update stock status for variations if needed
                        $variation->set_stock_status($product_stock);
            
                        $variation->save();
                    }
                }
            
                // Save the main product
                $product_id = $wc_product->save();
            
=======
                        } else {
                            // delete sale price
                            $variation->set_sale_price('');
                        }

                        // Optional: update stock status for variations if needed
                        $variation->set_stock_status($product_stock);

                        $variation->save();
                    }
                }

                // Save the main product
                $product_id = $wc_product->save();

                // Save Tags
                if (!empty($product_tags)) {
                    wp_set_object_terms($product_id, $product_tags, 'product_tag');
                }

                // Save SEO Meta (Yoast SEO)
                if (!empty($meta_description)) {
                    update_post_meta($product_id, '_yoast_wpseo_metadesc', $meta_description);
                }
                if (!empty($focus_keyphrase)) {
                    update_post_meta($product_id, '_yoast_wpseo_focuskw', $focus_keyphrase);
                }

                // Save SEO Meta (Rank Math)
                if (!empty($meta_description)) {
                    update_post_meta($product_id, 'rank_math_description', $meta_description);
                }
                if (!empty($focus_keyphrase)) {
                    update_post_meta($product_id, 'rank_math_focus_keyword', $focus_keyphrase);
                }

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
                // Report success
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
<<<<<<< HEAD
=======

                // Save Tags
                if (!empty($product_tags)) {
                    wp_set_object_terms($product_id, $product_tags, 'product_tag');
                }

                // Save SEO Meta (Yoast SEO)
                if (!empty($meta_description)) {
                    update_post_meta($product_id, '_yoast_wpseo_metadesc', $meta_description);
                }
                if (!empty($focus_keyphrase)) {
                    update_post_meta($product_id, '_yoast_wpseo_focuskw', $focus_keyphrase);
                }

                // Save SEO Meta (Rank Math)
                if (!empty($meta_description)) {
                    update_post_meta($product_id, 'rank_math_description', $meta_description);
                }
                if (!empty($focus_keyphrase)) {
                    update_post_meta($product_id, 'rank_math_focus_keyword', $focus_keyphrase);
                }

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
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
<<<<<<< HEAD
function get_discounted_price($price) {
=======
function get_discounted_price($price)
{
>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
    $discount_percentage = (float) get_option('dpi_discount_percentage', 0);
    $price = (float) $price;

    // Clamp discount percentage between 0 and 100
    $discount_percentage = max(0, min(100, $discount_percentage));

    return round($price - ($price * $discount_percentage / 100), 2);
}


/**
 * Upload image from URL helper function
 */
<<<<<<< HEAD
function upload_image_from_url($image_url, $image_name) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $image_name = sanitize_file_name($image_name);
    $tmp = download_url($image_url);
    
=======
function upload_image_from_url($image_url, $image_name)
{
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $image_name = sanitize_file_name($image_name);
    $tmp = download_url($image_url);

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
    if (is_wp_error($tmp)) {
        error_log("Image download failed: " . $tmp->get_error_message());
        return false;
    }
<<<<<<< HEAD
    
=======

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
    $file_array = [
        'name' => $image_name . '.jpg',
        'tmp_name' => $tmp
    ];
<<<<<<< HEAD
    
    $id = media_handle_sideload($file_array, 0);
    
    @unlink($file_array['tmp_name']);
    
=======

    $id = media_handle_sideload($file_array, 0);

    @unlink($file_array['tmp_name']);

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
    if (is_wp_error($id)) {
        error_log("Image upload failed: " . $id->get_error_message());
        return false;
    }
<<<<<<< HEAD
    
    return $id;
}

function upload_image_with_watermark_url($image_url, $image_name, $watermark_url) {
=======

    return $id;
}

function upload_image_with_watermark_url($image_url, $image_name, $watermark_url)
{
>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
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

/**
 * Get unique categories from the sync table
 */
<<<<<<< HEAD
function get_dpi_unique_categories() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sync_dropshipping_product';
    
    $categories = $wpdb->get_col("SELECT DISTINCT category_name FROM $table_name WHERE category_name != '' ORDER BY category_name ASC");
    
    return $categories;
}

=======
function get_dpi_unique_categories()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'sync_dropshipping_product';

    $categories = $wpdb->get_col("SELECT DISTINCT category_name FROM $table_name WHERE category_name != '' ORDER BY category_name ASC");

    return $categories;
}

/**
 * Generate SEO data using Gemini AI
 */
function generate_seo_data_with_gemini($product_name, $product_description)
{
    $api_key = get_option('dpi_gemini_api_key');
    $model = get_option('dpi_gemini_model', 'gemini-1.5-flash');

    if (empty($api_key)) {
        return false;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

    $prompt = "Generate SEO metadata for a product. 
    Product Name: {$product_name}
    Product Description: " . wp_strip_all_tags($product_description) . "
    
    Provide the response in JSON format with the following keys:
    - focus_keyphrase (strong SEO keyword)
    - meta_description (max 160 characters)
    - tags (comma separated list of 5-8 relevant tags)
    
    Return ONLY the raw JSON object, no markdown, no explanation.";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $response = wp_remote_post($url, [
        'body' => wp_json_encode($data),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $result['candidates'][0]['content']['parts'][0]['text'];
        // Clean up any potential markdown formatting
        $text = str_replace(['```json', '```'], '', $text);
        $decoded = json_decode(trim($text), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }

    return false;
}

>>>>>>> 265d153 (implement gemini support to generet product tag, meta description and Focus keyphrase)
