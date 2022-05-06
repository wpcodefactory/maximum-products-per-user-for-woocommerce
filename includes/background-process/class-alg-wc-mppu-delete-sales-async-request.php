<?php
/**
 * Maximum Products per User for WooCommerce - Async request - Delete sales.
 *
 * @version 3.6.4
 * @since   3.6.4
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/libraries/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/libraries/wp-background-process.php';
}

if ( ! class_exists( 'Alg_WC_MPPU_Delete_Sales_Async_Request' ) ) {

	class Alg_WC_MPPU_Delete_Sales_Async_Request extends WP_Async_Request {

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