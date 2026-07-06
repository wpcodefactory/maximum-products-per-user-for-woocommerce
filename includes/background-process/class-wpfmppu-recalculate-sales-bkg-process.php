<?php
/**
 * Maximum Products per User for WooCommerce - Background Process - Recalculate sales.
 *
 * @version 4.5.0
 * @since   3.6.4
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Recalculate_Sales_Bkg_Process' ) ) {

	class WPFMPPU_Recalculate_Sales_Bkg_Process extends WPFMPPU_Bkg_Process {

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
		 * @version 4.5.0
		 * @since   3.6.4
		 *
		 * @param mixed $item
		 *
		 * @return bool|mixed
		 */
		protected function task( $item ) {
			wpfmppu()->core->data->save_quantities( $item['order_id'] );
			$logger = wc_get_logger();
			$logger->info(
				sprintf(
					/* Translators: %d: Order ID. */
					__( 'Order processed: %d.', 'maximum-products-per-user-for-woocommerce' ),
					$item['order_id']
				),
				array( 'source' => $this->get_logger_context() )
			);
			return false;
		}

	}
}