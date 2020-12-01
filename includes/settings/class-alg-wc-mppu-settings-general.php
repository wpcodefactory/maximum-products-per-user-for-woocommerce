<?php
/**
 * Maximum Products per User for WooCommerce - General Section Settings
 *
 * @version 3.4.0
 * @since   1.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_General' ) ) :

class Alg_WC_MPPU_Settings_General extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id   = '';
		$this->desc = __( 'General', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 3.4.0
	 * @since   1.0.0
	 * @todo    [next] exclude unnecessary statuses from `alg_wc_mppu_order_status` (e.g. "Cancelled", "Refunded", "Failed") and `alg_wc_mppu_order_status_delete` (e.g. "Completed" etc.)?
	 * @todo    [next] `alg_wc_mppu_order_status_delete`: `$this->get_recalculate_sales_data_desc( __( 'Order statuses', 'maximum-products-per-user-for-woocommerce' ) )`?
	 * @todo    [maybe] `alg_wc_mppu_block_guests`: default to `yes`?
	 * @todo    [maybe] (desc) Use variations: better desc?
	 * @todo    [maybe] (desc) Hide products: better desc?
	 */
	function get_settings() {

		$plugin_settings = array(
			array(
				'title'    => __( 'Maximum Products per User Options', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'desc'     => '<p>' . '* ' . sprintf( __( 'While data is recalculated automatically (but only after the plugin was enabled), you can also force manual recalculation by running %s tool.', 'maximum-products-per-user-for-woocommerce' ),
						'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu&section=tools' ) . '">' .
							__( 'Recalculate sales data', 'maximum-products-per-user-for-woocommerce' ) . '</a>' ) . ' ' .
						__( 'This is useful on initial plugin install (i.e. to calculate sales data from before the plugin was enabled).', 'maximum-products-per-user-for-woocommerce' ) .
					'</p>',
				'id'       => 'alg_wc_mppu_plugin_options',
			),
			array(
				'title'    => __( 'Maximum Products per User', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable plugin', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'desc_tip' => __( 'Limit number of items your WooCommerce customers can buy (lifetime or in selected date range).', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'wpjup_wc_maximum_products_per_user_plugin_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_plugin_options',
			),
		);

		$general_settings = array(
			array(
				'title'    => __( 'General Options', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_general_options',
			),
			array(
				'title'    => __( 'Mode', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->get_recalculate_sales_data_desc( __( 'Mode', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_mode',
				'default'  => 'qty',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'qty'    => __( 'Product quantities', 'maximum-products-per-user-for-woocommerce' ),
					'price'  => __( 'Product prices', 'maximum-products-per-user-for-woocommerce' ),
					'weight' => __( 'Product weights', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Date range', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_date_range',
				'default'  => 'lifetime',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'lifetime'      => __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ),
					'this_hour'     => __( 'This hour', 'maximum-products-per-user-for-woocommerce' ),
					'this_day'      => __( 'This day', 'maximum-products-per-user-for-woocommerce' ),
					'this_week'     => __( 'This week', 'maximum-products-per-user-for-woocommerce' ),
					'this_month'    => __( 'This month', 'maximum-products-per-user-for-woocommerce' ),
					'this_year'     => __( 'This year', 'maximum-products-per-user-for-woocommerce' ),
					'last_hour'     => __( 'Last hour', 'maximum-products-per-user-for-woocommerce' ),
					'last_24_hours' => __( 'Last 24 hours', 'maximum-products-per-user-for-woocommerce' ),
					'last_7_days'   => __( 'Last 7 days', 'maximum-products-per-user-for-woocommerce' ),
					'last_30_days'  => __( 'Last 30 days', 'maximum-products-per-user-for-woocommerce' ),
					'last_365_days' => __( 'Last 365 days', 'maximum-products-per-user-for-woocommerce' ),
					'custom'        => __( 'Custom', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'desc'     => sprintf( __( 'Custom date range (in %s)', 'maximum-products-per-user-for-woocommerce' ), get_option( 'alg_wc_mppu_date_range_custom_unit', 'seconds' ) ),
				'desc_tip' => __( 'Used when "Custom" option is selected in "Date range".', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_date_range_custom',
				'default'  => 3600,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 1 ),
			),
			array(
				'desc'     => __( 'Custom date range unit', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Used for the "%s" option.', 'maximum-products-per-user-for-woocommerce' ), __( 'Custom date range', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_date_range_custom_unit',
				'default'  => 'seconds',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'seconds' => __( 'seconds', 'maximum-products-per-user-for-woocommerce' ),
					'minutes' => __( 'minutes', 'maximum-products-per-user-for-woocommerce' ),
					'hours'   => __( 'hours', 'maximum-products-per-user-for-woocommerce' ),
					'days'    => __( 'days', 'maximum-products-per-user-for-woocommerce' ),
					'weeks'   => __( 'weeks', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Order statuses', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Choose order statuses when product data should be updated.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					__( 'If you select multiple order statuses, data is updated only once, on whichever status change occurs first.', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->get_recalculate_sales_data_desc( __( 'Order statuses', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_order_status',
				'default'  => array( 'wc-completed' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => wc_get_order_statuses(),
			),
			array(
				'title'    => __( 'Order statuses: Delete', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Choose order statuses when product data should be deleted.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					__( 'If you select multiple order statuses, data is deleted only once, on whichever status change occurs first.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_order_status_delete',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => wc_get_order_statuses(),
			),
			array(
				'title'    => __( 'Payment gateways', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Choose payment gateways when product data should be updated.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					__( 'Leave empty to update data for all payment gateways.', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->get_recalculate_sales_data_desc( __( 'Payment gateways', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_payment_gateways',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => ( function_exists( 'WC' ) && WC()->payment_gateways() ? wp_list_pluck( WC()->payment_gateways->payment_gateways(), 'title' ) : array() ),
			),
			array(
				'title'    => __( 'Use variations', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Use variations in "All products" and "Per product" calculations.', 'maximum-products-per-user-for-woocommerce' ) . '<br>' .
					__( 'If "Limits > Per Product" section is enabled, you will be able to (optionally) override this option for each individual product.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_use_variations',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'User roles', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Set different limits for different user roles.', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_use_user_roles',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Hide products', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Hides products with exceeded limits for the current user from the catalog and search results. Products will still be accessible via the direct links.', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_hide_products',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Guests', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Sets how non-logged users (i.e. guests) should be handled by the plugin.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_block_guests', // mislabeled, should be `alg_wc_mppu_guests`
				'default'  => 'no',
				'type'     => 'radio',
				'options'  => array(
					'no'             => __( 'Do nothing', 'maximum-products-per-user-for-woocommerce' ),
					'yes'            => __( 'Block guests from buying products in your shop', 'maximum-products-per-user-for-woocommerce' ),
					'identify_by_ip' => __( 'Identify guests by IP address', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'desc'     => sprintf( __( 'For the "%s" option.', 'maximum-products-per-user-for-woocommerce' ),
					__( 'Block guests from buying products in your shop', 'maximum-products-per-user-for-woocommerce' ) ),
				'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					sprintf( __( 'E.g.: %s.', 'maximum-products-per-user-for-woocommerce' ), '<em>[alg_wc_mppu_translate]</em>' ),
				'id'       => 'alg_wc_mppu_block_guests_message',
				'default'  => __( 'You need to register to buy products.', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'textarea',
				'css'      => 'width:100%;height:100px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_general_options',
			),
		);

		return array_merge( $plugin_settings, $general_settings );
	}

}

endif;

return new Alg_WC_MPPU_Settings_General();
