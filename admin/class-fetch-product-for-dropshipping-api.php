<?php

// Fetch Product For Dropshipping API
function fetch_product_for_dropshipping_api($page = 1) {

    // get dpi_api_key 
    $dpi_api_key = get_option('dpi_api_key');

    // get dpi_secret_key
    $dpi_secret_key = get_option('dpi_secret_key');

    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://mohasagor.com.bd/api/reseller/product?page=' . $page, // Fixed: Add page number!
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'api-key: '.$dpi_api_key,
        'secret-key: '.$dpi_secret_key
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);

    return $response;
}

// Insert ALL products to database
function insert_product_to_database() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'sync_dropshipping_product';

   // ✅ Optional: Clear table before inserting new data
   $wpdb->query("TRUNCATE TABLE $table_name");

  $current_page = 1;
  $last_page = 1; // Temporary, will update after first call

  do {
      // Fetch products for current page
      $api_response = fetch_product_for_dropshipping_api($current_page);

      // Decode JSON response
      $data = json_decode($api_response, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
          return "Failed to parse JSON: " . json_last_error_msg();
      }

      // Check if products exist
      if (empty($data['products'])) {
          return "No products found.";
      }

      // Update last page from API response
      if (isset($data['pagination']['last_page'])) {
          $last_page = (int) $data['pagination']['last_page'];
      }

      $products = $data['products'];

      foreach ($products as $product) {
          $product_code = isset($product['product_code']) ? intval($product['product_code']) : 0;
          $product_category = isset($product['category']) ? $product['category'] : '';
          $status = "pending";
          $value = wp_json_encode($product); // Convert to JSON
          $current_time = current_time('mysql');

          if (empty($product_code)) {
              continue; // skip if no product code
          }

          // Insert into database
          $wpdb->insert(
              $table_name,
              array(
                  'product_code' => $product_code,
                  'category_name' => $product_category,
                  'status'       => $status,
                  'value'        => $value,
                  'created_at'   => $current_time,
                  'updated_at'   => $current_time
              ),
              array(
                  '%d', '%s', '%s', '%s', '%s', '%s'
              )
          );
      }

      $current_page++; // Go to next page

  } while ($current_page <= $last_page); // Loop until last page

  return "✅ All products inserted into database successfully.";
}
