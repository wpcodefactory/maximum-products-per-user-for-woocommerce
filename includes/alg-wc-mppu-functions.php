<?php
/**
 * Maximum Products per User for WooCommerce - Functions
 *
 * @version 2.4.0
 * @since   2.4.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'alg_wc_mppu_get_all_user_roles' ) ) {
	/**
	 * alg_wc_mppu_get_all_user_roles.
	 *
	 * @version 2.4.0
	 * @since   2.2.0
	 * @todo    [next] add "enabled user roles" option
	 * @todo    [maybe] `$role = ( 'super_admin' == $role ? 'administrator' : $role );`
	 */
	function alg_wc_mppu_get_all_user_roles() {
		global $wp_roles;
		$all_roles = apply_filters( 'editable_roles', ( isset( $wp_roles ) && is_object( $wp_roles ) ? $wp_roles->roles : array() ) );
		return ( ! empty( $all_roles ) ? wp_list_pluck( $all_roles, 'name' ) : array() );
	}
}
