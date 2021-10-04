<?php
/**
 * Maximum Products per User for WooCommerce - Users
 *
 * @version 3.5.0
 * @since   2.2.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Users' ) ) :

class Alg_WC_MPPU_Users {

	/**
	 * Constructor.
	 *
	 * @version 3.4.0
	 * @since   2.2.0
	 * @todo    [next] rename export functions, variables etc.
	 * @todo    [maybe] validation: `add_action( 'user_profile_update_errors', 'user_profile_update_errors', PHP_INT_MAX, 3 ); function user_profile_update_errors( $errors, $update, $user ) {}`
	 */
	function __construct() {
		if ( 'yes' === get_option( 'alg_wc_mppu_editable_sales_data', 'no' ) ) {
			$this->do_add_empty_totals = ( 'yes' === get_option( 'alg_wc_mppu_editable_sales_data_empty_totals', 'no' ) );
			add_action( 'show_user_profile',        array( $this, 'show_extra_profile_fields' ), PHP_INT_MAX );
			add_action( 'edit_user_profile',        array( $this, 'show_extra_profile_fields' ), PHP_INT_MAX );
			add_action( 'personal_options_update',  array( $this, 'update_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'update_profile_fields' ) );
			add_action( 'admin_init',               array( $this, 'export_orders_data' ) );
		}
		add_action( 'admin_init', array( $this, 'export_orders_data_all_users' ) );
	}

	/**
	 * check_current_user.
	 *
	 * @version 3.4.0
	 * @since   2.2.0
	 */
	function check_current_user( $user_id = false ) {
		return ( current_user_can( 'manage_woocommerce' ) && ( ! $user_id || current_user_can( 'edit_user', $user_id ) ) );
	}

	/**
	 * get_export_orders_data.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [next] merge with `show_extra_profile_fields()`?
	 */
	function get_export_orders_data( $user_id, $sep, $do_skip_user_info = false ) {
		// Products
		$csv        = array();
		$block_size = 1024;
		$offset     = 0;
		while ( true ) {
			$args = array(
				'post_type'      => ( 'yes' === get_option( 'alg_wc_mppu_use_variations', 'no' ) ? array( 'product', 'product_variation' ) : 'product' ),
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				$product_title = get_the_title( $product_id );
				$orders_data   = get_post_meta( $product_id, '_alg_wc_mppu_orders_data', true );
				if ( is_array( $orders_data ) && isset( $orders_data[ $user_id ] ) ) {
					foreach ( $orders_data[ $user_id ] as $order_id => $order_data ) {
						$data = array(
							$user_id,
							$product_id,
							'"' . $product_title . '"',
							$order_id,
							date_i18n( 'Y-m-d H:i:s', alg_wc_mppu()->core->get_order_date( $order_data['date_created'] ) ),
							$order_data['qty'],
						);
						if ( $do_skip_user_info ) {
							unset( $data[0] );
						}
						$csv[] = implode( $sep, $data );
					}
				}
			}
			$offset += $block_size;
		}
		return $csv;
	}

	/**
	 * export_orders_data.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [next] nonce (same for `export_orders_data_all_users()`)
	 * @todo    [next] terms (same for `export_orders_data_all_users()`)
	 * @todo    [next] `export_lifetime_data` (same for `export_orders_data_all_users()`)
	 */
	function export_orders_data() {
		if ( isset( $_GET['alg_wc_mppu_export_single_user_orders_data'] ) ) {
			$user_id = intval( $_GET['alg_wc_mppu_export_single_user_orders_data'] );
			if ( ! $this->check_current_user( $user_id ) ) {
				return false;
			}
			$sep = get_option( 'alg_wc_mppu_user_export_sep', ',' );
			$csv = $this->get_export_orders_data( $user_id, $sep );
			$this->get_file( 'maximum-products-per-user-orders-data-' . $user_id, $csv, $sep );
		}
	}

	/**
	 * export_orders_data_all_users.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @see     https://developer.wordpress.org/reference/classes/wp_user_query/prepare_query/
	 * @todo    [next] `get_users`: `offset` + `number`?
	 * @todo    [next] meta: for `! $do_merge`?
	 * @todo    [next] meta: `is_array` etc.?
	 * @todo    [maybe] include data for `identify_by_ip` in result?
	 */
	function export_orders_data_all_users() {
		if ( isset( $_GET['alg_wc_mppu_export_all_users_orders_data'] ) ) {
			if ( ! $this->check_current_user() ) {
				return false;
			}
			$csv      = array();
			$sep      = get_option( 'alg_wc_mppu_user_export_sep', ',' );
			$meta     = ( '' !== ( $_meta = get_option( 'alg_wc_mppu_user_export_meta', '' ) ) ? array_map( 'trim', explode( ',', $_meta ) ) : false );
			$do_merge = ( 'yes' === get_option( 'alg_wc_mppu_user_export_merge_user', 'no' ) );
			$args     = array( 'number' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'count_total' => false, 'fields' => 'ID' );
			if ( $do_merge ) {
				$data_sep = get_option( 'alg_wc_mppu_user_export_data_sep', ';' );
				foreach ( get_users( $args ) as $user_id ) {
					$data = $this->get_export_orders_data( $user_id, $data_sep, true );
					if ( ! empty( $data ) ) {
						if ( $meta ) {
							$user_meta = array();
							foreach ( $meta as $key ) {
								$user_meta[] = get_user_meta( $user_id, $key, true );
							}
							$user_meta = implode( $sep, $user_meta );
							if ( '' !== $user_meta ) {
								$user_meta .= $sep;
							}
						} else {
							$user_meta = '';
						}
						$csv[] = $user_id . $sep . $user_meta . implode( $sep, $data );
					}
				}
			} else {
				foreach ( get_users( $args ) as $user_id ) {
					$csv = array_merge( $csv, $this->get_export_orders_data( $user_id, $sep ) );
				}
			}
			$this->get_file( 'maximum-products-per-user-orders-data', $csv, $sep, ( ! $do_merge ) );
		}
	}

	/**
	 * get_file.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [next] `header( 'Content-Length: ' . strlen( $csv ) )`?
	 */
	function get_file( $filename, $csv, $sep, $add_header = true ) {
		if ( $add_header ) {
			array_unshift( $csv, '"' . implode( '"' . $sep . '"', array(
				__( 'User ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Product ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Product title', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order date', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order value', 'maximum-products-per-user-for-woocommerce' ),
			) ) . '"' );
		}
		$csv = implode( PHP_EOL, $csv );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
		die();
	}

	/**
	 * update_profile_fields.
	 *
	 * @version 3.5.0
	 * @since   2.2.0
	 * @todo    [maybe] nonce?
	 * @todo    [maybe] maybe `floatval` (instead `intval`?)
	 */
	function update_profile_fields( $user_id ) {
		if ( ! $this->check_current_user( $user_id ) ) {
			return false;
		}
		// Totals data
		if ( isset( $_POST['alg_wc_mppu_totals_data'] ) ) {
			foreach ( $_POST['alg_wc_mppu_totals_data'] as $product_or_term => $data ) {
				foreach ( $data as $product_or_term_id => $qty ) {
					$totals_data = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_totals_data' );
					$totals_data[ $user_id ] = intval( $qty );
					alg_wc_mppu()->core->update_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_totals_data', $totals_data );
				}
			}
		}
		// Orders data
		if ( isset( $_POST['alg_wc_mppu_orders_data'] ) ) {
			foreach ( $_POST['alg_wc_mppu_orders_data'] as $product_or_term => $data ) {
				foreach ( $data as $product_or_term_id => $order_data ) {
					$orders_data = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_orders_data' );
					foreach ( $order_data as $order_id => $qty ) {
						$orders_data[ $user_id ][ $order_id ]['qty'] = intval( $qty );
					}
					alg_wc_mppu()->core->update_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_orders_data', $orders_data );
				}
			}
		}
	}

	/**
	 * show_extra_profile_fields.
	 *
	 * @version 3.4.0
	 * @since   2.2.0
	 */
	function show_extra_profile_fields( $user ) {
		if ( ! $this->check_current_user( $user->ID ) ) {
			return false;
		}
		// Products
		$output     = '';
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
				$output .= $this->get_item_data( $user, $product_id, 'product',
					'<a href="' . admin_url( 'post.php?post=' . ( ( $parent_post_id = wp_get_post_parent_id( $product_id ) ) ? $parent_post_id : $product_id ) . '&action=edit' ) . '">' . get_the_title( $product_id ) . '</a>' );
			}
			$offset += $block_size;
		}
		if ( ! empty( $output ) ) {
			echo '<h2>' . __( 'Maximum Products per User', 'maximum-products-per-user-for-woocommerce' ) . ': ' .
				__( 'Products Data', 'maximum-products-per-user-for-woocommerce' ) . '</h2>' .
			'<style>table.alg_wc_mppu_products_data th, table.alg_wc_mppu_products_data td { width: 33%; }</style>' .
			'<a style="float:right;" href="' . add_query_arg( 'alg_wc_mppu_export_single_user_orders_data', $user->ID ). '">[' .
				__( 'export sales data', 'maximum-products-per-user-for-woocommerce' ) . ']</a>' .
			'<table class="widefat striped alg_wc_mppu_products_data">' .
				'<tr>' .
					'<th>' . __( 'Product', 'maximum-products-per-user-for-woocommerce' )  . '</th>' .
					'<th>' . __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
					'<th>' . __( 'Orders', 'maximum-products-per-user-for-woocommerce' )   . '</th>' .
				'</tr>' .
				$output .
			'</table>';
		}
		// Terms
		foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
			if ( 'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) ) {
				$output = '';
				$args = array(
					'taxonomy'       => $taxonomy,
					'orderby'        => 'name',
					'order'          => 'ASC',
					'hide_empty'     => false,
					'posts_per_page' => -1,
				);
				$loop = new WP_Term_Query( $args );
				foreach ( $loop->get_terms() as $term ) {
					$output .= $this->get_item_data( $user, $term->term_id, 'term',
						'<a href="' . admin_url( 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&post_type=product' ) . '">' . $term->name . '</a>' );
				}
				if ( ! empty( $output ) ) {
					echo '<h2>' . __( 'Maximum Products per User', 'maximum-products-per-user-for-woocommerce' ) . ': ' .
						( 'product_cat' === $taxonomy ?
							__( 'Product Categories Data', 'maximum-products-per-user-for-woocommerce' ) :
							__( 'Product Tags Data', 'maximum-products-per-user-for-woocommerce' ) ) .
					'</h2>' .
					'<style>table.alg_wc_mppu_' . $taxonomy . '_data th, table.alg_wc_mppu_' . $taxonomy . '_data td { width: 33%; }</style>' .
					'<table class="widefat striped alg_wc_mppu_' . $taxonomy . '_data">' .
						'<tr>' .
							'<th>' . __( 'Term', 'maximum-products-per-user-for-woocommerce' )     . '</th>' .
							'<th>' . __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
							'<th>' . __( 'Orders', 'maximum-products-per-user-for-woocommerce' )   . '</th>' .
						'</tr>' .
						$output .
					'</table>';
				}
			}
		}
	}

	/**
	 * get_item_data.
	 *
	 * @version 3.5.0
	 * @since   2.2.0
	 * @todo    [next] use `alg_wc_mppu()->core->get_date_format()`
	 */
	function get_item_data( $user, $product_or_term_id, $product_or_term, $product_or_term_title ) {
		$output = '';
		$totals_data = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_totals_data' );
		$orders_data = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_orders_data' );
		if ( $this->do_add_empty_totals || isset( $totals_data[ $user->ID ] ) || isset( $orders_data[ $user->ID ] ) ) {
			$output .= '<tr>';
			$output .= '<th>' . $product_or_term_title . ' (#' . $product_or_term_id . ')' . '</th>';
			$output .= '<td>';
			if ( $this->do_add_empty_totals || isset( $totals_data[ $user->ID ] ) ) {
				$name  = 'alg_wc_mppu_totals_data[' . $product_or_term . '][' . $product_or_term_id . ']';
				$value = ( isset( $totals_data[ $user->ID ] ) ? $totals_data[ $user->ID ] : 0 );
				$output .= '<input type="number" name="' . $name . '" value="' . $value . '">';
			}
			$output .= '</td>';
			$output .= '<td>';
			if ( isset( $orders_data[ $user->ID ] ) ) {
				$orders = array();
				foreach ( $orders_data[ $user->ID ] as $order_id => $order_data ) {
					$name  = 'alg_wc_mppu_orders_data[' . $product_or_term . '][' . $product_or_term_id . '][' . $order_id . ']';
					$value = $order_data['qty'];
					$orders[ $order_id ] = '<input type="number" name="' . $name . '" value="' . $value . '">' .
						' <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '">#' . $order_id . '</a>' .
						wc_help_tip( sprintf( __( 'Order #%d<br>(%s)', 'maximum-products-per-user-for-woocommerce' ),
							$order_id, date_i18n( 'Y-m-d H:i:s', alg_wc_mppu()->core->get_order_date( $order_data['date_created'] ) ) ), true );
				}
				$output .= implode( '<br>', $orders );
			}
			$output .= '</td>';
			$output .= '</tr>';
		}
		return $output;
	}

}

endif;

return new Alg_WC_MPPU_Users();
