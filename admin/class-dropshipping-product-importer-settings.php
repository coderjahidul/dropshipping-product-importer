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
        register_setting('dpi_settings_group', 'dpi_selected_category');

        // Main Settings section
        add_settings_section('dpi_main_section', 'Main Settings', null, 'dpi_settings_group');
        
        add_settings_field('dpi_api_key', 'API Key', array($this, 'dpi_api_key_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_secret_key', 'Secret Key', array($this, 'dpi_secret_key_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_import_limit', 'Product Import Limit (per minute)', array($this, 'dpi_import_limit_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_discount_percentage', 'Set Product Discount (%)', array($this, 'dpi_discount_percentage_field'), 'dpi_settings_group', 'dpi_main_section');
        add_settings_field('dpi_product_watermark_logo', 'Product Watermark Logo URL', array($this, 'dpi_product_watermark_logo_field'), 'dpi_settings_group', 'dpi_main_section');

        // Import Section
        add_settings_section('dpi_import_section', 'Import Products by Category', array($this, 'dpi_import_section_info'), 'dpi_settings_group');
        add_settings_field('dpi_selected_category', 'Select Category', array($this, 'dpi_selected_category_field'), 'dpi_settings_group', 'dpi_import_section');

        // Endpoints Section
        add_settings_section('dpi_endpoints_section', 'API Endpoints', array($this, 'dpi_endpoints_section_info'), 'dpi_settings_group');
        add_settings_field('dpi_fetch_endpoint', 'Fetch Products Endpoint', array($this, 'dpi_fetch_endpoint_field'), 'dpi_settings_group', 'dpi_endpoints_section');
        add_settings_field('dpi_import_endpoint', 'Import Products Endpoint', array($this, 'dpi_import_endpoint_field'), 'dpi_settings_group', 'dpi_endpoints_section');
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

    public function dpi_import_section_info() {
        echo '<p>Select a category from your local database and click "Import" to process products from that category.</p>';
    }

    public function dpi_selected_category_field() {
        $categories = get_dpi_unique_categories();
        $selected = get_option('dpi_selected_category');
        if (!is_array($selected)) {
            $selected = $selected ? (array) $selected : [];
        }
        
        echo '<div style="margin-bottom: 10px;">';
        echo '<button type="button" class="button button-secondary" id="dpi_select_all_categories">Select All</button> ';
        echo '<button type="button" class="button button-secondary" id="dpi_deselect_all_categories">Deselect All</button>';
        echo '</div>';

        echo '<select name="dpi_selected_category[]" id="dpi_selected_category" multiple style="height: 200px; width: 300px;">';
        echo '<option value="all" ' . (in_array('all', $selected) ? 'selected' : '') . '>All Products</option>';
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category) . '" ' . (in_array($category, $selected) ? 'selected' : '') . '>' . esc_html($category) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Hold Ctrl (Windows) or Command (Mac) to select multiple categories.</p>';

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#dpi_select_all_categories').on('click', function() {
                $('#dpi_selected_category option').prop('selected', true);
            });
            $('#dpi_deselect_all_categories').on('click', function() {
                $('#dpi_selected_category option').prop('selected', false);
            });
        });
        </script>
        <?php
    }

    public function dpi_endpoints_section_info() {
        echo '<p>Use these REST API endpoints for automation. Click "Copy" to copy the URL to your clipboard.</p>';
    }

    public function dpi_fetch_endpoint_field() {
        $url = get_rest_url(null, 'dropshipping/v1/fetch_product');
        $this->render_endpoint_field($url, 'fetch_endpoint');
    }

    public function dpi_import_endpoint_field() {
        $url = get_rest_url(null, 'dropshipping/v1/import_product');
        $this->render_endpoint_field($url, 'import_endpoint');
    }

    private function render_endpoint_field($url, $id) {
        echo '<div style="display: flex; align-items: center; gap: 10px;">';
        echo '<input type="text" id="' . esc_attr($id) . '" value="' . esc_url($url) . '" class="regular-text" readonly style="background: #f0f0f1;">';
        echo '<button type="button" class="button button-secondary dpi-copy-btn" data-target="' . esc_attr($id) . '">Copy</button>';
        echo '</div>';
        
        static $script_rendered = false;
        if (!$script_rendered) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.dpi-copy-btn').on('click', function() {
                    var targetId = $(this).data('target');
                    var copyText = $('#' + targetId);
                    
                    copyText.select();
                    document.execCommand("copy");
                    
                    var $btn = $(this);
                    var originalText = $btn.text();
                    $btn.text('Copied!');
                    setTimeout(function() {
                        $btn.text(originalText);
                    }, 2000);
                });
            });
            </script>
            <?php
            $script_rendered = true;
        }
    }
}
