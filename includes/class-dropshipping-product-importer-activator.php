<?php

/**
 * Fired during plugin activation
 *
 * @link       https://https://github.com/coderjahidul/
 * @since      1.0.0
 *
 * @package    Dropshipping_Product_Importer
 * @subpackage Dropshipping_Product_Importer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dropshipping_Product_Importer
 * @subpackage Dropshipping_Product_Importer/includes
 * @author     Jahidul islam Sabuz <sobuz0349@gmail.com>
 */
class Dropshipping_Product_Importer_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// create tables on activation
		global $wpdb;
		$table_name = $wpdb->prefix . 'sync_dropshipping_product';

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			id INT AUTO_INCREMENT,
            product_code INT NOT NULL,
			category_name varchar(255) NOT NULL,
            status varchar(255) NOT NULL,
            value text NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id)
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

}
