<?php
/**
 * Maximum Products per User for WooCommerce - Users.
 *
 * @version 4.2.8
 * @since   2.2.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Users' ) ) :

class Alg_WC_MPPU_Users {

	/**
	 * update user sales bkg process.
	 *
	 * @since 3.6.4
	 *
	 * @var Alg_WC_MPPU_Update_User_Sales_data_Bkg_Process
	 */
	public $update_user_sales_bkg_process;

	/**
	 * update user terms bkg process.
	 *
	 * @since 3.8.6
	 *
	 * @var Alg_WC_MPPU_Update_User_Terms_data_Bkg_Process
	 */
	public $update_user_terms_bkg_process;

	/**
	 * need_to_update_terms_with_bkg_process.
	 *
	 * @since 3.8.6
	 */
	protected $need_to_update_terms_with_bkg_process = null;

	/**
	 * $terms_query.
	 *
	 * @since 4.1.9
	 *
	 * @var array
	 */
	protected $terms_query = array();

	/**
	 * Constructor.
	 *
	 * @version 4.2.8
	 * @since   2.2.0
	 * @todo    [next] rename export functions, variables etc.
	 * @todo    [maybe] validation: `add_action( 'user_profile_update_errors', 'user_profile_update_errors', PHP_INT_MAX, 3 ); function user_profile_update_errors( $errors, $update, $user ) {}`
	 */
	function __construct() {

		// Manages sales data fields.
		add_action( 'show_user_profile', array( $this, 'show_extra_profile_fields' ), PHP_INT_MAX );
		add_action( 'edit_user_profile', array( $this, 'show_extra_profile_fields' ), PHP_INT_MAX );
		add_action( 'personal_options_update', array( $this, 'update_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_profile_fields' ) );
		add_action( 'admin_footer-profile.php', array( $this, 'handle_sales_data_via_js' ) );
		add_action( 'admin_footer-user-edit.php', array( $this, 'handle_sales_data_via_js' ) );
		add_action( 'wp_ajax_get_mppu_user_sales_data', array( $this, 'get_user_sales_data_html_ajax' ) );
		add_action( 'admin_notices', array( $this, 'show_profile_update_notices' ) );

		// Exports orders data.
		add_action( 'admin_init', array( $this, 'export_orders_data' ) );

		// Exports data.
		add_action( 'admin_init', array( $this, 'export_orders_data_all_users' ) );

		// Bkg Process.
		$this->init_bkg_process();

		// Manages user sales deletion.
		add_filter( 'alg_wc_mppu_profile_page_table_row', array( $this, 'add_delete_user_sales_button' ), 10, 2 );
		add_filter( 'admin_init', array( $this, 'delete_user_sales_data_on_btn_click' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'show_user_sales_data_delete_notices' ) );
	}

	/**
	 * show_user_sales_data_delete_notices.
	 *
	 * @version 4.2.8
	 * @since   4.2.8
	 *
	 * @return void
	 */
	function show_user_sales_data_delete_notices() {
		if ( 'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' ) ) {
			return;
		}
		// Check if the transient is set.
		$delete_result = get_user_meta( get_current_user_id(), 'alg_wc_mppu_delete_user_sales_notice', true );

		if ( 'success' === $delete_result ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . __( 'User sales data has been successfully deleted.', 'your-textdomain' ) . '</p></div>';
		}

		// Delete the transient so it doesn't keep showing the message.
		delete_user_meta( get_current_user_id(), 'alg_wc_mppu_delete_user_sales_notice' );
	}

	/**
	 * add_delete_user_sales_button.
	 *
	 * @version 4.2.8
	 * @since   4.2.8
	 *
	 * @param $table_row
	 * @param $user
	 *
	 * @return string
	 */
	function add_delete_user_sales_button( $table_row, $user ) {
		if (
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_delete_user_sales_btn_enabled', 'no' ) ||
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
		) {
			return $table_row;
		}
		$button_url = add_query_arg(
			array(
				'alg_wc_mppu_action' => 'delete_user_sales_data',
				'nonce'              => wp_create_nonce( 'alg_wc_mppu_delete_user_sales_data' ),
				'user_id'            => $user->ID,
			),
			''
		);
		$button     = '<a class="mppu-export-data button button-primary" href="' . esc_url( $button_url ) . '"><i class="dashicons dashicons-trash"></i>' . __( 'Delete sales data', 'maximum-products-per-user-for-woocommerce' ) . '</a>';
		$table_row  .= '<tr><th>' . __( 'Delete sales data', 'maximum-products-per-user-for-woocommerce' ) . '</h2>' . '</th><td>' . $button . '</td></tr>';

		return $table_row;
	}

	/**
	 * delete_user_sales_data_on_btn_click.
	 *
	 * @version 4.2.8
	 * @since   4.2.8
	 *
	 * @todo    Add Background processing to delete user sales data.
	 *
	 * @throws Exception
	 * @return void
	 */
	function delete_user_sales_data_on_btn_click() {
		if (
			! isset( $_GET['alg_wc_mppu_action'] ) ||
			! isset( $_GET['nonce'] ) ||
			! isset( $_GET['user_id'] ) ||
			empty( intval( $user_id = $_GET['user_id'] ) ) ||
			'delete_user_sales_data' !== sanitize_text_field( $_GET['alg_wc_mppu_action'] ) ||
			! wp_verify_nonce( $_GET['nonce'], 'alg_wc_mppu_delete_user_sales_data' ) ||
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_delete_user_sales_btn_enabled', 'no' ) ||
			! $this->check_current_user( $user_id ) ||
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
		) {
			return;
		}

		// User email type option.
		$user_email_type = alg_wc_mppu_get_option( 'alg_wc_mppu_delete_user_sales_email_type', 'user_email' );

		// Gets customer.
		$customer = new \WC_Customer( $user_id );

		// Gets email.
		$email = 'billing_email' === $user_email_type ? $customer->get_billing_email() : $customer->get_email();

		// Gets orders.
		$orderArg = array(
			'customer' => $email,
			'limit'    => - 1,
			'return'   => 'ids',
		);

		// Delete sales from orders.
		$orders = wc_get_orders( $orderArg );
		if ( $orders ) {
			foreach ( $orders as $order_id ) {
				alg_wc_mppu()->core->data->delete_quantities( $order_id );
			}
		}

		// Set a success flag and redirect.
		update_user_meta( get_current_user_id(), 'alg_wc_mppu_delete_user_sales_notice', 'success', 30 );
		wp_redirect( add_query_arg( array(
			'user_id' => $user_id,
		), remove_query_arg( array( 'alg_wc_mppu_action', 'nonce', 'user_id' ), wp_get_referer() ) ) );
		exit;
	}

	/**
	 * show_profile_update_notices.
	 *
	 * @version 4.2.8
	 * @since   3.8.1
	 */
	function show_profile_update_notices() {
		if (
			! empty( $profile_updated = get_user_meta( get_current_user_id(), 'alg_wc_mppu_profile_updated', true ) ) &&
			is_array( $profile_updated ) &&
			'yes' === alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' ) &&
			'bkg_process' === $profile_updated['method']
		) {
			$class   = 'notice notice-info';
			$message = __( 'The data is being processed in background. Please, reload the page in order to see the info refreshed completely.', 'maximum-products-per-user-for-woocommerce' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			delete_user_meta( get_current_user_id(), 'alg_wc_mppu_profile_updated' );
		}
	}

	/**
	 * init_bkg_process.
	 *
	 * @version 3.8.6
	 * @since   3.8.1
	 */
	function init_bkg_process() {
		require_once( alg_wc_mppu()->plugin_path() . '/includes/background-process/class-alg-wc-mppu-update-user-sales-data-bkg-process.php' );
		$this->update_user_sales_bkg_process = new Alg_WC_MPPU_Update_User_Sales_data_Bkg_Process();

		require_once( alg_wc_mppu()->plugin_path() . '/includes/background-process/class-alg-wc-mppu-update-user-terms-data-bkg-process.php' );
		$this->update_user_terms_bkg_process = new Alg_WC_MPPU_Update_User_Terms_data_Bkg_Process();
	}

	/**
	 * check_current_user.
	 *
	 * @version 3.4.0
	 * @since   2.2.0
	 */
	function check_current_user( $user_id = false ) {
		return ( current_user_can( 'manage_woocommerce' ) && ( ! $user_id || current_user_can( 'edit_user', $user_id ) ) );
	}

	/**
	 * get_export_orders_data.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [next] merge with `show_extra_profile_fields()`?
	 */
	function get_export_orders_data( $user_id, $sep, $do_skip_user_info = false ) {
		// Products
		$csv        = array();
		$block_size = 1024;
		$offset     = 0;
		while ( true ) {
			$args = array(
				'post_type'      => ( 'yes' === get_option( 'alg_wc_mppu_use_variations', 'no' ) ? array( 'product', 'product_variation' ) : 'product' ),
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				$product_title = get_the_title( $product_id );
				$orders_data   = get_post_meta( $product_id, '_alg_wc_mppu_orders_data', true );
				if ( is_array( $orders_data ) && isset( $orders_data[ $user_id ] ) ) {
					foreach ( $orders_data[ $user_id ] as $order_id => $order_data ) {
						$data = array(
							$user_id,
							$product_id,
							'"' . $product_title . '"',
							$order_id,
							date_i18n( 'Y-m-d H:i:s', alg_wc_mppu()->core->get_order_date( $order_data['date_created'] ) ),
							$order_data['qty'],
						);
						if ( $do_skip_user_info ) {
							unset( $data[0] );
						}
						$csv[] = implode( $sep, $data );
					}
				}
			}
			$offset += $block_size;
		}
		return $csv;
	}

	/**
	 * export_orders_data.
	 *
	 * @version 4.2.8
	 * @since   3.4.0
	 * @todo    [next] nonce (same for `export_orders_data_all_users()`)
	 * @todo    [next] terms (same for `export_orders_data_all_users()`)
	 * @todo    [next] `export_lifetime_data` (same for `export_orders_data_all_users()`)
	 */
	function export_orders_data() {
		if (
			isset( $_GET['alg_wc_mppu_export_single_user_orders_data'] ) &&
			'yes' === alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
		) {
			$user_id = intval( $_GET['alg_wc_mppu_export_single_user_orders_data'] );
			if ( ! $this->check_current_user( $user_id ) ) {
				return false;
			}
			$sep = get_option( 'alg_wc_mppu_user_export_sep', ',' );
			$csv = $this->get_export_orders_data( $user_id, $sep );
			$this->get_file( 'maximum-products-per-user-orders-data-' . $user_id, $csv, $sep );
		}
	}

	/**
	 * export_orders_data_all_users.
	 *
	 * @version 4.2.8
	 * @since   3.4.0
	 * @see     https://developer.wordpress.org/reference/classes/wp_user_query/prepare_query/
	 * @todo    [next] `get_users`: `offset` + `number`?
	 * @todo    [next] meta: for `! $do_merge`?
	 * @todo    [next] meta: `is_array` etc.?
	 * @todo    [maybe] include data for `identify_by_ip` in result?
	 */
	function export_orders_data_all_users() {
		if ( isset( $_GET['alg_wc_mppu_export_all_users_orders_data'] ) ) {
			if (
				! $this->check_current_user() ||
				'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
			) {
				return false;
			}
			$csv      = array();
			$sep      = get_option( 'alg_wc_mppu_user_export_sep', ',' );
			$meta     = ( '' !== ( $_meta = get_option( 'alg_wc_mppu_user_export_meta', '' ) ) ? array_map( 'trim', explode( ',', $_meta ) ) : false );
			$do_merge = ( 'yes' === get_option( 'alg_wc_mppu_user_export_merge_user', 'no' ) );
			$args     = array( 'number' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'count_total' => false, 'fields' => 'ID' );
			if ( $do_merge ) {
				$data_sep = get_option( 'alg_wc_mppu_user_export_data_sep', ';' );
				foreach ( get_users( $args ) as $user_id ) {
					$data = $this->get_export_orders_data( $user_id, $data_sep, true );
					if ( ! empty( $data ) ) {
						if ( $meta ) {
							$user_meta = array();
							foreach ( $meta as $key ) {
								$user_meta[] = get_user_meta( $user_id, $key, true );
							}
							$user_meta = implode( $sep, $user_meta );
							if ( '' !== $user_meta ) {
								$user_meta .= $sep;
							}
						} else {
							$user_meta = '';
						}
						$csv[] = $user_id . $sep . $user_meta . implode( $sep, $data );
					}
				}
			} else {
				foreach ( get_users( $args ) as $user_id ) {
					$csv = array_merge( $csv, $this->get_export_orders_data( $user_id, $sep ) );
				}
			}
			$this->get_file( 'maximum-products-per-user-orders-data', $csv, $sep, ( ! $do_merge ) );
		}
	}

	/**
	 * get_file.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [next] `header( 'Content-Length: ' . strlen( $csv ) )`?
	 */
	function get_file( $filename, $csv, $sep, $add_header = true ) {
		if ( $add_header ) {
			array_unshift( $csv, '"' . implode( '"' . $sep . '"', array(
				__( 'User ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Product ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Product title', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order ID', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order date', 'maximum-products-per-user-for-woocommerce' ),
				__( 'Order value', 'maximum-products-per-user-for-woocommerce' ),
			) ) . '"' );
		}
		$csv = implode( PHP_EOL, $csv );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
		die();
	}

	/**
	 * update_profile_fields.
	 *
	 * @version 4.2.8
	 * @since   2.2.0
	 * @todo    [maybe] nonce?
	 * @todo    [maybe] maybe `floatval` (instead `intval`?)
	 */
	function update_profile_fields( $user_id ) {
		if (
			! $this->check_current_user( $user_id ) ||
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
		) {
			return false;
		}
		// Totals data.
		if ( isset( $_POST['alg_wc_mppu_totals_data'] ) ) {
			foreach ( $_POST['alg_wc_mppu_totals_data'] as $product_or_term => $data ) {
				foreach ( $data as $product_or_term_id => $qty ) {
					$this->update_totals_data( array(
						'user_id'            => $user_id,
						'product_or_term'    => $product_or_term,
						'product_or_term_id' => $product_or_term_id,
						'qty'                => $qty,
					) );
				}
			}
		}
		// Orders data.
		if ( isset( $_POST['alg_wc_mppu_orders_data'] ) ) {
			$iteration_count = 0;
			foreach ( $_POST['alg_wc_mppu_orders_data'] as $product_or_term => $data ) {
				foreach ( $data as $product_or_term_id => $order_data ) {
					$iteration_count ++;
				}
			}
			if ( $iteration_count < get_option( 'alg_wc_mppu_bkg_process_min_amount', 50 ) ) {
				foreach ( $_POST['alg_wc_mppu_orders_data'] as $product_or_term => $data ) {
					foreach ( $data as $product_or_term_id => $order_data ) {
						$this->update_orders_data( array(
							'user_id'            => $user_id,
							'product_or_term'    => $product_or_term,
							'product_or_term_id' => $product_or_term_id,
							'order_data'         => $order_data
						) );
					}
				}
			} else {
				$this->update_user_sales_bkg_process->cancel_process();
				foreach ( $_POST['alg_wc_mppu_orders_data'] as $product_or_term => $data ) {
					foreach ( $data as $product_or_term_id => $order_data ) {
						$this->update_user_sales_bkg_process->push_to_queue( array(
							'user_id'            => $user_id,
							'product_or_term'    => $product_or_term,
							'product_or_term_id' => $product_or_term_id,
							'order_data'         => $order_data
						) );
					}
				}
				$this->update_user_sales_bkg_process->save()->dispatch();
				update_user_meta( get_current_user_id(), 'alg_wc_mppu_profile_updated', array( 'method' => 'bkg_process' ) );
			}

		}

		$this->calculate_terms_data_from_products_data( $user_id );
	}

	/**
	 * calculate_terms_data_from_products_data.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $user_id
	 *
	 * @return void
	 * @todo    Add background processing
	 *
	 */
	function calculate_terms_data_from_products_data( $user_id ) {
		if (
			'yes' === get_option( 'alg_wc_mppu_editable_sales_data_auto_update_terms_data', 'no' ) &&
			false !== get_user_by( 'ID', $user_id )
		) {
			if ( true === $this->need_to_update_terms_with_bkg_process() ) {
				$this->update_user_terms_bkg_process->cancel_process();
			}
			foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
				$terms = $this->get_terms_query( $taxonomy );
				if ( false !== $terms ) {
					foreach ( $terms->get_terms() as $term ) {
						if ( false === $this->need_to_update_terms_with_bkg_process() ) {
							$this->update_terms_data( array(
								'products_post' => $_POST['alg_wc_mppu_orders_data']['product'],
								'user_id'       => $user_id,
								'term'          => $term,
							) );
						} elseif ( true === $this->need_to_update_terms_with_bkg_process() ) {
							$this->update_user_terms_bkg_process->push_to_queue( array(
								'products_post' => $_POST['alg_wc_mppu_orders_data']['product'],
								'user_id'       => $user_id,
								'term'          => $term,
							) );
						}
					}
				}
			}
			if ( true === $this->need_to_update_terms_with_bkg_process() ) {
				$this->update_user_terms_bkg_process->save()->dispatch();
			}
		}
	}

	/**
	 * update_terms_data.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $args
	 *
	 * @return void
	 */
	function update_terms_data( $args = null ) {
		$args            = wp_parse_args( $args, array(
			'term'    => '',
			'user_id' => '',
            'products_post' => array()
		) );
		$term            = $args['term'];
		$user_id         = $args['user_id'];
		$products_post   = $args['products_post'];
		$totals_data     = alg_wc_mppu()->core->get_post_or_term_meta( 'term', $term->term_id, '_alg_wc_mppu_totals_data' );
		$totals_data_new = $totals_data;
		$orders_data     = alg_wc_mppu()->core->get_post_or_term_meta( 'term', $term->term_id, '_alg_wc_mppu_orders_data' );
		$orders_data_new = $orders_data;
		if ( ! empty( $orders_data ) && isset( $orders_data[ $user_id ] ) ) {
			$lifetime_total = 0;
			foreach ( $orders_data[ $user_id ] as $order_id => $info ) {
				$order_total                                     = $this->get_terms_total_from_order( $term, $order_id, $products_post );
				$lifetime_total                                  += $order_total;
				$orders_data_new[ $user_id ][ $order_id ]['qty'] = $order_total;
				$totals_data_new[ $user_id ]                     = $lifetime_total;
			}
			update_term_meta( $term->term_id, '_alg_wc_mppu_orders_data', $orders_data_new );
			update_term_meta( $term->term_id, '_alg_wc_mppu_totals_data', $totals_data_new );
		}
	}

	/**
	 * get_terms_query.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $taxonomy
	 *
	 * @return false|mixed|WP_Term_Query
	 */
	function get_terms_query( $taxonomy ) {
		if (
			'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) &&
			! isset( $this->terms_query[ $taxonomy ] )
		) {
			$args                           = array(
				'taxonomy'       => $taxonomy,
				'orderby'        => 'name',
				'order'          => 'ASC',
				'hide_empty'     => false,
				'posts_per_page' => - 1,
			);
			$loop                           = new WP_Term_Query( $args );
			$this->terms_query[ $taxonomy ] = $loop;
		}

		return isset( $this->terms_query[ $taxonomy ] ) ? $this->terms_query[ $taxonomy ] : false;
	}

	/**
	 * need_to_update_terms_with_bkg_process.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @return bool
	 */
	function need_to_update_terms_with_bkg_process() {
		if ( is_null( $this->need_to_update_terms_with_bkg_process ) ) {
			$count = 0;
			foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
				$terms = $this->get_terms_query( $taxonomy );
				if ( false !== $terms ) {
					$count += count( $terms->get_terms() );
				}
			}
			$this->need_to_update_terms_with_bkg_process = $count >= get_option( 'alg_wc_mppu_bkg_process_min_amount', 50 );
		}

		return $this->need_to_update_terms_with_bkg_process;
	}

	/**
	 * get_terms_total_from_order.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $term
	 * @param $order_id
	 * @param $products_post
	 *
	 * @return int|mixed
	 */
    function get_terms_total_from_order( $term, $order_id, $products_post ){
	    $products_from_term = $this->get_order_products_by_term( $term, $order_id );
        $total = 0;
        foreach ($products_from_term as $product_id){
	        $total += isset( $products_post[ $product_id ][ $order_id ] ) ? $products_post[ $product_id ][ $order_id ] : 0;
        }
        return $total;
    }

	/**
	 * get_order_products_by_term.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $term
	 * @param $order_id
	 *
	 * @return array
	 */
	function get_order_products_by_term( $term, $order_id ) {
		$products = array();
		$order    = wc_get_order( $order_id );
		$items    = $order->get_items();
		foreach ( $items as $item ) {
			$product_id = $item->get_product_id();
			$terms      = wp_get_post_terms( $product_id, $term->taxonomy, array( 'fields' => 'ids' ) );
			if ( in_array( $term->term_id, $terms ) ) {
				$products[] = $product_id;
			}
		}
		return $products;
	}

	/**
	 * update_orders_data.
	 *
	 * @version 3.8.6
	 * @since   3.8.1
	 *
	 * @param null $args
	 */
	function update_orders_data( $args = null ){
		$args = wp_parse_args( $args, array(
			'user_id'            => '',
			'order_data'         => '',
			'product_or_term'    => '',
			'product_or_term_id' => '',
		) );
		$product_or_term    = $args['product_or_term'];
		$product_or_term_id = $args['product_or_term_id'];
		$user_id            = $args['user_id'];
		$order_data         = $args['order_data'];
		$lifetime_totals    = 0;
		$orders_data        = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_orders_data' );
		foreach ( $order_data as $order_id => $qty ) {
			$orders_data[ $user_id ][ $order_id ]['qty'] = intval( $qty );
			$lifetime_totals                             += intval( $qty );
		}
		alg_wc_mppu()->core->update_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_orders_data', $orders_data );
		// Update totals data.
		if ( 'yes' === get_option( 'alg_wc_mppu_editable_sales_data_auto_update_lifetime', 'no' ) ) {
			$this->update_totals_data( array(
				'user_id'            => $user_id,
				'product_or_term'    => $product_or_term,
				'product_or_term_id' => $product_or_term_id,
				'qty'                => intval( $lifetime_totals )
			) );
		}
	}

	/**
	 * update_totals_data.
	 *
	 * @version 3.8.1
	 * @since   3.8.1
	 *
	 * @param null $args
	 */
	function update_totals_data( $args = null ) {
		$args                    = wp_parse_args( $args, array(
			'user_id'            => '',
			'product_or_term'    => '',
			'product_or_term_id' => '',
			'qty'                => 0
		) );
		$product_or_term         = $args['product_or_term'];
		$product_or_term_id      = $args['product_or_term_id'];
		$user_id                 = $args['user_id'];
		$qty                     = $args['qty'];
		$totals_data             = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_totals_data' );
		$totals_data[ $user_id ] = intval( $qty );
		alg_wc_mppu()->core->update_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_totals_data', $totals_data );
	}

	/**
	 * get_user_products_data_html.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $user
	 *
	 * @return false|string
	 */
	function get_user_products_data_html( $user ) {
		if ( ! $this->check_current_user( $user->ID ) ) {
			return '';
		}
		ob_start();
		$post_type = 'yes' === get_option( 'alg_wc_mppu_use_variations', 'no' ) || 'yes' === get_option( 'alg_wc_mppu_editable_sales_data_show_variations', 'no' ) ? array( 'product', 'product_variation' ) : 'product';
		// Products
		$output     = '';
		$block_size = 1024;
		$offset     = 0;
		while ( true ) {
			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				$output .= $this->get_item_data( $user, $product_id, 'product',
					'<a href="' . admin_url( 'post.php?post=' . ( ( $parent_post_id = wp_get_post_parent_id( $product_id ) ) ? $parent_post_id : $product_id ) . '&action=edit' ) . '">' . get_the_title( $product_id ) . '</a>' );
			}
			$offset += $block_size;
		}
		if ( ! empty( $output ) ) {
			echo
			     '<table class="widefat striped alg_wc_mppu_products_data">' .
			     '<tr>' .
			     '<th>' . __( 'Product', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			     '<th>' . __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			     '<th>' . __( 'Orders', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
			     '</tr>' .
			     $output .
			     '</table>';
		}
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	/**
	 * get_user_terms_data_html.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @param $user
	 * @param $taxonomy
	 *
	 * @return false|string
	 */
	function get_user_terms_data_html( $user, $taxonomy ) {
		ob_start();
		if ( 'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) ) {
			$output = '';
			$args   = array(
				'taxonomy'       => $taxonomy,
				'orderby'        => 'name',
				'order'          => 'ASC',
				'hide_empty'     => false,
				'posts_per_page' => - 1,
			);
			$loop   = new WP_Term_Query( $args );
			foreach ( $loop->get_terms() as $term ) {
				$output .= $this->get_item_data( $user, $term->term_id, 'term',
					'<a href="' . admin_url( 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&post_type=product' ) . '">' . $term->name . '</a>' );
			}
			if ( ! empty( $output ) ) {
				echo '<table class="widefat striped alg_wc_mppu_' . $taxonomy . '_data">' .
				     '<tr>' .
				     '<th>' . __( 'Term', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
				     '<th>' . __( 'Lifetime', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
				     '<th>' . __( 'Orders', 'maximum-products-per-user-for-woocommerce' ) . '</th>' .
				     '</tr>' .
				     $output .
				     '</table>';
			}
		}

		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * show_extra_profile_fields.
	 *
	 * @version 4.2.8
	 * @since   2.2.0
	 */
	function show_extra_profile_fields( $user ) {
		if (
			! $this->check_current_user( $user->ID ) ||
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
		) {
			return false;
		}

		$sales_data_btn = new Alg_WC_MPPU_Sales_Data_Btn();
		$sales_data_btn_html = $sales_data_btn->get_btn_html( array(
			'user_id' => $user->ID,
			'action'  => 'get_mppu_user_sales_data',
			'type'    => 'product'
		) );

        $style = $this->get_user_profile_styles();
		$get_html_using_ajax    = $this->show_extra_profile_fields_using_ajax();
		$product_data_html = false === $get_html_using_ajax ? $this->get_user_products_data_html( $user ) : $sales_data_btn_html;
		$terms_data             = $this->get_extra_profile_fields_terms_data_table_rows( $user );
		$export_sales_data_html = '<a class="mppu-export-data button button-primary" href="' . add_query_arg( 'alg_wc_mppu_export_single_user_orders_data', $user->ID ) . '"><i class="dashicons dashicons-download"></i>' . __( 'Export products data', 'maximum-products-per-user-for-woocommerce' ) . '</a>';

		echo $style;
		echo '<h2>' . __( 'Maximum Products per User', 'maximum-products-per-user-for-woocommerce' ) . '</h2>';
		echo '<table class="form-table" id="mppu-products-data">' .
			 '<tr><th>' . __( 'Export Data', 'maximum-products-per-user-for-woocommerce' ) . '</h2>' . '</th><td>' . $export_sales_data_html . '</td></tr>' .
			 apply_filters( 'alg_wc_mppu_profile_page_table_row', '', $user ) .
			 '<tr><th>' . __( 'Products Data', 'maximum-products-per-user-for-woocommerce' ) . '</h2>' . '</th><td>' . $product_data_html . '</td></tr>' .
			 $terms_data .
			 '</table>';
	}

	/**
	 * get_user_profile_styles.
	 *
	 * @version 4.2.3
	 * @since   3.8.6
	 *
	 * @return string
	 */
	function get_user_profile_styles() {
		ob_start();
		?>
        <style>
            /* Table with data */
            table.alg_wc_mppu_products_data th, table.alg_wc_mppu_products_data td, table.alg_wc_mppu_product_cat_data td, table.alg_wc_mppu_product_cat_data th, table.alg_wc_mppu_product_tag_data td, table.alg_wc_mppu_product_tag_data th {
                width: 33%;
                padding: 15px 10px
            }

            /* Export data button */
            .button.mppu-export-data{
                padding-right:13px;
            }
            .mppu-export-data, .mppu-show-sales-data{
                vertical-align: middle
            }

            .mppu-export-data i, .mppu-show-sales-data i{
                vertical-align:text-top;
            }

            .mppu-export-data i {
                margin: 0 4px 0 -2px;
            }
        </style>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		$sales_data_btn_style = new Alg_WC_MPPU_Sales_Data_Btn();
		$result               .= $sales_data_btn_style->get_style();

		return $result;
	}

	/**
	 * show_extra_profile_fields_using_ajax.
	 *
	 * @version 3.8.6
	 * @since   3.8.6
	 *
	 * @return boolean
	 */
	function show_extra_profile_fields_using_ajax(){
		return 'yes' === get_option( 'alg_wc_mppu_editable_sales_data_using_ajax', 'no' );
	}

	/**
	 * get_extra_profile_fields_terms_data_table_rows.
	 *
	 * @version 4.2.3
	 * @since   3.8.6
	 *
	 * @param $user
	 *
	 * @return string
	 */
	function get_extra_profile_fields_terms_data_table_rows( $user ) {
		$get_html_using_ajax = $this->show_extra_profile_fields_using_ajax();
		$terms_data          = '';
		foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
			if ( 'yes' === apply_filters( 'alg_wc_mppu_' . $taxonomy . '_enabled', 'no' ) ) {
				$term_name  = 'product_cat' === $taxonomy ? __( 'Product Categories Data', 'maximum-products-per-user-for-woocommerce' ) : __( 'Product Tags Data', 'maximum-products-per-user-for-woocommerce' );
				$sales_data_btn = new Alg_WC_MPPU_Sales_Data_Btn();
				$sales_data_btn_html = $sales_data_btn->get_btn_html( array(
					'user_id' => $user->ID,
					'action'  => 'get_mppu_user_sales_data',
					'type'    => $taxonomy
				) );
				$data_html  = false === $get_html_using_ajax ? $this->get_user_terms_data_html( $user, $taxonomy ) : $sales_data_btn_html;
				$terms_data .= '<tr><th>' . $term_name . '</h2>' . '</th><td>' . $data_html . '</td></tr>';
			}
		}

		return $terms_data;
	}

	/**
	 * handle_sales_data_via_js.
	 *
	 * @version 4.2.8
	 * @since   3.8.6
	 *
	 * @return void
	 */
	function handle_sales_data_via_js() {
		if (
			! $this->show_extra_profile_fields_using_ajax() ||
			'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' )
		) {
			return;
		}
		$php_to_js = array(
			'security' => wp_create_nonce( 'mppu-get_user_sales_data' ),
		);
		?>
        <script>
            jQuery(document).ready(function ($) {
                let data_from_php = <?php echo json_encode( $php_to_js );?>;
                $('.button[data-action="get_mppu_user_sales_data"]').on('click', function (e) {
                    e.preventDefault();
                    let clickedBtn = $(this);
                    clickedBtn.find('.loading').removeClass('hide');
                    let data = {
                        security: data_from_php.security,
                        'action': 'get_mppu_user_sales_data',
                        'user_id': clickedBtn.attr('data-user_id'),
                        'data_type': clickedBtn.attr('data-type'),
                    };
                    jQuery.post(ajaxurl, data, function (response) {
                        clickedBtn.replaceWith( response.data.output );
                    });
                });
            });
        </script>
		<?php
	}

	/**
	 * get_user_sales_data_html_ajax.
	 *
	 * @version 4.2.8
	 * @since   3.8.6
	 *
     * @return void
	 */
	function get_user_sales_data_html_ajax() {
		check_ajax_referer( 'mppu-get_user_sales_data', 'security' );
		if ( 'yes' !== alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data', 'no' ) ) {
			return;
		}
		$args      = wp_parse_args( $_POST, array(
			'data_type' => 'product',
			'user_id'   => get_current_user_id(),
		) );
		$user_id   = intval( $args['user_id'] );
		$data_type = $args['data_type'];
		$output    = '';
		if ( ! empty( $user_id ) ) {
			$user = get_user_by( 'ID', $user_id );
			if ( 'product' === $data_type ) {
				$output = $this->get_user_products_data_html( $user );
			} else {
				$output = $this->get_user_terms_data_html( $user, $data_type );
			}
			wp_send_json_success( array( 'output' => $output ) );
		} else {
			wp_send_json_error( array( 'output' => __( 'There was en error. Please, try again later.', 'maximum-products-per-user-for-woocommerce' ) ) );
		}
	}

	/**
	 * get_item_data.
	 *
	 * @version 4.2.8
	 * @since   2.2.0
	 * @todo    [next] use `alg_wc_mppu()->core->get_date_format()`
	 */
	function get_item_data( $user, $product_or_term_id, $product_or_term, $product_or_term_title ) {
		$output = '';
		$totals_data = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_totals_data' );
		$orders_data = alg_wc_mppu()->core->get_post_or_term_meta( $product_or_term, $product_or_term_id, '_alg_wc_mppu_orders_data' );
		$disable_orders_input = 'yes' === get_option( 'alg_wc_mppu_editable_sales_data_auto_update_terms_data', 'no' ) && 'term' === $product_or_term;
		$disable_lifetime_input = 'yes' === get_option( 'alg_wc_mppu_editable_sales_data_auto_update_lifetime', 'no' ) || $disable_orders_input;
		$lifetime_input_disabled_str = $disable_lifetime_input ? 'disabled="disabled"' : '';
		$orders_input_disabled_str = $disable_orders_input ? 'disabled="disabled"' : '';
		$do_add_empty_totals = ( 'yes' === alg_wc_mppu_get_option( 'alg_wc_mppu_editable_sales_data_empty_totals', 'no' ) );
		if ( $do_add_empty_totals || isset( $totals_data[ $user->ID ] ) || isset( $orders_data[ $user->ID ] ) ) {
			$output .= '<tr>';
			$output .= '<td>' . $product_or_term_title . ' (#' . $product_or_term_id . ')' . '</td>';
			$output .= '<td>';
			if ( $do_add_empty_totals || isset( $totals_data[ $user->ID ] ) ) {
				$name  = 'alg_wc_mppu_totals_data[' . $product_or_term . '][' . $product_or_term_id . ']';
				$value = ( isset( $totals_data[ $user->ID ] ) ? $totals_data[ $user->ID ] : 0 );
				$output .= '<input type="number" name="' . $name . '" value="' . $value . '"' . $lifetime_input_disabled_str . '>';
			}
			$output .= '</td>';
			$output .= '<td>';
			if ( isset( $orders_data[ $user->ID ] ) ) {
				$orders = array();
				foreach ( $orders_data[ $user->ID ] as $order_id => $order_data ) {
					$name  = 'alg_wc_mppu_orders_data[' . $product_or_term . '][' . $product_or_term_id . '][' . $order_id . ']';
					$value = $order_data['qty'];
					$orders[ $order_id ] = '<input type="number" name="' . $name . '" value="' . $value . '"'.$orders_input_disabled_str.'>' .
						' <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '">#' . $order_id . '</a>' .
						wc_help_tip( sprintf( __( 'Order #%d<br>(%s)', 'maximum-products-per-user-for-woocommerce' ),
							$order_id, date_i18n( 'Y-m-d H:i:s', alg_wc_mppu()->core->get_order_date( $order_data['date_created'] ) ) ), true );
				}
				$output .= implode( '<br>', $orders );
			}
			$output .= '</td>';
			$output .= '</tr>';
		}
		return $output;
	}

}

endif;

return new Alg_WC_MPPU_Users();
