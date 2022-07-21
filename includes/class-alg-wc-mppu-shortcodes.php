<?php
/**
 * Maximum Products per User for WooCommerce - Shortcodes.
 *
 * @version 3.6.8
 * @since   2.5.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Shortcodes' ) ) :

class Alg_WC_MPPU_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 3.6.8
	 * @since   2.5.0
	 */
	function __construct() {
		add_shortcode( 'alg_wc_mppu_translate', array( $this, 'language_shortcode' ) );
		add_shortcode( 'alg_wc_mppu_user_product_quantities', array( $this, 'user_product_limits_shortcode' ) );   // deprecated
		add_shortcode( 'alg_wc_mppu_current_product_limit', array( $this, 'current_product_limit_shortcode' ) );
		add_shortcode( 'alg_wc_mppu_current_product_quantity', array( $this, 'current_product_limit_shortcode' ) ); // deprecated
		add_shortcode( 'alg_wc_mppu_term_limit', array( $this, 'term_limit_shortcode' ) );
		add_shortcode( 'alg_wc_mppu_placeholder', array( $this, 'placeholder' ) );
		add_shortcode( 'alg_wc_mppu_customer_msg', array( $this, 'customer_msg_shortcode' ) );
		// User product limits.
		add_shortcode( 'alg_wc_mppu_user_product_limits', array( $this, 'user_product_limits_shortcode' ) );
		add_filter( 'alg_wc_mppu_user_product_limits_item_validation', array( $this, 'hide_unbought_user_product_limits_table_items' ), 10, 2 );
		// User terms limits.
		add_shortcode( 'alg_wc_mppu_user_terms_limits', array( $this, 'user_terms_limits_shortcode' ) );
		add_filter('alg_wc_mppu_user_terms_limits_item_validation', array( $this, 'hide_unbought_user_terms_limits_table_items' ), 10, 2 );
	}

	/**
	 * customer_msg_shortcode.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	function customer_msg_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'bought_msg'     => __( 'You can only buy maximum %limit% of %product_title% (you\'ve already bought %bought%).', 'maximum-products-per-user-for-woocommerce' ),
			'not_bought_msg' => __( 'You can only buy maximum %limit% of %product_title%.', 'maximum-products-per-user-for-woocommerce' ),
			'bought'         => null,
			'bought_msg_min' => 1
		), $atts, 'alg_wc_mppu_customer_msg' );
		if ( 0 === $atts['bought'] ) {
			return $atts['not_bought_msg'];
		} elseif ( $atts['bought'] >= $atts['bought_msg_min'] ) {
			return $atts['bought_msg'];
		}
		return '';
	}

	/**
	 * get_user_id.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function get_user_id( $atts ) {
		return ( isset( $atts['user_id'] ) ? $atts['user_id'] : alg_wc_mppu()->core->get_current_user_id() );
	}

	/**
	 * placeholder.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 * @todo    [maybe] add `$atts['on_zero']`?
	 */
	function placeholder( $atts, $content = '' ) {
		if ( ! isset( $atts['key'], alg_wc_mppu()->core->placeholders, alg_wc_mppu()->core->placeholders[ '%' . $atts['key'] . '%' ] ) ) {
			return '';
		}
		return ( '' == ( $value = alg_wc_mppu()->core->placeholders[ '%' . $atts['key'] . '%' ] ) ?
			( isset( $atts['on_empty'] ) ? $atts['on_empty'] : '' ) :
			( isset( $atts['before'] ) ? $atts['before'] : '' ) . $value . ( isset( $atts['after'] ) ? $atts['after'] : '' ) );
	}

	/**
	 * term_limit_shortcode.
	 *
	 * @version 3.6.0
	 * @since   3.1.0
	 * @todo    [next] `alg_wc_mppu()->core->get_notice_placeholders()`
	 * @todo    [later] different (customizable) message depending on `$remaining`
	 */
	function term_limit_shortcode( $atts, $content = '' ) {
		if (
			! empty( $atts['taxonomy'] ) && ( 'yes' === apply_filters( 'alg_wc_mppu_' . $atts['taxonomy'] . '_enabled', 'no' ) ) &&
			( ! empty( $atts['term_id'] ) || ! empty( $atts['term_slug'] ) )
		) {
			$term = ( ! empty( $atts['term_id'] ) ? get_term_by( 'id', $atts['term_id'], $atts['taxonomy'] ) : get_term_by( 'slug', $atts['term_slug'], $atts['taxonomy'] ) );
			if ( $term ) {
				$user_id = $this->get_user_id( $atts );
				if ( $user_id ) {
					if ( 0 != ( $max_qty = alg_wc_mppu()->core->get_max_qty( array( 'type' => 'per_term', 'product_or_term_id' => $term->term_id ) ) ) ) {
						$bought_data  = alg_wc_mppu()->core->get_user_already_bought_qty( $term->term_id, $user_id, false );
						$bought       = $bought_data['bought'];
						$remaining    = $max_qty - $bought;
						alg_wc_mppu()->core->placeholders = array(
							'%limit%'                           => $max_qty,
							'%bought%'                          => $bought,
							'%remaining%'                       => ( $remaining > 0 ? $remaining : 0 ),
							'%term_name%'                       => $term->name,
							'%first_order_date%'                => ( false !== $bought_data['first_order_date'] ? date_i18n( alg_wc_mppu()->core->get_date_format(), $bought_data['first_order_date'] ) : '' ),
							'%first_order_amount%'              => ( false !== $bought_data['first_order_amount'] ? $bought_data['first_order_amount'] : '' ),
							'%first_order_date_exp%'            => alg_wc_mppu()->core->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'] ),
							'%first_order_date_exp_timeleft%'   => alg_wc_mppu()->core->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'], true ),
						);
						$template = ( isset( $atts['template'] ) ? $atts['template'] :
							__( "The remaining amount is %remaining% (you've already bought %bought% out of %limit%).", 'maximum-products-per-user-for-woocommerce' ) );
						$message = alg_wc_mppu()->core->apply_placeholders( $template );
						return $message;
					}
				}
			}
		}
		return '';
	}

	/**
	 * current_product_limit_shortcode.
	 *
	 * @version 3.6.7
	 * @since   2.5.1
	 * @todo    [later] different (customizable) message depending on `$remaining`
	 */
	function current_product_limit_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'product_id'                 => get_the_ID(),
			'msg_template'               => get_option( 'alg_wc_mppu_permanent_notice_message', __( "The remaining amount for %product_title% is %remaining% (you've already bought %bought% out of %limit%).", 'maximum-products-per-user-for-woocommerce' ) ),
			'condition'                  => get_option( 'alg_wc_mppu_permanent_notice_condition', '' ),
			'output_template'            => '<span class="alg-wc-mppu-current-product-limit">{msg_template}</span>',
			'empty_msg_removes_template' => false
		), $atts, 'alg_wc_mppu_current_product_limit' );
		$product_id = $atts['product_id'];
		if ( ! is_a( wc_get_product( $product_id ), 'WC_Product' ) ) {
			return '';
		}
		$user_id    = $this->get_user_id( $atts );
		$output_msg='';
		$placeholders=array();
		if ( $product_id && $user_id ) {
			$limit = alg_wc_mppu()->core->get_max_qty_for_product( $product_id );
			if ( $limit ) {
				// Cart item quantity
				$cart_item_quantity   = 0;
				$cart_item_quantities = alg_wc_mppu()->core->get_cart_item_quantities();
				$is_cart_empty        = ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) );
				$_cart_item_quantity  = ( ! $is_cart_empty && isset( $cart_item_quantities[ $product_id ] ) ? $cart_item_quantities[ $product_id ] : 0 );
				// Placeholders
				if ( is_array( $limit ) ) {
					// Terms (returning lowest remaining)
					$_remaining   = PHP_INT_MAX;
					$_limit_data  = false;
					$_bought_data = false;
					foreach ( $limit as $limit_data ) {
						$bought_data = alg_wc_mppu()->core->get_user_already_bought_qty( $limit_data['term_id'], $user_id, false );
						$remaining   = $limit_data['max_qty'] - $bought_data['bought'];
						if ( $remaining < $_remaining ) {
							$_remaining   = $remaining;
							$_limit_data  = $limit_data;
							$_bought_data = $bought_data;
						}
					}
					if ( $_limit_data && $_bought_data ) {
						if ( ! $is_cart_empty ) {
							// Cart item quantity
							$cart_item_quantity = alg_wc_mppu()->core->get_cart_item_quantity_by_term( $product_id,
								$_cart_item_quantity, $cart_item_quantities, $_limit_data['term_id'], $_limit_data['taxonomy'] );
						}
						$term = get_term_by( 'id', $_limit_data['term_id'], $_limit_data['taxonomy'] );
						$placeholders = alg_wc_mppu()->core->get_notice_placeholders( $product_id, $_limit_data['max_qty'], $_bought_data, $cart_item_quantity, 0, $term );
					}
				} else {
					// Products
					if ( ! $is_cart_empty ) {
						// Cart item quantity
						$parent_product_id  = alg_wc_mppu()->core->get_parent_product_id( wc_get_product( $product_id ) );
						$use_parent         = ( $parent_product_id != $product_id && ! alg_wc_mppu()->core->do_use_variations( $parent_product_id ) );
						$cart_item_quantity = ( ! $use_parent ? $_cart_item_quantity : alg_wc_mppu()->core->get_cart_item_quantity_by_parent( $product_id,
							$_cart_item_quantity, $cart_item_quantities, $parent_product_id ) );
					}
					$bought_data = alg_wc_mppu()->core->get_user_already_bought_qty( $product_id, $user_id, true );
					$placeholders = alg_wc_mppu()->core->get_notice_placeholders( $product_id, $limit, $bought_data, $cart_item_quantity, 0, false );
				}
				// Final message
				$template = $atts['msg_template'];
				$message = alg_wc_mppu()->core->apply_placeholders( $template );
				$output_msg = $message;
			}
		}
		// Hide message if condition is wrong.
		if (
			! empty( $atts['condition'] ) &&
			! empty( $placeholders ) &&
			! empty( $condition = str_replace( array_keys( $placeholders ), $placeholders, $atts['condition'] ) ) &&
			is_a( $e = new \optimistex\expression\MathExpression(), 'optimistex\expression\MathExpression' ) &&
			false === filter_var( $e->evaluate( $condition ), FILTER_VALIDATE_BOOLEAN )
		) {
			$output_msg = false;
		}
		// Return message.
		if ( empty( $output_msg ) && $atts['empty_msg_removes_template'] ) {
			return $output_msg;
		} else {
			return str_replace( '{msg_template}', $output_msg, $atts['output_template'] );
		}
	}

	/**
	 * user_product_limits_shortcode.
	 *
	 * @version 3.6.8
	 * @since   2.5.0
	 * @todo    [later] customizable content: use `alg_wc_mppu()->core->get_notice_placeholders()`
	 * @todo    [later] customizable: columns, column order, column titles, table styling, "No data" text, (maybe) sorting
	 * @todo    [maybe] add `core::get_products()` function?
	 */
	function user_product_limits_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'user_id'             => alg_wc_mppu()->core->get_current_user_id(),
			'hide_products_by_id' => '',
			'bought_value'        => 'smart', // per_product | smart
			'show_unbought'       => 'true'
		), $atts, 'alg_wc_mppu_user_product_limits' );
		$bought_value = $atts['bought_value'];
		// Get user ID
		$user_id = $this->get_user_id( $atts );
		if ( ! $user_id ) {
			return;
		}
		// Products
		$output     = '';
		$block_size = 1024;
		$offset     = 0;
		$_output    = '';
		while ( true ) {
			$args = array(
				'post_type'      => ( 'yes' === get_option( 'alg_wc_mppu_use_variations', 'no' ) ? array( 'product', 'product_variation' ) : 'product' ),
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'post__not_in'   => isset( $atts['hide_products_by_id'] ) && ! empty( $hidden_products_ids_str = $atts['hide_products_by_id'] ) ? array_map( 'trim', explode( ",", $hidden_products_ids_str ) ) : '',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				$max_qty = alg_wc_mppu()->core->get_max_qty_for_product( $product_id );
				if ( $max_qty ) {
					$bought_data = false;
					if ( is_array( $max_qty ) && 'smart' === $bought_value ) {
						// Terms
						$_remaining = PHP_INT_MAX;
						foreach ( $max_qty as $_max_qty ) {
							$bought_data         = alg_wc_mppu()->core->get_user_already_bought_qty( $_max_qty['term_id'], $user_id, false );
							$user_already_bought = $bought_data['bought'];
							$remaining           = $_max_qty['max_qty'] - $user_already_bought;
							if ( $remaining < $_remaining ) {
								$_remaining = $remaining;
								$_output    = sprintf( '<td>%s</td><td>%s</td><td>%s</td>', max( $remaining, 0 ), $user_already_bought, max( $_max_qty['max_qty'], 0 ) );
							}
						}
					} elseif ( 'per_product' === $bought_value ) {
						// Products
						$bought_data         = alg_wc_mppu()->core->get_user_already_bought_qty( $product_id, $user_id, true );
						$user_already_bought = $bought_data['bought'];
						$max_qty             = is_array( $max_qty ) ? min( wp_list_pluck( $max_qty, 'max_qty' ) ) : $max_qty;
						$remaining           = $max_qty - $user_already_bought;
						$_output             = sprintf( '<td>%s</td><td>%s</td><td>%s</td>', max( $remaining, 0 ), $user_already_bought, max( $max_qty, 0 ) );
					}
					if ( apply_filters( 'alg_wc_mppu_user_product_limits_item_validation', true, array(
						'sc_atts'     => $atts,
						'product_id'  => $product_id,
						'user_id'     => $user_id,
						'bought_data' => $bought_data,
						'max_qty'     => $max_qty
					) ) ) {
						$output .= '<tr><td>' . '<a href="' . get_the_permalink( $product_id ) . '">' . get_the_title( $product_id ) . '</a>' . '</td>' . $_output . '</tr>';
					}
				}
			}
			$offset += $block_size;
		}
		if ( ! empty( $output ) ) {
			return '<table class="alg_wc_mppu_products_data_my_account">' .
				'<tr>' .
					'<th>' . __( 'Product', 'maximum-products-per-user-for-woocommerce' )   . '</th>' .
					'<th>' . __( 'Remaining', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
					'<th>' . __( 'Bought', 'maximum-products-per-user-for-woocommerce' )    . '</th>' .
					'<th>' . __( 'Max', 'maximum-products-per-user-for-woocommerce' )       . '</th>' .
				'</tr>' .
				$output .
			'</table>';
		} else {
			return __( 'No data', 'maximum-products-per-user-for-woocommerce' );
		}
	}

	/**
	 * hide_user_product_limits_table_row.
	 *
	 * @version 3.6.8
	 * @since   3.6.8
	 *
	 * @param $show
	 * @param $args
	 *
	 * @return bool
	 */
	function hide_unbought_user_product_limits_table_items( $show, $args ) {
		if (
			false === filter_var( $args['sc_atts']['show_unbought'], FILTER_VALIDATE_BOOLEAN ) &&
			( $user_id = $args['user_id'] ) &&
			( $product_id = $args['product_id'] )
		) {
			$bought_data         = alg_wc_mppu()->core->get_user_already_bought_qty( $product_id, $user_id, true );
			$user_already_bought = $bought_data['bought'];
			$show                = $user_already_bought > 0;
		}
		return $show;
	}

	/**
	 * hide_unbought_user_terms_limits_table_items.
	 *
	 * @version 3.6.8
	 * @since   3.6.8
	 *
	 * @param $show
	 * @param $args
	 *
	 * @return bool
	 */
	function hide_unbought_user_terms_limits_table_items( $show, $args ){
		if (
			false === filter_var( $args['sc_atts']['show_unbought'], FILTER_VALIDATE_BOOLEAN ) &&
			( $bought_data = $args['bought_data'] )
		) {
			$user_already_bought = $bought_data['bought'];
			$show                = $user_already_bought > 0;
		}
		return $show;
	}

	/**
	 * user_terms_limits_shortcode.
	 *
	 * @version 3.6.8
	 * @since   3.5.7
	 *
	 * @param $atts
	 * @param string $content
	 *
	 * @return string|void
	 */
	function user_terms_limits_shortcode( $atts, $content = '' ) {
		if ( ! $atts ) {
			$atts = array();
		}
		$atts = shortcode_atts( array(
			'taxonomy'      => 'product_cat',
			'show_unbought' => 'true'
		), $atts, 'alg_wc_mppu_user_terms_limits' );
		$taxonomy = $atts['taxonomy'];
		// Get user ID
		$user_id = $this->get_user_id( $atts );
		if ( ! $user_id ) {
			return;
		}
		if ( 'yes' !== apply_filters( 'alg_wc_mppu_' . $atts['taxonomy'] . '_enabled', 'no' ) ) {
			return;
		}
		// Products
		$output     = $_output = '';
		$block_size = 1024;
		$offset     = 0;
		while ( true ) {
			$args  = array(
				'taxonomy' => $taxonomy,
				'number'   => $block_size,
				'offset'   => $offset,
				'orderby'  => 'title',
				'order'    => 'ASC',
				'fields'   => 'all',
			);
			$terms = get_terms( $args );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				break;
			}
			foreach ( $terms as $term ) {
				$term_id = $term->term_id;
				$max_qty = alg_wc_mppu()->core->get_max_qty( array( 'type' => 'per_term', 'product_or_term_id' => $term_id ) );
				if ( $max_qty ) {
					$bought_data         = alg_wc_mppu()->core->get_user_already_bought_qty( $term_id, $user_id, false );
					$user_already_bought = $bought_data['bought'];
					$remaining           = $max_qty - $user_already_bought;
					$_output             = sprintf( '<td>%s</td><td>%s</td><td>%s</td>', max( $remaining, 0 ), $user_already_bought, max( $max_qty, 0 ) );
					if ( apply_filters( 'alg_wc_mppu_user_terms_limits_item_validation', true, array(
						'sc_atts'     => $atts,
						'taxonomy'    => $taxonomy,
						'term'        => $term,
						'user_id'     => $user_id,
						'bought_data' => $bought_data,
						'max_qty'     => $max_qty
					) ) ) {
						$output .= '<tr><td>' . '<a href="' . get_term_link( $term_id, $taxonomy ) . '">' . $term->name . '</a>' . '</td>' . $_output . '</tr>';
					}
				}
			}
			$offset += $block_size;
		}
		if ( ! empty( $output ) ) {
			return '<table class="alg_wc_mppu_products_data_my_account">' .
			       '<tr>' .
			       '<th>' . __( 'Term', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '<th>' . __( 'Remaining', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '<th>' . __( 'Bought', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '<th>' . __( 'Max', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '</tr>' .
			       $output .
			       '</table>';
		} else {
			return __( 'No data', 'maximum-products-per-user-for-woocommerce' );
		}
	}

	/**
	 * language_shortcode.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function language_shortcode( $atts, $content = '' ) {
		// E.g.: `[alg_wc_mppu_translate lang="DE" lang_text="Message in German" not_lang_text="Message for all other languages"]`
		if ( isset( $atts['lang_text'] ) && isset( $atts['not_lang_text'] ) && ! empty( $atts['lang'] ) ) {
			return ( ! defined( 'ICL_LANGUAGE_CODE' ) || ! in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['lang'] ) ) ) ) ) ?
				$atts['not_lang_text'] : $atts['lang_text'];
		}
		// E.g.: `[alg_wc_mppu_translate lang="DE"]Message in German[/alg_wc_mppu_translate][alg_wc_mppu_translate not_lang="DE"]Message for all other languages[/alg_wc_mppu_translate]`
		return (
			( ! empty( $atts['lang'] )     && ( ! defined( 'ICL_LANGUAGE_CODE' ) || ! in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['lang'] ) ) ) ) ) ) ||
			( ! empty( $atts['not_lang'] ) &&     defined( 'ICL_LANGUAGE_CODE' ) &&   in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['not_lang'] ) ) ) ) )
		) ? '' : $content;
	}

}

endif;

return new Alg_WC_MPPU_Shortcodes();
