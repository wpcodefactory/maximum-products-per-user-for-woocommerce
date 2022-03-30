<?php
/**
 * Maximum Products per User for WooCommerce - Data
 *
 * @version 3.6.2
 * @since   2.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Data' ) ) :

class Alg_WC_MPPU_Data {

	/**
	 * Constructor.
	 *
	 * @version 3.6.2
	 * @since   2.0.0
	 * @todo    [next] (feature) `woocommerce_order_status_changed` (i.e. on any order status change)
	 */
	function __construct() {
		// Save quantities.
		$this->order_statuses = get_option( 'alg_wc_mppu_order_status', array( 'wc-completed' ) );
		foreach ( $this->order_statuses as $order_status ) {
			add_action( 'woocommerce_order_status_' . substr( $order_status, 3 ), array( $this, 'save_quantities' ), PHP_INT_MAX );
		}
		add_action( 'woocommerce_thankyou', array( $this, 'save_quantities_on_new_created_order' ), PHP_INT_MAX );
		// Delete quantities.
		$this->order_statuses_delete = get_option( 'alg_wc_mppu_order_status_delete', array() );
		foreach ( $this->order_statuses_delete as $order_status ) {
			add_action( 'woocommerce_order_status_' . substr( $order_status, 3 ), array( $this, 'delete_quantities' ), PHP_INT_MAX );
		}
		// Calculate data.
		add_action( 'alg_wc_mppu_after_save_settings', array( $this, 'calculate_data' ) );
		// Duplicate product functionality.
		if ( 'no' === get_option( 'alg_wc_mppu_duplicate_product', 'no' ) ) {
			add_filter( 'woocommerce_duplicate_product_exclude_meta', array( $this, 'duplicate_product_exclude_meta' ), PHP_INT_MAX );
		}
	}

	/**
	 * duplicate_product_exclude_meta.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function duplicate_product_exclude_meta( $meta ) {
		$meta[] = '_alg_wc_mppu_totals_data';
		$meta[] = '_alg_wc_mppu_orders_data';
		$meta[] = '_wpjup_wc_maximum_products_per_user_report'; // deprecated
		return $meta;
	}

	/**
	 * calculate_data_notice.
	 *
	 * @version 2.4.0
	 * @since   1.0.0
	 */
	function calculate_data_notice() {
		$class   = 'notice notice-info';
		$message = __( 'Tool task completed.', 'maximum-products-per-user-for-woocommerce' );
		if ( $this->admin_notice_data['order_num'] > 0 ) {
			$message .= '<br>' . sprintf( __( '%s order(s) processed.', 'maximum-products-per-user-for-woocommerce' ),
				'<strong>' . $this->admin_notice_data['order_num'] . '</strong>' );
		}
		if ( $this->admin_notice_data['meta_num'] > 0 ) {
			$message .= '<br>' . sprintf( __( '%s meta(s) deleted.', 'maximum-products-per-user-for-woocommerce' ),
				'<strong>' . $this->admin_notice_data['meta_num']  . '</strong>' );
		}
		if ( $this->admin_notice_data['order_num'] > 0 && 'yes' === get_option( 'alg_wc_mppu_tool_recalculate_debug', 'no' ) ) {
			$message .= '<br>' . '[' . __( 'Debug', 'maximum-products-per-user-for-woocommerce' ) . ']' . ' ' .
				sprintf( __( 'Orders: %s.', 'maximum-products-per-user-for-woocommerce' ),
					implode( ', ', $this->admin_notice_data['order_ids'] ) );
		}
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}

	/**
	 * delete_meta_data.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 * @todo    [next] (feature) add tool to only *delete data* and *reset data* (i.e. no recalculation) (with `_alg_wc_mppu_order_data_saved` deleted and not deleted)
	 */
	function delete_meta_data() {
		global $wpdb;
		$counter = 0;
		// Products and Orders
		$keys    = array(
			'_alg_wc_mppu_totals_data',                   // product meta
			'_alg_wc_mppu_orders_data',                   // product meta
			'_alg_wc_mppu_order_data_saved',              // order meta
			'_wpjup_wc_maximum_products_per_user_report', // product meta // deprecated
			'_wpjup_wc_maximum_products_per_user_saved',  // order meta   // deprecated
		);
		$query   = "SELECT * FROM $wpdb->postmeta WHERE" . " meta_key = '" . implode( "' OR meta_key = '", $keys ) . "'";
		foreach( $wpdb->get_results( $query ) as $meta ) {
			delete_post_meta( $meta->post_id, $meta->meta_key );
			$counter++;
		}
		// Terms
		$keys    = array(
			'_alg_wc_mppu_totals_data',
			'_alg_wc_mppu_orders_data',
		);
		$query   = "SELECT * FROM $wpdb->termmeta WHERE" . " meta_key = '" . implode( "' OR meta_key = '", $keys ) . "'";
		foreach( $wpdb->get_results( $query ) as $meta ) {
			delete_term_meta( $meta->term_id, $meta->meta_key );
			$counter++;
		}
		return $counter;
	}

	/**
	 * calculate_data.
	 *
	 * @version 2.5.0
	 * @since   1.0.0
	 * @todo    [later] `delete_quantities`: on `$do_recalculate`?
	 * @todo    [later] recheck `date_query` arg for `wc_get_orders()`
	 */
	function calculate_data() {
		$do_recalculate        = ( 'yes' === get_option( 'alg_wc_mppu_tool_recalculate',        'no' ) );
		$do_delete_recalculate = ( 'yes' === get_option( 'alg_wc_mppu_tool_delete_recalculate', 'no' ) );
		$do_delete             = ( 'yes' === get_option( 'alg_wc_mppu_tool_delete',             'no' ) );
		if ( $do_recalculate || $do_delete_recalculate || $do_delete ) {
			// Delete data
			$delete_counter_meta = 0;
			if ( $do_delete_recalculate || $do_delete ) {
				$delete_counter_meta = $this->delete_meta_data();
			}
			$total_orders = 0;
			$order_ids    = array();
			if ( $do_recalculate || $do_delete_recalculate ) {
				$do_debug = ( 'yes' === get_option( 'alg_wc_mppu_tool_recalculate_debug', 'no' ) );
				// Date query
				$do_add_date_query = ( 'yes' === get_option( 'alg_wc_mppu_tool_recalculate_date_range', 'no' ) &&
					( 'lifetime' != ( $date_range = get_option( 'alg_wc_mppu_date_range', 'lifetime' ) ) ) );
				if ( $do_add_date_query ) {
					$date_query = array(
						'after'     => date( 'Y-m-d H:i:s', alg_wc_mppu()->core->get_date_to_check( $date_range ) ),
						'inclusive' => true,
					);
				}
				// Recalculate data
				$offset       = 0;
				$block_size   = get_option( 'alg_wc_mppu_tool_recalculate_block_size', 1024 );
				$time_limit   = get_option( 'alg_wc_mppu_tool_recalculate_time_limit', -1 );
				$do_wp_query  = ( 'wp_query' === get_option( 'alg_wc_mppu_tool_recalculate_loop_func', 'wp_query' ) );
				while ( true ) {
					// Time limit
					if ( $time_limit > -1 ) {
						set_time_limit( $time_limit );
					}
					// Loop
					if ( $do_wp_query ) {
						// Args
						$args = array(
							'post_type'      => 'shop_order',
							'post_status'    => $this->order_statuses,
							'posts_per_page' => $block_size,
							'orderby'        => 'ID',
							'order'          => 'DESC',
							'offset'         => $offset,
							'fields'         => 'ids',
						);
						if ( $do_add_date_query ) {
							$args['date_query'] = $date_query;
						}
						// Loop
						$loop = new WP_Query( $args );
						if ( ! $loop->have_posts() ) {
							break;
						}
						foreach ( $loop->posts as $order_id ) {
							$this->save_quantities( $order_id );
							if ( $do_debug ) {
								$order_ids[] = $order_id;
							}
							$total_orders++;
						}
					} else {
						// Args
						$args = array(
							'type'           => 'shop_order',
							'status'         => $this->order_statuses,
							'limit'          => $block_size,
							'orderby'        => 'ID',
							'order'          => 'DESC',
							'offset'         => $offset,
							'return'         => 'ids',
						);
						if ( $do_add_date_query ) {
							$args['date_query'] = $date_query;
						}
						// Loop
						$orders = wc_get_orders( apply_filters( 'alg_wc_mppu_calculate_data_wc_get_orders_args', $args ) );
						if ( empty( $orders ) ) {
							break;
						}
						foreach ( $orders as $order_id ) {
							$this->save_quantities( $order_id );
							if ( $do_debug ) {
								$order_ids[] = $order_id;
							}
							$total_orders++;
						}
					}
					// Offset
					$offset += $block_size;
				}
			}
			// Reset options
			update_option( 'alg_wc_mppu_tool_recalculate',        'no' );
			update_option( 'alg_wc_mppu_tool_delete_recalculate', 'no' );
			update_option( 'alg_wc_mppu_tool_delete',             'no' );
			// Admin notice
			$this->admin_notice_data['order_num'] = $total_orders;
			$this->admin_notice_data['order_ids'] = $order_ids;
			$this->admin_notice_data['meta_num']  = $delete_counter_meta;
			add_action( 'admin_notices', array( $this, 'calculate_data_notice' ) );
		}
	}

	/**
	 * transaction_update_meta.
	 *
	 * @version 3.3.0
	 * @since   2.0.0
	 */
	function transaction_update_meta( $product_or_term_id, $meta_key, $meta_value, $is_product ) {
		$update_meta_func = ( $is_product ? 'update_post_meta' : 'update_term_meta' );
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$query = ( $update_meta_func( $product_or_term_id, $meta_key, $meta_value ) ? 'COMMIT' : 'ROLLBACK' );
		$wpdb->query( $query );
	}

	/**
	 * get_order_data.
	 *
	 * @version 3.5.0
	 * @since   2.0.0
	 */
	function get_order_data( $order, $product_qty ) {
		return array(
			'date_created'   => ( ( $date = $order->get_date_created() )   && is_callable( array( $date, 'getTimestamp' ) ) ? $date->getTimestamp() : null ),
			'date_modified'  => ( ( $date = $order->get_date_modified() )  && is_callable( array( $date, 'getTimestamp' ) ) ? $date->getTimestamp() : null ),
			'date_completed' => ( ( $date = $order->get_date_completed() ) && is_callable( array( $date, 'getTimestamp' ) ) ? $date->getTimestamp() : null ),
			'date_paid'      => ( ( $date = $order->get_date_paid() )      && is_callable( array( $date, 'getTimestamp' ) ) ? $date->getTimestamp() : null ),
			'payment_method' => $order->get_payment_method(),
			'qty'            => $product_qty,
		);
	}

	/**
	 * delete_quantities.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function delete_quantities( $order_id ) {
		$this->update_quantities( $order_id, 'delete' );
	}

	/**
	 * save_quantities.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function save_quantities( $order_id ) {
		$this->update_quantities( $order_id, 'save' );
	}

	/**
	 * save_quantities_on_new_created_order.
	 *
	 * @version 3.6.2
	 * @since   3.6.2
	 *
	 * @param $order_id
	 */
	function save_quantities_on_new_created_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( in_array( 'wc-' . $order->get_status(), $this->order_statuses ) ) {
			$this->update_quantities( $order_id, 'save' );
		}
	}

	/**
	 * get_user_id_from_order.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function get_user_id_from_order( $order ) {
		$user_id = ( alg_wc_mppu()->core->is_wc_version_below_3_0_0 ? $order->customer_user : $order->get_customer_id() );
		if ( ! $user_id && alg_wc_mppu()->core->do_identify_guests_by_ip ) {
			$user_id = 'ip:' . $order->get_customer_ip_address();
		}
		return $user_id;
	}

	/**
	 * update_quantities.
	 *
	 * @version 3.6.2
	 * @since   1.0.0
	 * @todo    [next] mysql transaction: lock before `get_post_meta` / `get_term_meta`?
	 * @todo    [next] `alg_wc_mppu_payment_gateways`: on `$do_save` only?
	 * @todo    [next] `do_use_variations`: desc: recalculate data?
	 */
	function update_quantities( $order_id, $action ) {
		if ( $order = wc_get_order( $order_id ) ) {
			$do_save       = ( 'save' === $action );
			$is_data_saved = ( 'yes' === get_post_meta( $order_id, '_alg_wc_mppu_order_data_saved', true ) );
			if ( ( $do_save && ! $is_data_saved ) || ( ! $do_save && $is_data_saved ) ) {
				if ( ! apply_filters( "alg_wc_mppu_{$action}_quantities", true, $order_id, $order ) ) {
					return;
				}
				if ( ( $gateways = get_option( 'alg_wc_mppu_payment_gateways', array() ) ) && ! empty( $gateways ) && ! in_array( $order->get_payment_method(), $gateways ) ) {
					return;
				}
				if ( sizeof( $order->get_items() ) > 0 ) {
					$user_id = $this->get_user_id_from_order( $order );
					foreach ( $order->get_items() as $item ) {
						if ( $item->is_type( 'line_item' ) && ( $product = $item->get_product() ) ) {
							$parent_product_id = alg_wc_mppu()->core->get_parent_product_id( $product );
							$product_id        = alg_wc_mppu()->core->get_product_id( $product );
							$product_qty       = apply_filters( 'alg_wc_mppu_save_quantities_item_qty', $item->get_quantity(), $item );
							// Maybe exclude products
							$exclude_products = get_option( 'alg_wc_mppu_exclude_products', array() );
							if ( ! empty( $exclude_products ) && in_array( ( alg_wc_mppu()->core->do_use_variations( $parent_product_id ) ? $product_id : $parent_product_id ), $exclude_products ) ) {
								continue;
							}
							// Get products
							$products_and_terms = array();
							$products_and_terms[ $product_id ] = true;
							if ( $parent_product_id != $product_id ) {
								$products_and_terms[ $parent_product_id ] = true;
							}
							// Get terms
							foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
								$terms = get_the_terms( $parent_product_id, $taxonomy );
								if ( $terms && ! is_wp_error( $terms ) ) {
									foreach ( $terms as $term ) {
										$products_and_terms[ $term->term_id ] = false;
									}
								}
							}
							// Loop thorough all products and terms
							foreach ( $products_and_terms as $product_or_term_id => $is_product ) {
								$product_or_term_id = apply_filters( 'alg_wc_mppu_data_product_or_term_id', $product_or_term_id, $is_product );
								$get_meta_func = ( $is_product ? 'get_post_meta' : 'get_term_meta' );
								// Orders
								if ( '' == ( $users_orders_quantities = $get_meta_func( $product_or_term_id, '_alg_wc_mppu_orders_data', true ) ) ) {
									$users_orders_quantities = array();
								}
								if ( $do_save ) {
									// Save
									if ( ! isset( $users_orders_quantities[ $user_id ][ $order_id ] ) ) {
										$users_orders_quantities[ $user_id ][ $order_id ] = $this->get_order_data( $order, $product_qty );
									} elseif ( apply_filters( 'alg_wc_mppu_orders_data_increase_qty', true, $order_id, $user_id, $product_or_term_id, $is_product ) ) {
										$users_orders_quantities[ $user_id ][ $order_id ]['qty'] += $product_qty;
									}
								} else {
									// Delete
									if ( isset( $users_orders_quantities[ $user_id ][ $order_id ] ) ) {
										unset( $users_orders_quantities[ $user_id ][ $order_id ] );
									}
								}
								$this->transaction_update_meta( $product_or_term_id, '_alg_wc_mppu_orders_data', $users_orders_quantities, $is_product );
								// Lifetime
								if ( '' == ( $users_quantities = $get_meta_func( $product_or_term_id, '_alg_wc_mppu_totals_data', true ) ) ) {
									$users_quantities = array();
								}
								if ( $do_save ) {
									// Save
									$total_product_qty = $product_qty;
									if (
										isset( $users_quantities[ $user_id ] ) &&
										apply_filters( 'alg_wc_mppu_totals_data_increase_qty', true, $user_id, $product_or_term_id, $is_product )
									) {
										$total_product_qty += $users_quantities[ $user_id ];
									}
								} else {
									// Delete
									$total_product_qty = 0;
									if (
										isset( $users_quantities[ $user_id ] ) &&
										apply_filters( 'alg_wc_mppu_totals_data_decrease_qty', true, $user_id, $product_or_term_id, $is_product )
									) {
										$total_product_qty = $users_quantities[ $user_id ] - $product_qty;
									}
									if ( $total_product_qty < 0 ) {
										$total_product_qty = 0;
									}
								}
								$users_quantities[ $user_id ] = $total_product_qty;
								$this->transaction_update_meta( $product_or_term_id, '_alg_wc_mppu_totals_data', apply_filters( 'alg_wc_mppu_totals_data', $users_quantities, $user_id, $product_or_term_id, $is_product, $users_orders_quantities ), $is_product );
							}
						}
					}
				}
				update_post_meta( $order_id, '_alg_wc_mppu_order_data_saved', ( $do_save ? 'yes' : 'no' ) );
			}
		}
	}

}

endif;

return new Alg_WC_MPPU_Data();
