<?php
/**
 * Maximum Products per User for WooCommerce - Tools Section Settings
 *
 * @version 2.4.0
 * @since   2.4.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Tools' ) ) :

class Alg_WC_MPPU_Settings_Tools extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function __construct() {
		$this->id   = 'tools';
		$this->desc = __( 'Tools', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 * @todo    [next] add "Reset all settings" option (i.e. reset all settings sections at once)
	 */
	function get_settings() {

		$tools_settings = array(
			array(
				'title'    => __( 'Tools', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Check the box and save settings to run the tool. Please note that there is no undo for this action.', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_tools_options',
			),
			array(
				'title'    => __( 'Recalculate sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Run', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_mppu_tool_recalculate',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Delete & recalculate sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Run', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_mppu_tool_delete_recalculate',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Delete sales data', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Run', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_mppu_tool_delete',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_tools_options',
			),
			array(
				'title'    => __( 'Advanced', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Advanced options for both recalculation tools.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					__( 'Leave the default values if not sure.', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_tools_advanced_options',
			),
			array(
				'title'    => __( 'Orders date range', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_tool_recalculate_date_range',
				'default'  => 'no',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'no'  => __( 'All orders', 'maximum-products-per-user-for-woocommerce' ),
					'yes' => __( 'Only orders in "General > Date range"', 'maximum-products-per-user-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Query block size', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'How many orders to process in a single query.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_tool_recalculate_block_size',
				'default'  => 1024,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 1 ),
			),
			array(
				'title'    => __( 'Time limit', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'seconds', 'maximum-products-per-user-for-woocommerce' ),
				'desc_tip' => __( 'The maximum execution time, in seconds.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					__( 'If set to zero, no time limit is imposed.', 'maximum-products-per-user-for-woocommerce' ) . ' ' .
					sprintf( __( 'If set to minus one, server time limit (%s seconds) is used.', 'maximum-products-per-user-for-woocommerce' ), ini_get( 'max_execution_time' ) ),
				'id'       => 'alg_wc_mppu_tool_recalculate_time_limit',
				'default'  => -1,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => -1 ),
			),
			array(
				'title'    => __( 'Loop function', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_tool_recalculate_loop_func',
				'default'  => 'wp_query',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'wp_query'      => sprintf( __( 'WordPress standard (%s)', 'maximum-products-per-user-for-woocommerce' ), 'WP_Query' ),
					'wc_get_orders' => sprintf( __( 'WooCommerce specific (%s)', 'maximum-products-per-user-for-woocommerce' ), 'wc_get_orders' ),
				),
			),
			array(
				'title'    => __( 'Debug', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'Enable', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_tool_recalculate_debug',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_tools_advanced_options',
			),
		);

		return $tools_settings;
	}

}

endif;

return new Alg_WC_MPPU_Settings_Tools();
