<?php
/*
Plugin Name: Maximum Products per User for WooCommerce
Plugin URI: https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/
Description: Limit number of items your WooCommerce customers can buy (lifetime or in selected date range).
Version: 4.1.2
Author: WPFactory
Author URI: https://wpfactory.com
Text Domain: maximum-products-per-user-for-woocommerce
Domain Path: /langs
Copyright: © 2024 WPFactory
WC tested up to: 8.5
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
	if ( function_exists( 'alg_wc_mppu' ) ) {
		$plugin = alg_wc_mppu();
		if ( method_exists( $plugin, 'set_free_version_filesystem_path' ) ) {
			$plugin->set_free_version_filesystem_path( __FILE__ );
		}
	}
	return;
}

if ( ! class_exists( 'Alg_WC_MPPU' ) ) :
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
endif;

require_once( 'includes/class-alg-wc-mppu.php' );

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

// Initializes the plugin.
add_action( 'plugins_loaded', function () {
	$plugin = alg_wc_mppu();
	$plugin->set_filesystem_path( __FILE__ );
	$plugin->init();
} );

// Custom deactivation/activation hooks.
$activation_hook   = 'alg_wc_mppu_on_activation';
$deactivation_hook = 'alg_wc_mppu_on_deactivation';
register_activation_hook( __FILE__, function () use ( $activation_hook ) {
	add_option( $activation_hook, 'yes' );
} );
register_deactivation_hook( __FILE__, function () use ( $deactivation_hook ) {
	do_action( $deactivation_hook );
} );
add_action( 'admin_init', function () use ( $activation_hook ) {
	if ( is_admin() && get_option( $activation_hook ) === 'yes' ) {
		delete_option( $activation_hook );
		do_action( $activation_hook );
	}
} );
