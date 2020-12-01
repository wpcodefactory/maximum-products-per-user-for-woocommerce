<?php
/**
 * Maximum Products per User for WooCommerce - Settings
 *
 * @version 3.0.0
 * @since   1.0.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Settings' ) ) :

class Alg_WC_MPPU_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id    = 'alg_wc_mppu';
		$this->label = __( 'Maximum Products per User', 'maximum-products-per-user-for-woocommerce' );
		parent::__construct();
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'maybe_unclean_field' ), PHP_INT_MAX, 3 );
		// Sections
		require_once( 'class-alg-wc-mppu-settings-section.php' );
		require_once( 'class-alg-wc-mppu-settings-general.php' );
		require_once( 'class-alg-wc-mppu-settings-limits.php' );
		require_once( 'class-alg-wc-mppu-settings-formula.php' );
		require_once( 'class-alg-wc-mppu-settings-frontend.php' );
		require_once( 'class-alg-wc-mppu-settings-admin.php' );
		require_once( 'class-alg-wc-mppu-settings-tools.php' );
		require_once( 'class-alg-wc-mppu-settings-advanced.php' );
	}

	/**
	 * maybe_unclean_field.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 * @todo    [later] find better solution
	 */
	function maybe_unclean_field( $value, $option, $raw_value ) {
		return ( isset( $option['alg_wc_mppu_raw'] ) && $option['alg_wc_mppu_raw'] ? $raw_value : $value );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.6.0
	 * @since   1.0.0
	 */
	function get_settings() {
		global $current_section;
		return array_merge( apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() ), array(
			array(
				'title'     => __( 'Reset Settings', 'maximum-products-per-user-for-woocommerce' ),
				'type'      => 'title',
				'id'        => $this->id . '_' . $current_section . '_reset_options',
			),
			array(
				'title'     => __( 'Reset section settings', 'maximum-products-per-user-for-woocommerce' ),
				'desc'      => '<strong>' . __( 'Reset', 'maximum-products-per-user-for-woocommerce' ) . '</strong>',
				'desc_tip'  => __( 'Check the box and save changes to reset.', 'maximum-products-per-user-for-woocommerce' ),
				'id'        => $this->id . '_' . $current_section . '_reset',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => $this->id . '_' . $current_section . '_reset_options',
			),
		) );
	}

	/**
	 * maybe_reset_settings.
	 *
	 * @version 3.0.0
	 * @since   1.0.0
	 */
	function maybe_reset_settings() {
		global $current_section;
		if ( 'yes' === get_option( $this->id . '_' . $current_section . '_reset', 'no' ) ) {
			foreach ( $this->get_settings() as $value ) {
				if ( isset( $value['id'] ) ) {
					$id = explode( '[', $value['id'] );
					delete_option( $id[0] );
				}
			}
			if ( method_exists( 'WC_Admin_Settings', 'add_message' ) ) {
				WC_Admin_Settings::add_message( __( 'Your settings have been reset.', 'maximum-products-per-user-for-woocommerce' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'admin_notice_settings_reset' ) );
			}
		}
	}

	/**
	 * admin_notice_settings_reset.
	 *
	 * @version 1.1.2
	 * @since   1.1.2
	 */
	function admin_notice_settings_reset() {
		echo '<div class="notice notice-warning is-dismissible"><p><strong>' .
			__( 'Your settings have been reset.', 'maximum-products-per-user-for-woocommerce' ) . '</strong></p></div>';
	}

	/**
	 * Save settings.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function save() {
		parent::save();
		$this->maybe_reset_settings();
		do_action( 'alg_wc_mppu_after_save_settings' );
	}

}

endif;

return new Alg_WC_MPPU_Settings();
