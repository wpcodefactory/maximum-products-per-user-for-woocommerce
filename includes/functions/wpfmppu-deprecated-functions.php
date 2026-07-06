<?php
/**
 * Maximum Products per User for WooCommerce - Deprecated Functions.
 *
 * Backwards-compatible wrappers for global functions renamed in 4.5.0.
 * Each shim calls `_deprecated_function()` so developers are notified
 * via the WordPress debug log, then proxies to the current function.
 *
 * @version 4.5.0
 * @since   4.5.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'alg_wc_mppu' ) ) {
	/**
	 * alg_wc_mppu.
	 *
	 * @version 4.5.0
	 * @since   1.0.0
	 * @deprecated 4.5.0 Use wpfmppu() instead.
	 *
	 * @return WPFMPPU
	 */
	function alg_wc_mppu() {
		_deprecated_function( __FUNCTION__, '4.5.0', 'wpfmppu()' );

		return wpfmppu();
	}
}

if ( ! function_exists( 'alg_wc_mppu_is_user_logged_in' ) ) {
	/**
	 * alg_wc_mppu_is_user_logged_in.
	 *
	 * @version 4.5.0
	 * @since   3.9.0
	 * @deprecated 4.5.0 Use wpfmppu_is_user_logged_in() instead.
	 */
	function alg_wc_mppu_is_user_logged_in() {
		_deprecated_function( __FUNCTION__, '4.5.0', 'wpfmppu_is_user_logged_in()' );

		return wpfmppu_is_user_logged_in();
	}
}

if ( ! function_exists( 'alg_wc_mppu_get_option' ) ) {
	/**
	 * alg_wc_mppu_get_option.
	 *
	 * @version 4.5.0
	 * @since   4.2.3
	 * @deprecated 4.5.0 Use wpfmppu_get_option() instead.
	 */
	function alg_wc_mppu_get_option( $option, $default_value = false, $get_value_from_cache = true ) {
		_deprecated_function( __FUNCTION__, '4.5.0', 'wpfmppu_get_option()' );

		return wpfmppu_get_option( $option, $default_value, $get_value_from_cache );
	}
}