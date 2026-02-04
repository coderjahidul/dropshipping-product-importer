# Dropshipping Product Importer

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/plugins/dropshipping-product-importer/)
[![Version](https://img.shields.io/badge/Version-1.3.0-green.svg)](https://github.com/coderjahidul/dropshipping-product-importer)
[![License](https://img.shields.io/badge/License-GPLv2-orange.svg)](LICENSE.txt)

**Dropshipping Product Importer** is a powerful WordPress plugin designed to streamline the workflow for dropshipping businesses. It allows you to sync products from [dropshipping.com.bd](https://dropshipping.com.bd) into a local staging table and selectively import them into your WooCommerce store with full category control.

## 🚀 Features

- **Modern Admin UI**: A beautiful, responsive, and user-friendly dashboard for managing imports.
- **Local Staging Table**: Sync products to a local database table (`wp_sync_dropshipping_product`) to review before importing to WooCommerce.
- **Multi-Category Import**: Select multiple categories or use the "Select All" feature for bulk processing.
- **REST API Integration**: Trigger fetch and import operations via standard WordPress REST API endpoints.
- **One-Click Copy**: Easily copy API endpoints from the admin settings page for use in external automation (CRON jobs, etc.).
- **Automatic Sync**: Seamlessly creates or updates products, handles images, categories, and attributes.

## 🛠 Installation

1. Clone or download this repository into your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **DPI API Settings** in your admin dashboard.
4. Enter your API credentials and save your settings.

## 📡 REST API Endpoints

The plugin exposes the following endpoints for automation:

### 1. Fetch Products
`GET /wp-json/dropshipping/v1/fetch_product`
- **Description**: Syncs products from the external distributor API to your local staging table.
- **Workflow**: Truncates the local sync table and repopulates it with the latest data from all available pages.

### 2. Import Products
`GET /wp-json/dropshipping/v1/import_product?category=Jewelry`
- **Description**: Imports products from the local staging table into WooCommerce.
- **Parameters**:
  - `category` (Optional): Filter by a specific category name. If omitted, it uses the multiple categories selected in the plugin settings.

## ⚙️ Configuration

In the admin settings page, you can:
- Set your **API Key** and **Secret Key**.
- Define an **Import Limit** per batch.
- Set a **Discount Percentage** to apply to imported product prices.
- Select **Multiple Categories** for targeted imports with batch selection support.

## 📝 License

This project is licensed under the GPL-2.0 License - see the [LICENSE.txt](LICENSE.txt) file for details.

## 👨‍💻 Author

**Jahidul Islam Sabuz** - [GitHub](https://github.com/coderjahidul/)
