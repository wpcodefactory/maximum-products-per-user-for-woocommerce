<?php
/**
 * Maximum Products per User for WooCommerce - Guests Settings.
 *
 * @version 4.3.9
 * @since   3.6.7
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Guests' ) ) :

	class Alg_WC_MPPU_Settings_Guests extends Alg_WC_MPPU_Settings_Section {

		/**
		 * Constructor.
		 *
		 * @version 3.6.7
		 * @since   3.6.7
		 */
		function __construct() {
			$this->id   = 'guests';
			$this->desc = __( 'Guests', 'maximum-products-per-user-for-woocommerce' );
			parent::__construct();
		}

		/**
		 * get_settings.
		 *
		 * @version 4.3.9
		 * @since   3.6.7
		 */
		function get_settings() {

			$guest_opts = array(
				array(
					'title'    => __( 'Guest users options', 'maximum-products-per-user-for-woocommerce' ),
					'type'     => 'title',
					'desc'     => __( 'Options regarding how non-logged users (i.e. guests) should be handled by the plugin.', 'maximum-products-per-user-for-woocommerce' ),
					'id'       => 'alg_wc_mppu_plugin_options',
				),
				array(
					'title'    => __( 'Guests', 'maximum-products-per-user-for-woocommerce' ),
					'id'       => 'alg_wc_mppu_block_guests', // mislabeled, should be `alg_wc_mppu_guests`
					'default'  => 'no',
					'type'     => 'radio',
					'options'  => array(
						'no'                 => __( 'Do nothing (i.e. do not track guests sales)', 'maximum-products-per-user-for-woocommerce' ),
						'block_beyond_limit' => __( 'Do nothing but block guests from purchasing products beyond the limits', 'maximum-products-per-user-for-woocommerce' ),
						'yes'                => __( 'Block guests from buying products', 'maximum-products-per-user-for-woocommerce' ),
						'identify_by_ip'     => __( 'Identify guests by IP address', 'maximum-products-per-user-for-woocommerce' ),
						'identify_by_checkout_email'     => __( 'Identify guests by checkout billing email address ( N.B: It will work at the time of checkout )', 'maximum-products-per-user-for-woocommerce' ),
					),
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'alg_wc_mppu_plugin_options',
				),
			);

			$block_guest_from_buying_product_opts = array(
				array(
					'title' => __( 'Block guests from buying products', 'maximum-products-per-user-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf( __( 'This whole section will only make sense if %s option is set as %s.', 'maximum-products-per-user-for-woocommerce' ), '<strong>' . __( 'Guests', 'maximum-products-per-user-for-woocommerce' ) . '</strong>','<strong>' . __( 'Block guests from buying products', 'maximum-products-per-user-for-woocommerce' ) . '</strong>' ),
					'id'    => 'alg_wc_mppu_block_guests_from_buying_options',
				),
				array(
					'title'             => __( 'Block method', 'maximum-products-per-user-for-woocommerce' ),
					'desc_tip'          => sprintf( __( 'Choose "%s" if you want to block guests from buying specific products.', 'maximum-products-per-user-for-woocommerce' ), __( 'According to limit options', 'maximum-products-per-user-for-woocommerce' ) ) . '<br />' .
					                       sprintf( __( 'In that case it will be necessary to enable the %s option on the product or taxonomy page after activating %s or %s options.', 'maximum-products-per-user-for-woocommerce' ), '"' . __( 'Block guests', 'maximum-products-per-user-for-woocommerce' ) . '"', '"' . __( 'Per product', 'maximum-products-per-user-for-woocommerce' ) . '"', '"' . __( 'Per product taxonomy', 'maximum-products-per-user-for-woocommerce' ) . '"' ),
					'id'                => 'alg_wc_mppu_block_guests_method',
					'default'           => 'all_products',
					'type'              => 'select',
					'class'             => 'chosen_select',
					'options'           => array(
						'all_products'     => __( 'All Products', 'maximum-products-per-user-for-woocommerce' ),
						'by_limit_options' => __( 'According to limit options', 'maximum-products-per-user-for-woocommerce' )
					),
					'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
				),
				array(
					'title'    => __( 'Block message', 'maximum-products-per-user-for-woocommerce' ),
					'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					              sprintf( __( 'E.g.: %s.', 'maximum-products-per-user-for-woocommerce' ), '<em>[alg_wc_mppu_translate]</em>' ),
					'id'       => 'alg_wc_mppu_block_guests_message',
					'default'  => sprintf( '<a href="%s" class="button wc-forward">' . __( 'Login', 'woocommerce' ) . '</a>', esc_url( wc_get_page_permalink( 'myaccount' ) ) ) . ' ' .
					              __( 'You need to register to buy products.', 'maximum-products-per-user-for-woocommerce' ),
					'type'     => 'textarea',
					'css'      => 'width:100%;height:100px;',
				),
				array(
					'title'             => __( 'Add to cart text', 'maximum-products-per-user-for-woocommerce' ),
					'desc'              => __( 'Change add to cart button text from blocked products', 'maximum-products-per-user-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_enable',
					'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
					'default'           => 'no',
					'checkboxgroup'     => 'start',
					'type'              => 'checkbox',
				),
				array(
					'desc'              => __( 'Change add to cart button text from blocked variations', 'maximum-products-per-user-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_variations',
					'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
					'default'           => 'no',
					'checkboxgroup'     => 'end',
					'type'              => 'checkbox',
				),
				array(
					'desc'     => __( 'Custom add to cart button text', 'maximum-products-per-user-for-woocommerce' ),
					'id'       => 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt',
					'default'  => __( 'Login to purchase', 'maximum-products-per-user-for-woocommerce' ),
					'type'     => 'text',
				),
				array(
					'title'             => __( 'Add to cart redirect', 'maximum-products-per-user-for-woocommerce' ),
					'desc'              => __( 'Redirect after clicking on an add to cart button from a blocked product', 'maximum-products-per-user-for-woocommerce' ),
					'id'                => 'alg_wc_mppu_block_guests_add_to_cart_redirect',
					'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
					'default'           => 'no',
					'type'              => 'checkbox',
				),
				array(
					'desc'     => __( 'Redirect URL', 'maximum-products-per-user-for-woocommerce' ),
					'id'       => 'alg_wc_mppu_block_guests_add_to_cart_redirect_url',
					'default'  => wc_get_page_permalink( 'myaccount' ),
					'type'     => 'text',
				),
				array(
					'title'    => __( 'Hide products', 'maximum-products-per-user-for-woocommerce' ),
					'desc_tip' => __( 'Hides products from the catalog and search results. Products will still be accessible via the direct links.', 'maximum-products-per-user-for-woocommerce' ),
					'desc'     => __( 'Hide products blocked from guest users', 'maximum-products-per-user-for-woocommerce' ),
					'id'       => 'alg_wc_mppu_hide_guest_blocked_products',
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'alg_wc_mppu_block_guests_from_buying_options',
				),
			);

			return array_merge( $guest_opts, $block_guest_from_buying_product_opts );
		}

	}

endif;

return new Alg_WC_MPPU_Settings_Guests();
