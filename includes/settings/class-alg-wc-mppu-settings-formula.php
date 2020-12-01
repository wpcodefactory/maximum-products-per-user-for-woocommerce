<?php
/**
 * Maximum Products per User for WooCommerce - Formula Section Settings
 *
 * @version 3.3.0
 * @since   2.3.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Formula' ) ) :

class Alg_WC_MPPU_Settings_Formula extends Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function __construct() {
		$this->id   = 'formula';
		$this->desc = __( 'Formula', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 3.3.0
	 * @since   2.3.0
	 * @todo    [next] (desc) add more examples
	 * @todo    [later] (desc) add link to site ("Check plugin site for more examples")
	 */
	function get_settings() {

		$limits_section_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu&section=limits' ) . '">' .
			__( 'Limits', 'maximum-products-per-user-for-woocommerce' ) . '</a>';

		return array(
			array(
				'title'    => __( 'Limits by Formula', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'This section introduces an alternative method for setting limits.', 'maximum-products-per-user-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_formula_options',
			),
			array(
				'title'    => __( 'Limits by formula', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'desc_tip' => apply_filters( 'alg_wc_mppu_settings', sprintf( 'You will need %s plugin to enable "max quantity by formula" option.',
					'<a target="_blank" href="https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/">' . 'Maximum Products per User for WooCommerce Pro' . '</a>' ) ),
				'id'       => 'alg_wc_mppu_formula_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_mppu_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'    => __( 'Formula', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => __( 'One shortcode per line.', 'maximum-products-per-user-for-woocommerce' ),
				'id'       => 'alg_wc_mppu_formula',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:100%;min-height:200px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_formula_options',
			),
			array(
				'title'    => __( 'Notes', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<ul>' . '<li>' . implode ( '</li><li>', array(
					'* ' . sprintf( __( 'You need to use %s shortcode here.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>[alg_wc_mppu]</code>' ),
					'* ' . __( 'One shortcode per line.', 'maximum-products-per-user-for-woocommerce' ),
					'* ' . __( 'Algorithm stops when first matching shortcode is found (from top to bottom).', 'maximum-products-per-user-for-woocommerce' ),
					'* ' . sprintf( __( 'Available shortcode attributes: %s.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>' . implode( '</code>, <code>', array( 'user_id', 'user_role', 'membership_plan', 'product_id', 'term_id', 'product_sku', 'is_downloadable', 'is_virtual', 'limit' ) ) . '</code>' ),
					'* ' . sprintf( __( '%s attribute is required.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>limit</code>' ),
					'* ' . sprintf( __( 'You need to enable %s checkbox in "%s" section to use %s attributes.', 'maximum-products-per-user-for-woocommerce' ),
						__( '"Per product"', 'maximum-products-per-user-for-woocommerce' ),
						$limits_section_link,
						'<code>' . implode( '</code>, <code>', array( 'product_id', 'product_sku', 'is_downloadable', 'is_virtual' ) ) . '</code>' ),
					'* ' . sprintf( __( 'You need to enable %s checkbox in "%s" section to use %s attribute.', 'maximum-products-per-user-for-woocommerce' ),
						__( '"Per product category" and/or "Per product tag"', 'maximum-products-per-user-for-woocommerce' ),
						$limits_section_link,
						'<code>term_id</code>' ),
					'* ' . sprintf( __( 'You can not use %s (or %s) and %s simultaneously in one shortcode.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>product_id</code>',
						'<code>product_sku</code>/<code>is_downloadable</code>/<code>is_virtual</code>',
						'<code>term_id</code>' ),
					'* ' . sprintf( __( 'You can set shortcode effective date(s) with %s (and %s) attributes.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>' . implode( '</code>, <code>', array( 'start_date', 'end_date' ) ) . '</code>',
						'<code>not_date_limit</code>' ),
				) ) . '</li>' . '</ul>',
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_formula_notes_options',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_formula_notes_options',
			),
			array(
				'title'    => __( 'Examples', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => '<ul>' . '<li>' . implode( '</li><li>', array(
					'* ' . '<code>[alg_wc_mppu limit="18" user_id="2,5"]</code>' . ' - ' .
						'<em>' . sprintf( __( 'Sets maximum limit to %s for users %s and %s.', 'maximum-products-per-user-for-woocommerce' ),
							'<code>18</code>', '<code>2</code>', '<code>5</code>' ) . '</em>',
					'* ' . '<code>[alg_wc_mppu limit="18" user_id="2,5" product_id="100,110"]</code>' . ' - ' .
						'<em>' . sprintf( __( 'Sets maximum limit to %s for products %s and %s for users %s and %s.', 'maximum-products-per-user-for-woocommerce' ),
							'<code>18</code>', '<code>100</code>', '<code>110</code>', '<code>2</code>', '<code>5</code>' ) . '</em>',
				) ). '</li>' . '</ul>',
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_formula_examples_options',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_formula_examples_options',
			),
		);

	}

}

endif;

return new Alg_WC_MPPU_Settings_Formula();
