<?php
/**
 * Maximum Products per User for WooCommerce - Week Days.
 *
 * @version 4.0.9
 * @since   4.0.9
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Week_Days' ) ) :

	class Alg_WC_MPPU_Week_Days {

		/**
		 * Initializes the class.
		 *
		 * @version 4.0.9
		 * @since   4.0.9
		 *
		 * @return void
		 */
		function init() {

		}

		/**
		 * get_week_starts_on_default_val.
		 *
		 * @version 4.0.9
		 * @since   4.0.9
		 *
		 * @return int
		 */
		function get_week_starts_on_default_val() {
			return (int) get_option( 'start_of_week' );
		}

		/**
		 * get_week_starts_on_default_val.
		 *
		 * @version 4.0.9
		 * @since   4.0.9
		 *
		 * @return int
		 */
		function get_week_starts_on_option() {
			return $this->get_week_day_by_key( get_option( 'alg_wc_mppu_date_range_week_starts_on', $this->get_week_starts_on_default_val() ) );
		}

		/**
		 * @version 4.0.9
		 * @since   4.0.9
		 *
		 * @return array[]
		 */
		function get_week_days() {
			$week_days = array(
				array( 'id' => 0, 'name_slug' => 'sunday', 'name_formatted' => __( 'Sunday', 'maximum-products-per-user-for-woocommerce' ) ),
				array( 'id' => 1, 'name_slug' => 'monday', 'name_formatted' => __( 'Monday', 'maximum-products-per-user-for-woocommerce' ) ),
				array( 'id' => 2, 'name_slug' => 'tuesday', 'name_formatted' => __( 'Tuesday', 'maximum-products-per-user-for-woocommerce' ) ),
				array( 'id' => 3, 'name_slug' => 'wednesday', 'name_formatted' => __( 'Wednesday', 'maximum-products-per-user-for-woocommerce' ) ),
				array( 'id' => 4, 'name_slug' => 'thursday', 'name_formatted' => __( 'Thursday', 'maximum-products-per-user-for-woocommerce' ) ),
				array( 'id' => 5, 'name_slug' => 'friday', 'name_formatted' => __( 'Friday', 'maximum-products-per-user-for-woocommerce' ) ),
				array( 'id' => 6, 'name_slug' => 'saturday', 'name_formatted' => __( 'Saturday', 'maximum-products-per-user-for-woocommerce' ) )
			);

			return $week_days;
		}

		/**
		 * get_week_day_by_key.
		 *
		 * @version 4.0.9
		 * @since   4.0.9
		 *
		 * @param $key_value
		 * @param $key_name
		 *
		 * @return array|false
		 */
		function get_week_day_by_key( $key_value, $key_name = 'id' ) {
			$weekday = array_filter( $this->get_week_days(), function ( $weekday ) use ( $key_name, $key_value ) {
				if ( $key_value == $weekday[ $key_name ] ) {
					return true;
				}
			} );
			if ( ! empty( $weekday ) ) {
				return reset( $weekday );
			} else {
				return false;
			}
		}

		/**
		 * get_week_days_by_key_and_value.
		 *
		 * @version 4.0.9
		 * @since   4.0.9
		 *
		 * @param $value
		 * @param $key
		 *
		 * @return array
		 */
		function get_week_days_by_key_and_value( $value = 'name_slug', $key = 'id' ) {
			return wp_list_pluck( $this->get_week_days(), $value, $key );
		}

	}
endif;