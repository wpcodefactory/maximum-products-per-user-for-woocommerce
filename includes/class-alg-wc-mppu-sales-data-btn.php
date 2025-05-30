<?php
/**
 * Maximum Products per User for WooCommerce - Sales data button.
 *
 * @version 4.3.8
 * @since   4.2.3
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Sales_Data_Btn' ) ) {

	class Alg_WC_MPPU_Sales_Data_Btn {

		/**
		 * Get style.
		 *
		 * @version 4.2.3
		 * @since   4.2.3
		 *
		 * @return false|string
		 */
		public function get_style() {
			ob_start();
			?>
			<style>
                .mppu-show-sales-data {
                    vertical-align: middle !important;
                }

                .mppu-show-sales-data .loading {
                    margin: 0 0 0 8px;
                    display: inline-block;
                }

                .mppu-show-sales-data .loading i {
                    line-height: 19px;
                    vertical-align: text-top;
                }

                .mppu-show-sales-data .loading.hide {
                    display: none;
                }

                @-webkit-keyframes rotating {
                    to {
                        -webkit-transform: rotate(-360deg);
                    }
                }

                .rotating {
                    -webkit-animation: rotating 1.2s linear infinite;
                }
			</style>
			<?php
			$result = ob_get_contents();
			ob_end_clean();

			return $result;
		}

		/**
		 * get_btn_html.
		 *
		 * @version 4.3.8
		 * @since   4.2.3
		 *
		 * @param $args
		 *
		 * @return string
		 */
		function get_btn_html( $args = null ) {
			$args = wp_parse_args( $args, array(
				'user_id'    => '',
				'product_id' => '',
				'action'     => '',
				'type'       => '',
				'style'      => '',
			) );

			$action     = $args['action'];
			$user_id    = intval( $args['user_id'] );
			$product_id = intval( $args['product_id'] );
			$term_id    = intval( $args['term_id'] );
			$type       = $args['type'];
			$style      = $args['style'];

			$data_attributes = array(
				'user_id'    => $user_id,
				'product_id' => $product_id,
				'term_id'    => $term_id,
				'type'       => $type,
				'action'     => $action
			);
			$data_attributes_array = array();
			foreach ( $data_attributes as $key => $value ) {
				if ( ! empty( $value ) ) {
					$data_attributes_array[] = 'data-' . $key . '="' . esc_attr( $value ) . '"';
				}
			}

			return '<button style="' . esc_attr( $style ) . '" class="mppu-show-sales-data button button-secondary" ' . implode( " ", $data_attributes_array ) . '>' . __( 'Show sales data', 'maximum-products-per-user-for-woocommerce' ) . '<span class="hide rotating loading"><i class="dashicons dashicons-image-rotate"></i></span></span></button>';
		}
	}
}