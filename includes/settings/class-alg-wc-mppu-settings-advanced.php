<?php
/**
 * Maximum Products per User for WooCommerce - Advanced Section Settings
 *
 * @version 3.4.0
 * @since   2.3.1
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Advanced' ) ) :

class Alg_WC_MPPU_Settings_Advanced extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.3.1
	 * @since   2.3.1
	 */
	function __construct() {
		$this->id   = 'advanced';
		$this->desc = __( 'Advanced', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_products.
	 *
	 * @version 2.2.0
	 * @since   2.0.0
	 * @todo    [later] use `wc_get_products()`
	 */
	function get_products() {
		$products   = array();
		$block_size = 1024;
		$offset     = 0;
		while ( true ) {
			$args = array(
				'post_type'      => ( 'yes' === get_option( 'alg_wc_mppu_use_variations', 'no' ) ? array( 'product', 'product_variation' ) : 'product' ),
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				$products[ $product_id ] = get_the_title( $product_id ) . ' (#' . $product_id . ')';
			}
			$offset += $block_size;
		}
		return $products;
	}

	/**
	 * get_settings.
	 *
	 * @version 3.4.0
	 * @since   2.3.1
	 * @todo    [maybe] `alg_wc_mppu_time_func`: remove option (i.e. always use local time)?
	 */
	function get_settings() {

		$advanced_settings = array(
			array(
				'title'    => __( 'Advanced Options', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_advanced_options',
			),
			array(
				'title'    => __( 'Time function', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_time_func',
				'default'  => 'current_time',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'time'         => __( 'Coordinated Universal Time (UTC)', 'maximum-products-per-user-for-woocommerce' ),
					'current_time' => __( 'Local (WordPress) time', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Exclude products', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->get_recalculate_sales_data_desc( __( 'Exclude products', 'maximum-products-per-user-for-woocommerce' ) ),
				'id'       => 'alg_wc_mppu_exclude_products',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $this->get_products(),
				'css'      => 'width:100%;',
			),
			array(
				'title'    => __( 'Duplicate product', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'Enable this if you want to copy plugin\'s product meta data on product "Duplicate".', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_duplicate_product',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_advanced_options',
			),
		);

		return $advanced_settings;
	}

}

endif;

return new Alg_WC_MPPU_Settings_Advanced();
