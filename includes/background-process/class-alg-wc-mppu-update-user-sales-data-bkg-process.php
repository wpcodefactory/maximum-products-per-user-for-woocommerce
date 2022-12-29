<?php
/**
 * Maximum Products per User for WooCommerce - Background Process - Update user sales data.
 *
 * @version 3.8.1
 * @since   3.8.1
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Update_User_Sales_data_Bkg_Process' ) ) {

	class Alg_WC_MPPU_Update_User_Sales_data_Bkg_Process extends Alg_WC_MPPU_Bkg_Process {

		/**
		 * action.
		 *
		 * @since 3.8.1
		 *
		 * @var string
		 */
		protected $action = 'alg_wc_mppu_update_user_sales_data_bkg_process';

		/**
		 * get_action_label.
		 *
		 * @version 3.8.1
		 * @since   3.8.1
		 *
		 * @return string
		 */
		protected function get_action_label() {
			return __( 'Maximum Products per User - Update user sales data', 'maximum-products-per-user-for-woocommerce' );
		}

		/**
		 * task.
		 *
		 * @version 3.8.1
		 * @since   3.8.1
		 *
		 * @param mixed $item
		 *
		 * @return bool|mixed
		 */
		protected function task( $item ) {
			alg_wc_mppu()->core->users->update_orders_data( $item );
			$logger = wc_get_logger();
			$logger->info(
				sprintf( __( 'Orders processed: %s.', 'maximum-products-per-user-for-woocommerce' ), implode( ", ", array_keys( $item['order_data'] ) ) ),
				array( 'source' => $this->get_logger_context() )
			);
			return false;
		}

	}
}