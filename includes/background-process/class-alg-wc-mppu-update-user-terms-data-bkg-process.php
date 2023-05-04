<?php
/**
 * Maximum Products per User for WooCommerce - Background Process - Update user terms data.
 *
 * @version 3.8.6
 * @since   3.8.6
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Update_User_Terms_data_Bkg_Process' ) ) {
	class Alg_WC_MPPU_Update_User_Terms_data_Bkg_Process extends Alg_WC_MPPU_Bkg_Process {

		/**
		 * action.
		 *
		 * @since 3.8.6
		 *
		 * @var string
		 */
		protected $action = 'alg_wc_mppu_update_user_terms_data_bkg_process';

		/**
		 * get_action_label.
		 *
		 * @version 3.8.6
		 * @since   3.8.6
		 *
		 * @return string
		 */
		protected function get_action_label() {
			return __( 'Maximum Products per User - Update user terms data', 'maximum-products-per-user-for-woocommerce' );
		}

		/**
		 * task.
		 *
		 * @version 3.8.6
		 * @since   3.8.6
		 *
		 * @param mixed $item
		 *
		 * @return bool|mixed
		 */
		protected function task( $item ) {
			alg_wc_mppu()->core->users->update_terms_data( $item );
			$logger = wc_get_logger();
			$user = get_user_by('ID',$item['user_id']);
			$logger->info( sprintf( __( 'Term "%s" updated from user %s.', 'maximum-products-per-user-for-woocommerce' ), $item['term']->name, $user->display_name ), array( 'source' => $this->action ) );
			return false;
		}

	}
}