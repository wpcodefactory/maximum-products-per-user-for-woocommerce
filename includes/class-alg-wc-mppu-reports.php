<?php
/**
 * Maximum Products per User for WooCommerce - Reports.
 *
 * @version 3.7.7
 * @since   2.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Reports' ) ) :

class Alg_WC_MPPU_Reports {

	/**
	 * Constructor.
	 *
	 * @version 3.2.3
	 * @since   2.0.0
	 */
	function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_report_meta_box' ) );
		if ( 'yes' === apply_filters( 'alg_wc_mppu_product_tag_enabled', 'no' ) ) {
			add_action( 'product_tag_edit_form', array( $this, 'product_terms_show_data' ),   PHP_INT_MAX, 2 );
		}
		if ( 'yes' === apply_filters( 'alg_wc_mppu_product_cat_enabled', 'no' ) ) {
			add_action( 'product_cat_edit_form', array( $this, 'product_terms_show_data' ),   PHP_INT_MAX, 2 );
		}
	}

	/**
	 * product_terms_show_data.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function product_terms_show_data( $term, $taxonomy ) {
		echo '<h2>' . __( 'Maximum Products per User: Sales Data', 'maximum-products-per-user-for-woocommerce' ) . '</h2>';
		$this->get_report_data_table( $term->term_id, false );
	}

	/**
	 * add_report_meta_box.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function add_report_meta_box() {
		add_meta_box(
			'alg-wc-maximum-products-per-user-report',
			__( 'Maximum Products per User: Sales Data', 'maximum-products-per-user-for-woocommerce' ),
			array( $this, 'create_report_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * create_report_meta_box.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function create_report_meta_box() {
		$product_id = get_the_ID();
		$this->get_report_data_table( $product_id, true );
		if ( ( $product = wc_get_product( $product_id ) ) && ( $children = $product->get_children() ) ) {
			foreach ( $children as $child_id ) {
				$this->get_report_data_table( $child_id, true );
			}
		}
	}

	/**
	 * get_user_name.
	 *
	 * @version 3.8.8
	 * @since   3.4.0
	 */
	function get_user_name( $user_id ) {
		if ( ! $user_id ) {
			return __( 'Guest', 'maximum-products-per-user-for-woocommerce' );
		} elseif ( filter_var( $user_id, FILTER_VALIDATE_EMAIL ) ) {
			return __( 'Guest by billing email id', 'maximum-products-per-user-for-woocommerce' );
		} elseif ( ! is_numeric( $user_id ) ) {
			return __( 'Guest by IP', 'maximum-products-per-user-for-woocommerce' );
		} else {
			$user = get_user_by( 'id', $user_id );
			return ( isset( $user->data->user_nicename ) ? $user->data->user_nicename : '-' );
		}
	}

	/**
	 * get_report_data_table.
	 *
	 * @version 3.7.7
	 * @since   2.0.0
	 * @todo    [next] use `alg_wc_mppu()->core->get_date_format()`
	 */
	function get_report_data_table( $product_or_term_id, $is_product ) {
		if ( $is_product ) {
			echo sprintf( '<h3>' . __( 'Product #%d', 'maximum-products-per-user-for-woocommerce' ) . '</h3>', $product_or_term_id );
		}
		// Lifetime
		$users_quantities = alg_wc_mppu()->core->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_totals_data' );
		if ( $users_quantities && is_array( $users_quantities ) ) {
			$table_data   = array();
			$table_data[] = array(
				__( 'User ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'User Name', 'maximum-products-per-user-for-woocommerce' ),
				'',
				'',
				__( 'Bought', 'maximum-products-per-user-for-woocommerce' ),
			);
			foreach ( $users_quantities as $user_id => $qty_bought ) {
				$table_data[] = array( $user_id, $this->get_user_name( $user_id ), '', '', $qty_bought );
			}
			echo '<h4>' . __( 'Lifetime Data', 'maximum-products-per-user-for-woocommerce' ) . '</h4>';
			echo $this->get_table_html( $table_data, array(
				'table_class'    => 'widefat striped', 'table_heading_type' => 'horizontal',
				'columns_styles' => array_fill( 0, 5, 'width:20%;' )
			) );
		} else {
			echo '<em>' . __( 'No data.', 'maximum-products-per-user-for-woocommerce' ) . '</em>';
		}
		// Orders
		$users_orders_quantities = alg_wc_mppu()->core->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_orders_data' );
		if ( $users_orders_quantities ) {
			$table_data = array();
			$table_data[] = array(
				__( 'User ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'User Name', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order Date', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Bought', 'maximum-products-per-user-for-woocommerce' ),
			);
			foreach ( $users_orders_quantities as $user_id => $orders ) {
				foreach ( $orders as $order_id => $order_data ) {
					$table_data[] = array(
						$user_id,
						$this->get_user_name( $user_id ),
						$order_id,
						date_i18n( 'Y-m-d H:i:s', alg_wc_mppu()->core->get_order_date( $order_data['date_created'] ) ),
						$order_data['qty'],
					);
				}
			}
			echo '<h4>' . __( 'Orders Data', 'maximum-products-per-user-for-woocommerce' ) . '</h4>';
			echo $this->get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'horizontal',
				'columns_styles' => array_fill( 0, 5, 'width:20%;' ) ) );
		}
	}

	/**
	 * get_table_html.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function get_table_html( $data, $args = array() ) {
		$args = array_merge( array(
			'table_class'        => '',
			'table_style'        => '',
			'row_styles'         => '',
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => array(),
		), $args );
		$table_class = ( '' == $args['table_class'] ) ? '' : ' class="' . $args['table_class'] . '"';
		$table_style = ( '' == $args['table_style'] ) ? '' : ' style="' . $args['table_style'] . '"';
		$row_styles  = ( '' == $args['row_styles'] )  ? '' : ' style="' . $args['row_styles']  . '"';
		$html = '';
		$html .= '<table' . $table_class . $table_style . '>';
		$html .= '<tbody>';
		foreach( $data as $row_number => $row ) {
			$html .= '<tr' . $row_styles . '>';
			foreach( $row as $column_number => $value ) {
				$th_or_td = ( ( 0 === $row_number && 'horizontal' === $args['table_heading_type'] ) || ( 0 === $column_number && 'vertical' === $args['table_heading_type'] ) ) ?
					'th' : 'td';
				$column_class = ( ! empty( $args['columns_classes'][ $column_number ] ) ) ? ' class="' . $args['columns_classes'][ $column_number ] . '"' : '';
				$column_style = ( ! empty( $args['columns_styles'][ $column_number ] ) )  ? ' style="' . $args['columns_styles'][ $column_number ]  . '"' : '';
				$html .= '<' . $th_or_td . $column_class . $column_style . '>';
				$html .= $value;
				$html .= '</' . $th_or_td . '>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		$html .= '</table>';
		return $html;
	}

}

endif;

return new Alg_WC_MPPU_Reports();
