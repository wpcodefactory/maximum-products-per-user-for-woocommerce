<?php
/*
Plugin Name: Maximum Products per User for WooCommerce
Plugin URI: https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/
Description: Limit number of items your WooCommerce customers can buy (lifetime or in selected date range).
Version: 3.9.4
Author: WPFactory
Author URI: https://wpfactory.com
Text Domain: maximum-products-per-user-for-woocommerce
Domain Path: /langs
Copyright: © 2023 WPFactory
WC tested up to: 8.1
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Handle is_plugin_active function
if ( ! function_exists( 'alg_wc_mppu_is_plugin_active' ) ) {
	/**
	 * alg_wc_cog_is_plugin_active.
	 *
	 * @version 3.5.7
	 * @since   3.5.7
	 */
	function alg_wc_mppu_is_plugin_active( $plugin ) {
		return ( function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) :
			(
				in_array( $plugin, apply_filters( 'active_plugins', ( array ) get_option( 'active_plugins', array() ) ) ) ||
				( is_multisite() && array_key_exists( $plugin, ( array ) get_site_option( 'active_sitewide_plugins', array() ) ) )
			)
		);
	}
}

// Check for active plugins
if (
	! alg_wc_mppu_is_plugin_active( 'woocommerce/woocommerce.php' ) ||
	( 'maximum-products-per-user-for-woocommerce.php' === basename( __FILE__ ) && alg_wc_mppu_is_plugin_active( 'maximum-products-per-user-for-woocommerce-pro/maximum-products-per-user-for-woocommerce-pro.php' ) )
) {
	return;
}

if ( ! class_exists( 'Alg_WC_MPPU' ) ) :
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
endif;

if ( ! class_exists( 'Alg_WC_MPPU' ) ) :

/**
 * Main Alg_WC_MPPU Class.
 *
 * @class   Alg_WC_MPPU
 * @version 3.5.0
 * @since   1.0.0
 */
class Alg_WC_MPPU {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = '3.9.4';

	/**
	 * @var   Alg_WC_MPPU The single instance of the class
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * core.
	 *
	 * @since 3.6.4
	 *
	 * @var Alg_WC_MPPU_Core
	 */
	public $core;

	/**
	 * pro.
	 *
	 * @since 3.6.9
	 *
	 * @var Alg_WC_MPPU_Pro
	 */
	public $pro;

	/**
	 * Main Alg_WC_MPPU Instance
	 *
	 * Ensures only one instance of Alg_WC_MPPU is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @static
	 * @return  Alg_WC_MPPU - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Alg_WC_MPPU Constructor.
	 *
	 * @version 3.4.0
	 * @since   1.0.0
	 * @access  public
	 */
	function __construct() {

		// Set up localisation
		add_action( 'init', array( $this, 'localize' ) );

		// Pro
		if ( 'maximum-products-per-user-for-woocommerce-pro.php' === basename( __FILE__ ) ) {
			$this->pro = require_once( 'includes/pro/class-alg-wc-mppu-pro.php' );
		}

		// Include required files
		$this->includes();

		// Admin
		if ( is_admin() ) {
			$this->admin();
		}
	}

	/**
	 * localize.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function localize() {
		load_plugin_textdomain( 'maximum-products-per-user-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 3.8.2
	 * @since   1.0.0
	 */
	function includes() {
		// Functions
		require_once( 'includes/alg-wc-mppu-functions.php' );
		// Core
		$this->core = require_once( 'includes/class-alg-wc-mppu-core.php' );
	}

	/**
	 * admin.
	 *
	 * @version 2.4.1
	 * @since   1.1.1
	 */
	function admin() {
		// Action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		// Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
		// Version update
		if ( $this->version !== get_option( 'alg_wc_mppu_version', '' ) ) {
			add_action( 'admin_init', array( $this, 'version_updated' ) );
		}
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @version 2.6.0
	 * @since   1.0.0
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();
		if ( ! in_array( $settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>', $links ) ) {
			$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
		}
		if (
			'maximum-products-per-user-for-woocommerce.php' === basename( __FILE__ ) &&
			! in_array( $go_pro = '<a target="_blank" style="font-weight: bold; color: green;" href="https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/">' . __( 'Go Pro', 'maximum-products-per-user-for-woocommerce' ) . '</a>', $links )
		) {
			$custom_links[] = $go_pro;
		}
		return array_merge( $custom_links, $links );
	}

	/**
	 * Add Maximum Products per User settings tab to WooCommerce settings.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function add_woocommerce_settings_tab( $settings ) {
		$settings[] = require_once( 'includes/settings/class-alg-wc-mppu-settings.php' );
		return $settings;
	}

	/**
	 * version_updated.
	 *
	 * @version 2.0.0
	 * @since   1.1.0
	 */
	function version_updated() {
		update_option( 'alg_wc_mppu_version', $this->version );
	}

	/**
	 * Get the plugin url.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * get_filesystem_path.
	 *
	 * @version 3.7.4
	 * @since   3.7.4
	 *
	 * @return string
	 */
	function get_filesystem_path(){
		return __FILE__;
	}

}

endif;

if ( ! function_exists( 'alg_wc_mppu' ) ) {
	/**
	 * Returns the main instance of Alg_WC_MPPU to prevent the need to use globals.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 * @return  Alg_WC_MPPU
	 */
	function alg_wc_mppu() {
		return Alg_WC_MPPU::instance();
	}
}

alg_wc_mppu();
