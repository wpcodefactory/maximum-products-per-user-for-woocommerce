<?php
/*
Plugin Name: Maximum Products per User for WooCommerce
Plugin URI: https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/
Description: Limit number of items your WooCommerce customers can buy (lifetime or in selected date range).
Version: 4.5.1
Author: WPFactory
Author URI: https://wpfactory.com
Text Domain: maximum-products-per-user-for-woocommerce
Domain Path: /langs
WC tested up to: 10.9
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined( 'ABSPATH' ) || exit;

// Handle is_plugin_active function
if ( ! function_exists( 'wpfmppu_is_plugin_active' ) ) {
	/**
	 * wpfmppu_is_plugin_active.
	 *
	 * @version 3.5.7
	 * @since   3.5.7
	 */
	function wpfmppu_is_plugin_active( $plugin ) {
		return ( function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) :
			(
				in_array(
					$plugin,
					apply_filters( 'active_plugins', ( array ) get_option( 'active_plugins', array() ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				) ||
				( is_multisite() && array_key_exists( $plugin, ( array ) get_site_option( 'active_sitewide_plugins', array() ) ) )
			)
		);
	}
}

/**
 * deprecated.
 *
 * @version 4.5.0
 */
require_once( 'includes/functions/wpfmppu-deprecated-functions.php' );

/**
 * deprecated hooks.
 *
 * @version 4.5.0
 */
require_once( 'includes/functions/wpfmppu-deprecated-hooks.php' );

/**
 * deprecated shortcodes.
 *
 * @version 4.5.0
 */
require_once( 'includes/functions/wpfmppu-deprecated-shortcodes.php' );

// Check for active plugins
if (
	! wpfmppu_is_plugin_active( 'woocommerce/woocommerce.php' ) ||
	(
		'maximum-products-per-user-for-woocommerce.php' === basename( __FILE__ ) &&
		wpfmppu_is_plugin_active( 'maximum-products-per-user-for-woocommerce-pro/maximum-products-per-user-for-woocommerce-pro.php' )
	)
) {
	if ( function_exists( 'wpfmppu' ) ) {
		$plugin = wpfmppu();
		if ( method_exists( $plugin, 'set_free_version_filesystem_path' ) ) {
			$plugin->set_free_version_filesystem_path( __FILE__ );
		}
	}
	return;
}

if ( ! class_exists( 'WPFMPPU' ) ) :
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
endif;

require_once( 'includes/class-wpfmppu-dynamic-properties-obj.php' );
require_once( 'includes/class-wpfmppu.php' );

if ( ! function_exists( 'wpfmppu' ) ) {
	/**
	 * Returns the main instance of WPFMPPU to prevent the need to use globals.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 * @return  WPFMPPU
	 */
	function wpfmppu() {
		return WPFMPPU::instance();
	}
}

// Initializes the plugin.
add_action( 'plugins_loaded', function () {
	$plugin = wpfmppu();
	$plugin->set_filesystem_path( __FILE__ );
	$plugin->init();
} );

// Custom deactivation/activation hooks.
register_activation_hook( __FILE__, function () {
	add_option( 'wpfmppu_on_activation', 'yes' );
} );
register_deactivation_hook( __FILE__, function () {
	do_action( 'wpfmppu_on_deactivation' );
} );
add_action( 'admin_init', function () {
	if ( is_admin() && get_option( 'alg_wc_mppu_on_deactivation' ) === 'yes' ) {
		delete_option( 'wpfmppu_on_activation' );
		do_action( 'wpfmppu_on_activation' );
	}
} );
