<?php
/**
 * Maximum Products per User for WooCommerce - Per Term Settings
 *
 * @version 3.5.0
 * @since   2.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Per_Term' ) ) :

class Alg_WC_MPPU_Settings_Per_Term {

	/**
	 * Constructor.
	 *
	 * @version 3.2.3
	 * @since   2.0.0
	 */
	function __construct() {
		if ( 'yes' === apply_filters( 'alg_wc_mppu_product_tag_enabled', 'no' ) ) {
			add_action( 'product_tag_edit_form_fields', array( $this, 'product_terms_add_fields' ),  PHP_INT_MAX );
			add_action( 'edit_product_tag',             array( $this, 'product_terms_save_fields' ), PHP_INT_MAX );
		}
		if ( 'yes' === apply_filters( 'alg_wc_mppu_product_cat_enabled', 'no' ) ) {
			add_action( 'product_cat_edit_form_fields', array( $this, 'product_terms_add_fields' ),  PHP_INT_MAX );
			add_action( 'edit_product_cat',             array( $this, 'product_terms_save_fields' ), PHP_INT_MAX );
		}
	}

	/**
	 * product_terms_add_fields.
	 *
	 * @version 3.5.0
	 * @since   2.0.0
	 * @todo    [next] (desc) descriptions (maybe use `wc_help_tip()`)
	 */
	function product_terms_add_fields( $term ) {
		$value = get_term_meta( $term->term_id, '_alg_wc_mppu_qty', true );
		if ( empty( $value ) ) {
			$value = 0;
		}
		echo '<tr class="form-field">' .
			'<th scope="row" valign="top"><label for="alg_wc_mppu_qty">' .
				__( 'Limit per user', 'maximum-products-per-user-for-woocommerce' ) . '</label></th>' .
			'<td>' . '<input type="number" min="-1" name="alg_wc_mppu_qty" id="alg_wc_mppu_qty" value="' . $value . '">' .
				'<span class="description">' . '</span>' . '<input type="hidden" name="alg_wc_mppu_edit_terms" value="1">' . '</td>' .
		'</tr>';
		// User roles
		if ( 'yes' === get_option( 'alg_wc_mppu_use_user_roles', 'no' ) ) {
			$values = get_term_meta( $term->term_id, '_alg_wc_mppu_user_roles_max_qty', true );
			$i      = 0;
			foreach ( alg_wc_mppu()->core->get_user_roles() as $role => $role_name ) {
				$value  = ( isset( $values[ $role ] ) ? $values[ $role ] : 0 );
				$hidden = ( 0 == $i ? '<input type="hidden" name="alg_wc_mppu_edit_terms_user_roles" value="1">' : '' );
				$i++;
				echo '<tr class="form-field">' .
					'<th scope="row" valign="top"><label for="alg_wc_mppu_qty_' . $role . '">' .
						__( 'Limit per user', 'maximum-products-per-user-for-woocommerce' ) . ': ' . $role_name . '</label></th>' .
					'<td>' . '<input type="number" min="-1" name="alg_wc_mppu_qty_user_roles[' . $role . ']" id="alg_wc_mppu_qty_' . $role . '" value="' . $value . '">' .
						'<span class="description">' . '</span>' . $hidden . '</td>' .
				'</tr>';
			}
		}
	}

	/**
	 * product_terms_save_fields.
	 *
	 * @version 2.2.0
	 * @since   2.0.0
	 */
	function product_terms_save_fields( $term_id ) {
		if ( isset( $_POST['alg_wc_mppu_edit_terms'] ) ) {
			update_term_meta( $term_id, '_alg_wc_mppu_qty',
				( isset( $_POST['alg_wc_mppu_qty'] ) ? sanitize_text_field( $_POST['alg_wc_mppu_qty'] ) : 0 ) );
		}
		if ( isset( $_POST['alg_wc_mppu_edit_terms_user_roles'] ) ) {
			update_term_meta( $term_id, '_alg_wc_mppu_user_roles_max_qty',
				( isset( $_POST['alg_wc_mppu_qty_user_roles'] ) ? array_map( 'sanitize_text_field', $_POST['alg_wc_mppu_qty_user_roles'] ) : array() ) );
		}
	}

}

endif;

return new Alg_WC_MPPU_Settings_Per_Term();
