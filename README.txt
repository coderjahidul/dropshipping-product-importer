=== Dropshipping Product Importer ===
Contributors: jahidul
Donate link: https://github.com/coderjahidul/
Tags: dropshipping, woocommerce, product import, automation
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import products directly from local sync table into your WordPress WooCommerce store with category filtering and REST API support.

== Description ==

Dropshipping Product Importer is a powerful tool designed to streamline your dropshipping workflow. It allows you to sync products from dropshipping.com.bd into a local staging table and then selectively import them into WooCommerce.

The latest version introduces a revamped, beautiful admin dashboard, category-wise import capabilities, and robust REST API endpoints for automation.

== Features ==

*   **Modern Admin UI**: A beautiful, responsive, and user-friendly dashboard for managing imports.
*   **Multi-Category Import**: Select multiple categories or use "Select All" to batch import products.
*   **REST API Integration**: Trigger product fetching and importing via standard WordPress REST API endpoints.
*   **One-Click Copy**: Easily copy API endpoints from the settings page for use in external automation tools.
*   **Local Staging Table**: Sync products to a local database table (`wp_sync_dropshipping_product`) before importing to WooCommerce.
*   **WooCommerce Sync**: Seamlessly creates or updates products, handles images, categories, and attributes.
*   **Automatic Attributes**: Automatically creates and assigns product attributes like Size and Color.

== Installation ==

1. Upload the `dropshipping-product-importer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your API credentials in the 'DPI API Settings' page.
4. Use the REST API endpoints or the admin settings to manage your product imports.

== REST API Endpoints ==

The plugin provides the following API endpoints:

*   **Fetch Products**: `GET /wp-json/dropshipping/v1/fetch_product`
    Syncs products from the external API to your local staging table.
*   **Import Products**: `GET /wp-json/dropshipping/v1/import_product`
    Imports products from the local staging table to WooCommerce. Uses the categories selected in settings for filtering.

== Changelog ==

= 1.3.0 =
* Revamped Admin Dashboard with beautiful, modern UI.
* Improved UX for settings and category selection.

= 1.2.0 =
* Added Multi-Category product import functionality.
* Added "Select All" and "Deselect All" options for easier category management.
* Refactored SQL query generation for improved performance and security.

= 1.1.0 =
* Added Category-wise product import functionality.
* Added REST API endpoints for fetch and import operations.
* Improved UI settings with endpoint copy functionality.
* Bug fixes and performance improvements.

= 1.0.0 =
* Initial release.