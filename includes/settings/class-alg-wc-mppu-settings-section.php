<?php
/**
 * Maximum Products per User for WooCommerce - Section Settings
 *
 * @version 3.5.0
 * @since   1.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Section' ) ) :

class Alg_WC_MPPU_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function __construct() {
		add_filter( 'woocommerce_get_sections_alg_wc_mppu',              array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_alg_wc_mppu_' . $this->id, array( $this, 'get_settings' ), PHP_INT_MAX );
	}

	/**
	 * settings_section.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->desc;
		return $sections;
	}

	/**
	 * get_section_link.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @todo    [later] get `$sections` automatically
	 */
	function get_section_link( $section, $title = '' ) {
		$section_id = ( 'general' === $section ? '' : $section );
		if ( '' === $title ) {
			$sections = array(
				''           => __( 'General', 'maximum-products-per-user-for-woocommerce' ),
				'limits'     => __( 'Limits', 'maximum-products-per-user-for-woocommerce' ),
				'formula'    => __( 'Formula', 'maximum-products-per-user-for-woocommerce' ),
				'frontend'   => __( 'Frontend', 'maximum-products-per-user-for-woocommerce' ),
				'admin'      => __( 'Admin', 'maximum-products-per-user-for-woocommerce' ),
				'tools'      => __( 'Tools', 'maximum-products-per-user-for-woocommerce' ),
				'advanced'   => __( 'Advanced', 'maximum-products-per-user-for-woocommerce' ),
			);
			$title = $sections[ $section_id ];
		}
		return '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu&section=' . $section_id ) . '">' . $title . '</a>';
	}

	/**
	 * get_placeholders_desc.
	 *
	 * @version 3.3.0
	 * @since   1.0.0
	 */
	function get_placeholders_desc( $values, $template = false ) {
		return sprintf( ( false !== $template ? $template : __( 'Placeholders: %s.', 'maximum-products-per-user-for-woocommerce' ) ),
			'<code>' . implode( '</code>, <code>', $values ) . '</code>' );
	}

	/**
	 * get_recalculate_sales_data_desc.
	 *
	 * @version 3.5.0
	 * @since   3.0.0
	 * @todo    [maybe] add "... for older orders..."?
	 */
	function get_recalculate_sales_data_desc( $option ) {
		return sprintf( __( 'You will need to %s after changing "%s" option.', 'maximum-products-per-user-for-woocommerce' ),
			$this->get_section_link( 'tools', __( 'Recalculate sales data', 'maximum-products-per-user-for-woocommerce' ) ),
			$option
		);
	}

}

endif;
