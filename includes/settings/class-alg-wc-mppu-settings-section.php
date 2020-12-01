<?php
/**
 * Maximum Products per User for WooCommerce - Section Settings
 *
 * @version 3.3.0
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
	 * @version 3.0.0
	 * @since   3.0.0
	 * @todo    [maybe] add "... for older orders..."?
	 */
	function get_recalculate_sales_data_desc( $option ) {
		return sprintf( __( 'You will need to %s after changing "%s" option.', 'maximum-products-per-user-for-woocommerce' ),
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu&section=tools' ) . '">' .
				__( 'Recalculate sales data', 'maximum-products-per-user-for-woocommerce' ) . '</a>',
			$option
		);
	}

}

endif;
