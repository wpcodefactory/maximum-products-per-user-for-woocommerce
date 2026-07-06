<?php
/**
 * Maximum Products per User for WooCommerce - Deprecated Hooks.
 *
 * Adds backward-compatible aliases for hooks renamed in version 4.5.0.
 * Old hook names (alg_wc_mppu_*) are forwarded to new hook names (wpfmppu_*).
 *
 * @version 4.5.0
 * @since   4.5.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

/**
 * wpfmppu_deprecated_hook_map.
 *
 * Returns the mapping of old hook names to new hook names.
 *
 * @version 4.5.0
 * @since   4.5.0
 *
 * @return array
 */
function wpfmppu_deprecated_hook_map() {
	return array(
		'alg_mc_mppu_get_monthly_range_origin_date'           => 'wpfmppu_get_monthly_range_origin_date',
		'alg_wc_mppu_after_save_settings'                     => 'wpfmppu_after_save_settings',
		'alg_wc_mppu_before_block_guest_on_add_to_cart'       => 'wpfmppu_before_block_guest_on_add_to_cart',
		'alg_wc_mppu_bkg_process_email_params'                => 'wpfmppu_bkg_process_email_params',
		'alg_wc_mppu_calculate_data_wc_get_orders_args'       => 'wpfmppu_calculate_data_wc_get_orders_args',
		'alg_wc_mppu_cart_item_amount'                        => 'wpfmppu_cart_item_amount',
		'alg_wc_mppu_check_quantities_for_product'            => 'wpfmppu_check_quantities_for_product',
		'alg_wc_mppu_core_loaded'                             => 'wpfmppu_core_loaded',
		'alg_wc_mppu_data_product_or_term_id'                 => 'wpfmppu_data_product_or_term_id',
		'alg_wc_mppu_date_range'                              => 'wpfmppu_date_range',
		'alg_wc_mppu_date_to_check'                           => 'wpfmppu_date_to_check',
		'alg_wc_mppu_datetime_to_compare'                     => 'wpfmppu_datetime_to_compare',
		'alg_wc_mppu_get_cart_item_amount_by_parent'          => 'wpfmppu_get_cart_item_amount_by_parent',
		'alg_wc_mppu_get_cart_item_amount_by_term'            => 'wpfmppu_get_cart_item_amount_by_term',
		'alg_wc_mppu_get_cart_item_quantities'                => 'wpfmppu_get_cart_item_quantities',
		'alg_wc_mppu_get_first_order_date_exp'                => 'wpfmppu_get_first_order_date_exp',
		'alg_wc_mppu_get_max_qty'                             => 'wpfmppu_get_max_qty',
		'alg_wc_mppu_get_notice_placeholders'                 => 'wpfmppu_get_notice_placeholders',
		'alg_wc_mppu_is_product_blocked_for_guests'           => 'wpfmppu_is_product_blocked_for_guests',
		'alg_wc_mppu_local_enabled'                           => 'wpfmppu_local_enabled',
		'alg_wc_mppu_max_qty_by_formula'                      => 'wpfmppu_max_qty_by_formula',
		'alg_wc_mppu_on_activation'                           => 'wpfmppu_on_activation',
		'alg_wc_mppu_on_deactivation'                         => 'wpfmppu_on_deactivation',
		'alg_wc_mppu_order_data_saved'                        => 'wpfmppu_order_data_saved',
		'alg_wc_mppu_orders_data_increase_qty'                => 'wpfmppu_orders_data_increase_qty',
		'alg_wc_mppu_output_notices_args'                     => 'wpfmppu_output_notices_args',
		'alg_wc_mppu_product_cat_enabled'                     => 'wpfmppu_product_cat_enabled',
		'alg_wc_mppu_product_tag_enabled'                     => 'wpfmppu_product_tag_enabled',
		'alg_wc_mppu_profile_page_table_row'                  => 'wpfmppu_profile_page_table_row',
		'alg_wc_mppu_save_quantities_item_qty'                => 'wpfmppu_save_quantities_item_qty',
		'alg_wc_mppu_settings'                                => 'wpfmppu_settings',
		'alg_wc_mppu_totals_data'                             => 'wpfmppu_totals_data',
		'alg_wc_mppu_totals_data_decrease_qty'                => 'wpfmppu_totals_data_decrease_qty',
		'alg_wc_mppu_totals_data_increase_qty'                => 'wpfmppu_totals_data_increase_qty',
		'alg_wc_mppu_user_already_bought'                     => 'wpfmppu_user_already_bought',
		'alg_wc_mppu_user_already_bought_do_count_order'      => 'wpfmppu_user_already_bought_do_count_order',
		'alg_wc_mppu_user_already_bought_validation'          => 'wpfmppu_user_already_bought_validation',
		'alg_wc_mppu_user_product_limits_item_validation'     => 'wpfmppu_user_product_limits_item_validation',
		'alg_wc_mppu_user_product_limits_query_args'          => 'wpfmppu_user_product_limits_query_args',
		'alg_wc_mppu_user_terms_limits_item_validation'       => 'wpfmppu_user_terms_limits_item_validation',
		'alg_wc_mppu_validate_on_add_to_cart_quantity'        => 'wpfmppu_validate_on_add_to_cart_quantity',
		'alg_wc_mppu_validate_on_add_to_cart_quantity_do_add' => 'wpfmppu_validate_on_add_to_cart_quantity_do_add',
	);
}

/**
 * Register deprecated hook aliases.
 *
 * For each old hook, we hook into the new hook and forward any callbacks
 * registered on the old hook name. Also, when the new hook fires, any
 * callbacks registered on the old hook are called via apply_filters on the
 * old name so external code using the old hook names continues to work.
 *
 * @version 4.5.0
 * @since   4.5.0
 */
add_action( 'init', function () {
	foreach ( wpfmppu_deprecated_hook_map() as $old => $new ) {
		$deprecated_version = '4.5.0';

		// Forward filters: if someone hooked into the old name, pipe through new name
		add_filter( $new, function () use ( $old, $new, $deprecated_version ) {
			$args = func_get_args();
			// Only forward if there are actually callbacks on the old hook
			if ( has_filter( $old ) ) {
				_deprecated_hook( esc_html( $old ), esc_html( $deprecated_version ), esc_html( $new ) );

				return call_user_func_array( 'apply_filters', array_merge( array( $old ), $args ) );
			}

			return $args[0];
		}, 5, PHP_INT_MAX );

		// Forward actions: if someone hooked into the old action name
		add_action( $new, function () use ( $old, $new, $deprecated_version ) {
			$args = func_get_args();
			if ( has_action( $old ) ) {
				_deprecated_hook( esc_html( $old ), esc_html( $deprecated_version ), esc_html( $new ) );
				call_user_func_array( 'do_action', array_merge( array( $old ), $args ) );
			}

			return ( isset( $args[0] ) ? $args[0] : null );
		}, 5, PHP_INT_MAX );
	}
}, 1 );