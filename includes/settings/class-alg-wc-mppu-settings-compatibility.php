<?php
/**
 * Order Minimum Amount for WooCommerce - Compatibility Settings.
 *
 * @version 3.6.9
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
		 * @version 3.6.9
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
			$wpc_composite_opts = array(
				array(
					'title' => __( 'WPC Composite Products', 'order-minimum-amount-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf( __( 'Compatibility with %s plugin.', 'order-minimum-amount-for-woocommerce' ), sprintf( '<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/plugins/wpc-composite-products/', __( 'WPC Composite Products for WooCommerce', 'order-minimum-amount-for-woocommerce' ) ) ),
					'id'    => 'alg_wc_mppu_wpccp_options',
				),
				array(
					'title'             => __( 'Add to cart text', 'order-minimum-amount-for-woocommerce' ),
					'desc'              => __( 'Change add to cart button text from blocked guest user products', 'order-minimum-amount-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_wpccp_change_add_to_cart_btn_text_from_guest_users',
					'default'           => 'no',
					'type'              => 'checkbox',
					'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'alg_wc_mppu_wpccp_options',
				),
			);
			return array_merge( $prod_bundle_opts, $wpc_composite_opts );
		}
	}

endif;

return new Alg_WC_MPU_Settings_Compatibility();
