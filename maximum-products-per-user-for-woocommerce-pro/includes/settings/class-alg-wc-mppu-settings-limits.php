<?php
/**
 * Maximum Products per User for WooCommerce - Limits Section Settings
 *
 * @version 3.5.6
 * @since   2.2.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Limits' ) ) :

class Alg_WC_MPPU_Settings_Limits extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 * @since   2.2.0
	 */
	function __construct() {
		$this->id   = 'limits';
		$this->desc = __( 'Limits', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 3.5.6
	 * @since   2.2.0
	 * @todo    [maybe] rethink `wpjup_wc_maximum_products_per_user_global_max_qty`: default: `1` or `0`?
	 */
	function get_settings() {

		$all_products_settings = array(
			array(
				'title'    => __( 'All Products', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_global_options',
			),
			array(
				'title'    => __( 'All products', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'id'       => 'wpjup_wc_maximum_products_per_user_global_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Limit per user', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Maximum allowed each product\'s limit per user.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'wpjup_wc_maximum_products_per_user_global_max_qty',
				'default'  => 1,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => -1 ),
			),
		);
		if ( 'yes' === get_option( 'alg_wc_mppu_use_user_roles', 'no' ) ) {
			foreach ( alg_wc_mppu()->core->get_user_roles() as $role => $role_name ) {
				$all_products_settings = array_merge( $all_products_settings, array(
					array(
						'title'    => __( 'Limit per user', 'maximum-products-per-user-for-woocommerce' ) . ': ' . $role_name,
						'desc_tip' => __( 'If set to zero - "Limit per user" option will be used.', 'maximum-products-per-user-for-woocommerce' ),
						'id'       => "alg_wc_mppu_user_roles_max_qty[{$role}]",
						'default'  => 0,
						'type'     => 'number',
						'custom_attributes' => array( 'min' => -1 ),
					),
				) );
			}
		}
		$all_products_settings = array_merge( $all_products_settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_global_options',
			),
		) );

		$per_product_settings = array(
			array(
				'title' => __( 'Per Product', 'maximum-products-per-user-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'alg_wc_mppu_local_options',
			),
			array(
				'title'             => __( 'Per product', 'maximum-products-per-user-for-woocommerce' ),
				'desc'              => '<strong>' . __( 'Enable', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'desc_tip'          => __( 'This will add new meta box to each product\'s edit page.', 'maximum-products-per-user-for-woocommerce' ),
				'id'                => 'wpjup_wc_maximum_products_per_user_local_enabled',
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'alg_wc_mppu_local_options',
			),
		);

		$per_taxonomy_settings = array(
			array(
				'title'    => __( 'Per Product Taxonomy', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_taxonomy_options',
			),
			array(
				'title'             => __( 'Per product tag', 'maximum-products-per-user-for-woocommerce' ),
				'desc'              => '<strong>' . __( 'Enable', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'desc_tip'          => __( 'This will add new meta box to each product tag term\'s edit page.', 'maximum-products-per-user-for-woocommerce' ),
				'id'                => 'alg_wc_mppu_product_tag_enabled',
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'             => __( 'Per product category', 'maximum-products-per-user-for-woocommerce' ),
				'desc'              => '<strong>' . __( 'Enable', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'desc_tip'          => __( 'This will add new meta box to each product category term\'s edit page.', 'maximum-products-per-user-for-woocommerce' ),
				'id'                => 'alg_wc_mppu_product_cat_enabled',
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_taxonomy_options',
			),
		);

		return array_merge( $all_products_settings, $per_taxonomy_settings, $per_product_settings );
	}

}

endif;

return new Alg_WC_MPPU_Settings_Limits();
