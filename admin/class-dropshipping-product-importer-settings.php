<?php

class Dropshipping_Product_Importer_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_plugin_settings_page() {
        add_menu_page(
            'Product Importer Settings', 
            'DPI API Settings',         
            'manage_options',             
            'dropshipping-product-importer-settings', 
            array($this, 'create_admin_page'),         
            'dashicons-admin-generic',    
            81                            
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Product Importer Settings</h1>
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php
                settings_fields('dpi_settings_group'); 
                do_settings_sections('dpi_settings_group');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        // Register all settings
        register_setting('dpi_settings_group', 'dpi_api_key');
        register_setting('dpi_settings_group', 'dpi_secret_key');
        register_setting('dpi_settings_group', 'dpi_import_limit');
        register_setting('dpi_settings_group', 'dpi_discount_percentage');
        register_setting('dpi_settings_group', 'dpi_product_watermark_logo');

        // API Key
        add_settings_section('dpi_main_section', 'Main Settings', null, 'dpi_settings_group');
        
        add_settings_field('dpi_api_key', 'API Key', array($this, 'dpi_api_key_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_secret_key', 'Secret Key', array($this, 'dpi_secret_key_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_import_limit', 'Product Import Limit (per minute)', array($this, 'dpi_import_limit_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_discount_percentage', 'Set Product Discount (%)', array($this, 'dpi_discount_percentage_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_product_watermark_logo', 'Product Watermark Logo URL', array($this, 'dpi_product_watermark_logo_field'), 'dpi_settings_group', 'dpi_main_section');
    }

    // Fields:

    public function dpi_api_key_field() {
        $value = esc_attr(get_option('dpi_api_key'));
        echo '<input type="password" name="dpi_api_key" value="' . $value . '" class="regular-text">';
    }

    public function dpi_secret_key_field() {
        $value = esc_attr(get_option('dpi_secret_key'));
        echo '<input type="password" name="dpi_secret_key" value="' . $value . '" class="regular-text">';
    }

    public function dpi_import_limit_field() {
        $value = esc_attr(get_option('dpi_import_limit'));
        echo '<input type="number" name="dpi_import_limit" value="' . $value . '" class="small-text">';
    }

    public function dpi_discount_percentage_field() {
        $value = esc_attr(get_option('dpi_discount_percentage'));
        echo '<input type="number" step="0.01" name="dpi_discount_percentage" value="' . $value . '" class="small-text"> %';
    }

    public function dpi_product_watermark_logo_field() {
        $value = esc_attr(get_option('dpi_product_watermark_logo'));
        echo '<input type="url" name="dpi_product_watermark_logo" value="' . $value . '" placeholder="https://example.com/logo.png" class="regular-text">';
    }
}
