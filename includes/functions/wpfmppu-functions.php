<?php
/**
 * Maximum Products per User for WooCommerce - Functions.
 *
 * @version 4.5.0
 * @since   3.8.2
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wpfmppu_is_user_logged_in' ) ) {
	/**
	 * wpfmppu_is_user_logged_in.
	 *
	 * @version 4.5.0
	 * @since   3.9.0
	 */
	function wpfmppu_is_user_logged_in() {
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			include_once( ABSPATH . 'wp-includes/pluggable.php' );
		}


		return is_user_logged_in();
	}
}

if ( ! function_exists( 'wpfmppu_get_option' ) ) {
	/**
	 * wpfmppu_get_option.
	 *
	 * @version 4.5.0
	 * @since   4.2.3
	 *
	 * @return false|mixed|null
	 */
	function wpfmppu_get_option( $option, $default_value = false, $get_value_from_cache = true ) {
		return wpfmppu()->core->options->get_option( $option, $default_value, $get_value_from_cache );
	}
}
