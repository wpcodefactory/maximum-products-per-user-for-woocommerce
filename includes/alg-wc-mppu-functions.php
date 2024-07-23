<?php
/**
 * Maximum Products per User for WooCommerce - Functions.
 *
 * @version 4.2.3
 * @since   3.8.2
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'alg_wc_mppu_maybe_compress_string' ) ) {
	/**
	 * alg_wc_mppu_maybe_compress_string.
	 *
	 * @version 3.8.2
	 * @since   3.8.2
	 */
	function alg_wc_mppu_maybe_compress_string( $string, $args = null ) {
		if ( function_exists( 'gzcompress' ) && ! empty( $string ) ) {
			$string = base64_encode( gzcompress( $string ) );
		}

		return $string;
	}
}

if ( ! function_exists( 'alg_wc_mppu_maybe_uncompress_string' ) ) {
	/**
	 * alg_wc_mppu_maybe_uncompress_string.
	 *
	 * @version 3.8.2
	 * @since   3.8.2
	 */
	function alg_wc_mppu_maybe_uncompress_string( $string ) {
		if ( function_exists( 'gzuncompress' ) && ! empty( $string ) ) {
			$string = gzuncompress( base64_decode( $string ) );
		}

		return $string;
	}
}

if ( ! function_exists( 'alg_wc_mppu_is_user_logged_in' ) ) {
	/**
	 * alg_wc_mppu_is_user_logged_in.
	 *
	 * @version 3.9.1
	 * @since   3.9.0
	 */
	function alg_wc_mppu_is_user_logged_in() {
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			include_once( ABSPATH . 'wp-includes/pluggable.php' );
		}


		return is_user_logged_in();
	}
}

if ( ! function_exists( 'alg_wc_mppu_get_option' ) ) {
	/**
	 * alg_wc_mppu_get_option.
	 *
	 * @version 4.2.3
	 * @since   4.2.3
	 *
	 * @return false|mixed|null
	 */
	function alg_wc_mppu_get_option( $option, $default_value = false, $get_value_from_cache = true ) {
		return alg_wc_mppu()->core->options->get_option( $option, $default_value, $get_value_from_cache );
	}
}
