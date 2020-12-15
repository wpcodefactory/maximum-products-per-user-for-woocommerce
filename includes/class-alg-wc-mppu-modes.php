<?php
/**
 * Maximum Products per User for WooCommerce - Modes
 *
 * @version 3.5.0
 * @since   3.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Modes' ) ) :

class Alg_WC_MPPU_Modes {

	/**
	 * Constructor.
	 *
	 * @version 3.5.0
	 * @since   3.0.0
	 * @todo    [next] price: do we really need to `round()`? maybe make it optional at least (enabled bu default)?
	 */
	function __construct() {
		if ( 'qty' != ( $this->mode = get_option( 'alg_wc_mppu_mode', 'qty' ) ) ) {
			add_filter( 'alg_wc_mppu_get_cart_item_quantities',         array( $this, 'get_cart_item_quantities_by_mode' ) );
			add_filter( 'alg_wc_mppu_validate_on_add_to_cart_quantity', array( $this, 'validate_on_add_to_cart_quantity_by_mode' ), 10, 2 );
			add_filter( 'alg_wc_mppu_save_quantities_item_qty',         array( $this, 'save_quantities_item_qty_by_mode' ), 10, 2 );
			if ( 'orders' === $this->mode ) {
				add_filter( 'alg_wc_mppu_get_cart_item_amount_by_term',   array( $this, 'cart_item_amount' ) );
				add_filter( 'alg_wc_mppu_get_cart_item_amount_by_parent', array( $this, 'cart_item_amount' ) );
				add_filter( 'alg_wc_mppu_cart_item_amount',               array( $this, 'cart_item_amount' ) );
			}
		}
	}

	/**
	 * cart_item_amount.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function cart_item_amount( $cart_item_amount ) {
		return max( 1, $cart_item_amount );
	}

	/**
	 * save_quantities_item_qty_by_mode.
	 *
	 * @version 3.5.0
	 * @since   3.0.0
	 */
	function save_quantities_item_qty_by_mode( $item_qty, $item ) {
		switch ( $this->mode ) {
			case 'price':
				return round( ( $item->get_total() + $item->get_total_tax() ), wc_get_price_decimals() );
			case 'price_excl_tax':
				return round( $item->get_total(), wc_get_price_decimals() );
			case 'weight':
				$product_id = ( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
				$product    = wc_get_product( $product_id );
				$weight     = ( $product && ( $weight = $product->get_weight() ) ? $weight : 0 );
				return $item['quantity'] * $weight;
			case 'volume':
				$product_id = ( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
				$product    = wc_get_product( $product_id );
				$volume     = ( $product && ( $length = $product->get_length() ) && ( $width = $product->get_width() ) && ( $height = $product->get_height() ) ? $length * $width * $height : 0 );
				return $item['quantity'] * $volume;
			case 'orders':
				return 1;
		}
	}

	/**
	 * validate_on_add_to_cart_quantity_by_mode.
	 *
	 * @version 3.5.0
	 * @since   3.0.0
	 */
	function validate_on_add_to_cart_quantity_by_mode( $quantity, $product_id ) {
		$product = wc_get_product( $product_id );
		switch ( $this->mode ) {
			case 'price':
				return ( $product && ( $price = wc_get_price_including_tax( $product, array( 'qty' => $quantity ) ) ) ? $price  : 0 );
			case 'price_excl_tax':
				return ( $product && ( $price = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) ) ) ? $price  : 0 );
			case 'weight':
				$weight = ( $product && ( $weight = $product->get_weight() ) ? $weight : 0 );
				return ( $quantity * $weight );
			case 'volume':
				$volume = ( $product && ( $length = $product->get_length() ) && ( $width = $product->get_width() ) && ( $height = $product->get_height() ) ? $length * $width * $height : 0 );
				return ( $quantity * $volume );
			case 'orders':
				return 1;
		}
	}

	/**
	 * get_cart_item_quantities_by_mode.
	 *
	 * @version 3.5.0
	 * @since   3.0.0
	 */
	function get_cart_item_quantities_by_mode( $cart_item_quantities ) {
		$cart_item_res = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$product_id = ( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
			switch ( $this->mode ) {
				case 'price':
					$cart_item_res[ $product_id ] = round( ( $item['line_total'] + $item['line_tax'] ), wc_get_price_decimals() );
					break;
				case 'price_excl_tax':
					$cart_item_res[ $product_id ] = round( $item['line_total'], wc_get_price_decimals() );
					break;
				case 'weight':
					$product = wc_get_product( $product_id );
					$weight  = ( $product && ( $weight = $product->get_weight() ) ? $weight : 0 );
					$cart_item_res[ $product_id ] = $item['quantity'] * $weight;
					break;
				case 'volume':
					$product = wc_get_product( $product_id );
					$volume = ( $product && ( $length = $product->get_length() ) && ( $width = $product->get_width() ) && ( $height = $product->get_height() ) ? $length * $width * $height : 0 );
					$cart_item_res[ $product_id ] = $item['quantity'] * $volume;
					break;
				case 'orders':
					$cart_item_res[ $product_id ] = 1;
					break;
			}
		}
		return $cart_item_res;
	}

}

endif;

return new Alg_WC_MPPU_Modes();
