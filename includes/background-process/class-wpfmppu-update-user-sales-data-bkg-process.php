<?php
/**
 * Maximum Products per User for WooCommerce - Background Process - Update user sales data.
 *
 * @version 4.5.0
 * @since   3.8.1
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Update_User_Sales_data_Bkg_Process' ) ) {

	class WPFMPPU_Update_User_Sales_data_Bkg_Process extends WPFMPPU_Bkg_Process {

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
		 * @version 4.5.0
		 * @since   3.8.1
		 *
		 * @param mixed $item
		 *
		 * @return bool|mixed
		 */
		protected function task( $item ) {
			wpfmppu()->core->users->update_orders_data( $item );
			$logger = wc_get_logger();
			$logger->info(
				sprintf(
					/* Translators: %s: Order IDs. */
					__( 'Orders processed: %s.', 'maximum-products-per-user-for-woocommerce' ),
					implode( ", ", array_keys( $item['order_data'] ) )
				),
				array( 'source' => $this->get_logger_context() )
			);
			return false;
		}

	}
}