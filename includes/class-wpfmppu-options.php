<?php
/**
 * Maximum Products per User for WooCommerce - Options.
 *
 * @version 4.5.0
 * @since   4.2.3
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Options' ) ) {

	class WPFMPPU_Options {

		/**
		 * Options.
		 *
		 * @since 4.2.3
		 *
		 * @var array
		 */
		protected $options = array();

		/**
		 * get_option.
		 *
		 * @version 4.2.3
		 * @since   4.2.3
		 *
		 * @param  $option
		 * @param  $default_value
		 * @param  $get_value_from_cache
		 *
		 * @return false|mixed|null
		 */
		function get_option( $option, $default_value = false, $get_value_from_cache = true ) {
			if (
				! isset( $this->options[ $option ] ) ||
				! $get_value_from_cache
			) {
				$this->options[ $option ] = get_option( $option, $default_value );
			}

			return $this->options[ $option ];
		}

	}

}