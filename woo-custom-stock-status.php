<?php
/*
Plugin Name: Woo Custom Stock Status
Plugin URI:  https://www.softound.com/
Description: Write the custom stock status with different colors for each woocommerce product, to show in product details and listing pages.
Version:     1.5.9
Author:      Softound Solutions
Author URI:  https://www.softound.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woo-custom-stock-status
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define( 'WCSS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define( 'WCSS_PLUGIN_URL', plugin_dir_url(__FILE__));
define( 'WCSS_PLUGIN_VER', '1.5.9');

/**
* Main Woocommerce Stock status class
*/
class WC_Custom_Stock_status {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of base class
	 *
	 * @since 1.1.0
	 */
	public $stock_base = null;

	/**
	 * Instance of Setting class
	 *
	 * @since 1.1.0
	 */
	public $stock_setting = null;

	/**
	 * Instance of Product class
	 *
	 * @since 1.1.0
	 */
	public $stock_product = null;

	/**
	 * Instance of Wp all import class
	 *
	 * @since 1.1.0
	 */
	public $stock_wp_all_import = null;

	/**
	 * Instance of Yith wishlist class
	 *
	 * @since 1.1.0
	 */
	public $stock_yith_wishlist = null;

	/**
	 * Instance of Yoast seo class
	 *
	 * @since 1.1.0
	 */
	public $stock_yoast_seo = null;

	/**
	 * Instance of General settings class
	 *
	 * @since 1.5.4
	 */
	public $general_settings = null;

	/**
	 * Instance of Stock status settings class
	 *
	 * @since 1.5.4
	 */
	public $stock_status_settings = null;
	
	/**
	 * Main WC_Custom_Stock_status Instance.
	 *
	 * Ensures only one instance of WC_Custom_Stock_status is loaded or can be loaded.
	 *
	 * @since 1.1.0
	 * @static
	 * @return WC_Custom_Stock_status - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WC_Custom_Stock_status Constructor.
	 */
	public function __construct() {
		$this->includes();
		// init other classes
		$this->stock_base = new Woo_Stock_Base();
		$this->stock_setting = new Woo_Stock_Setting();
		$this->stock_product = new Woo_Stock_Product();
		$this->stock_wp_all_import = new Woo_Stock_with_WpAllImport();
		$this->stock_yith_wishlist = new Woo_Stock_with_yith_wishlist();
		$this->general_settings = new Woo_Stock_General();
		$this->stock_status_settings = new Woo_Stock_Status();
		$disable_yoast_compatibility = get_option( 'wc_slr_disable_yoast_compatibility' , 'no' );
		if( $disable_yoast_compatibility == 'no'){ 
			$this->stock_yoast_seo = new Woo_Stock_With_Yoast_Seo();
		}
		add_action( 'init', array( $this, 'polylang_stock_status' ));
	}

	/**
	 * Register Woocommerce default stock status for Polylang string translation.
	 */
	public function polylang_stock_status(){
		$woo_stock_obj = $this->stock_base;
		foreach ($woo_stock_obj->status_array as $key => $value) {
			if ( function_exists( 'pll_register_string' ) ) {
				pll_register_string( $key, $value, 'woo-custom-stock-status',false );
			}
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		require_once( 'includes/class-wc-stock-base.php' ); 
		require_once( 'includes/class-wc-stock-setting.php' ); 
		require_once( 'includes/class-wc-stock-product.php' ); 
		require_once( 'integration/class-wp-all-import.php' );
		require_once( 'integration/class-yith-wishlist.php' );
		require_once( 'includes/class-wc-stock-general.php' );
		require_once( 'includes/class-wc-stock-status.php' );

		$disable_yoast_compatibility = get_option( 'wc_slr_disable_yoast_compatibility' , 'no' );
		if( $disable_yoast_compatibility == 'no'){ 
			require_once( 'integration/class-yoast-seo.php' );
		}
		
	}

	public static function deactive_error_notice() {
	  printf('<div class="error notice is-dismissible"><p>%1$s</p></div>',__( 'Please install WooCommerce, it is required for this plugin to work properly!', 'wc-stock-status' ));
	}
}

/**
 * Check if WooCommerce is active
 **/
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	deactivate_plugins( plugin_dir_path( __FILE__ ) . 'woo-custom-stock-status.php', false );
	add_action( 'admin_notices' , array( 'WC_Custom_Stock_status' , 'deactive_error_notice' ) );
} else {

	/**
	 * Main instance of Main Woocommerce Stock status.
	 * @since  1.1.0
	 */
	WC_Custom_Stock_status::instance();
}



?>
