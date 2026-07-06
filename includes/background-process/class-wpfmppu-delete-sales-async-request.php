<?php
/**
 * Maximum Products per User for WooCommerce - Async request - Delete sales.
 *
 * @version 4.5.0
 * @since   3.6.4
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_DeliciousBrains_Async_Request', false ) ) {
	require_once( wpfmppu()->plugin_path() . '/includes/background-process/class-wpfmppu-deliciousbrains-async-request.php' );
}

if ( ! class_exists( 'WPFMPPU_DeliciousBrains_Background_Process', false ) ) {
	require_once( wpfmppu()->plugin_path() . '/includes/background-process/class-wpfmppu-deliciousbrains-background-process.php' );
}

if ( ! class_exists( 'WPFMPPU_Delete_Sales_Async_Request' ) ) {

	class WPFMPPU_Delete_Sales_Async_Request extends WPFMPPU_DeliciousBrains_Async_Request {

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
		 * @version 4.5.0
		 * @since   3.6.4
		 *
		 */
		protected function handle() {
			$amount = wpfmppu()->core->data->delete_meta_data();
			$logger = wc_get_logger();
			$logger->info(
				sprintf(
					/* Translators: %d: Number of metadata items. */
					_n( '%d meta deleted.', '%d metas deleted.', $amount, 'maximum-products-per-user-for-woocommerce' ),
					$amount
				),
				array( 'source' => $this->action )
			);
		}
	}
}