<?php
/**
 * Maximum Products per User for WooCommerce - My Account
 *
 * @version 3.5.2
 * @since   2.5.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_My_Account' ) ) :

class Alg_WC_MPPU_My_Account {

	/**
	 * Constructor.
	 *
	 * @version 3.5.2
	 * @since   2.5.0
	 */
	function __construct() {
		if ( 'yes' === get_option( 'alg_wc_mppu_my_account_enabled', 'no' ) ) {
			$this->my_account_tab_id = do_shortcode( get_option( 'alg_wc_mppu_my_account_tab_id', 'product-limits' ) );
			add_filter( 'the_title',                                                     array( $this, 'endpoint_title' ) );
			add_action( 'alg_wc_mppu_after_save_settings',                               array( $this, 'flush_rewrite_rules' ) );
			add_action( 'init',                                                          array( $this, 'add_endpoint' ) );
			add_filter( 'query_vars',                                                    array( $this, 'query_vars' ), 0 );
			add_filter( 'woocommerce_account_menu_items',                                array( $this, 'add_link' ) );
			add_action( 'woocommerce_account_' . $this->my_account_tab_id . '_endpoint', array( $this, 'content' ) );
			add_action( 'wp_head',                                                       array( $this, 'icon' ) );
		}
	}

	/**
	 * icon.
	 *
	 * @version 3.3.2
	 * @since   3.3.2
	 */
	function icon() {
		if ( '' !== ( $icon_code = get_option( 'alg_wc_mppu_my_account_tab_icon', '' ) ) ) {
			echo '<style>' . '.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--' . $this->my_account_tab_id . ' a::before { ' .
				'content: "\\' . $icon_code . '";' . ' }' .
			'</style>';
		}
	}

	/**
	 * get_tab_title.
	 *
	 * @version 3.3.1
	 * @since   3.3.1
	 */
	function get_tab_title() {
		return do_shortcode( get_option( 'alg_wc_mppu_my_account_tab_title', __( 'Product limits', 'maximum-products-per-user-for-woocommerce' ) ) );
	}

	/**
	 * content.
	 *
	 * @version 3.4.0
	 * @since   2.5.0
	 * @todo    [later] terms
	 */
	function content() {
		echo do_shortcode( get_option( 'alg_wc_mppu_my_account_tab_content', '[alg_wc_mppu_user_product_limits]' ) );
	}

	/**
	 * add_link.
	 *
	 * @version 3.3.1
	 * @since   2.5.0
	 */
	function add_link( $items ) {
		$_items   = array();
		$is_added = false;
		foreach ( $items as $id => $item ) {
			$_items[ $id ] = $item;
			if ( 'orders' == $id ) {
				$_items[ $this->my_account_tab_id ] = $this->get_tab_title();
				$is_added = true;
			}
		}
		if ( ! $is_added ) {
			$_items[ $this->my_account_tab_id ] = $this->get_tab_title();
		}
		return $_items;
	}

	/**
	 * query_vars.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function query_vars( $vars ) {
		$vars[] = $this->my_account_tab_id;
		return $vars;
	}

	/**
	 * add_endpoint.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_endpoint() {
		add_rewrite_endpoint( $this->my_account_tab_id, EP_ROOT | EP_PAGES );
	}

	/**
	 * flush_rewrite_rules.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function flush_rewrite_rules() {
		global $current_section;
		if ( $current_section && 'frontend' == $current_section ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * endpoint_title.
	 *
	 * @version 3.3.1
	 * @since   2.5.0
	 */
	function endpoint_title( $title ) {
		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ $this->my_account_tab_id ] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			$title = $this->get_tab_title();
			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}
		return $title;
	}

}

endif;

return new Alg_WC_MPPU_My_Account();
