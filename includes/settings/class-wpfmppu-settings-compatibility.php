<?php
/**
 * Order Minimum Amount for WooCommerce - Compatibility Settings.
 *
 * @version 4.5.0
 * @since   3.6.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Settings_Compatibility' ) ) :

	class WPFMPPU_Settings_Compatibility extends WPFMPPU_Settings_Section {

		/**
		 * Constructor.
		 *
		 * @version 4.5.0
		 * @since   3.6.0
		 */
		function __construct() {
			$this->id   = 'compatibility';
			$this->desc = __( 'Compatibility', 'maximum-products-per-user-for-woocommerce' );
			parent::__construct();
		}

		/**
		 * get_settings.
		 *
		 * @version 4.5.0
		 * @since   3.6.0
		 */
		function get_settings() {
			$prod_bundle_opts = array(
				array(
					'title' => __( 'Point of Sale for WooCommerce', 'maximum-products-per-user-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf(
						/* Translators: %s: Plugin URL. */
						__( 'Compatibility with %s plugin.', 'maximum-products-per-user-for-woocommerce' ),
						sprintf(
							/* Translators: %1$s: URL, %2$s: Link text. */
							'<a href="%1$s" target="_blank">%2$s</a>',
							'https://woocommerce.com/products/point-of-sale-for-woocommerce/',
							__( 'Point of Sale for WooCommerce', 'maximum-products-per-user-for-woocommerce' )
						)
					),
					'id'    => 'alg_wc_mppu_pos_wc_options',
				),
				array(
					'title'             => __( 'Registers', 'maximum-products-per-user-for-woocommerce' ),
					'desc'              => __( 'Check limits when creating orders via registers', 'maximum-products-per-user-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_pos_wc_registers_check_limits',
					'default'           => 'no',
					'type'              => 'checkbox',
					'custom_attributes' => apply_filters( 'wpfmppu_settings', array( 'disabled' => 'disabled' ) ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'alg_wc_mppu_pos_wc_options',
				),
			);
			$wpc_composite_opts = array(
				array(
					'title' => __( 'WPC Composite Products', 'maximum-products-per-user-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf(
						/* Translators: %s: Link. */
						__( 'Compatibility with %s plugin.', 'maximum-products-per-user-for-woocommerce' ),
						sprintf(
							/* Translators: %1$s: URL, %2$s: Link text. */
							'<a href="%1$s" target="_blank">%2$s</a>',
							'https://wordpress.org/plugins/wpc-composite-products/',
							__( 'WPC Composite Products for WooCommerce', 'maximum-products-per-user-for-woocommerce' )
						)
					),
					'id'    => 'alg_wc_mppu_wpccp_options',
				),
				array(
					'title'             => __( 'Add to cart text', 'maximum-products-per-user-for-woocommerce' ),
					'desc'              => __( 'Change add to cart button text from blocked guest user products', 'maximum-products-per-user-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_wpccp_change_add_to_cart_btn_text_from_guest_users',
					'default'           => 'no',
					'type'              => 'checkbox',
					'custom_attributes' => apply_filters( 'wpfmppu_settings', array( 'disabled' => 'disabled' ) ),
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

return new WPFMPPU_Settings_Compatibility();
