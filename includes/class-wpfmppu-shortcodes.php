<?php
/**
 * Maximum Products per User for WooCommerce - Shortcodes.
 *
 * @version 4.5.2
 * @since   2.5.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Shortcodes' ) ) :

class WPFMPPU_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 4.5.0
	 * @since   2.5.0
	 */
	function __construct() {
		add_shortcode( 'wpfmppu_translate', array( $this, 'language_shortcode' ) );
		add_shortcode( 'wpfmppu_current_product_limit', array( $this, 'current_product_limit_shortcode' ) );
		add_shortcode( 'wpfmppu_current_product_quantity', array( $this, 'current_product_limit_shortcode' ) ); // deprecated
		add_shortcode( 'wpfmppu_term_limit', array( $this, 'term_limit_shortcode' ) );
		add_shortcode( 'wpfmppu_placeholder', array( $this, 'placeholder' ) );
		add_shortcode( 'wpfmppu_customer_msg', array( $this, 'customer_msg_shortcode' ) );
		// User product limits.
		add_shortcode( 'wpfmppu_user_product_quantities', array( $this, 'user_product_limits_shortcode' ) );   // deprecated
		add_shortcode( 'wpfmppu_user_product_limits', array( $this, 'user_product_limits_shortcode' ) );
		add_filter( 'wpfmppu_user_product_limits_item_validation', array( $this, 'hide_unbought_user_product_limits_table_items' ), 10, 2 );
		add_filter( 'wpfmppu_user_product_limits_query_args', array( $this, 'hide_unbought_items_from_user_produce_limits_query' ), 10, 2 );
		// User terms limits.
		add_shortcode( 'wpfmppu_user_terms_limits', array( $this, 'user_terms_limits_shortcode' ) );
		add_filter('wpfmppu_user_terms_limits_item_validation', array( $this, 'hide_unbought_user_terms_limits_table_items' ), 10, 2 );
	}

	/**
	 * customer_msg_shortcode.
	 *
	 * @version 4.5.0
	 * @since   3.5.3
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	function customer_msg_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				/* Translators: %limit%: Limit quantity, %product_title%: Product title, %bought%: Bought quantity. */
				'bought_msg'     => __( 'You can only buy maximum %limit% of %product_title% (you\'ve already bought %bought%).', 'maximum-products-per-user-for-woocommerce' ),
				'not_bought_msg' => __( 'You can only buy maximum %limit% of %product_title%.', 'maximum-products-per-user-for-woocommerce' ),
				'bought'         => null,
				'bought_msg_min' => 1
			),
			$atts,
			'wpfmppu_customer_msg'
		);
		if ( 0 === $atts['bought'] ) {
			return esc_html( $atts['not_bought_msg'] );
		} elseif ( $atts['bought'] >= $atts['bought_msg_min'] ) {
			return esc_html( $atts['bought_msg'] );
		}

		return '';
	}

	/**
	 * get_user_id.
	 *
	 * @version 4.5.0
	 * @since   3.4.0
	 */
	function get_user_id( $atts ) {
		return ( isset( $atts['user_id'] ) ? $atts['user_id'] : wpfmppu()->core->get_current_user_id() );
	}

	/**
	 * placeholder.
	 *
	 * @version 4.5.2
	 * @since   3.2.2
	 * @todo    [maybe] add `$atts['on_zero']`?
	 */
	function placeholder( $atts, $content = '' ) {
		$key = isset( $atts['key'] ) ? sanitize_key( $atts['key'] ) : '';

		if ( '' === $key || ! isset( wpfmppu()->core->placeholders[ '%' . $key . '%' ] ) ) {
			return '';
		}

		$value = wpfmppu()->core->placeholders[ '%' . $key . '%' ];

		if ( '' === $value ) {
			return isset( $atts['on_empty'] ) ? wp_kses_post( $atts['on_empty'] ) : '';
		}

		$before = isset( $atts['before'] ) ? wp_kses_post( $atts['before'] ) : '';
		$after  = isset( $atts['after'] ) ? wp_kses_post( $atts['after'] ) : '';

		return $before . wp_kses_post( $value ) . $after;
	}

	/**
	 * term_limit_shortcode.
	 *
	 * @version 4.5.2
	 * @since   3.1.0
	 * @todo    [next] `wpfmppu()->core->get_notice_placeholders()`
	 * @todo    [later] different (customizable) message depending on `$remaining`
	 */
	function term_limit_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'user_id'   => wpfmppu()->core->get_current_user_id(),
				'taxonomy'  => '',
				'term_id'   => '',
				'term_slug' => '',
				'template'  => '',
			),
			$atts,
			'wpfmppu_term_limit'
		);

		$taxonomy  = sanitize_key( $atts['taxonomy'] );
		$term_id   = absint( $atts['term_id'] );
		$term_slug = sanitize_title( $atts['term_slug'] );

		if (
			! empty( $taxonomy ) && ( 'yes' === apply_filters( 'wpfmppu_' . $taxonomy . '_enabled', 'no' ) ) &&
			( ! empty( $term_id ) || ! empty( $term_slug ) )
		) {
			$term = ( ! empty( $term_id ) ? get_term_by( 'id', $term_id, $taxonomy ) : get_term_by( 'slug', $term_slug, $taxonomy ) );
			if ( $term ) {
				$user_id = $this->get_user_id( $atts );
				if ( $user_id ) {
					if ( 0 != ( $max_qty = wpfmppu()->core->get_max_qty( array( 'type' => 'per_term', 'product_or_term_id' => $term->term_id ) ) ) ) {
						$bought_data  = wpfmppu()->core->get_user_already_bought_qty( $term->term_id, $user_id, false );
						$bought       = $bought_data['bought'];
						$remaining    = $max_qty - $bought;
						wpfmppu()->core->placeholders = array(
							'%limit%'                           => esc_html( $max_qty ),
							'%bought%'                          => esc_html( $bought ),
							'%remaining%'                       => esc_html( $remaining > 0 ? $remaining : 0 ),
							'%term_name%'                       => esc_html( $term->name ),
							'%first_order_date%'                => ( false !== $bought_data['first_order_date'] ?
								esc_html( date_i18n( wpfmppu()->core->get_date_format(), $bought_data['first_order_date'] ) ) :
								''
							),
							'%first_order_amount%'              => ( false !== $bought_data['first_order_amount'] ?
								esc_html( $bought_data['first_order_amount'] ) :
								''
							),
							'%first_order_date_exp%'            => esc_html( wpfmppu()->core->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'] ) ),
							'%first_order_date_exp_timeleft%'   => esc_html( wpfmppu()->core->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'], true ) ),
						);
						$template = ( isset( $atts['template'] ) ?
							wp_kses_post( $atts['template'] ) :
							/* Translators: %remaining%: Remaining quantity, %bought%: Bought quantity, %limit%: Limit quantity. */
							__( "The remaining amount is %remaining% (you've already bought %bought% out of %limit%).", 'maximum-products-per-user-for-woocommerce' ) );
						$message = wpfmppu()->core->apply_placeholders( $template );
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
	 * @version 4.5.0
	 * @since   2.5.1
	 * @todo    [later] different (customizable) message depending on `$remaining`
	 */
	function current_product_limit_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'user_id'                    => wpfmppu()->core->get_current_user_id(),
			'product_id'                 => get_the_ID(),
			'msg_template'               => get_option(
				'alg_wc_mppu_permanent_notice_message',
				/* Translators: %product_title%: Product title, %remaining%: Remaining quantity, %bought%: Bought quantity, %limit%: Limit quantity. */
				__( "The remaining amount for %product_title% is %remaining% (you've already bought %bought% out of %limit%).", 'maximum-products-per-user-for-woocommerce' )
			),
			'condition'                  => get_option( 'alg_wc_mppu_permanent_notice_condition', '' ),
			'output_template'            => '<span class="alg-wc-mppu-current-product-limit">{msg_template}</span>',
			'empty_msg_removes_template' => false
		), $atts, 'wpfmppu_current_product_limit' );
		$product_id = $atts['product_id'];

		if (
			is_admin() ||
			! WC()->cart ||
			! is_a( wc_get_product( $product_id ), 'WC_Product' ) ) {
			return '';
		}
		$user_id    = $this->get_user_id( $atts );
		$output_msg = '';
		$placeholders=array();
		if ( $product_id && $user_id ) {
			$limit = wpfmppu()->core->get_max_qty_for_product( $product_id );
			if ( $limit ) {
				// Cart item quantity
				$cart_item_quantity   = 0;
				$cart_item_quantities = wpfmppu()->core->get_cart_item_quantities();
				$is_cart_empty        = ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) );
				$_cart_item_quantity  = ( ! $is_cart_empty && isset( $cart_item_quantities[ $product_id ] ) ? $cart_item_quantities[ $product_id ] : 0 );
				// Placeholders
				if ( is_array( $limit ) ) {
					// Terms (returning lowest remaining)
					$_remaining   = PHP_INT_MAX;
					$_limit_data  = false;
					$_bought_data = false;
					foreach ( $limit as $limit_data ) {
						$bought_data = wpfmppu()->core->get_user_already_bought_qty( $limit_data['term_id'], $user_id, false );
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
							$cart_item_quantity = wpfmppu()->core->get_cart_item_quantity_by_term( $product_id,
								$_cart_item_quantity, $cart_item_quantities, $_limit_data['term_id'], $_limit_data['taxonomy'] );
						}
						$term = get_term_by( 'id', $_limit_data['term_id'], $_limit_data['taxonomy'] );
						$placeholders = wpfmppu()->core->get_notice_placeholders( $product_id, $_limit_data['max_qty'], $_bought_data, $cart_item_quantity, 0, $term );
					}
				} else {
					// Products
					if ( ! $is_cart_empty ) {
						// Cart item quantity
						$parent_product_id  = wpfmppu()->core->get_parent_product_id( wc_get_product( $product_id ) );
						$use_parent         = ( $parent_product_id != $product_id && ! wpfmppu()->core->do_use_variations( $parent_product_id ) );
						$cart_item_quantity = ( ! $use_parent ? $_cart_item_quantity : wpfmppu()->core->get_cart_item_quantity_by_parent( $product_id,
							$_cart_item_quantity, $cart_item_quantities, $parent_product_id ) );
					}
					$bought_data = wpfmppu()->core->get_user_already_bought_qty( $product_id, $user_id, true );
					$placeholders = wpfmppu()->core->get_notice_placeholders( $product_id, $limit, $bought_data, $cart_item_quantity, 0, false );
				}
				// Final message
				$template = wp_kses_post( $atts['msg_template'] );
				$message = wpfmppu()->core->apply_placeholders( $template );
				$output_msg = $message;
			}
		}
		// Hide message if condition is wrong.
		if (
			! empty( $atts['condition'] ) &&
			! empty( $placeholders ) &&
			! empty( $condition = str_replace( array_keys( $placeholders ), $placeholders, html_entity_decode( $atts['condition'] ) ) ) &&
			( class_exists( '\optimistex\expression\MathExpression' ) && is_a( $e = new \optimistex\expression\MathExpression(), 'optimistex\expression\MathExpression' ) ) &&
			false === @filter_var( $e->evaluate( $condition ), FILTER_VALIDATE_BOOLEAN )
		) {
			$output_msg = false;
		}
		// Return message.
		if ( empty( $output_msg ) && $atts['empty_msg_removes_template'] ) {
			return wp_kses_post( $output_msg );
		} else {
			return str_replace( '{msg_template}', wp_kses_post( $output_msg ), wp_kses_post( $atts['output_template'] ) );
		}
	}

	/**
	 * user_product_limits_shortcode.
	 *
	 * @version 4.5.2
	 * @since   2.5.0
	 * @todo    [later] customizable content: use `wpfmppu()->core->get_notice_placeholders()`
	 * @todo    [later] customizable: columns, column order, column titles, table styling, "No data" text, (maybe) sorting
	 * @todo    [maybe] add `core::get_products()` function?
	 */
	function user_product_limits_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'user_id'                    => wpfmppu()->core->get_current_user_id(),
			'hide_products_by_id'        => '',
			'per_page'                   => wc_get_default_products_per_row() * wc_get_default_product_rows_per_page(),
			'bought_value'               => 'smart', // per_product | smart
			'show_unbought'              => 'true',
			'off_page_nav'               => 'false',
			'show_restrictions_col'      => 'true',
			'show_only_limited_products' => 'false'
		), $atts, 'wpfmppu_user_product_limits' );

		$posts_per_page = intval( $atts['per_page'] );
		$off_page_nav = $atts['off_page_nav'];
		$show_only_limited_products = $atts['show_only_limited_products'];
		$bought_value = $atts['bought_value'];
		$show_restrictions_column = filter_var( $atts['show_restrictions_col'], FILTER_VALIDATE_BOOLEAN );
		$restrictions_column_data = array(
			'column_template' => '<td>%s</td>',
			'column_val_yes'  => __( 'Yes', 'maximum-products-per-user-for-woocommerce' ),
			'column_val_no'   => __( 'No', 'maximum-products-per-user-for-woocommerce' )
		);

		// Get user ID
		$user_id = $this->get_user_id( $atts );
		if ( ! $user_id ) {
			return;
		}

		$output          = '';
		$pagination_html = '';
		$row_output      = '';
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		if ( 1 === $paged ) {
			global $wp_query;
			$paged = str_replace( "page/", "", $wp_query->query_vars[ wpfmppu()->core->my_account->get_product_limits_tab_id() ] );
			$paged = ! empty( $paged ) ? $paged : 1;
		}
		$query_args = array(
			'fields'         => 'ids',
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'paged'          => $paged,
			'post_type'      => ( 'yes' === get_option( 'alg_wc_mppu_use_variations', 'no' ) ?
					array( 'product', 'product_variation' )
					: 'product'
				),
			'post_status'    => 'any',
			'posts_per_page' => $posts_per_page,
			'post__not_in'   => isset( $atts['hide_products_by_id'] ) && ! empty( $hidden_products_ids_str = $atts['hide_products_by_id'] ) ? // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
				array_map( 'trim', explode( ",", $hidden_products_ids_str ) ) :
				'',
		);

		if ( 'true' === $show_only_limited_products ) {
			$query_args['meta_query'][0]['key']     = '_wpjup_wc_maximum_products_per_user_qty';
			$query_args['meta_query'][0]['value']   = 0;
			$query_args['meta_query'][0]['type']    = 'numeric';
			$query_args['meta_query'][0]['compare'] = '>=';
		}

		$query_args = apply_filters( 'wpfmppu_user_product_limits_query_args', $query_args, array(
			'sc_atts' => $atts,
			'user_id' => $user_id,
		) );
		$loop       = new WP_Query( $query_args );
		if ( $loop->have_posts() ) :
			foreach ( $loop->posts as $product_id ) {
				$max_qty = wpfmppu()->core->get_max_qty_for_product( $product_id );
				if ( $max_qty ) {
					$bought_data = false;
					if ( is_array( $max_qty ) && 'smart' === $bought_value ) {
						// Terms
						$_remaining = PHP_INT_MAX;
						foreach ( $max_qty as $_max_qty ) {
							$bought_data         = wpfmppu()->core->get_user_already_bought_qty( $_max_qty['term_id'], $user_id, false );
							$user_already_bought = $bought_data['bought'];
							$remaining           = $_max_qty['max_qty'] - $user_already_bought;
							if ( $remaining < $_remaining ) {
								$_remaining = $remaining;
								$row_output    = sprintf(
									'<td>%s</td><td>%s</td><td>%s</td>',
									esc_html( max( $remaining, 0 ) ),
									esc_html( $user_already_bought ),
									esc_html( max( $_max_qty['max_qty'], 0 ) )
								);
								if ( $show_restrictions_column ) {
									$row_output .= sprintf(
										$restrictions_column_data['column_template'],
										esc_html( $restrictions_column_data['column_val_yes'] )
									);
								}
							}
						}
					} elseif ( ! is_array( $max_qty ) || 'per_product' === $bought_value ) {
						// Products
						$bought_data         = wpfmppu()->core->get_user_already_bought_qty( $product_id, $user_id, true );
						$user_already_bought = $bought_data['bought'];
						$max_qty             = is_array( $max_qty ) ? min( wp_list_pluck( $max_qty, 'max_qty' ) ) : $max_qty;
						$remaining           = $max_qty - $user_already_bought;
						$row_output             = sprintf(
							'<td>%s</td><td>%s</td><td>%s</td>',
							esc_html( max( $remaining, 0 ) ),
							esc_html( $user_already_bought ),
							esc_html( max( $max_qty, 0 ) )
						);
						if ( $show_restrictions_column ) {
							$row_output .= sprintf(
								$restrictions_column_data['column_template'],
								esc_html( $restrictions_column_data['column_val_yes'] )
							);
						}
					}
					if ( apply_filters( 'wpfmppu_user_product_limits_item_validation', true, array(
						'sc_atts'     => $atts,
						'product_id'  => $product_id,
						'user_id'     => $user_id,
						'bought_data' => $bought_data,
						'max_qty'     => $max_qty
					) ) ) {
						$output .= '<tr><td>' .
						           '<a href="' . esc_url( get_permalink( $product_id ) ) . '">' . esc_html( get_the_title( $product_id ) ) . '</a>' .
						           '</td>' . $row_output . '</tr>';
					}
				} else {

					if ( $show_restrictions_column ) {
						$output .= '<tr class="alg-wc-mppu-no-restrictions"><td>' .
						           '<a href="' . esc_url( get_permalink( $product_id ) ) . '">' . esc_html( get_the_title( $product_id ) ) . '</a>' . '</td><td>-</td><td>-</td><td>-</td><td>' . esc_html( $restrictions_column_data['column_val_no'] ) . '</td></tr>';
					}else{
						$output .= '<tr class="alg-wc-mppu-no-restrictions"><td>' .
						           '<a href="' . esc_url( get_permalink( $product_id ) ) . '">' . esc_html( get_the_title( $product_id ) ) . '</a>' . '</td><td>-</td><td>-</td><td>-</td></tr>';
					}
				}
			}
			$total_pages = $loop->max_num_pages;
			if ( $total_pages > 1 && 'true' !== $off_page_nav ) {
				$current_page    = max( 1, $paged );
				$pagination_html .= '<nav class="woocommerce-pagination">';
				$pagination_html .= paginate_links( array(
					'base'      => esc_url( get_pagenum_link( 1 ) ) . '%_%',
					'format'    => 'page/%#%',
					'current'   => $current_page,
					'total'     => $total_pages,
					'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
					'next_text' => is_rtl() ? '&larr;' : '&rarr;',
					'type'      => 'list',
				) );
				$pagination_html .= '</nav>';
			}
		endif;
		wp_reset_postdata();
		if ( ! empty( $output ) ) {
			$thead = '<th>' . esc_html__( 'Product', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			         '<th>' . esc_html__( 'Remaining', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			         '<th>' . esc_html__( 'Bought', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			         '<th>' . esc_html__( 'Max', 'maximum-products-per-user-for-woocommerce' ) . '</th>';

			if ( $show_restrictions_column ) {
				$thead .=
					'<th>' . esc_html__( 'Restrictions', 'maximum-products-per-user-for-woocommerce' ) . '</th>';

			}

			$final_output =
				'<table class="alg_wc_mppu_products_data_my_account">' .
				'<tr>' .
				$thead.
				'</tr>' .
				$output .
				'</table>';
			if ( ! empty( $pagination_html ) ) {
				$final_output .= $pagination_html;
				$final_output .= '<div style="clear:both"></div>';
			}
			$final_output.='<style>.alg-wc-mppu-no-restrictions td{opacity:0.5}</style>';
			return $final_output;
		} else {
			return esc_html__( 'No data', 'maximum-products-per-user-for-woocommerce' );
		}
	}

	/**
	 * hide_user_product_limits_table_row.
	 *
	 * @version 4.5.0
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
			$bought_data         = wpfmppu()->core->get_user_already_bought_qty( $product_id, $user_id, true );
			$user_already_bought = $bought_data['bought'];
			$show                = $user_already_bought > 0;
		}
		return $show;
	}

	/**
	 * hide_unbought_items_from_user_produce_limits_query.
	 *
	 * @version 3.8.4
	 * @since   3.7.1
	 *
	 * @param $query_args
	 * @param $args
	 *
	 * @return mixed
	 */
	function hide_unbought_items_from_user_produce_limits_query( $query_args, $args ) {
		if (
			false === filter_var( $args['sc_atts']['show_unbought'], FILTER_VALIDATE_BOOLEAN ) &&
			! empty( $user_id = $args['user_id'] )
		) {
			$additional_args = array(
				'meta_key'     => '_alg_wc_mppu_orders_data', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => '\}\}i:'.$user_id.'|^a:\d:\{i:'.$user_id.';', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => 'REGEXP',
			);
			$query_args = array_merge( $query_args, $additional_args );
		}
		return $query_args;
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
	 * @version 4.5.2
	 * @since   3.5.7
	 *
	 * @param $atts
	 * @param string $content
	 *
	 * @return string|void
	 */
	function user_terms_limits_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'user_id'       => wpfmppu()->core->get_current_user_id(),
				'taxonomy'      => 'product_cat',
				'show_unbought' => 'true'
			),
			$atts,
			'wpfmppu_user_terms_limits'
		);
		$taxonomy = sanitize_key( $atts['taxonomy'] );
		// Get user ID
		$user_id = $this->get_user_id( $atts );
		if ( ! $user_id ) {
			return;
		}
		if ( 'yes' !== apply_filters( 'wpfmppu_' . $taxonomy . '_enabled', 'no' ) ) {
			return;
		}
		// Products
		$output     = '';
		$block_size = 1024;
		$offset     = 0;
		while ( true ) {
			$args  = array(
				'taxonomy' => $taxonomy,
				'number'   => $block_size,
				'offset'   => $offset,
				'orderby'  => 'menu_order title',
				'order'    => 'ASC',
				'fields'   => 'all',
			);
			$terms = new WP_Term_Query( $args );
			if ( empty( $terms ) || is_wp_error( $terms ) || empty( $terms->terms ) ) {
				break;
			}


			foreach ( $terms->terms as $term ) {
				$term_id = $term->term_id;
				$max_qty = wpfmppu()->core->get_max_qty( array( 'type' => 'per_term', 'product_or_term_id' => $term_id ) );
				if ( $max_qty ) {
					$bought_data         = wpfmppu()->core->get_user_already_bought_qty( $term_id, $user_id, false );
					$user_already_bought = $bought_data['bought'];
					$remaining           = $max_qty - $user_already_bought;
					$row_output             = sprintf(
						'<td>%s</td><td>%s</td><td>%s</td>',
						esc_html( max( $remaining, 0 ) ),
						esc_html( $user_already_bought ),
						esc_html( max( $max_qty, 0 ) )
					);
					if ( apply_filters( 'wpfmppu_user_terms_limits_item_validation', true, array(
						'sc_atts'     => $atts,
						'taxonomy'    => $taxonomy,
						'term'        => $term,
						'user_id'     => $user_id,
						'bought_data' => $bought_data,
						'max_qty'     => $max_qty
					) ) ) {
						$output .= '<tr><td>' .
						           '<a href="' . esc_url( get_term_link( $term_id, $taxonomy ) ) . '">' . esc_html( $term->name ) . '</a>' .
						           '</td>' . $row_output . '</tr>';
					}
				}
			}
			$offset += $block_size;
		}
		if ( ! empty( $output ) ) {
			return '<table class="alg_wc_mppu_products_data_my_account">' .
			       '<tr>' .
			       '<th>' . esc_html__( 'Term', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '<th>' . esc_html__( 'Remaining', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '<th>' . esc_html__( 'Bought', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '<th>' . esc_html__( 'Max', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			       '</tr>' .
			       $output .
			       '</table>';
		} else {
			return esc_html__( 'No data', 'maximum-products-per-user-for-woocommerce' );
		}
	}

	/**
	 * language_shortcode.
	 *
	 * @version 4.5.0
	 * @since   2.0.0
	 */
	function language_shortcode( $atts, $content = '' ) {
		// E.g.: `[wpfmppu_translate lang="DE" lang_text="Message in German" not_lang_text="Message for all other languages"]`
		if ( isset( $atts['lang_text'] ) && isset( $atts['not_lang_text'] ) && ! empty( $atts['lang'] ) ) {
			return ( ! defined( 'ICL_LANGUAGE_CODE' ) || ! in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['lang'] ) ) ) ) ) ?
				esc_html( $atts['not_lang_text'] ) : esc_html( $atts['lang_text'] );
		}

		// E.g.: `[wpfmppu_translate lang="DE"]Message in German[/wpfmppu_translate][wpfmppu_translate not_lang="DE"]Message for all other languages[/wpfmppu_translate]`
		return (
			( ! empty( $atts['lang'] ) && ( ! defined( 'ICL_LANGUAGE_CODE' ) || ! in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['lang'] ) ) ) ) ) ) ||
			( ! empty( $atts['not_lang'] ) && defined( 'ICL_LANGUAGE_CODE' ) && in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['not_lang'] ) ) ) ) )
		) ? '' : esc_html( $content );
	}

}

endif;

return new WPFMPPU_Shortcodes();
