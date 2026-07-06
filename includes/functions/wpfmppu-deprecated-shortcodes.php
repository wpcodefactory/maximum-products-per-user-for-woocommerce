<?php
/**
 * Maximum Products per User for WooCommerce - Deprecated Shortcodes.
 *
 * Adds backward-compatible aliases for shortcodes renamed in version 4.5.0.
 * Old shortcode tags (alg_wc_mppu_*) are forwarded to new tags (wpfmppu_*).
 *
 * @version 4.5.0
 * @since   4.5.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

/**
 * wpfmppu_deprecated_shortcode_map.
 *
 * Returns the mapping of old shortcode tags to new shortcode tags.
 *
 *
 * @since   4.5.0
 * @version 4.5.0
 *
 * @return array
 */
function wpfmppu_deprecated_shortcode_map() {
	return array(
		'alg_wc_mppu'                          => 'wpfmppu',
		'alg_wc_mppu_max_qty'                  => 'wpfmppu_max_qty',
		'alg_wc_mppu_user_bought'              => 'wpfmppu_user_bought',
		'alg_wc_mppu_translate'                => 'wpfmppu_translate',
		'alg_wc_mppu_current_product_limit'    => 'wpfmppu_current_product_limit',
		'alg_wc_mppu_current_product_quantity' => 'wpfmppu_current_product_quantity',
		'alg_wc_mppu_term_limit'               => 'wpfmppu_term_limit',
		'alg_wc_mppu_placeholder'              => 'wpfmppu_placeholder',
		'alg_wc_mppu_customer_msg'             => 'wpfmppu_customer_msg',
		'alg_wc_mppu_user_product_quantities'  => 'wpfmppu_user_product_quantities',
		'alg_wc_mppu_user_product_limits'      => 'wpfmppu_user_product_limits',
		'alg_wc_mppu_user_terms_limits'        => 'wpfmppu_user_terms_limits',
	);
}

/**
 * Register deprecated shortcode aliases.
 *
 * Each old shortcode tag is registered and, when used, forwards its call to
 * the new shortcode tag while notifying developers via _deprecated_function().
 *
 * Priority 20 ensures the new shortcodes (registered on init at default
 * priority) are already in place before the forwarding callbacks run.
 *
 * @version 4.5.0
 * @since   4.5.0
 */
add_action( 'init', function () {
	foreach ( wpfmppu_deprecated_shortcode_map() as $old => $new ) {
		add_shortcode( $old, function ( $atts, $content, $tag ) use ( $old, $new ) {
			global $shortcode_tags;

			_deprecated_function(
				'[' . esc_html( $old ) . ']',
				'4.5.0',
				'[' . esc_html( $new ) . ']'
			);

			if ( isset( $shortcode_tags[ $new ] ) ) {
				return call_user_func( $shortcode_tags[ $new ], $atts, $content, $new );
			}

			return '';
		} );
	}
}, 20 );