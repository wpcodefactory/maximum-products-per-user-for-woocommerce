<?php
/**
 * Maximum Products per User for WooCommerce - General Section Settings.
 *
 * @version 4.2.6
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
	 * @version 4.2.6
	 * @since   1.0.0
	 * @todo    [next] exclude unnecessary statuses from `alg_wc_mppu_order_status` (e.g. "Cancelled", "Refunded", "Failed") and `alg_wc_mppu_order_status_delete` (e.g. "Completed" etc.)?
	 * @todo    [next] (desc) `alg_wc_mppu_order_status_delete`: `$this->get_recalculate_sales_data_desc( __( 'Order statuses', 'maximum-products-per-user-for-woocommerce' ) )`?
	 * @todo    [maybe] `alg_wc_mppu_block_guests`: default to `yes` or `identify_by_ip`?
	 * @todo    [maybe] (desc) Use variations: better desc?
	 * @todo    [maybe] (desc) Hide products: better desc?
	 * @todo    [maybe] (desc) Enabled user roles: better desc?
	 * @todo    [maybe] (desc) Multi-language: better desc?
	 * @todo    [maybe] (desc) Count by current payment method: better desc?
	 */
	function get_settings() {

		$general_settings = array(
			array(
				'title'    => __( 'General options', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'desc'     => '<p>' . '* ' . sprintf( __( 'While data is recalculated automatically (but only after the plugin was enabled), you can also force manual recalculation by running %s tool.', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'tools', __( 'Recalculate sales data', 'maximum-products-per-user-for-woocommerce' ) ) ) . ' ' .
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
				'title'    => __( 'Mode', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->get_recalculate_sales_data_desc( __( 'Mode', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_mode',
				'default'  => 'qty',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'qty'             => __( 'Product quantities', 'maximum-products-per-user-for-woocommerce' ),
					'orders'          => __( 'Product orders', 'maximum-products-per-user-for-woocommerce' ),
					'price'           => __( 'Product prices (incl. tax)', 'maximum-products-per-user-for-woocommerce' ),
					'price_excl_tax'  => __( 'Product prices (excl. tax)', 'maximum-products-per-user-for-woocommerce' ),
					'weight'          => __( 'Product weights', 'maximum-products-per-user-for-woocommerce' ),
					'volume'          => __( 'Product volumes', 'maximum-products-per-user-for-woocommerce' ),
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
				'title'   => __( 'Refunds', 'maximum-products-per-user-for-woocommerce' ),
				'desc'    => __( 'Deduct partial refunds from user limits', 'maximum-products-per-user-for-woocommerce' ),
				'id'      => 'alg_wc_mppu_deduct_refunds',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'    => __( 'Quantity input', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Set a maximum value for the product quantity field based on its current limit', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_set_qty_field_max_attr',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Add to cart button', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Disable product purchase if its limit has been reached for the current user', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_disable_product_purchase_by_limit',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Use variations', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Use variations in "All products" and "Per product" calculations.', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'If "Limits > Per Product" section is enabled, you will be able to (optionally) override this option for each individual product.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_use_variations',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'User roles', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Set different limits for different user roles', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_use_user_roles',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'Enabled user roles', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Select user roles for which you want to set different limits. If empty, then all user roles will be added to the settings.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_enabled_user_roles',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => alg_wc_mppu()->core->get_user_roles( false ),
			),
			array(
				'title'         => __( 'Hide products', 'maximum-products-per-user-for-woocommerce' ),
				'desc'          => __( 'Hide products with exceeded limits from the catalog', 'maximum-products-per-user-for-woocommerce' ),
				'id'            => 'alg_wc_mppu_hide_products',
				'default'       => 'no',
				'checkboxgroup' => 'start',
				'type'          => 'checkbox',
			),
			array(
				'desc'          => __( 'Hide products with exceeded limits from search and direct links', 'maximum-products-per-user-for-woocommerce' ),
				'id'            => 'alg_wc_mppu_hide_products_on_search_and_direct_links',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'title'    => __( 'Count by current payment method', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Count "user already bought" data for current (i.e. chosen) payment method only', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'You may also want to disable "%s" and "%s" options in the "%s" section, so your customer could change the payment method on exceeded limits.', 'maximum-products-per-user-for-woocommerce' ),
					__( 'Validate on add to cart', 'maximum-products-per-user-for-woocommerce' ),
					__( 'Block checkout page', 'maximum-products-per-user-for-woocommerce' ),
					$this->get_section_link( 'frontend' ) ),
				'id'       => 'alg_wc_mppu_count_by_current_payment_method',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_plugin_options',
			),
		);

		$date_range_options = array(
			array(
				'title' => __( 'Date range options', 'maximum-products-per-user-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'alg_wc_mppu_date_range_options',
			),
			array(
				'title'    => __( 'Date range', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_date_range',
				'default'  => 'lifetime',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'lifetime'       => __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ),
					'this_hour'      => __( 'This hour', 'maximum-products-per-user-for-woocommerce' ),
					'this_day'       => __( 'This day', 'maximum-products-per-user-for-woocommerce' ),
					'this_week'      => __( 'This week', 'maximum-products-per-user-for-woocommerce' ),
					'this_month'     => __( 'This month', 'maximum-products-per-user-for-woocommerce' ),
					'this_year'      => __( 'This year', 'maximum-products-per-user-for-woocommerce' ),
					'last_hour'      => __( 'Last hour', 'maximum-products-per-user-for-woocommerce' ),
					'last_24_hours'  => __( 'Last 24 hours', 'maximum-products-per-user-for-woocommerce' ),
					'last_7_days'    => __( 'Last 7 days', 'maximum-products-per-user-for-woocommerce' ),
					'last_30_days'   => __( 'Last 30 days', 'maximum-products-per-user-for-woocommerce' ),
					'last_365_days'  => __( 'Last 365 days', 'maximum-products-per-user-for-woocommerce' ),
					'custom'         => __( 'Custom range', 'maximum-products-per-user-for-woocommerce' ),
					'fixed_date'     => __( 'Fixed date', 'maximum-products-per-user-for-woocommerce' ),
					'monthly'        => __( 'Monthly', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Custom date range', 'maximum-products-per-user-for-woocommerce' ), get_option( 'alg_wc_mppu_date_range_custom_unit', 'seconds' ),
				'desc'     => sprintf( __( 'In %s.', 'maximum-products-per-user-for-woocommerce' ), get_option( 'alg_wc_mppu_date_range_custom_unit', 'seconds' ) ),
				'desc_tip' => __( 'Used when "Custom" option is selected in "Date range".', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_date_range_custom',
				'default'  => 3600,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 1 ),
			),
			array(
				'desc'     => __( 'Custom date range unit.', 'maximum-products-per-user-for-woocommerce' ),
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
				'title'    => __( 'Fixed date range', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Used with the "%s" date range option.', 'maximum-products-per-user-for-woocommerce' ), __( 'Fixed date', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_date_range_fixed_date',
				'default'  => '',
				'css'      => 'width:398px;',
				'type'     => 'datetime-local',
			),
			array(
				'title'     => __( 'Origin date', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Used with the "%s" date range option.', 'maximum-products-per-user-for-woocommerce' ), __( 'Monthly', 'maximum-products-per-user-for-woocommerce' ) ) .
				              ' ' . sprintf( __( 'If the user register date is %s, the limits would be reset monthly, like: %s and so on, always on day 15th.', 'maximum-products-per-user-for-woocommerce' ), '<code>2023-04-15</code>', '(2023-04-15 > 2023-05-15 > 2023-06-15)' ) .
				              ' ' . __( 'The free version will only work with the "User register date" option.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_date_range_origin_date',
				'default'  => '',
				'options'  => array(
					'user_register_date'            => __( 'User register date', 'maximum-products-per-user-for-woocommerce' ),
					'memberpress_subscription_date' => __( 'MemberPress subscription date', 'maximum-products-per-user-for-woocommerce' )
				),
				'type'     => 'select',
			),
			array(
				'title'    => __( 'Week starts on', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Used with the "%s" date range option.', 'maximum-products-per-user-for-woocommerce' ), __( 'This week', 'maximum-products-per-user-for-woocommerce' ) ) . ' ' .
				              sprintf( __( 'Should be used along with the "%s" option.', 'maximum-products-per-user-for-woocommerce' ), __( 'Advanced > Current time offset', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_date_range_week_starts_on',
				'default'  => alg_wc_mppu()->core->weekdays ? alg_wc_mppu()->core->weekdays->get_week_starts_on_default_val() : '0',
				'options'  => alg_wc_mppu()->core->weekdays ? alg_wc_mppu()->core->weekdays->get_week_days_by_key_and_value( 'name_formatted' ) : array(),
				'type'     => 'select',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_date_range_options',
			),
		);

		$multi_lang_options = array(
			array(
				'title' => __( 'Multi-language', 'maximum-products-per-user-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'alg_wc_mppu_multilang_options',
			),
			array(
				'title'    => __( 'Multi-language', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Use the default language product/term ID instead of the translated one.', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->get_recalculate_sales_data_desc( __( 'Multi-language', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_multi_language',
				'default'  => 'no',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'       => __( 'Disabled', 'maximum-products-per-user-for-woocommerce' ),
					'wpml'     => __( 'WPML', 'maximum-products-per-user-for-woocommerce' ),
					'polylang' => __( 'Polylang', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Limit checking', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Use product ID from default language when checking product limits', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_multi_language_use_main_prod_id_on_checking_limits',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_multilang_options',
			),
		);

		return array_merge( $general_settings, $date_range_options, $multi_lang_options );
	}

}

endif;

return new Alg_WC_MPPU_Settings_General();
