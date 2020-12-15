<?php
/**
 * Maximum Products per User for WooCommerce - Per Product Settings
 *
 * @version 3.5.0
 * @since   2.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Per_product' ) ) :

class Alg_WC_MPPU_Settings_Per_product {

	/**
	 * Constructor.
	 *
	 * @version 3.2.3
	 * @since   2.0.0
	 */
	function __construct() {
		if ( 'yes' === apply_filters( 'alg_wc_mppu_local_enabled', 'no' ) ) {
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * add_meta_box.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function add_meta_box() {
		add_meta_box(
			'alg-wc-maximum-products-per-user',
			__( 'Maximum Products per User', 'maximum-products-per-user-for-woocommerce' ),
			array( $this, 'create_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * create_meta_box.
	 *
	 * @version 3.3.0
	 * @since   1.0.0
	 */
	function create_meta_box() {
		$product_id = get_the_ID();
		$html = '';
		foreach ( $this->get_meta_box_options( $product_id ) as $option ) {
			$value = get_post_meta( $option['product_id'], $option['meta'], true );
			if ( isset( $option['key'] ) ) {
				$value = ( isset( $value[ $option['key'] ] ) ? $value[ $option['key'] ] : $option['default'] );
			}
			if ( '' === $value ) {
				$value = $option['default'];
			}
			$custom_attributes = ( isset( $option['custom_attributes'] ) ? ' ' . $option['custom_attributes'] : '' );
			$id                = $option['name'] . ( isset( $option['key'] ) ? '_' . $option['key']       : '' );
			$name              = $option['name'] . ( isset( $option['key'] ) ? '[' . $option['key'] . ']' : '' );
			$html .= '<tr>';
			$html .= '<td><label for="' . $id . '">' . $option['title'] . '</label></td>' . ' ' . '<td>';
			switch ( $option['type'] ) {
				case 'select':
					$options = '';
					foreach ( $option['options'] as $select_option_id => $select_option_title ) {
						$options .= '<option value="' . $select_option_id . '"' . selected( $value, $select_option_id, false ) . '>' . $select_option_title . '</option>';
					}
					$html .= '<select class="chosen_select" id="' . $id . '" name="' . $name . '"' . $custom_attributes . '>' . $options . '</select>';
					break;
				default:
					$html .= '<input type="' . $option['type'] . '" id="' . $id . '" name="' . $name . '" value="' . $value . '"' . $custom_attributes . '>';
			}
			if ( isset( $option['tooltip'] ) ) {
				$html .= wc_help_tip( $option['tooltip'], true );
			}
			$html .= '</td></tr>';
		}
		echo ( ! empty( $html ) ? '<table>' . $html . '</table>' : $html );
	}

	/**
	 * save_meta_box.
	 *
	 * @version 2.2.0
	 * @since   1.0.0
	 */
	function save_meta_box( $product_id, $__post ) {
		$data = array();
		foreach ( $this->get_meta_box_options( $product_id ) as $option ) {
			if ( isset( $_POST[ $option['name'] ] ) ) {
				if ( ! isset( $option['key'] ) ) {
					update_post_meta( $option['product_id'], $option['meta'], sanitize_text_field( $_POST[ $option['name'] ] ) );
				} else {
					$data[ $option['product_id'] ][ $option['meta'] ][ $option['key'] ] = sanitize_text_field( $_POST[ $option['name'] ][ $option['key'] ] );
				}
			}
		}
		if ( ! empty( $data ) ) {
			foreach ( $data as $_product_id => $product_data ) {
				foreach ( $product_data as $meta => $values ) {
					update_post_meta( $_product_id, $meta, $values );
				}
			}
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 3.5.0
	 * @since   1.0.0
	 * @todo    [maybe] (desc) Limits: better tooltips?
	 * @todo    [maybe] (desc) Use variations: better title/desc/tooltip?
	 */
	function get_meta_box_options( $product_id = 0 ) {
		$result = array();
		if ( 0 == $product_id ) {
			$product_id = get_the_ID();
		}
		$product        = wc_get_product();
		$children       = ( $product ? $product->get_children() : false );
		$use_variations = ( alg_wc_mppu()->core->do_use_variations( $product_id ) && $children );
		$products       = ( $use_variations ? $children : array( $product_id ) );
		// Limits
		foreach ( $products as $_product_id ) {
			$result[] = array(
				'title'             => __( 'Limit per user', 'maximum-products-per-user-for-woocommerce' ) . ( $use_variations ? ' (#' . $_product_id . ')' : '' ),
				'name'              => 'wpjup_wc_maximum_products_per_user_qty' . ( $use_variations ? '_' . $_product_id : '' ),
				'meta'              => '_wpjup_wc_maximum_products_per_user_qty',
				'default'           => 0,
				'type'              => 'number',
				'tooltip'           => __( 'If set to zero, and "All Products" section is enabled - global limit will be used; in case if "All Products" section is disabled - no limit will be used.', 'maximum-products-per-user-for-woocommerce' ),
				'custom_attributes' => 'min="-1"',
				'product_id'        => $_product_id,
			);
			// User roles
			if ( 'yes' === get_option( 'alg_wc_mppu_use_user_roles', 'no' ) ) {
				foreach ( alg_wc_mppu()->core->get_user_roles() as $role => $role_name ) {
					$result[] = array(
						'title'             => __( 'Limit per user', 'maximum-products-per-user-for-woocommerce' ) . ( $use_variations ? ' (#' . $_product_id . ')' : '' ) . ': ' . $role_name,
						'name'              => 'alg_wc_mppu_user_roles_max_qty' . ( $use_variations ? '_' . $_product_id : '' ),
						'key'               => $role,
						'meta'              => '_alg_wc_mppu_user_roles_max_qty',
						'default'           => 0,
						'type'              => 'number',
						'tooltip'           => __( 'If set to zero - "Limit per user" option will be used.', 'maximum-products-per-user-for-woocommerce' ),
						'custom_attributes' => 'min="-1"',
						'product_id'        => $_product_id,
					);
				}
			}
		}
		// Use variations
		if ( $children ) {
			$result[] = array(
				'title'             => __( 'Use variations', 'maximum-products-per-user-for-woocommerce' ),
				'name'              => 'alg_wc_mppu_use_variations',
				'meta'              => '_alg_wc_mppu_use_variations',
				'default'           => '',
				'type'              => 'select',
				'product_id'        => $product_id,
				'options'           => array(
					''    => __( 'Default', 'maximum-products-per-user-for-woocommerce' ) . ' (' . get_option( 'alg_wc_mppu_use_variations', 'no' ) . ')',
					'yes' => __( 'Yes', 'maximum-products-per-user-for-woocommerce' ),
					'no'  => __( 'No', 'maximum-products-per-user-for-woocommerce' ),
				),
				'tooltip'           => __( '"Update" the product after you change this option.', 'maximum-products-per-user-for-woocommerce' ),
			);
		}
		return $result;
	}

}

endif;

return new Alg_WC_MPPU_Settings_Per_product();
