<?php
add_action( 'rest_api_init', 'dropshipping_api_endpoints' );

function dropshipping_api_endpoints() {
    register_rest_route( 'dropshipping/v1', '/fetch_product', array(
        'methods' => 'GET',
        'callback' => 'call_fetch_product',
    ) );

    register_rest_route( 'dropshipping/v1', '/import_product', array(
        'methods' => 'GET',
        'callback' => 'call_import_product',
    ));
}

function call_fetch_product() {
    return insert_product_to_database();
}

function call_import_product() {
    return dropshipping_product_import_in_db();
}