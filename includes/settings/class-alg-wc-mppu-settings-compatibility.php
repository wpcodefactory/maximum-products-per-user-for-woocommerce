<?php
/**
 * Order Minimum Amount for WooCommerce - Compatibility Settings.
 *
 * @version 3.6.0
 * @since   3.6.0
 *
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPU_Settings_Compatibility' ) ) :

	class Alg_WC_MPU_Settings_Compatibility extends Alg_WC_MPPU_Settings_Section {

		/**
		 * Constructor.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		function __construct() {
			$this->id   = 'compatibility';
			$this->desc = __( 'Compatibility', 'order-minimum-amount-for-woocommerce' );
			parent::__construct();
		}

		/**
		 * get_settings.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		function get_settings() {
			$prod_bundle_opts = array(
				array(
					'title' => __( 'Point of Sale for WooCommerce', 'order-minimum-amount-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf( __( 'Compatibility with %s plugin.', 'order-minimum-amount-for-woocommerce' ), sprintf( '<a href="%s" target="_blank">%s</a>', 'https://woocommerce.com/products/point-of-sale-for-woocommerce/', __( 'Point of Sale for WooCommerce', 'order-minimum-amount-for-woocommerce' ) ) ),
					'id'    => 'alg_wc_mppu_pos_wc_options',
				),
				array(
					'title'             => __( 'Registers', 'order-minimum-amount-for-woocommerce' ),
					'desc'              => __( 'Check limits when creating orders via registers', 'order-minimum-amount-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_pos_wc_registers_check_limits',
					'default'           => 'no',
					'type'              => 'checkbox',
					'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'alg_wc_mppu_pos_wc_options',
				),
			);
			return array_merge(
				$prod_bundle_opts, array()
			);
		}
	}

endif;

return new Alg_WC_MPU_Settings_Compatibility();
