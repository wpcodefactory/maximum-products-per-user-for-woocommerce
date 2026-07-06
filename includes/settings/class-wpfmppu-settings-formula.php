<?php
/**
 * Maximum Products per User for WooCommerce - Formula Section Settings.
 *
 * @version 4.5.0
 * @since   2.3.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Settings_Formula' ) ) :

class WPFMPPU_Settings_Formula extends WPFMPPU_Settings_Section {

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
	 * @version 4.5.0
	 * @since   2.3.0
	 * @todo    [next] (desc) Examples: add more examples
	 * @todo    [later] (desc) Examples: add link to site ("Check plugin site for more examples")
	 * @todo    [maybe] (desc) Notes: `payment_method`: better desc?
	 */
	function get_settings() {
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
				'id'       => 'alg_wc_mppu_formula_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'wpfmppu_settings', array( 'disabled' => 'disabled' ) ),
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
				'desc' => $this->format_notes( array(
					sprintf(
						/* Translators: %s: Shortcode. */
						__( 'You need to use %s shortcode here.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>[wpfmppu]</code>'
					),

					__( 'One shortcode per line.', 'maximum-products-per-user-for-woocommerce' ),

					__( 'Algorithm stops when first matching shortcode is found (from top to bottom).', 'maximum-products-per-user-for-woocommerce' ),

					sprintf(
						/* Translators: %s: Shortcode attributes. */
						__( 'Available shortcode attributes: %s.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>' . implode( '</code>, <code>', array(
							'user_id',
							'user_role',
							'membership_plan',
							'memberpress_plan_id',
							'sumo_membership_plan',
							'swpm_membership_id',
							'payment_method',
							'product_id',
							'term_id',
							'product_sku',
							'is_downloadable',
							'is_virtual',
							'limit',
							'product_limit_meta'
						) ) . '</code>'
					),

					sprintf(
						/* Translators: %s: Shortcode attribute. */
						__( '%s attribute is required.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>limit</code>'
					),

					sprintf(
						/* Translators: %1$s: Option name, %2$s: Link, %3$s: Shortcode attributes. */
						__( 'You need to enable "%1$s" checkbox in "%2$s" section to use %3$s attributes.', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Per product', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'limits' ),
						'<code>' . implode( '</code>, <code>', array(
							'product_id',
							'product_sku',
							'is_downloadable',
							'is_virtual'
						) ) . '</code>'
					),

					sprintf(
						/* Translators: %1$s: Option name, %2$s: Option name, %3$s: Link, %4$s: Shortcode attribute. */
						__( 'You need to enable "%1$s" and/or "%2$s" checkbox in "%3$s" section to use %4$s attribute.', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Per product category', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Per product tag', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'limits' ),
						'<code>term_id</code>'
					),

					sprintf(
						/* Translators: %1$s: Option label, %2$s: Link, %3$s: Shortcode attribute. */
						__( 'You need to enable "%1$s" checkbox in "%2$s" section to use %3$s attribute.', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Count by current payment method', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'general' ),
						'<code>payment_method</code>'
					) . ' ' .
					sprintf(
						/* Translators: %1$s: Option name, %2$s: Option name, %3$s: Link. */
						__( 'You may also want to disable "%1$s" and "%2$s" options in the "%3$s" section, so your customer could change the payment method on exceeded limits.', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Validate on add to cart', 'maximum-products-per-user-for-woocommerce' ),
						__( 'Block checkout page', 'maximum-products-per-user-for-woocommerce' ),
						$this->get_section_link( 'frontend' )
					),

					sprintf(
						/* Translators: %1$s: Shortcode attribute, %2$s: Shortcode attributes, %3$s: Shortcode attribute. */
						__( 'You can not use %1$s (or %2$s) and %3$s simultaneously in one shortcode.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>product_id</code>',
						'<code>product_sku</code>/<code>is_downloadable</code>/<code>is_virtual</code>',
						'<code>term_id</code>'
					),

					sprintf(
						/* Translators: %1$s: Shortcode attributes, %2$s: Shortcode attribute. */
						__( 'You can set shortcode effective date(s) with %1$s (and %2$s) attributes.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>' . implode( '</code>, <code>', array( 'start_date', 'end_date' ) ) . '</code>',
						'<code>not_date_limit</code>'
					),

					sprintf(
						/* Translators: %1$s: Shortcode attribute, %2$s: Shortcode attribute. */
						__( 'You can only use %1$s combined with %2$s.', 'maximum-products-per-user-for-woocommerce' ),
						'<code>product_limit_meta</code>',
						'<code>product_id</code>'
					),
				) ),
				'type'     => 'title',
				'id'       => 'alg_wc_mppu_formula_notes_options',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_mppu_formula_notes_options',
			),
			array(
				'title'    => __( 'Examples', 'maximum-products-per-user-for-woocommerce' ),
				'desc'     => $this->format_notes(
					array(
						'<code>[wpfmppu limit="18" user_id="2,5"]</code>' . ' - ' .
						'<em>' . sprintf(
							/* Translators: %1$s: Limit value, %2$s: User ID, %3$s: User ID. */
							__( 'Sets maximum limit to %1$s for users %2$s and %3$s.', 'maximum-products-per-user-for-woocommerce' ),
							'<code>18</code>',
							'<code>2</code>',
							'<code>5</code>'
						) .
						'</em>',
						'<code>[wpfmppu limit="18" user_id="2,5" product_id="100,110"]</code>' . ' - ' .
						'<em>' . sprintf(
							/* Translators: %1$s: Limit value, %2$s: Product ID, %3$s: Product ID, %4$s: User ID, %5$s: User ID. */
							__( 'Sets maximum limit to %1$s for products %2$s and %3$s for users %4$s and %5$s.', 'maximum-products-per-user-for-woocommerce' ),
							'<code>18</code>', '<code>100</code>', '<code>110</code>', '<code>2</code>', '<code>5</code>'
						) . '</em>',
					)
				),
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

return new WPFMPPU_Settings_Formula();
