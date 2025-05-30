<?php
/**
 * Maximum Products per User for WooCommerce - Admin Section Settings.
 *
 * @version 4.3.8
 * @since   2.2.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Admin' ) ) :

class Alg_WC_MPPU_Settings_Admin extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.4.0
	 * @since   2.2.0
	 * @todo    [next] (desc) rename section to "Users"?
	 */
	function __construct() {
		$this->id   = 'admin';
		$this->desc = __( 'Admin', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 4.3.8
	 * @since   2.2.0
	 * @todo    [next] `alg_wc_mppu_user_export_sep`: separate for "single user export" and "all users export"?
	 * @todo    [next] (desc) Extra meta: better desc
	 */
	function get_settings() {
		$product_sales_data_opts = array(
			array(
				'title' => __( 'Product sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'  => sprintf( __( 'See sales data from products on the %s.', 'maximum-products-per-user-for-woocommerce' ), __( 'admin product page', 'maximum-products-per-user-for-woocommerce' ) ),
				'type'  => 'title',
				'id'    => 'alg_wc_mppu_product_sales_data_options',
			),
			array(
				'title'    => __( 'Sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable sales data on product page', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_enable_product_sales_data',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'AJAX', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Load sales data using AJAX', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_enable_product_sales_data_via_ajax',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'alg_wc_mppu_product_sales_data_options',
			),
		);

		$term_sales_data_opts = array(
			array(
				'title' => __( 'Term sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'  => __( 'See sales data from categories and tags.', 'maximum-products-per-user-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'alg_wc_mppu_term_sales_data_options',
			),
			array(
				'title'    => __( 'Sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable sales data on term pages', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_enable_term_sales_data',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'AJAX', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Load sales data using AJAX', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_enable_term_sales_data_via_ajax',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'alg_wc_mppu_term_sales_data_options',
			),
		);

		$users_editable_sales_data_opts = array(
			array(
				'title'    => __( 'User\'s sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => sprintf( __( 'Manage sales data from users on the %s.', 'maximum-products-per-user-for-woocommerce' ), '<a href="' . admin_url( 'profile.php' ) . '">' . __( 'profile page', 'maximum-products-per-user-for-woocommerce' ) . '</a>' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_sales_data_options',
			),
			array(
				'title'    => __( 'User\'s sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Display user\'s sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'This option is necessary for all of the options below.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Delete sales button', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Add a button to delete user\'s sales data', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_delete_user_sales_btn_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'User email type used to delete the user\'s sales.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_delete_user_sales_email_type',
				'default'  => 'user_email',
				'options' => array(
					'user_email'    => __( 'User email', 'maximum-products-per-user-for-woocommerce' ),
					'billing_email' => __( 'Customer billing email', 'maximum-products-per-user-for-woocommerce' ),
				),
				'type'     => 'select',
				'class'    => 'chosen_select'
			),
			array(
				'title'    => __( 'AJAX', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Load sales data using AJAX', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Enable it if the results are taking too long to be retrieved or if you are experiencing timeout errors.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_using_ajax',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Variations', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => sprintf( __( 'Show variations even if %s option is disabled', 'maximum-products-per-user-for-woocommerce' ), '<strong>' . __( 'Use Variations', 'maximum-products-per-user-for-woocommerce' ) . '</strong>' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_show_variations',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Automatically update lifetime column from orders column', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Lifetime column won\'t be editable anymore.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_auto_update_lifetime',
				'default'  => 'no',
				'checkboxgroup' => 'start',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Empty items', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Add "Lifetime" column for products/terms with no sales data', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_empty_totals',
				'default'  => 'no',
				'checkboxgroup' => 'end',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Terms data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Automatically calculate terms data from products data', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Tags and categories data won\'t be editable anymore.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_auto_update_terms_data',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_sales_data_options',
			),
		);

		$user_sales_data_export_opts = array(
			array(
				'title'    => __( 'User sales data export options', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<a class="button" href="' . add_query_arg( 'alg_wc_mppu_export_all_users_orders_data', true ). '">' .
				              __( 'Export sales data for all users', 'maximum-products-per-user-for-woocommerce' ) . '</a>',
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_sales_data_export_options',
			),
			array(
				'title'    => __( 'Export', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Column separator', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_user_export_sep',
				'default'  => ',',
				'type'     => 'text',
			),
			array(
				'title'     => __( 'Merge user', 'maximum-products-per-user-for-woocommerce' ),
				'desc'      => __( 'Output all user\'s data in a single line', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip'  => sprintf( __( 'Used in "%s" tool.', 'maximum-products-per-user-for-woocommerce' ), __( 'Export sales data for all users', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'        => 'alg_wc_mppu_user_export_merge_user',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'    => __( 'Data separator', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => sprintf( __( 'Ignored unless "%s" option is enabled.', 'maximum-products-per-user-for-woocommerce' ), __( 'Merge user', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_user_export_data_sep',
				'default'  => ';',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Extra meta', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Use comma separated values.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
				              sprintf( __( 'Ignored unless "%s" option is enabled.', 'maximum-products-per-user-for-woocommerce' ), __( 'Merge user', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_user_export_meta',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:100%;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_sales_data_export_options',
			),
		);

		return array_merge(
			$product_sales_data_opts,
			$term_sales_data_opts,
			$users_editable_sales_data_opts,
			$user_sales_data_export_opts,
		);
	}

}

endif;

return new Alg_WC_MPPU_Settings_Admin();
