<?php
/**
 * Maximum Products per User for WooCommerce - Async request - Delete sales.
 *
 * @version 4.4.1
 * @since   3.6.4
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_DeliciousBrains_Async_Request', false ) ) {
	require_once( alg_wc_mppu()->plugin_path() . '/includes/background-process/class-alg-wc-mppu-deliciousbrains-async-request.php' );
}

if ( ! class_exists( 'Alg_WC_MPPU_DeliciousBrains_Background_Process', false ) ) {
	require_once( alg_wc_mppu()->plugin_path() . '/includes/background-process/class-alg-wc-mppu-deliciousbrains-background-process.php' );
}

if ( ! class_exists( 'Alg_WC_MPPU_Delete_Sales_Async_Request' ) ) {

	class Alg_WC_MPPU_Delete_Sales_Async_Request extends Alg_WC_MPPU_DeliciousBrains_Async_Request {

		/**
		 * action.
		 *
		 * @since 3.6.4
		 *
		 * @var string
		 */
		protected $action = 'alg_wc_mppu_delete_sales_async_request';

		/**
		 * Handle.
		 *
		 * @version 3.6.4
		 * @since   3.6.4
		 *
		 */
		protected function handle() {
			$amount = alg_wc_mppu()->core->data->delete_meta_data();
			$logger = wc_get_logger();
			$logger->info( sprintf( _n( '%d meta deleted.', '%d metas deleted.', $amount, 'maximum-products-per-user-for-woocommerce' ), $amount ), array( 'source' => $this->action ) );
		}
	}
}