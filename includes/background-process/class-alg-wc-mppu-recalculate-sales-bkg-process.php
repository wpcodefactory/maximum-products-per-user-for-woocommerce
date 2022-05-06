<?php
/**
 * Maximum Products per User for WooCommerce - Background Process - Recalculate sales.
 *
 * @version 3.6.4
 * @since   3.6.4
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Recalculate_Sales_Bkg_Process' ) ) {

	class Alg_WC_MPPU_Recalculate_Sales_Bkg_Process extends Alg_WC_MPPU_Bkg_Process {

		/**
		 * action.
		 *
		 * @since 3.6.4
		 *
		 * @var string
		 */
		protected $action = 'alg_wc_mppu_recalculate_sales_bkg_process';

		/**
		 * get_action_label.
		 *
		 * @version 3.6.4
		 * @since   3.6.4
		 *
		 * @return string
		 */
		protected function get_action_label() {
			return __( 'Maximum Products per User - Recalculate sales data', 'maximum-products-per-user-for-woocommerce' );
		}

		/**
		 * task.
		 *
		 * @version 3.6.4
		 * @since   3.6.4
		 *
		 * @param mixed $item
		 *
		 * @return bool|mixed
		 */
		protected function task( $item ) {
			alg_wc_mppu()->core->data->save_quantities( $item['order_id'] );
			$logger = wc_get_logger();
			$logger->info( sprintf( __( 'Order processed: %d.', 'maximum-products-per-user-for-woocommerce' ), $item['order_id'] ), array( 'source' => $this->get_logger_context() ) );
			return false;
		}

	}
}