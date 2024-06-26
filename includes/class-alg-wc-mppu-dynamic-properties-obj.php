<?php
/**
 * Maximum Products per User for WooCommerce - Dynamic Properties Object.
 *
 * @link https://wiki.php.net/rfc/deprecate_dynamic_properties.
 *
 * @version 4.1.4
 * @since   4.1.4
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Dynamic_Properties_Obj' ) ) :

	class Alg_WC_MPPU_Dynamic_Properties_Obj {

		/**
		 * $dynamic_properties.
		 *
		 * @since   4.1.4
		 *
		 * @var array
		 */
		protected $dynamic_properties = array();

		/**
		 * get.
		 *
		 * @version 4.1.4
		 * @since   4.1.4
		 *
		 * @param $name
		 *
		 * @return mixed
		 */
		public function &__get( $name ) {
			return $this->dynamic_properties[ $name ];
		}

		/**
		 * isset.
		 *
		 * @version 4.1.4
		 * @since   4.1.4
		 *
		 * @param $name
		 *
		 * @return bool
		 */
		public function __isset( $name ) {
			return isset( $this->dynamic_properties[ $name ] );
		}

		/**
		 * set.
		 *
		 * @version 4.1.4
		 * @since   4.1.4
		 *
		 * @param $name
		 * @param $value
		 *
		 * @return void
		 */
		public function __set( $name, $value ) {
			$this->dynamic_properties[ $name ] = $value;
		}

		/**
		 * unset.
		 *
		 * @version 4.1.4
		 * @since   4.1.4
		 *
		 * @param $name
		 *
		 * @return void
		 */
		public function __unset( $name ) {
			unset( $this->dynamic_properties[ $name ] );
		}

	}
endif;