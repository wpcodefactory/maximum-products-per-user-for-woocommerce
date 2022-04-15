<?php
/**
 * Maximum Products per User for WooCommerce - Core Class.
 *
 * @version 3.6.3
 * @since   1.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Core' ) ) :

class Alg_WC_MPPU_Core {

	/**
	 * $error_messages.
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	public $error_messages = array();

	/**
	 * Constructor.
	 *
	 * @version 3.6.1
	 * @since   1.0.0
	 * @todo    [next] split file
	 * @todo    [next] `alg_wc_mppu_cart_notice`: `text`: customizable (and maybe multiple) positions (i.e. hooks)
	 * @todo    [later] product terms (back-end): add fields to "*add* category/tag" pages also (i.e. not only to "edit" pages)
	 * @todo    [later] (feature) correct qty on add to cart
	 * @todo    [later] (feature) limit max quantity with WC filter
	 * @todo    [maybe] `alg_wc_mppu_cart_notice`: `notice` + `text` (i.e. simultaneous)?
	 * @todo    [maybe] `is_admin && ! ajax`?
	 */
	function __construct() {
		$this->is_wc_version_below_3_0_0 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		if ( 'yes' === get_option( 'wpjup_wc_maximum_products_per_user_plugin_enabled', 'yes' ) ) {
			// Properties
			$this->do_use_user_roles           = ( 'yes' === get_option( 'alg_wc_mppu_use_user_roles', 'no' ) );
			$this->do_identify_guests_by_ip    = ( 'identify_by_ip' === get_option( 'alg_wc_mppu_block_guests', 'no' ) );
			$this->do_get_lifetime_from_totals = ( 'yes' === get_option( 'alg_wc_mppu_get_lifetime_from_totals', 'no' ) );
			// Check quantities - Checkout
			add_action( 'woocommerce_checkout_process', array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
			// Check quantities - Cart
			if ( 'no' != ( $this->cart_notice = get_option( 'alg_wc_mppu_cart_notice', 'yes' ) ) ) {
				add_action( ( 'yes' === $this->cart_notice ? 'woocommerce_before_cart' : 'woocommerce_before_cart_table' ), array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
			}
			// Block checkout page
			if ( 'yes' === get_option( 'wpjup_wc_maximum_products_per_user_stop_from_seeing_checkout', 'no' ) ) {
				add_action( 'wp', array( $this, 'block_checkout' ), PHP_INT_MAX );
				// Validation actions
				$actions = array_map( 'trim', array_values( array_filter( explode( PHP_EOL, get_option( 'alg_wc_mppu_validation_actions', '' ) ) ) ) );
				foreach ( $actions as $action ) {
					add_action( $action, array( $this, 'block_checkout' ), PHP_INT_MAX );
				}
			}
			// Validate on add to cart
			if ( 'yes' === get_option( 'alg_wc_mppu_validate_on_add_to_cart', 'yes' ) ) {
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_on_add_to_cart' ), PHP_INT_MAX, 4 );
				if ( 'yes' === get_option( 'alg_wc_mppu_block_guests', 'no' ) ) {
					add_action( 'woocommerce_init', array( $this, 'block_guest_add_to_cart_ajax_error' ), PHP_INT_MAX );
				}
			}
			// Hide products
			add_filter( 'woocommerce_product_is_visible', array( $this, 'product_visibility' ), PHP_INT_MAX, 2 );
			// Single product page
			switch ( get_option( 'alg_wc_mppu_permanent_notice', 'no' ) ) {
				case 'yes':
					add_action( 'woocommerce_before_single_product',  array( $this, 'permanent_notice' ) );
					break;
				case 'text':
					add_action( 'woocommerce_single_product_summary', array( $this, 'permanent_notice_text' ), 30 );
					break;
				case 'text_content':
					add_filter( 'the_content', array( $this, 'permanent_notice_text_content' ) );
					break;
			}
			// Count by current payment method
			if ( 'yes' === get_option( 'alg_wc_mppu_count_by_current_payment_method', 'no' ) ) {
				add_filter( 'alg_wc_mppu_user_already_bought_do_count_order', array( $this, 'count_by_current_payment_method' ), 10, 3 );
			}
			// Shortcodes
			require_once( 'class-alg-wc-mppu-shortcodes.php' );
			// Per product options
			require_once( 'settings/class-alg-wc-mppu-settings-per-product.php' );
			// Product terms options
			require_once( 'settings/class-alg-wc-mppu-settings-per-term.php' );
			// Update quantities
			require_once( 'class-alg-wc-mppu-data.php' );
			// Sales data reports
			require_once( 'class-alg-wc-mppu-reports.php' );
			// Users
			require_once( 'class-alg-wc-mppu-users.php' );
			// My Account
			require_once( 'class-alg-wc-mppu-my-account.php' );
			// Modes
			require_once( 'class-alg-wc-mppu-modes.php' );
			// Multi-language
			$this->multilanguage = require_once( 'class-alg-wc-mppu-multi-language.php' );
		}
		// Hook msg shortcode
		add_filter( 'shortcode_atts_' . 'alg_wc_mppu_customer_msg', array( $this, 'filter_customer_message_shortcode' ) );
		// Set bought data to zero if guest option is set as "Do nothing and block guests from purchasing products beyond the limits"
		add_filter( 'alg_wc_mppu_user_already_bought', array( $this, 'set_guest_user_bought_to_zero' ) );
		// Core loaded
		do_action( 'alg_wc_mppu_core_loaded', $this );
	}

	/**
	 * set_guest_user_bought_to_zero
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	function set_guest_user_bought_to_zero( $data ) {
		if (
			! is_user_logged_in()
			&& 'block_beyond_limit' == get_option( 'alg_wc_mppu_block_guests' )
		) {
			$data['bought'] = 0;
		}
		return $data;
	}

	/**
	 * filter_customer_message_shortcode.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 *
	 * @param $atts
	 *
	 * @return mixed
	 */
	function filter_customer_message_shortcode( $atts ) {
		if (
			is_null( $atts['bought'] )
			&& isset( $this->placeholders['%bought%'] )
		) {
			$atts['bought'] = $this->placeholders['%bought%'];
		}
		return $atts;
	}

	/**
	 * add_to_log.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function add_to_log( $message ) {
		if ( function_exists( 'wc_get_logger' ) && ( $log = wc_get_logger() ) ) {
			$log->log( 'info', $message, array( 'source' => 'alg-wc-mppu' ) );
		}
	}

	/**
	 * count_by_current_payment_method.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @todo    [maybe] customizable `return false;` (i.e. optional `return true;` on `empty( $chosen_payment_method )`)?
	 */
	function count_by_current_payment_method( $do_count, $order_id, $order_data ) {
		$chosen_payment_method = $this->get_chosen_payment_method();
		if ( ! empty( $chosen_payment_method ) ) {
			if ( isset( $order_data['payment_method'] ) ) {
				return ( $order_data['payment_method'] === $chosen_payment_method );
			} else { // fallback for < MPPU v3.5.0
				$order = wc_get_order( $order_id );
				return ( $order && $order->get_payment_method() === $chosen_payment_method );
			}
		}
		return false;
	}

	/**
	 * get_current_user_id.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [next] identify guests: by address (shipping/billing); by email (also in `Alg_WC_MPPU_Data::get_user_id_from_order()`)
	 * @todo    [later] identify guests: IP: option to block "unidentifiable" users (i.e. when `'' === WC_Geolocation::get_ip_address()`)?
	 * @todo    [maybe] identify guests: IP: fallback to `WC_Geolocation::get_external_ip_address()`?
	 */
	function get_current_user_id() {
		if ( ! isset( $this->current_user_id ) ) {
			if ( ! function_exists( 'get_current_user_id' ) ) {
				return 0;
			}
			$this->current_user_id = get_current_user_id();
			if ( 0 == $this->current_user_id && $this->do_identify_guests_by_ip ) {
				$this->current_user_id = 'ip:' . WC_Geolocation::get_ip_address();
			}
		}
		return $this->current_user_id;
	}

	/**
	 * product_visibility.
	 *
	 * @version 3.5.3
	 * @since   3.4.0
	 * @todo    [next] (fix) `Showing all X results` (try filtering `product_visibility` taxonomy) (already tried `woocommerce_product_get_catalog_visibility` filter (returning `hidden`) - didn't help)
	 * @todo    [next] add option to count current `$cart_item_quantities` as well (i.e. pass `$cart_item_quantities` as 6th param in `check_quantities_for_product()`, and `$cart_item_quantities[ $product_id ] + 1` instead of `1`)
	 * @todo    [next] add similar option for `is_purchasable`?
	 * @todo    [next] (feature) hide via query (i.e. no direct link access)
	 * @todo    [later] (feature) option to hide everything for guests?
	 */
	function product_visibility( $visible, $product_id ) {
		if (
			'yes' === get_option( 'alg_wc_mppu_hide_products', 'no' )
			&& is_user_logged_in()
			&& ( $current_user_id = $this->get_current_user_id() )
			&& ! $this->check_quantities_for_product( $product_id, array(
				'_cart_item_quantity' => 1,
				'do_add_notices'      => false,
				'current_user_id'     => $current_user_id
			) )
		) {
			return false;
		}
		if (
			'yes' === get_option( 'alg_wc_mppu_hide_guest_blocked_products', 'no' )
			&& 'by_limit_options' === get_option( 'alg_wc_mppu_block_guests_method', 'all_products' )
			&& ! is_user_logged_in()
			&& $this->is_product_blocked_for_guests( $product_id )
		) {
			return false;
		}
		return $visible;
	}

	/**
	 * get_permanent_notice.
	 *
	 * @see https://stackoverflow.com/a/18081767/1193038
	 *
	 * @version 3.6.2
	 * @since   3.2.0
	 */
	function get_permanent_notice( $args = null ) {
		$args                           = wp_parse_args( $args, array(
			'current_product_limit_args' => array(
				'empty_msg_removes_template' => 'woocommerce_before_single_product' === current_filter() ? true : false
			),
		) );
		$current_product_limit_args_arr = $args['current_product_limit_args'];
		$current_product_limit_args     = join( ' ', array_map( function ( $key ) use ( $current_product_limit_args_arr ) {
			return $key . "='" . $current_product_limit_args_arr[ $key ] . "'";
		}, array_keys( $current_product_limit_args_arr ) ) );
		return ( is_singular( array( 'product' ) ) && ( $message = do_shortcode( '[alg_wc_mppu_current_product_limit ' . $current_product_limit_args . ']' ) ) ? $message : false );
	}

	/**
	 * permanent_notice_text_content.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function permanent_notice_text_content( $content ) {
		if ( $message = $this->get_permanent_notice() ) {
			$content = '<p>' . $message . '</p>' . $content;
		}
		return $content;
	}

	/**
	 * permanent_notice_text.
	 *
	 * @version 3.2.0
	 * @since   2.5.1
	 */
	function permanent_notice_text() {
		if ( $message = $this->get_permanent_notice() ) {
			echo $message;
		}
	}

	/**
	 * permanent_notice.
	 *
	 * @version 3.2.0
	 * @since   2.5.0
	 * @todo    [later] customizable type (and type depending on `$remaining`, i.e. `notice` vs `error`)
	 */
	function permanent_notice() {
		if ( $message = $this->get_permanent_notice() ) {
			wc_print_notice( $message, 'notice' );
		}
	}

	/**
	 * get_cart_item_quantities.
	 *
	 * @version 2.4.2
	 * @since   2.4.2
	 */
	function get_cart_item_quantities() {
		return apply_filters( 'alg_wc_mppu_get_cart_item_quantities', WC()->cart->get_cart_item_quantities() );
	}

	/**
	 * get_order_item_quantities.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	function get_order_item_quantities( $order ) {
		$quantities = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product                = $item->get_product();
			$prod_id                = $product->get_id();
			$quantities[ $prod_id ] = $item->get_quantity();
		}
		return $quantities;
	}

	/**
	 * handle_user_roles.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function handle_user_roles( $role ) {
		return ( '' == $role ? 'guest' : ( 'super_admin' == $role ? 'administrator' : $role ) );
	}

	/**
	 * get_max_qty_for_user_role.
	 *
	 * @version 3.6.0
	 * @since   2.2.0
	 * @todo    [maybe] (feature) per user (currently can be done via "Formula" section)
	 */
	function get_max_qty_for_user_role( $args = null ) {
		$args = wp_parse_args( $args, array(
			'type'               => '',
			'product_or_term_id' => 0,
			'user_id'            => 0,
		) );
		$type = $args['type'];
		$product_or_term_id = $args['product_or_term_id'];
		$user_id = $args['user_id'];
		$current_user = empty( $user_id ) ? wp_get_current_user() : get_user_by( 'ID', $user_id );
		// Get qty data
		switch ( $type ) {
			case 'per_product':
				$user_roles_data = ( '' != ( $data = get_post_meta( $product_or_term_id, '_alg_wc_mppu_user_roles_max_qty', true ) ) ? $data : array() );
				break;
			case 'all_products':
				$user_roles_data = get_option( 'alg_wc_mppu_user_roles_max_qty', array() );
				break;
			case 'per_term':
				$user_roles_data = ( '' != ( $data = get_term_meta( $product_or_term_id, '_alg_wc_mppu_user_roles_max_qty', true ) ) ? $data : array() );
				break;
			default: // 'formula'
				return 0;
		}
		// Get current user roles
		if ( ! isset( $current_user->roles ) || empty( $current_user->roles ) ) {
			$current_user->roles = array( 'guest' );
		}
		$current_user->roles = array_map( array( $this, 'handle_user_roles' ), $current_user->roles );
		// Return result
		foreach ( $current_user->roles as $role ) {
			if ( ! empty( $user_roles_data[ $role ] ) && $this->is_user_role_enabled( $role ) ) {
				return $user_roles_data[ $role ];
			}
		}
		// Nothing found for the user role
		return 0;
	}

	/**
	 * get_max_qty.
	 *
	 * @version 3.6.0
	 * @since   1.0.0
	 * @todo    [later] (feature) `per_product`: add "enabled/disabled" option
	 * @todo    [later] (feature) `all_products`: apply only to selected products (i.e. include/exclude products, cats, tags)
	 * @todo    [maybe] rename filters, e.g. `alg_wc_mppu_max_qty_by_formula` to `alg_wc_mppu_limits_by_formula` etc.
	 */
	function get_max_qty( $args = null ) {
		$args = wp_parse_args( $args, array(
			'type'               => '',
			'product_or_term_id' => '',
			'user_id'            => 0
		) );
		$type = $args['type'];
		$product_or_term_id = $args['product_or_term_id'];
		$user_id = $args['user_id'];
		if ( 0 != ( $max_qty_by_formula = apply_filters( 'alg_wc_mppu_max_qty_by_formula', 0, $type, $product_or_term_id ) ) ) {
			return apply_filters( 'alg_wc_mppu_get_max_qty', $max_qty_by_formula, $product_or_term_id, $type );
		}

		if ( $this->do_use_user_roles && 0 != ( $max_qty_for_user_role = $this->get_max_qty_for_user_role( array( 'type' => $type, 'product_or_term_id' => $product_or_term_id, 'user_id' => $user_id ) ) ) ) {
			return apply_filters( 'alg_wc_mppu_get_max_qty', $max_qty_for_user_role, $product_or_term_id, $type );
		}
		switch ( $type ) {
			case 'per_product':
				return apply_filters( 'alg_wc_mppu_get_max_qty', ( ( $qty = get_post_meta( $product_or_term_id, '_wpjup_wc_maximum_products_per_user_qty', true ) ) ? $qty : 0 ), $product_or_term_id, $type );
			case 'all_products':
				return apply_filters( 'alg_wc_mppu_get_max_qty', get_option( 'wpjup_wc_maximum_products_per_user_global_max_qty', 1 ), $product_or_term_id, $type );
			case 'per_term':
				return apply_filters( 'alg_wc_mppu_get_max_qty', ( ( $qty = get_term_meta( $product_or_term_id, '_alg_wc_mppu_qty', true ) ) ? $qty : 0 ), $product_or_term_id, $type );
		}
		return apply_filters( 'alg_wc_mppu_get_max_qty', 0, $product_or_term_id, $type ); // 'formula'
	}

	/**
	 * is_product_blocked_for_guests.
	 *
	 * @version 3.5.5
	 * @since   3.5.1
	 *
	 * @param $product_id
	 * @param null $args
	 *
	 * @return boolean
	 */
	function is_product_blocked_for_guests( $product_id, $args = null ) {
		$is_blocked = false;
		if ( isset( $this->product_blocked_for_guests[ $product_id ] ) ) {
			$is_blocked = apply_filters( 'alg_wc_mppu_is_product_blocked_for_guests', $this->product_blocked_for_guests[ $product_id ], $product_id, $args );
			return $is_blocked;
		}
		if ( 'yes' !== get_option( 'alg_wc_mppu_block_guests', 'no' ) ) {
			$is_blocked = $this->product_blocked_for_guests[ $product_id ] = apply_filters( 'alg_wc_mppu_is_product_blocked_for_guests', false, $product_id, $args );
			return $is_blocked;
		}
		if ( 'all_products' === $block_method = get_option( 'alg_wc_mppu_block_guests_method', 'all_products' ) ) {
			$is_blocked = $this->product_blocked_for_guests[ $product_id ] = apply_filters( 'alg_wc_mppu_is_product_blocked_for_guests', true, $product_id, $args );
			return $is_blocked;
		}

		$smart_product_id = $this->get_parent_or_product_id( $product_id );
		$use_variations = $this->do_use_variations( ( $parent_id = $this->get_parent_product_id( wc_get_product( $product_id ) ) ) );
		// By product
		if ( 'yes' === apply_filters( 'alg_wc_mppu_local_enabled', 'no' ) ) {
			$is_blocked = 'yes' === get_post_meta( $smart_product_id, '_wpjup_wc_mppu_block_guests', true );
			if ( $use_variations && 'variable' == WC_Product_Factory::get_product_type( $smart_product_id ) ) {
				$variable_product = wc_get_product( $product_id );
				$is_blocked       = true;
				foreach ( wp_list_pluck( $variable_product->get_available_variations(), 'variation_id' ) as $variation_id ) {
					if ( 'yes' !== get_post_meta( $variation_id, '_wpjup_wc_mppu_block_guests', true ) ) {
						$is_blocked = false;
						break;
					}
				}
			}
			return $this->product_blocked_for_guests[ $product_id ] = $is_blocked = apply_filters( 'alg_wc_mppu_is_product_blocked_for_guests', $is_blocked, $product_id, $args );
		} else {
			// By term
			$parent_product_id = $parent_product_id = $this->get_parent_product_id( $product_id );
			foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
				if ( 'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) ) {
					$terms = get_the_terms( $parent_product_id, $taxonomy );
					if ( $terms && ! is_wp_error( $terms ) ) {
						foreach ( $terms as $term ) {
							$is_blocked = 'yes' === get_term_meta( $term->term_id, '_wpjup_wc_mppu_block_guests', true );
							if ( $is_blocked ) {
								$this->product_blocked_for_guests[ $product_id ] = true;
								return apply_filters( 'alg_wc_mppu_is_product_blocked_for_guests', true, $product_id, $args );
							}
						}
					}
				}
			}
		}
		return $this->product_blocked_for_guests[ $product_id ] = apply_filters( 'alg_wc_mppu_is_product_blocked_for_guests', $is_blocked, $product_id, $args );
	}

	/**
	 * block_checkout.
	 *
	 * @version 3.6.1
	 * @since   1.0.0
	 */
	function block_checkout() {
		if (
			! function_exists( 'is_checkout' )
			|| ! is_checkout()
			|| WC()->cart->get_cart_contents_count() == 0
			|| isset( $_POST['woocommerce_checkout_place_order'] )
			|| defined( 'WOOCOMMERCE_CHECKOUT' )
		) {
			return;
		}
		if (
			! is_order_received_page()
			&& ! $this->check_quantities( array(
				'do_add_notices' => false
			) )
		) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
	}

	/**
	 * validate_on_add_to_cart.
	 *
	 * @version 3.6.2
	 * @since   2.0.0
	 * @todo    [later] `alg_wc_mppu_block_guests`: add "Block products with max qty" option (`yes_max_qty`), i.e. check for `0 != $this->get_max_qty_for_product( $_product_id, $product_id )`
	 * @todo    [maybe] different message (i.e. different from "cart" message)?
	 * @todo    [maybe] perform check only `$passed` is `true`?
	 */
	function validate_on_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0 ) {
		$cart_item_quantities = $this->get_cart_item_quantities();
		if ( 0 != $variation_id ) {
			$product_id = $variation_id;
		}
		$quantity = apply_filters( 'alg_wc_mppu_validate_on_add_to_cart_quantity', $quantity, $product_id, $cart_item_quantities );
		$adding   = $quantity;
		if (
			apply_filters( 'alg_wc_mppu_validate_on_add_to_cart_quantity_do_add', true ) &&
			! empty( $cart_item_quantities[ $product_id ] )
		) {
			$quantity += $cart_item_quantities[ $product_id ];
		}
		if ( $current_user_id = $this->get_current_user_id() ) {
			if ( ! $this->check_quantities_for_product( $product_id, array(
				'_cart_item_quantity'  => $quantity,
				'current_user_id'      => $current_user_id,
				'cart_item_quantities' => $cart_item_quantities,
				'adding'               => $adding,
				'notice_type'          => 'error'
			) ) ) {
				return false;
			}
		} elseif ( 'yes' === get_option( 'alg_wc_mppu_block_guests', 'no' ) ) {
			if ( $this->is_product_blocked_for_guests( $product_id ) ) {
				if ( ! wp_doing_ajax() ) {
					do_action( 'alg_wc_mppu_before_block_guest_on_add_to_cart' );
					$this->output_guest_notice( false );
				} else {
					add_filter( 'woocommerce_cart_redirect_after_error', array( $this, 'block_guest_add_to_cart_ajax_redirect' ), PHP_INT_MAX, 2 );
				}
				return false;
			}
		} elseif ( 'block_beyond_limit' == get_option( 'alg_wc_mppu_block_guests' ) ) {
			if ( ! $this->check_quantities_for_product( $product_id, array(
				'_cart_item_quantity'  => $quantity,
				'check_guest_blocking' => false,
				'current_user_id'      => $current_user_id,
				'cart_item_quantities' => $cart_item_quantities,
				'adding'               => $adding,
				'notice_type'          => 'error'
			) ) ) {
				return false;
			}
		}
		return $passed;
	}

	/**
	 * block_guest_add_to_cart_ajax_redirect.
	 *
	 * @version 3.5.5
	 * @since   3.1.1
	 */
	function block_guest_add_to_cart_ajax_redirect( $url, $product_id ) {
		$url = apply_filters( 'woocommerce_cart_redirect_after_block_guest_error', add_query_arg( 'alg_wc_mppu_guest', true, $url ), $product_id );
		return $url;
	}

	/**
	 * block_guest_add_to_cart_ajax_error.
	 *
	 * @version 3.1.1
	 * @since   3.1.1
	 */
	function block_guest_add_to_cart_ajax_error() {
		if ( isset( $_GET['alg_wc_mppu_guest'] ) ) {
			$this->output_guest_notice( false );
		}
	}

	/**
	 * output_guest_notice.
	 *
	 * @version 3.5.2
	 * @since   2.6.0
	 * @todo    [next] (fix) AJAX add to cart
	 * @todo    [later] customizable notice type
	 */
	function output_guest_notice( $is_cart ) {
		$default_msg = sprintf( '<a href="%s" class="button wc-forward">' . __( 'Login', 'woocommerce' ) . '</a>', esc_url( wc_get_page_permalink( 'myaccount' ) ) ) .' '.
		               __( 'You need to register to buy products.', 'maximum-products-per-user-for-woocommerce' );
		$message = get_option( 'alg_wc_mppu_block_guests_message', $default_msg );
		$message = do_shortcode( $message );
		if ( $is_cart ) {
			wc_print_notice( $message, 'notice' );
		} else {
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * check_cart_quantities.
	 *
	 * @version 3.6.1
	 * @since   1.0.0
	 */
	function check_cart_quantities() {
		$this->check_quantities( array(
			'do_add_notices' => true
		) );
	}

	/**
	 * get_current_time.
	 *
	 * @version 3.4.0
	 * @since   3.2.0
	 */
	function get_current_time() {
		if ( ! isset( $this->current_time ) ) {
			$this->current_time = ( 'time' === get_option( 'alg_wc_mppu_time_func', 'current_time' ) ? time() : ( int ) current_time( 'timestamp' ) );
		}
		return $this->current_time;
	}

	/**
	 * get_custom_date_range_in_seconds.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [maybe] add approximate values, i.e. 'months' (`MONTH_IN_SECONDS`), 'years' (`YEAR_IN_SECONDS`)?
	 */
	function get_custom_date_range_in_seconds() {
		$value = get_option( 'alg_wc_mppu_date_range_custom', 3600 );
		switch ( get_option( 'alg_wc_mppu_date_range_custom_unit', 'seconds' ) ) {
			case 'minutes':
				return $value * MINUTE_IN_SECONDS;
			case 'hours':
				return $value * HOUR_IN_SECONDS;
			case 'days':
				return $value * DAY_IN_SECONDS;
			case 'weeks':
				return $value * WEEK_IN_SECONDS;
			default: // 'seconds'
				return $value;
		}
	}

	/**
	 * get_date_to_check.
	 *
	 * @version 3.6.3
	 * @since   2.4.0
	 * @todo    [maybe] add `alg_wc_mppu_date_to_check_custom` filter
	 * @todo    [maybe] add more predefined ranges, e.g. `last_14_days`, `last_45_days`, `last_60_days`, `MINUTE_IN_SECONDS`
	 */
	function get_date_to_check( $date_range, $product_or_term_id = null, $current_user_id = null, $is_product = null ) {
		$date_to_check = 0;
		$current_time  = $this->get_current_time();
		switch ( $date_range ) {
			case 'lifetime':
				$date_to_check = 0;
				break;
			case 'this_hour':
				$date_to_check = strtotime( date( 'Y-m-d H:00:00' ), $current_time );
				break;
			case 'this_day':
				$date_to_check = strtotime( date( 'Y-m-d 00:00:00' ), $current_time );
				break;
			case 'this_week':
				$date_to_check = strtotime( 'monday this week', $current_time );
				break;
			case 'this_month':
				$date_to_check = strtotime( date( 'Y-m-01' ), $current_time );
				break;
			case 'this_year':
				$date_to_check = strtotime( date( 'Y-01-01' ), $current_time );
				break;
			case 'last_hour':
				$date_to_check = ( $current_time - HOUR_IN_SECONDS );
				break;
			case 'last_24_hours':
				$date_to_check = ( $current_time - DAY_IN_SECONDS );
				break;
			case 'last_7_days':
				$date_to_check = ( $current_time - WEEK_IN_SECONDS );
				break;
			case 'last_30_days':
				$date_to_check = ( $current_time - MONTH_IN_SECONDS );
				break;
			case 'last_365_days':
				$date_to_check = ( $current_time - YEAR_IN_SECONDS );
				break;
			case 'custom':
				$date_to_check = ( $current_time - $this->get_custom_date_range_in_seconds() );
				break;
			default:
				$date_to_check = false !== ( $date_time = DateTime::createFromFormat( 'Y-m-d', $date_range ) ) ? $date_time->getTimestamp() : $date_to_check;
				break;
		}
		return apply_filters( 'alg_wc_mppu_date_to_check', $date_to_check, $date_range, $current_time, $product_or_term_id, $current_user_id, $is_product );
	}

	/**
	 * check_order_date_range.
	 *
	 * @version 3.1.0
	 * @since   2.0.0
	 */
	function check_order_date_range( $order_date, $date_range, $product_or_term_id = null, $current_user_id = null, $is_product = null ) {
		$date_to_check = $this->get_date_to_check( $date_range, $product_or_term_id, $current_user_id, $is_product );
		return ( $order_date >= $date_to_check );
	}

	/**
	 * get_date_range.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 * @todo    [later] (feature) `alg_wc_mppu_date_range_per_product_or_term_id` ("Date range per product / term ID")
	 */
	function get_date_range( $product_or_term_id, $is_product ) {
		$date_range = get_option( 'alg_wc_mppu_date_range', 'lifetime' );
		/**
		 * Date range per product / term ID.
		 *
		 * if ( 'yes' === get_option( 'alg_wc_mppu_date_range_per_product_or_term_id', 'no' ) ) {
		 *     $date_range_per_product_or_term = $this->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_date_range' );
		 *     if ( ! empty( $date_range_per_product_or_term ) && 'default' != $date_range_per_product_or_term ) {
		 *         $date_range = $date_range_per_product_or_term;
		 *     }
		 * }
		 */
		return apply_filters( 'alg_wc_mppu_date_range', $date_range, $product_or_term_id, $is_product );
		/**
		 * For example: Custom date range for product ID 100.
		 *
		 * As of v2.1.0, possible date range values are:
		 * 'lifetime', 'this_hour', 'this_day', 'this_week', 'this_month', 'this_year', 'last_hour', 'last_24_hours', 'last_7_days', 'last_30_days', 'last_365_days'
		 *
		 * add_filter( 'alg_wc_mppu_date_range', 'my_custom_alg_wc_mppu_date_range', 10, 3 );
		 * function my_custom_alg_wc_mppu_date_range( $date_range, $product_or_term_id, $is_product ) {
		 *     return ( $is_product && 100 == $product_or_term_id ? 'lifetime' : $date_range );
		 * }
		 */
	}

	/**
	 * get_order_date.
	 *
	 * @version 3.4.0
	 * @since   3.2.3
	 */
	function get_order_date( $date_utc ) {
		return ( 'time' === get_option( 'alg_wc_mppu_time_func', 'current_time' ) ? $date_utc : strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $date_utc ), 'Y-m-d H:i:s' ) ) );
	}

	/**
	 * get_user_already_bought_qty.
	 *
	 * @version 3.5.0
	 * @since   2.0.0
	 * @todo    [next] completely remove separate `lifetime` calculation (i.e. `$this->do_get_lifetime_from_totals` always `false`)
	 * @todo    [maybe] add option to use e.g. order date completed (instead of `date_created`)
	 */
	function get_user_already_bought_qty( $product_or_term_id, $current_user_id, $is_product, $date_range = false ) {
		$product_or_term_id  = apply_filters( 'alg_wc_mppu_data_product_or_term_id', $product_or_term_id, $is_product );
		$user_already_bought = 0;
		$first_order_date    = false;
		$first_order_amount  = false;
		$date_range          = ( false === $date_range ? $this->get_date_range( $product_or_term_id, $is_product ) : $date_range );
		if ( $this->do_get_lifetime_from_totals && 'lifetime' === $date_range ) {
			$users_quantities = $this->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_totals_data' );
			if ( $users_quantities && isset( $users_quantities[ $current_user_id ] ) ) {
				$user_already_bought = $users_quantities[ $current_user_id ];
			}
		} else {
			$users_quantities = $this->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_orders_data' );
			if ( $users_quantities && isset( $users_quantities[ $current_user_id ] ) ) {
				foreach ( $users_quantities[ $current_user_id ] as $order_id => $order_data ) {
					$order_date = $this->get_order_date( $order_data['date_created'] );
					if (
						$this->check_order_date_range( $order_date, $date_range, $product_or_term_id, $current_user_id, $is_product ) &&
						apply_filters( 'alg_wc_mppu_user_already_bought_do_count_order', true, $order_id, $order_data )
					) {
						$user_already_bought += $order_data['qty'];
						if ( false === $first_order_date || $order_date < $first_order_date ) {
							$first_order_date   = $order_date;
							$first_order_amount = $order_data['qty'];
						}
					}
				}
			}
		}
		return apply_filters( 'alg_wc_mppu_user_already_bought', array(
			'bought'              => ( $user_already_bought ? $user_already_bought : 0 ),
			'first_order_date'    => $first_order_date,
			'first_order_amount'  => $first_order_amount,
			'date_range'          => $date_range,
		) );
	}

	/**
	 * get_date_format.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 */
	function get_date_format() {
		return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	/**
	 * get_first_order_date_exp.
	 *
	 * @version 3.5.7
	 * @since   3.2.0
	 * @todo    [maybe] customizable date/time format
	 * @todo    [maybe] alternative to `human_time_diff()`?
	 */
	function get_first_order_date_exp( $first_order_date, $date_range, $do_timeleft = false ) {
		$current_time = $this->get_current_time(); // needed for the `this_` part, and for the final filters
		if ( false !== $first_order_date ) {
			$offset = 0;
			if ( 'this_' === substr( $date_range, 0, 5 ) ) {
				switch ( $date_range ) {
					case 'this_hour':
						$offset = strtotime( date( 'Y-m-d H:00:00' ) . ' + 1 hour', $current_time );
						break;
					case 'this_day':
						$offset = strtotime( date( 'Y-m-d 00:00:00' ) . ' + 1 day', $current_time );
						break;
					case 'this_week':
						$offset = strtotime( 'monday this week' . ' + 1 week', $current_time );
						break;
					case 'this_month':
						$offset = strtotime( date( 'Y-m-01' ) . ' + 1 month', $current_time );
						break;
					case 'this_year':
						$offset = strtotime( date( 'Y-01-01' ) . ' + 1 year', $current_time );
						break;
				}
			} else { // 'last_', 'custom'
				switch ( $date_range ) {
					case 'last_hour':
						$offset = HOUR_IN_SECONDS;
						break;
					case 'last_24_hours':
						$offset = DAY_IN_SECONDS;
						break;
					case 'last_7_days':
						$offset = WEEK_IN_SECONDS;
						break;
					case 'last_30_days':
						$offset = MONTH_IN_SECONDS;
						break;
					case 'last_365_days':
						$offset = YEAR_IN_SECONDS;
						break;
					case 'custom':
						$offset = $this->get_custom_date_range_in_seconds();
						break;
				}
				$offset += $first_order_date;
			}
			return apply_filters( 'alg_wc_mppu_get_first_order_date_exp', ( $do_timeleft ? human_time_diff( $offset, $current_time ) : date_i18n( $this->get_date_format(), $offset ) ),
				$first_order_date, $date_range, $do_timeleft, $offset, $current_time );
		}
		return apply_filters( 'alg_wc_mppu_get_first_order_date_exp', '', $first_order_date, $date_range, $do_timeleft, 0, $current_time );
	}

	/**
	 * get_notice_placeholders.
	 *
	 * @version 3.5.0
	 * @since   3.1.1
	 * @todo    [next] `%product_title%`: `get_the_title( $product_id )`?
	 */
	function get_notice_placeholders( $product_id, $limit, $bought_data, $in_cart_plus_adding, $adding, $term = false ) {
		$product   = wc_get_product( $product_id );
		$remaining = ( $limit - $bought_data['bought'] );
		$in_cart   = ( $in_cart_plus_adding - $adding );
		$this->placeholders = apply_filters( 'alg_wc_mppu_get_notice_placeholders', array(
			'%limit%'                                => max( $limit, 0 ),
			'%max_qty%'                              => max( $limit, 0 ),            // deprecated
			'%bought%'                               => $bought_data['bought'],
			'%qty_already_bought%'                   => $bought_data['bought'],      // deprecated
			'%remaining%'                            => max( $remaining, 0 ),
			'%remaining_qty%'                        => max( $remaining, 0 ),        // deprecated
			'%in_cart%'                              => $in_cart,
			'%bought_plus_in_cart%'                  => ( $bought_data['bought'] + $in_cart ),
			'%remaining_minus_in_cart%'              => max( ( $remaining - $in_cart ), 0 ),
			'%adding%'                               => $adding,
			'%in_cart_plus_adding%'                  => $in_cart_plus_adding,
			'%bought_plus_in_cart_plus_adding%'      => ( $bought_data['bought'] + $in_cart_plus_adding ),
			'%remaining_minus_in_cart_minus_adding%' => max( ( $remaining - $in_cart_plus_adding ), 0 ),
			'%product_title%'                        => $product->get_name(),
			'%term_name%'                            => ( $term ? $term->name : '' ),
			'%first_order_date%'                     => ( false !== $bought_data['first_order_date'] ? date_i18n( $this->get_date_format(), $bought_data['first_order_date'] ) : '' ),
			'%first_order_amount%'                   => ( false !== $bought_data['first_order_amount'] ? $bought_data['first_order_amount'] : '' ),
			'%first_order_date_exp%'                 => $this->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'] ),
			'%first_order_date_exp_timeleft%'        => $this->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'], true ),
			'%payment_method_title%'                 => $this->get_chosen_payment_method_title(),
		), $product_id, $limit, $bought_data, $in_cart_plus_adding, $adding, $term );
	}

	/**
	 * apply_placeholders.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function apply_placeholders( $message ) {
		$result = do_shortcode( str_replace( array_keys( $this->placeholders ), $this->placeholders, $message ) );
		unset( $this->placeholders );
		return $result;
	}

	/**
	 * output_notice.
	 *
	 * @version 3.6.2
	 * @since   2.0.0
	 * @todo    [maybe] customizable notice type in `wc_add_notice()`?
	 */
	function output_notice( $args = null ) {
		$args                = wp_parse_args( $args, array(
			'product_id'          => '',
			'limit'               => '',
			'bought_data'         => '',
			'in_cart_plus_adding' => false,
			'adding'              => '',
			'return'              => false,
			'term'                => false,
			'msg'                 => false,
			'output_function'     => 'wc_add_notice', // wc_print_notice || wc_add_notice
			'notice_type'         => 'notice', // notice || error
		) );
		$args                = apply_filters( 'alg_wc_mppu_output_notices_args', $args );
		$product_id          = $args['product_id'];
		$limit               = (float) $args['limit'];
		$bought_data         = $args['bought_data'];
		$output_function     = $args['output_function'];
		$notice_type         = $args['notice_type'];
		$in_cart_plus_adding = $args['in_cart_plus_adding'];
		$adding              = $args['adding'];
		$term                = $args['term'];
		$msg                 = $args['msg'];
		$return              = $args['return'];
		$this->get_notice_placeholders( $product_id, $limit, $bought_data, $in_cart_plus_adding, $adding, $term );
		$message = ( $msg ? $msg : get_option( 'wpjup_wc_maximum_products_per_user_message',
			sprintf(
				__( '[alg_wc_mppu_customer_msg bought_msg="%s" not_bought_msg="%s"]' ),
				__( "You can only buy maximum %limit% of %product_title% (you've already bought %bought%).", 'maximum-products-per-user-for-woocommerce' ),
				__( "You can only buy maximum %limit% of %product_title%.", 'maximum-products-per-user-for-woocommerce' )
			) ) );
		$message = $this->apply_placeholders( do_shortcode( $message ) );
		if ( ! $return ) {
			if ( ! empty( $output_function ) ) {
				call_user_func_array( $output_function, array( $message, $notice_type ) );
			} else {
				echo $message;
			}
		} else {
			$this->error_messages[] = $message;
			return $message;
		}
	}

	/**
	 * get_cart_item_quantity_by_term.
	 *
	 * @version 3.5.0
	 * @since   2.0.0
	 */
	function get_cart_item_quantity_by_term( $current_product_id, $current_cart_item_quantity, $cart_item_quantities, $current_term_id, $taxonomy ) {
		$result = $current_cart_item_quantity;
		if ( ! empty( $cart_item_quantities ) ) {
			foreach ( $cart_item_quantities as $product_id => $product_qty ) {
				if ( $product_id != $current_product_id ) {
					if ( 0 != ( $parent_product_id = wp_get_post_parent_id( $product_id ) ) ) {
						$product_id = $parent_product_id;
					}
					$terms = get_the_terms( $product_id, $taxonomy );
					if ( $terms && ! is_wp_error( $terms ) ) {
						foreach( $terms as $term ) {
							if ( $term->term_id === $current_term_id ) {
								$result += $product_qty;
								break;
							}
						}
					}
				}
			}
		}
		return apply_filters( 'alg_wc_mppu_get_cart_item_amount_by_term', $result );
	}

	/**
	 * get_cart_item_quantity_by_parent.
	 *
	 * @version 3.5.0
	 * @since   3.3.0
	 */
	function get_cart_item_quantity_by_parent( $current_product_id, $current_cart_item_quantity, $cart_item_quantities, $parent_product_id ) {
		$result = $current_cart_item_quantity;
		if ( ! empty( $cart_item_quantities ) ) {
			foreach ( $cart_item_quantities as $product_id => $product_qty ) {
				if ( $product_id != $current_product_id && 0 != ( $_parent_product_id = wp_get_post_parent_id( $product_id ) ) && $_parent_product_id === $parent_product_id ) {
					$result += $product_qty;
				}
			}
		}
		return apply_filters( 'alg_wc_mppu_get_cart_item_amount_by_parent', $result );
	}

	/**
	 * get_max_qty_for_product.
	 *
	 * @version 3.6.0
	 * @since   2.5.0
	 * @todo    [next] use this inside the `check_quantities_for_product()` function
	 */
	function get_max_qty_for_product( $product_id, $parent_product_id = false ) {
		// Maybe exclude products
		$exclude_products = get_option( 'alg_wc_mppu_exclude_products', array() );
		if ( ! empty( $exclude_products ) && in_array( $product_id, $exclude_products ) ) {
			return 0;
		}
		// Per product
		if ( 'yes' === apply_filters( 'alg_wc_mppu_local_enabled', 'no' ) && 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'per_product', 'product_or_term_id' => $product_id ) ) ) ) {
			return $max_qty;
		}
		// Per taxonomy
		if ( ! $parent_product_id && ( 0 == ( $parent_product_id = wp_get_post_parent_id( $product_id ) ) ) ) {
			$parent_product_id = $product_id;
		}
		foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
			if ( 'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) ) {
				$_max_qty = array();
				$terms    = get_the_terms( $parent_product_id, $taxonomy );
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'per_term', 'product_or_term_id' => $term->term_id ) ) ) ) {
							$_max_qty[] = array( 'max_qty' => $max_qty, 'term_id' => $term->term_id, 'taxonomy' => $taxonomy );
						}
					}
				}
				if ( ! empty( $_max_qty ) ) {
					return $_max_qty;
				}
			}
		}
		// All products or Formula (all products)
		if (
			( 'yes' === get_option( 'wpjup_wc_maximum_products_per_user_global_enabled', 'no' ) && 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'all_products' ) ) ) ) ||
			( 'yes' === get_option( 'alg_wc_mppu_formula_enabled', 'no' ) && 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'formula' ) ) ) )
		) {
			return $max_qty;
		}
		// No max qty for the current product
		return 0;
	}

	/**
	 * do_use_variations.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    [next] no `$parent_product_id`? (for `get_products()`, i.e. for products list display)
	 * @todo    [maybe] add filter
	 */
	function do_use_variations( $parent_product_id ) {
		return ( 'yes' === ( 0 != $parent_product_id && '' != ( $per_product = get_post_meta( $parent_product_id, '_alg_wc_mppu_use_variations', true ) ) ?
			$per_product : get_option( 'alg_wc_mppu_use_variations', 'no' ) ) );
	}

	/**
	 * check_quantities_for_product.
	 *
	 * @version 3.6.1
	 * @since   2.0.0
	 * @todo    [maybe] add `alg_wc_mppu_check_quantities_for_product_product_id` filter?
	 */
	function check_quantities_for_product( $_product_id, $args ) {
		$args = wp_parse_args( $args, array(
			'_cart_item_quantity'               => '',
			'cart_item_quantities'              => array(),
			'output_function'                   => 'wc_add_notice', // wc_print_notice || wc_add_notice
			'do_add_notices'                    => true,
			'return_notices'                    => false,
			'notice_type'                       => 'notice', // notice || error
			'current_user_id'                   => 0,
			'check_guest_blocking'              => true,
			'adding'                            => 0,
			'get_product_id_from_main_language' => $this->multilanguage->get_product_id_from_main_language()
		) );
		// Variables from $args
		$_cart_item_quantity               = $args['_cart_item_quantity'];
		$cart_item_quantities              = $args['cart_item_quantities'];
		$output_function                   = $args['output_function'];
		$do_add_notices                    = $args['do_add_notices'];
		$current_user_id                   = $args['current_user_id'];
		$adding                            = $args['adding'];
		$get_product_id_from_main_language = $args['get_product_id_from_main_language'];
		$return_notices                    = $args['return_notices'];
		$notice_type                       = $args['notice_type'];
		// Dynamic values
		$parent_product_id  = $this->get_parent_product_id( wc_get_product( $_product_id ) );
		$use_parent         = ( $parent_product_id != $_product_id && ! $this->do_use_variations( $parent_product_id ) );
		$product_id         = ( ! $use_parent ? $_product_id : $parent_product_id );
		$cart_item_quantity = ( ! $use_parent ? $_cart_item_quantity : $this->get_cart_item_quantity_by_parent( $_product_id, $_cart_item_quantity, $cart_item_quantities, $parent_product_id ) );
		$cart_item_quantity = apply_filters( 'alg_wc_mppu_cart_item_amount', $cart_item_quantity );
		if ( $get_product_id_from_main_language ) {
			$product_id = apply_filters( 'alg_wc_mppu_data_product_or_term_id', $product_id, true );
		}
		$args               = array_merge( $args, array(
			'parent_product_id'  => $parent_product_id,    // can be the same as product ID (for non-variable products)
			'product_id'         => $product_id,           // product or (maybe) parent ID
			'cart_item_quantity' => $cart_item_quantity,   // product or (maybe) parent cart qty
		) );
		// Maybe exclude products
		$exclude_products = get_option( 'alg_wc_mppu_exclude_products', array() );
		if ( ! empty( $exclude_products ) && in_array( $product_id, $exclude_products ) ) {
			return apply_filters( 'alg_wc_mppu_check_quantities_for_product', true, $this, $args );
		}
		// Block guest by limit
		if (
			$args['check_guest_blocking']
			&& empty( $current_user_id )
			&& $this->is_product_blocked_for_guests( $product_id )
		) {
			return apply_filters( 'alg_wc_mppu_check_quantities_for_product', false, $this, $args );
		}
		// Block - default mechanism
		if ( 'yes' === apply_filters( 'alg_wc_mppu_local_enabled', 'no' ) && 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'per_product', 'product_or_term_id' => $product_id, 'user_id' => $current_user_id ) ) ) ) {
			// Per product
			$bought_data         = $this->get_user_already_bought_qty( $product_id, $current_user_id, true );
			$user_already_bought = $bought_data['bought'];
			if ( ( $user_already_bought + $cart_item_quantity ) > $max_qty ) {
				if ( $do_add_notices ) {
					$this->output_notice( array(
						'product_id'          => $product_id,
						'limit'               => $max_qty,
						'bought_data'         => $bought_data,
						'output_function'     => $output_function,
						'in_cart_plus_adding' => $cart_item_quantity,
						'adding'              => $adding,
						'notice_type'         => $notice_type,
						'return'              => $return_notices
					) );
				}
				return apply_filters( 'alg_wc_mppu_check_quantities_for_product', false, $this, $args );
			}
		} else {
			// Per taxonomy
			$validated_by_term = false;
			foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
				if ( ! $validated_by_term && 'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) ) {
					$terms = get_the_terms( $parent_product_id, $taxonomy );
					if ( $terms && ! is_wp_error( $terms ) ) {
						foreach ( $terms as $term ) {
							if ( 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'per_term', 'product_or_term_id' => $term->term_id, 'user_id' => $current_user_id ) ) ) ) {
								$validated_by_term      = true;
								$cart_item_quantity_all = $this->get_cart_item_quantity_by_term( $_product_id, $_cart_item_quantity, $cart_item_quantities, $term->term_id, $taxonomy );
								$bought_data            = $this->get_user_already_bought_qty( $term->term_id, $current_user_id, false );
								$user_already_bought    = $bought_data['bought'];
								if ( ( $user_already_bought + $cart_item_quantity_all ) > $max_qty ) {
									if ( $do_add_notices ) {
										$this->output_notice( array(
											'product_id'          => $product_id,
											'limit'               => $max_qty,
											'bought_data'         => $bought_data,
											'notice_type'         => $notice_type,
											'output_function'     => $output_function,
											'in_cart_plus_adding' => $cart_item_quantity_all,
											'adding'              => $adding,
											'term'                => $term,
											'return'              => $return_notices
										) );
									}
									return apply_filters( 'alg_wc_mppu_check_quantities_for_product', false, $this, $args );
								}
							}
						}
					}
				}
			}
			if (
				! $validated_by_term &&
				(
					( 'yes' === get_option( 'wpjup_wc_maximum_products_per_user_global_enabled', 'no' ) && 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'all_products', 'user_id' => $current_user_id ) ) ) ) ||
					( 'yes' === get_option( 'alg_wc_mppu_formula_enabled', 'no' ) && 0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'formula', 'user_id' => $current_user_id ) ) ) )
				)
			) {
				$bought_data         = $this->get_user_already_bought_qty( $product_id, $current_user_id, true );
				$user_already_bought = $bought_data['bought'];
				if ( ( $user_already_bought + $cart_item_quantity ) > $max_qty ) {
					if ( $do_add_notices ) {
						$this->output_notice( array(
							'product_id'          => $product_id,
							'limit'               => $max_qty,
							'bought_data'         => $bought_data,
							'notice_type'         => $notice_type,
							'output_function'     => $output_function,
							'in_cart_plus_adding' => $cart_item_quantity,
							'adding'              => $adding,
							'return'              => $return_notices
						) );
					}
					return apply_filters( 'alg_wc_mppu_check_quantities_for_product', false, $this, $args );
				}
			}
		}
		return apply_filters( 'alg_wc_mppu_check_quantities_for_product', true, $this, $args );
	}

	/**
	 * check_quantities.
	 *
	 * @version 3.6.1
	 * @since   1.0.0
	 *
	 * @param null $args
	 *
	 * @return bool
	 */
	function check_quantities( $args = null ) {
		$args = wp_parse_args( $args, array(
			'do_add_notices' => true,
			'return_notices' => false,
		) );
		$do_add_notices = $args['do_add_notices'];
		$return_notices = $args['return_notices'];
		if ( ! isset( WC()->cart ) ) {
			return true;
		}
		$is_cart = ( function_exists( 'is_cart' ) && is_cart() );
		$output_function = 'wc_add_notice';
		if ( $is_cart ) {
			$output_function = 'yes' === $this->cart_notice ? 'wc_print_notice' : '';
		}
		$is_checkout = ( function_exists( 'is_checkout' ) && is_checkout() );
		$notice_type = $is_checkout ? 'error' : 'notice';
		if ( ! ( $current_user_id = $this->get_current_user_id() ) ) {
			if (
				'yes' === ( $block_guests_opt = get_option( 'alg_wc_mppu_block_guests', 'no' ) )
				&& 'all_products' === get_option( 'alg_wc_mppu_block_guests_method', 'all_products' )
			) {
				if ( $do_add_notices ) {
					$this->output_guest_notice( $is_cart );
				}
				return false;
			} elseif ( 'block_beyond_limit' !== $block_guests_opt ) {
				return true;
			}
		}
		if ( ( $gateways = get_option( 'alg_wc_mppu_payment_gateways', array() ) ) && ! empty( $gateways ) ) {
			if ( ( $chosen_payment_method = $this->get_chosen_payment_method() ) && ! in_array( $chosen_payment_method, $gateways ) ) {
				return true;
			}
		}
		$cart_item_quantities = $this->get_cart_item_quantities();
		if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
			return true;
		}
		$result = true;
		foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
			if ( ! $this->check_quantities_for_product( $_product_id, array(
				'_cart_item_quantity'  => $cart_item_quantity,
				'notice_type'         => $notice_type,
				'output_function'     => $output_function,
				'do_add_notices'       => $do_add_notices,
				'return_notices'       => $return_notices,
				'current_user_id'      => $current_user_id,
				'cart_item_quantities' => $cart_item_quantities
			) ) ) {
				if ( $do_add_notices && 'yes' === get_option( 'alg_wc_mppu_multiple_notices', 'yes' ) ) {
					$result = false;
				} else {
					return false;
				}
			}
		}
		return $result;
	}

	/**
	 * get_chosen_payment_method.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function get_chosen_payment_method() {
		if ( ! empty( $_REQUEST['payment_method'] ) ) {
			return $_REQUEST['payment_method'];
		}
		if ( isset( WC()->session->chosen_payment_method ) ) {
			return WC()->session->chosen_payment_method;
		}
		return false;
	}

	/**
	 * get_chosen_payment_method_title.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function get_chosen_payment_method_title() {
		if ( $chosen_payment_method = $this->get_chosen_payment_method() ) {
			$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( ! empty( $available_payment_gateways[ $chosen_payment_method ] ) ) {
				$payment_gateway = $available_payment_gateways[ $chosen_payment_method ];
				return $payment_gateway->get_title();
			}
		}
		return '';
	}

	/**
	 * Gets parent product id based on `$this->do_use_variations()`.
	 *
	 * If it's a variation and `false === $this->do_use_variations()`, gets the parent id.
	 * In all other circumstances, gets id from current product, regardless if it's a variation or not.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 *
	 * @param $_product
	 *
	 * @return int
	 */
	function get_parent_or_product_id( $_product ) {
		if ( is_numeric( $_product ) ) {
			$_product = wc_get_product( $_product );
		}
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		$final_product_id = $_product->get_id();
		if (
			! $this->do_use_variations( ( $parent_id = $this->get_parent_product_id( $_product ) ) )
			&& $_product->is_type( 'variation' )
		) {
			$final_product_id = $_product->get_parent_id();
		}
		return $final_product_id;
	}

	/**
	 * Gets parent product id from a variation or the current product id from a not variation product.
	 *
	 * @version 3.5.5
	 * @since   1.0.0
	 */
	function get_parent_product_id( $_product ) {
		if ( is_numeric( $_product ) ) {
			$_product = wc_get_product( $_product );
		}
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( $this->is_wc_version_below_3_0_0 ) {
			return $_product->id;
		} else {
			return ( $_product->is_type( 'variation' ) ) ? $_product->get_parent_id() : $_product->get_id();
		}
	}

	/**
	 * get_product_id.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_product_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( $this->is_wc_version_below_3_0_0 ) {
			return ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
		} else {
			return $_product->get_id();
		}
	}

	/**
	 * get_user_roles.
	 *
	 * @version 3.5.0
	 * @since   2.2.0
	 * @todo    [maybe] `$role = ( 'super_admin' == $role ? 'administrator' : $role );`
	 */
	function get_user_roles( $is_enabled_roles_only = true ) {
		global $wp_roles;
		$all_roles = apply_filters( 'editable_roles', ( isset( $wp_roles ) && is_object( $wp_roles ) ? $wp_roles->roles : array() ) );
		$roles     = ( ! empty( $all_roles ) ? wp_list_pluck( $all_roles, 'name' ) : array() );
		if ( $is_enabled_roles_only ) {
			$enabled_roles = get_option( 'alg_wc_mppu_enabled_user_roles', array() );
			if ( ! empty( $enabled_roles ) ) {
				$roles = array_intersect_key( $roles, array_flip( $enabled_roles ) );
			}
		}
		return $roles;
	}

	/**
	 * is_user_role_enabled.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function is_user_role_enabled( $role ) {
		if ( ! isset( $this->enabled_user_roles ) ) {
			$this->enabled_user_roles = get_option( 'alg_wc_mppu_enabled_user_roles', array() );
		}
		return ( empty( $this->enabled_user_roles ) || in_array( $role, $this->enabled_user_roles ) );
	}

	/**
	 * get_post_or_term_meta.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function get_post_or_term_meta( $product_or_term, $product_or_term_id, $key, $single = true ) {
		$func = ( 'product' === $product_or_term ? 'get_post_meta' : 'get_term_meta' );
		return $func( $product_or_term_id, $key, $single );
	}

	/**
	 * update_post_or_term_meta.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function update_post_or_term_meta( $product_or_term, $product_or_term_id, $key, $value ) {
		$func = ( 'product' === $product_or_term ? 'update_post_meta' : 'update_term_meta' );
		return $func( $product_or_term_id, $key, $value );
	}

	/**
	 * get_error_messages.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 *
	 * @return array
	 */
	public function get_error_messages() {
		return $this->error_messages;
	}

}

endif;

return new Alg_WC_MPPU_Core();
