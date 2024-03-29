<?php
/**
 * Maximum Products per User for WooCommerce - Section Settings.
 *
 * @version 4.1.4
 * @since   1.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings_Section' ) ) :

class Alg_WC_MPPU_Settings_Section {

	/**
	 * id.
	 *
	 * @since   4.1.4
	 *
	 * @var
	 */
	protected $id;

	/**
	 * desc.
	 *
	 * @since   4.1.4
	 *
	 * @var
	 */
	protected $desc;

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
	 * format_notes.
	 *
	 * @version 3.8.7
	 * @since   3.8.7
	 */
	function format_notes( $notes ) {
		return '<div class="alg-wc-mppu-notes-wrapper"><div class="alg-wc-mppu-note">' . implode( '</div><div class="alg-wc-mppu-note">', $notes ) . '</div></div>';
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
	 * convert_array_to_string.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 *
	 * @param $arr
	 * @param array $args
	 *
	 * @return string
	 */
	function convert_array_to_string( $arr, $args = array() ) {
		$args            = wp_parse_args( $args, array(
			'glue'          => ', ',
			'item_template' => '{value}' //  {key} and {value} allowed
		) );
		$transformed_arr = array_map( function ( $key, $value ) use ( $args ) {
			$item = str_replace( array( '{key}', '{value}' ), array( $key, $value ), $args['item_template'] );
			return $item;
		}, array_keys( $arr ), $arr );
		return implode( $args['glue'], $transformed_arr );
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
