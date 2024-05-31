<?php
/**
 * Maximum Products per User for WooCommerce - Multi-language.
 *
 * @version 4.1.8
 * @since   3.5.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Multi_Language' ) ) :

class Alg_WC_MPPU_Multi_Language {

	/**
	 * get_product_id_from_main_language.
	 *
	 * @since 3.5.9
	 *
	 * @var bool
	 */
	protected $get_product_id_from_main_language = null;

	/**
	 * $default_language.
	 *
	 * @since 4.1.8
	 */
	protected $default_language;

	/**
	 * Constructor.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function __construct() {
		if ( 'no' != ( $multi_language = get_option( 'alg_wc_mppu_multi_language', 'no' ) ) ) {
			add_filter( 'alg_wc_mppu_data_product_or_term_id', array( $this, $multi_language ), 10, 2 );
		}
	}

	/**
	 * polylang.
	 *
	 * @version 3.6.5
	 * @since   3.5.0
	 * @see     https://polylang.wordpress.com/documentation/documentation-for-developers/functions-reference/
	 * @see     https://wordpress.stackexchange.com/questions/302550/get-the-id-of-the-default-language-equivalent-page-in-polylang
	 * @todo    [maybe] `pll_get_post_translations( $product_or_term_id )`
	 */
	function polylang( $product_or_term_id, $is_product ) {
		// Get default language
		if ( ! isset( $this->default_language ) ) {
			$this->default_language = ( function_exists( 'pll_default_language' ) ? pll_default_language() : null );
		}
		// Get product or term ID
		return ( $this->default_language ?
			( $is_product ?
				( function_exists( 'pll_get_post' ) && is_int( $translated_post_id = pll_get_post( $product_or_term_id, $this->default_language ) ) ? $translated_post_id : $product_or_term_id ) :
				( function_exists( 'pll_get_term' ) && is_int( $translated_term_id = pll_get_term( $product_or_term_id, $this->default_language ) ) ? $translated_term_id : $product_or_term_id )
			) :
			$product_or_term_id
		);
	}

	/**
	 * wpml.
	 *
	 * @version 4.1.8
	 * @since   3.5.0
	 * @see     https://wpml.org/wpml-hook/wpml_object_id/
	 * @see     https://wpml.org/documentation/support/creating-multilingual-wordpress-themes/language-dependent-ids/
	 * @todo    [maybe] `icl_object_id( $product_or_term_id, $element_type, true, $default_language );`
	 */
	function wpml( $product_or_term_id, $is_product ) {
		// Get default language.
		if ( ! isset( $this->default_language ) ) {
			$this->default_language = null;
			global $sitepress;
			if ( $sitepress ) {
				$this->default_language = $sitepress->get_default_language();
			} elseif ( function_exists( 'icl_get_setting' ) ) {
				$this->default_language = icl_get_setting( 'default_language' );
			}
		}
		// Get product or term ID.
		if ( $this->default_language ) {
			// Get element type.
			if ( $is_product ) {
				$element_type = 'product';
			} else {
				$term = get_term( $product_or_term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$element_type = $term->taxonomy;
				}
			}
			// Get element ID.
			if ( isset( $element_type ) && $element_type ) {
				return apply_filters( 'wpml_object_id', $product_or_term_id, $element_type, true, $this->default_language );
			}
		}
		// Return original ID
		return $product_or_term_id;
	}

	/**
	 * get_product_id_from_main_language.
	 *
	 * @version 3.5.9
	 * @since   3.5.9
	 *
	 * @return bool
	 */
	function get_product_id_from_main_language() {
		if ( null === $this->get_product_id_from_main_language ) {
			$this->get_product_id_from_main_language = 'yes' === get_option( 'alg_wc_mppu_multi_language_use_main_prod_id_on_checking_limits', 'no' );
		}
		return $this->get_product_id_from_main_language;
	}

}

endif;

return new Alg_WC_MPPU_Multi_Language();
