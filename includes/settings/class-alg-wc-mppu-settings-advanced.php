<?php
/**
 * Maximum Products per User for WooCommerce - Advanced Section Settings.
 *
 * @version 4.1.8
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
	 * @version 4.1.8
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
				'title'    => __( 'Time offset', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => sprintf( __( 'Use a <a href="%s" target="_blank">date/time</a> string.', 'maximum-products-per-user-for-woocommerce' ), ' . https://www.php.net/manual/en/function.strtotime.php' ) . ' ' . __( 'Examples:', 'maximum-products-per-user-for-woocommerce' ) . ' ' . '<code>+2 hours</code>, <code>-2 hours</code>.',
				'desc_tip' => sprintf( __( 'Compensates the date used on the Date Range option. It\'s used on the %s filter.', 'maximum-products-per-user-for-woocommerce' ), '<strong>alg_wc_mppu_date_to_check</strong>' ),
				'id'       => 'alg_wc_mppu_time_offset',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Current time offset', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => sprintf( __( 'Use a <a href="%s" target="_blank">date/time</a> string.', 'maximum-products-per-user-for-woocommerce' ), ' . https://www.php.net/manual/en/function.strtotime.php' ) . ' ' . __( 'Examples:', 'maximum-products-per-user-for-woocommerce' ) . ' ' . '<code>+2 hours</code>, <code>-2 hours</code>',
				'desc_tip' => sprintf( __( 'Compensates the current time. It\'s used on the %s filter.', 'maximum-products-per-user-for-woocommerce' ), '<strong>alg_wc_mppu_datetime_to_compare</strong>' ).' '.
				              sprintf( __( 'If used with the %s option, it should reflect the difference of days starting on Monday.', 'maximum-products-per-user-for-woocommerce' ), '"'.__( 'General > Weeks starts on', 'maximum-products-per-user-for-woocommerce' ).'"' ).' '.
				              sprintf( __( 'Example: If the %s is set as Thursday, it should be set as %s', 'maximum-products-per-user-for-woocommerce' ), '"'.__( 'Weeks starts on', 'maximum-products-per-user-for-woocommerce' ).'"','<code>'.__( '- 3 days', 'maximum-products-per-user-for-woocommerce' ).'</code>' ),
				'id'       => 'alg_wc_mppu_current_time_offset',
				'default'  => '',
				'type'     => 'text',
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
				'title'    => __( 'Lifetime from totals', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Ignored unless "%s" is set to "%s" in "%s" section.', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Date range', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'general' ) ) . '<br>' .
					sprintf( __( 'Enabling this may make "user already bought" data calculations faster, however, it will also disable some functionality, like "%s" option in "%s" section.', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Count by current payment method', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'general' ) ),
				'id'       => 'alg_wc_mppu_get_lifetime_from_totals',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_advanced_options',
			),
		);

		$bkg_process_options = array(
			array(
				'title' => __( 'Background processing', 'maximum-products-per-user-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'alg_wc_mppu_advanced_bkg_process_options',
			),
			array(
				'title'    => __( 'Minimum amount', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'The minimum amount of results from a query in order to trigger a background processing.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_bkg_process_min_amount',
				'default'  => 50,
				'type'     => 'number',
			),
			array(
				'title'   => __( 'Send email', 'maximum-products-per-user-for-woocommerce' ),
				'desc'    => __( 'Send email when a background processing is complete', 'maximum-products-per-user-for-woocommerce' ),
				'id'      => 'alg_wc_mppu_bkg_process_send_email',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'desc'        => __( 'Email to.', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip'    => __( 'The email address that is going to receive the email when a background processing task is complete.', 'maximum-products-per-user-for-woocommerce' ) . '<br />' . __( 'Requires the "Send email" option enabled in order to work.', 'maximum-products-per-user-for-woocommerce' ),
				'id'          => 'alg_wc_mppu_bkg_process_email_to',
				'placeholder' => get_option( 'admin_email' ),
				'default'     => get_option( 'admin_email' ),
				'type'        => 'text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'alg_wc_mppu_advanced_bkg_process_options',
			)
		);

		$orders_above_limits_opts = $this->get_orders_above_limits_options();

		return array_merge( $advanced_settings, $bkg_process_options, $orders_above_limits_opts );
	}

	/**
	 * get_orders_above_limit_options.
	 *
	 * @version 3.8.5
	 * @since   3.6.1
	 *
	 * @return array
	 */
	function get_orders_above_limits_options() {
		$orders_above_limits_opts = array(
			array(
				'title' => __( 'Orders above limits', 'maximum-products-per-user-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Let users place orders that do not respect the limits.', 'maximum-products-per-user-for-woocommerce' ),
				'id'    => 'alg_wc_mppu_orders_above_limits_options',
			),
			array(
				'title'             => __( 'Orders above limits', 'maximum-products-per-user-for-woocommerce' ),
				'desc'              => __( 'Allow users to place orders with exceeding limits', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip'          => __( 'Notices regarding limit issues will not be displayed as errors on checkout.', 'maximum-products-per-user-for-woocommerce' ),
				'id'                => 'alg_wc_mppu_orders_above_limits_allowed',
				'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
				'default'           => 'no',
				'type'              => 'checkbox',
			),
			array(
				'title'    => __( 'Order status', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Change status from newly placed orders above limits', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Order status will only be changed if %s option is enabled.', 'maximum-products-per-user-for-woocommerce' ), '<strong>' . __( 'Orders above limits', 'maximum-products-per-user-for-woocommerce' ) . '</strong>' ),
				'id'       => 'alg_wc_mppu_orders_above_limits_change_status',
				'type'     => 'checkbox',
				'default'  => 'no',
				'options'  => wc_get_order_statuses(),
			),
		);
		if ( 'yes' === get_option( 'alg_wc_mppu_orders_above_limits_change_status', 'no' ) ) {
			$orders_above_limits_opts = array_merge( $orders_above_limits_opts, array(
					array(
						'desc'    => __( 'Status used on orders above limits.', 'maximum-products-per-user-for-woocommerce' ),
						'id'      => 'alg_wc_mppu_orders_above_limits_status',
						'type'    => 'select',
						'class'   => 'chosen_select',
						'default' => 'wc-mppu-invalid',
						'options' => wc_get_order_statuses(),
					),
					array(
						'desc'    => __( 'Custom order status label.', 'maximum-products-per-user-for-woocommerce' ),
						'id'      => 'alg_wc_mppu_orders_above_limits_custom_status_label',
						'default' => __( 'Above limit', 'maximum-products-per-user-for-woocommerce' ),
						'type'    => 'text',
					)
				)
			);
		}
		$orders_above_limits_opts = array_merge(
			$orders_above_limits_opts, array(
				array(
					'type' => 'sectionend',
					'id'   => 'alg_wc_mppu_orders_above_limits_options',
				)
			)
		);
		return $orders_above_limits_opts;
	}

}

endif;

return new Alg_WC_MPPU_Settings_Advanced();
