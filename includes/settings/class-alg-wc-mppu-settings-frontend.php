<?php
/**
 * Maximum Products per User for WooCommerce - Frontend Section Settings
 *
 * @version 3.5.4
 * @since   2.4.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Frontend' ) ) :

class Alg_WC_MPPU_Settings_Frontend extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function __construct() {
		$this->id   = 'frontend';
		$this->desc = __( 'Frontend', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 3.5.4
	 * @since   2.4.0
	 * @todo    [next] My Account: move to a new section?
	 * @todo    [next] My Account: Tab icon: add Font Awesome icon selector (i.e. instead of requiring to enter the code directly)
	 * @todo    [next] (desc) Cart notice: better desc?
	 * @todo    [next] (desc) Cart notice type: better desc?
	 * @todo    [later] (desc) add more info about "Customer message" (i.e. when and where is it displayed) and maybe extend description for the "Single product page" notice
	 * @todo    [maybe] (desc) My Account: add link to the tab (affected by "Tab id" option value)
	 */
	function get_settings() {

		$frontend_settings = array(
			array(
				'title'    => __( 'Frontend Options', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_frontend_options',
				'desc'     => $this->get_placeholders_desc( array(
						'%limit%',
						'%bought%',
						'%remaining%',
						'%in_cart%',
						'%bought_plus_in_cart%',
						'%remaining_minus_in_cart%',
						'%adding%',
						'%in_cart_plus_adding%',
						'%bought_plus_in_cart_plus_adding%',
						'%remaining_minus_in_cart_minus_adding%',
						'%product_title%',
						'%term_name%',
						'%first_order_amount%',
						'%first_order_date%',
						'%first_order_date_exp%',
						'%first_order_date_exp_timeleft%',
						'%payment_method_title%',
					), __( 'Available message placeholders: %s.', 'maximum-products-per-user-for-woocommerce' ) ),
			),
			array(
				'title'    => __( 'Validate on add to cart', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Validate limits immediately when "add to cart" button is pressed', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_validate_on_add_to_cart',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Cart notice', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Shows notice on the cart page.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_cart_notice',
				'default'  => 'yes',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'   => __( 'Disable', 'maximum-products-per-user-for-woocommerce' ),
					'yes'  => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
					'text' => __( 'As text', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'desc'     => __( 'Cart notice type', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Ignored unless "%s" option is set to "%s".', 'maximum-products-per-user-for-woocommerce' ),
					__( 'Cart notice', 'maximum-products-per-user-for-woocommerce' ), __( 'Enable', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_cart_notice_type',
				'default'  => 'notice',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'notice'  => __( 'Notice', 'maximum-products-per-user-for-woocommerce' ),
					'error'   => __( 'Error', 'maximum-products-per-user-for-woocommerce' ),
					'success' => __( 'Success', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'           => __( 'Customer message', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip'        => __( 'You can use HTML and/or shortcodes here.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
				                     sprintf( __( 'E.g.: %s.', 'maximum-products-per-user-for-woocommerce' ), '<em>[alg_wc_mppu_translate]</em>' ),
				'id'              => 'wpjup_wc_maximum_products_per_user_message',
				'default'         => sprintf(
					__( '[alg_wc_mppu_customer_msg bought_msg="%s" not_bought_msg="%s"]' ),
					__( "You can only buy maximum %limit% of %product_title% (you've already bought %bought%).", 'maximum-products-per-user-for-woocommerce' ),
					__( "You can only buy maximum %limit% of %product_title%.", 'maximum-products-per-user-for-woocommerce' )
				),
				'type'            => 'textarea',
				'css'             => 'width:100%;height:100px;',
				'desc'            => sprintf( __( 'Available %s shortcode params:', 'maximum-products-per-user-for-woocommerce' ), '<code>[alg_wc_mppu_customer_msg]</code>' ) .
				                     $this->convert_array_to_string( array(
					                     'bought_msg',
					                     'not_bought_msg',
					                     'bought_msg_min',
				                     ), array(
					                     'item_template' => '<code>{value}</code>'
				                     ) ),
				'alg_wc_mppu_raw' => true,
			),
			array(
				'title'    => __( 'Multiple notices', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Display cart & checkout notices for each product', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'If disabled, only one message will be displayed for the first product.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_multiple_notices',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Block checkout page', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Stop customer from accessing the checkout page on exceeded limits', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Customer will be redirected to the cart page.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'wpjup_wc_maximum_products_per_user_stop_from_seeing_checkout',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'Additional validation actions.', 'maximum-products-per-user-for-woocommerce' ) . ' ' . sprintf( __( 'For example, use %s to validate the limits on checkout update.', 'maximum-products-per-user-for-woocommerce' ), '<code>' . 'woocommerce_review_order_before_submit' . '</code>' ),
				'desc_tip' => __( 'WordPress action hooks used to validate the limits.', 'maximum-products-per-user-for-woocommerce' ) . '<br />' .
				              __( 'Use one per line.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_validation_actions',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:100%;height:100px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_frontend_options',
			),
			// Product limit message
			array(
				'title'    => __( 'Product limit message', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Adds current product limit info to the single product page.', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_product_page_options',
			),
			array(
				'title'    => __( 'Product limit message', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Adds current product limit info to the single product pages.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_permanent_notice',
				'default'  => 'no',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'           => __( 'Disable', 'maximum-products-per-user-for-woocommerce' ),
					'yes'          => __( 'Notice', 'maximum-products-per-user-for-woocommerce' ),
					'text'         => __( 'Text in product summary', 'maximum-products-per-user-for-woocommerce' ),
					'text_content' => __( 'Text in product description', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Message content', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
				              sprintf( __( 'E.g.: %s.', 'maximum-products-per-user-for-woocommerce' ), '<em>[alg_wc_mppu_translate]</em>' ),
				'id'       => 'alg_wc_mppu_permanent_notice_message',
				'default'  => __( "The remaining amount for %product_title% is %remaining% (you've already bought %bought% out of %limit%).", 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'textarea',
				'css'      => 'width:100%;height:100px;',
				'alg_wc_mppu_raw' => true,
			),
			array(
				'title'             => __( 'Variations', 'maximum-products-per-user-for-woocommerce' ),
				'desc'              => __( 'Show limit message for variations', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip'          => sprintf( __( 'Probably you\'d like to have %s option enabled.', 'maximum-products-per-user-for-woocommerce' ), '"' . __( 'General > Use variations', 'maximum-products-per-user-for-woocommerce' ) . '"' ),
				'id'                => 'alg_wc_mppu_permanent_notice_handle_variations',
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_product_page_options',
			),
			// My account
			array(
				'title'    => __( 'My account tab', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_my_account_options',
			),
			array(
				'title'    => __( 'Enable tab', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Add tab to "My Account" page', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_my_account_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'     => __( 'Tab id', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_my_account_tab_id',
				'default'  => 'product-limits',
				'type'     => 'text',
			),
			array(
				'title'     => __( 'Tab title', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'You can use shortcodes here.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
				              sprintf( __( 'E.g.: %s.', 'maximum-products-per-user-for-woocommerce' ), '<em>[alg_wc_mppu_translate]</em>' ),
				'id'       => 'alg_wc_mppu_my_account_tab_title',
				'default'  => __( 'Product limits', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'text',
				'css'      => 'width:100%;',
			),
			array(
				'title'    => __( 'Tab icon', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => sprintf( __( 'You need to enter icon code here, e.g. %s. Icon codes are available on %s site.', 'maximum-products-per-user-for-woocommerce' ),
					              '<code>f2b9</code>',
					              '<a href="https://fontawesome.com/icons?d=gallery&s=regular&m=free" target="_blank">Font Awesome</a>' ),
				'desc_tip' => __( 'Will use the default icon if empty.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_my_account_tab_icon',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Tab content', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
				              sprintf( __( 'E.g.: %s.', 'maximum-products-per-user-for-woocommerce' ), '<em>[alg_wc_mppu_translate]</em>' ),
				'id'       => 'alg_wc_mppu_my_account_tab_content',
				'default'  => '[alg_wc_mppu_user_product_limits]',
				'type'     => 'textarea',
				'css'      => 'width:100%;height:100px;',
				'alg_wc_mppu_raw' => true,
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_my_account_options',
			),


		);

		return $frontend_settings;
	}

}

endif;

return new Alg_WC_MPPU_Settings_Frontend();
