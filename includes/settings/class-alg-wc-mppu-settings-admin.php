<?php
/**
 * Maximum Products per User for WooCommerce - Admin Section Settings.
 *
 * @version 3.7.8
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
	 * @version 3.7.8
	 * @since   2.2.0
	 * @todo    [next] `alg_wc_mppu_user_export_sep`: separate for "single user export" and "all users export"?
	 * @todo    [next] (desc) Extra meta: better desc
	 */
	function get_settings() {

		$admin_settings = array(
			array(
				'title'    => __( 'Admin Options', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<a class="button" href="' . add_query_arg( 'alg_wc_mppu_export_all_users_orders_data', true ). '">' .
				__( 'Export sales data for all users', 'maximum-products-per-user-for-woocommerce' ) . '</a>',
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_admin_options',
			),
			array(
				'title'    => __( 'Editable sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Edit each user\'s sales data on user\'s edit page (in "Users")', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data',
				'default'  => 'no',
				'checkboxgroup' => 'start',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'Add "Lifetime" column for products/terms with no sales data', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_empty_totals',
				'checkboxgroup' => '',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => sprintf( __( 'Show variations even if %s option is disabled', 'maximum-products-per-user-for-woocommerce' ), '<strong>' . __( 'Use Variations', 'maximum-products-per-user-for-woocommerce' ) . '</strong>' ),
				'id'       => 'alg_wc_mppu_editable_sales_data_show_variations',
				'default'  => 'no',
				'checkboxgroup' => 'end',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Export', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Column separator', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_user_export_sep',
				'default'  => ',',
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Merge user', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'This will output all user\'s data in a single line.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					sprintf( __( 'Used in "%s" tool.', 'maximum-products-per-user-for-woocommerce' ), __( 'Export sales data for all users', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_user_export_merge_user',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'Data separator.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					sprintf( __( 'Ignored unless "%s" option is enabled.', 'maximum-products-per-user-for-woocommerce' ), __( 'Merge user', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_user_export_data_sep',
				'default'  => ';',
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Extra meta (as comma separated values).', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					sprintf( __( 'Ignored unless "%s" option is enabled.', 'maximum-products-per-user-for-woocommerce' ), __( 'Merge user', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_user_export_meta',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:100%;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_admin_options',
			),
		);

		return $admin_settings;
	}

}

endif;

return new Alg_WC_MPPU_Settings_Admin();
