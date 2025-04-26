<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://https://github.com/coderjahidul/
 * @since      1.0.0
 *
 * @package    Dropshipping_Product_Importer
 * @subpackage Dropshipping_Product_Importer/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Dropshipping_Product_Importer
 * @subpackage Dropshipping_Product_Importer/includes
 * @author     Jahidul islam Sabuz <sobuz0349@gmail.com>
 */
class Dropshipping_Product_Importer_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'dropshipping-product-importer',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
