<?php
/**
 * Maximum Products per User for WooCommerce - Core Class.
 *
 * @version 4.5.1
 * @since   1.0.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU_Core' ) ) :

class WPFMPPU_Core extends WPFMPPU_Dynamic_Properties_Obj {

	/**
	 * $error_messages.
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	public $error_messages = array();

	/**
	 * data.
	 *
	 * @since 3.6.4
	 *
	 * @var WPFMPPU_Data
	 */
	public $data;

	/**
	 * Time offset.
	 *
	 * @since 3.7.0
	 *
	 * @var string
	 */
	public $time_offset = null;

	/**
	 * $current_time_offset.
	 *
	 * @since 4.1.5
	 *
	 * @var string
	 */
	public $current_time_offset = null;

	/**
	 * user_already_bought_qty.
	 *
	 * @since 3.7.0
	 *
	 * @var array
	 */
	public $user_already_bought_qty_cache = array();

	/**
     * $products_max_qty.
     *
	 * @since 3.9.9
     *
	 * @var array
	 */
	public $products_max_qty = array();

	/**
     * $products_remaining_qty.
     *
	 * @since 3.9.9
     *
	 * @var array
	 */
    public $products_remaining_qty = array();

	/**
     * $disable_product_purchase_by_limit.
     *
	 * @since 3.9.9
     *
	 * @var null
	 */
    public $disable_product_purchase_by_limit = null;

	/**
	 * $my_account.
	 *
	 * @since 4.0.0
	 *
	 * @var null
	 */
	public $my_account = null;

	/**
	 * $multilanguage.
	 *
	 * @since 4.0.6
	 *
	 * @var WPFMPPU_Multi_Language
	 */
	public $multilanguage;

	/**
	 * $weekdays.
	 *
	 * @since 4.0.9
	 *
	 * @var WPFMPPU_Week_Days
	 */
    public $weekdays;

	/**
	 * Options.
	 *
	 * @since 4.2.3
	 *
	 * @var WPFMPPU_Options
	 */
	public $options;

	/**
	 * shown_term_notices.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	public $shown_term_notices = array();

	/**
	 * Constructor.
	 *
	 * @version 4.5.0
	 * @since   1.0.0
	 * @todo    [next] split file
	 * @todo    [next] `alg_wc_mppu_cart_notice`: `text`: customizable (and maybe multiple) positions (i.e. hooks)
	 * @todo    [later] product terms (back-end): add fields to "*add* category/tag" pages also (i.e. not only to "edit" pages)
	 * @todo    [later] (feature) correct qty on add to cart
	 * @todo    [later] (feature) limit max quantity with WC filter
	 * @todo    [maybe] `alg_wc_mppu_cart_notice`: `notice` + `text` (i.e. simultaneous)?
	 * @todo    [maybe] `is_admin && ! ajax`?
	 */
	function __construct() {
		$this->is_wc_version_below_3_0_0 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		if ( 'yes' === get_option( 'wpjup_wc_maximum_products_per_user_plugin_enabled', 'yes' ) ) {
			// Background process.
			$this->init_bkg_process();
            // Initialize classes.
            $this->initialize_classes();
			// Properties
			$this->do_use_user_roles           = ( 'yes' === get_option( 'alg_wc_mppu_use_user_roles', 'no' ) );
			$this->do_identify_guests_by_ip    = ( 'identify_by_ip' === get_option( 'alg_wc_mppu_block_guests', 'no' ) );
			$this->do_identify_by_checkout_email    = ( 'identify_by_checkout_email' === get_option( 'alg_wc_mppu_block_guests', 'no' ) );
			$this->do_get_lifetime_from_totals = ( 'yes' === get_option( 'alg_wc_mppu_get_lifetime_from_totals', 'no' ) );

			// Check quantities - Checkout.
			add_action( 'woocommerce_checkout_process', array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
			add_action( 'woocommerce_store_api_cart_errors', array( $this, 'check_cart_quantities_and_add_errors_to_cart' ), 10, 2 );

			// Check quantities - Cart
			if ( 'no' != ( $this->cart_notice = get_option( 'alg_wc_mppu_cart_notice', 'yes' ) ) ) {
				add_action( ( 'yes' === $this->cart_notice ? 'woocommerce_before_cart' : 'woocommerce_before_cart_table' ), array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
			}
			// Block checkout page
			if ( 'yes' === get_option( 'wpjup_wc_maximum_products_per_user_stop_from_seeing_checkout', 'no' ) ) {
				add_action( 'wp', array( $this, 'block_checkout' ), PHP_INT_MAX );
				// Validation actions
				$actions = array_map( 'trim', array_values( array_filter( explode( PHP_EOL, get_option( 'alg_wc_mppu_validation_actions', '' ) ) ) ) );
				foreach ( $actions as $action ) {
					add_action( $action, array( $this, 'block_checkout' ), PHP_INT_MAX );
				}
			}
			// Validate on add to cart
			if ( 'yes' === get_option( 'alg_wc_mppu_validate_on_add_to_cart', 'yes' ) ) {
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_on_add_to_cart' ), PHP_INT_MAX, 4 );
				if ( 'yes' === get_option( 'alg_wc_mppu_block_guests', 'no' ) ) {
					add_action( 'woocommerce_init', array( $this, 'block_guest_add_to_cart_ajax_error' ), PHP_INT_MAX );
				}
			}
			// Hide products.
			add_filter( 'woocommerce_product_is_visible', array( $this, 'product_visibility' ), PHP_INT_MAX, 2 );
			add_filter( 'the_posts', array( $this, 'hide_products_from_search_and_direct_links' ), PHP_INT_MAX, 2 );
			// Single product page
			switch ( get_option( 'alg_wc_mppu_permanent_notice', 'no' ) ) {
				case 'yes':
					add_action( 'woocommerce_before_single_product',  array( $this, 'permanent_notice' ) );
					break;
				case 'text':
					add_action( 'woocommerce_single_product_summary', array( $this, 'permanent_notice_text' ), 30 );
					break;
				case 'text_content':
					add_filter( 'the_content', array( $this, 'permanent_notice_text_content' ) );
					break;
			}
			// Count by current payment method
			if ( 'yes' === get_option( 'alg_wc_mppu_count_by_current_payment_method', 'no' ) ) {
				add_filter( 'wpfmppu_user_already_bought_do_count_order', array( $this, 'count_by_current_payment_method' ), 10, 3 );
			}

			// Compensate date to check.
			add_filter( 'wpfmppu_date_to_check', array( $this, 'compensate_date_to_check_time' ), 900 );
			// Compensate datetime to compare.
			add_filter( 'wpfmppu_datetime_to_compare', array( $this, 'compensate_current_time_to_compare' ), 900 );
			// Hook msg shortcode
			add_filter( 'shortcode_atts_' . 'wpfmppu_customer_msg', array( $this, 'filter_customer_message_shortcode' ) );
			// Set bought data to zero if guest option is set as "Do nothing and block guests from purchasing products beyond the limits"
			add_filter( 'wpfmppu_user_already_bought', array( $this, 'set_guest_user_bought_to_zero' ) );
			// Optionally use date_to_check as primary validation through the validation hook.
			add_filter( 'wpfmppu_user_already_bought_validation', array( $this, 'validate_user_already_bought_date_to_check_primary' ), 999, 2 );
			// Last month day check.
			add_filter( 'wpfmppu_user_already_bought_validation', array( $this, 'validate_user_already_bought_monthly_range' ), 10, 2 );
			// Manages max attribute from quantity field.
			$this->handle_qty_field_max_attr();
			add_filter( 'woocommerce_is_purchasable', array( $this, 'disallow_product_purchase' ), 10, 2 );
		}
		// Core loaded
		do_action( 'wpfmppu_core_loaded', $this );
	}

	/**
	 * load_classes.
	 *
	 * @version 4.5.0
	 * @since   4.0.9
	 *
	 * @return void
	 */
    function initialize_classes(){
	    require_once( 'class-wpfmppu-options.php' );
	    $this->options = new WPFMPPU_Options();
	    if ( is_admin() ) {
		    require_once( 'class-wpfmppu-sales-data-btn.php' );
	    }
	    // Shortcodes
	    require_once( 'class-wpfmppu-shortcodes.php' );
	    // Update quantities
	    $this->data = require_once( 'class-wpfmppu-data.php' );
	    // Sales data reports
	    require_once( 'class-wpfmppu-reports.php' );
	    // Users
	    $this->users = require_once( 'class-wpfmppu-users.php' );
	    // My Account.
	    $this->my_account = require_once( 'class-wpfmppu-my-account.php' );
	    // Modes
	    require_once( 'class-wpfmppu-modes.php' );
	    // Multi-language
	    $this->multilanguage = require_once( 'class-wpfmppu-multi-language.php' );
	    // Week Days.
	    require_once( 'class-wpfmppu-week-days.php' );
	    $this->weekdays = new WPFMPPU_Week_Days();
	    $this->weekdays->init();
    }

	/**
	 * Manages max attribute from quantity field.
	 *
	 * @version 4.2.1
	 * @since   3.8.5
	 *
	 */
	function handle_qty_field_max_attr(){
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'set_qty_field_max_attr' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'set_qty_field_max_attr' ), 10, 3 );
		add_action( 'woocommerce_after_single_variation', array( $this, 'change_variation_qty_input_script' ) );
		add_filter( 'woocommerce_store_api_product_quantity_maximum', array( $this, 'set_store_api_product_max_qty' ), 10, 3 );
	}

	/**
	 * change_variation_qty_input_script.
	 *
	 * @version 4.0.4
	 * @since   3.8.5
	 */
	function change_variation_qty_input_script() {
		if ( 'yes' !== get_option( 'alg_wc_mppu_set_qty_field_max_attr', 'no' ) ) {
			return;
		}
		?>
        <script type="text/javascript">
            jQuery(function ($) {
                $(document).on('show_variation', function (e, variation) {
                    let qty_input = $('div.quantity > input.qty');
                    if (variation.max_qty) {
                        if (qty_input.val() > variation.max_qty) {
                            qty_input.val(variation.max_qty);
                        }
                        qty_input.attr('max', variation.max_qty);
                    }

                })
            });
        </script>
		<?php
	}

	/**
	 * set_qty_field_max_attr.
	 *
	 * @version 4.5.0
	 * @since   3.8.5
	 *
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
	function set_qty_field_max_attr( $args, $product ) {
		if ( 'yes' === get_option( 'alg_wc_mppu_set_qty_field_max_attr', 'no' ) ) {
			$final_product = 3 === func_num_args() ? func_get_args()[2] : $product;
			$max_qty_data  = wpfmppu()->core->get_max_qty_for_product( $final_product->get_id() );
			$max_qty_data = $this->sort_max_qty_data( $max_qty_data );
			$final_remaining   = $this->get_product_remaining_qty( array( 'product' => $final_product ) );
			$max_qty_input_val = $final_remaining > 0 ? $final_remaining : $max_qty_data;
			if ( $max_qty_input_val > 0 ) {
				$args['max_value'] = isset( $args['max_value'] ) && (int) $args['max_value'] > 0 ? min( $args['max_value'], $max_qty_input_val ) : $max_qty_input_val;
				$args['max_qty']   = isset( $args['max_qty'] ) && (int) $args['max_qty'] > 0 ? min( $args['max_qty'], $max_qty_input_val ) : $max_qty_input_val;
				if ( isset( $args['min_qty'] ) && $args['min_qty'] > $args['max_qty'] ) {
					unset( $args['max_qty'] );
				}
				if ( isset( $args['min_value'] ) && $args['min_value'] > $args['max_value'] ) {
					unset( $args['max_value'] );
				}
			}

		}

		return $args;
	}

	/**
	 * set_store_api_product_max_qty.
	 *
	 * @version 4.5.0
	 * @since   4.2.1
	 *
	 * @param $quantity
	 * @param $product
	 * @param $cart_item
	 *
	 * @return int|mixed|null
	 */
	function set_store_api_product_max_qty( $quantity, $product, $cart_item )  {
		if ( 'yes' === get_option( 'alg_wc_mppu_set_qty_field_max_attr', 'no' ) ) {
			$max_qty_data  = wpfmppu()->core->get_max_qty_for_product( $product->get_id() );
			$max_qty_data = $this->sort_max_qty_data( $max_qty_data );
			$final_remaining   = $this->get_product_remaining_qty( array( 'product' => $product ) );
			$max_qty_input_val = $final_remaining > 0 ? $final_remaining : $max_qty_data;
			if ( $max_qty_input_val > 0 ) {
				$quantity = $max_qty_input_val;
			}
		}
		return $quantity;
	}

	/**
	 * sort_max_qty_data.
	 *
	 * @version 4.2.1
	 * @since   4.2.1
	 *
	 * @param $max_qty_data
	 *
	 * @return mixed
	 */
	function sort_max_qty_data( $max_qty_data ) {
		if ( is_array( $max_qty_data ) ) {
			usort( $max_qty_data, function ( $a, $b ) {
				return $b['max_qty'] <=> $a['max_qty'];
			} );
			$max_qty_data = $max_qty_data[0]['max_qty'];
		} else {
			$max_qty_data;
		}

		return $max_qty_data;
	}

	/**
     * get_product_remaining_qty.
     *
	 * @version 4.5.1
	 * @since   3.9.9
     *
	 * @param $args
	 *
	 * @return int|mixed|null
	 */
	function get_product_remaining_qty( $args = null ) {
		$args            = wp_parse_args( $args, array(
			'product' => ''
		) );
		$current_user_id = $this->get_current_user_id();
		$product         = $args['product'];
		$product_id      = ( is_a( $product, 'WC_Product' ) ? $product->get_id() : 0 );
		$cached_obj_name = md5( maybe_serialize( array(
			'product_id'      => $product_id,
			'current_user_id' => $current_user_id,
		) ) );
		if ( isset( $this->products_remaining_qty[ $cached_obj_name ] ) ) {
			return $this->products_remaining_qty[ $cached_obj_name ];
		}
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$this->products_remaining_qty[ $cached_obj_name ] = 0;

			return 0;
		}
		$limit           = $this->get_max_qty_for_product( $product_id );
		$bought_data     = $this->get_user_already_bought_qty( $product_id, $current_user_id, true );
		$final_remaining = 0;
		if ( is_array( $limit ) ) {
			$_remaining = PHP_INT_MAX;
			foreach ( $limit as $_max_qty ) {
				$bought_data         = $this->get_user_already_bought_qty( $_max_qty['term_id'], $current_user_id, false );
				$user_already_bought = $bought_data['bought'];
				$remaining           = $_max_qty['max_qty'] - $user_already_bought;
				if ( $remaining < $_remaining ) {
					$_remaining      = $remaining;
					$final_remaining = $remaining;
				}
			}
		} else {
			$final_remaining = $limit - $bought_data['bought'];
		}
		$this->products_remaining_qty[ $cached_obj_name ] = $final_remaining;

		return $final_remaining;
	}

	/**
     * disallow_product_purchase.
     *
	 * @version 4.5.0
	 * @since   3.9.9
     *
	 * @param $is_purchasable
	 * @param $product
	 *
	 * @return boolean
	 */
	function disallow_product_purchase( $is_purchasable, $product ) {
		if (
			$this->need_to_disable_product_purchase_by_limit() &&
			wpfmppu()->core->get_max_qty_for_product( $product->get_id() ) &&
			$this->get_product_remaining_qty( array( 'product' => $product ) ) <= 0
		) {
			$is_purchasable = false;
		}

		return $is_purchasable;
	}

	/**
	 * need_to_disable_product_purchase_by_limit
	 *
	 * @version 3.9.9
	 * @since   3.9.9
	 *
	 * @return bool|null
	 */
	function need_to_disable_product_purchase_by_limit() {
		if ( is_null( $this->disable_product_purchase_by_limit ) ) {
			$this->disable_product_purchase_by_limit = 'yes' === get_option( 'alg_wc_mppu_disable_product_purchase_by_limit', 'no' );
		}

		return $this->disable_product_purchase_by_limit;
	}

	/**
	 * validate_user_already_bought_monthly_range.
	 *
	 * @version 3.8.4
	 * @since   3.8.4
	 *
	 * @param $validation
	 * @param $args
	 *
	 * @return bool
	 */
	function validate_user_already_bought_monthly_range( $validation, $args ) {
		if (
			'monthly' === $args['date_range'] &&
			$args['order_date'] < $this->get_monthly_range_origin_date( $args )
		) {
			$validation = false;
		}
		return $validation;
	}

	/**
	 * validate_user_already_bought_date_to_check_primary.
	 *
	 * @version 4.4.7
	 * @since   4.4.7
	 *
	 * @param bool  $validation
	 * @param array $args
	 *
	 * @return bool
	 */
	function validate_user_already_bought_date_to_check_primary( $validation, $args ) {
		if ( 'yes' !== get_option( 'alg_wc_mppu_date_to_check_primary_validation', 'no' ) ) {
			return $validation;
		}
		if ( empty( $args['date_range'] ) ) {
			return $validation;
		}
		// Get the cutoff date using get_date_to_check with the actual date_range from args
		$date_to_check = $this->get_date_to_check( array(
			'date_range'         => $args['date_range'],
			'product_or_term_id' => ( isset( $args['product_or_term_id'] ) ? $args['product_or_term_id'] : null ),
			'current_user_id'    => ( isset( $args['current_user_id'] ) ? $args['current_user_id'] : null ),
			'is_product'         => ( isset( $args['is_product'] ) ? $args['is_product'] : null ),
		) );
		return ( isset( $args['order_date'] ) && $args['order_date'] >= $date_to_check );
	}

	/**
	 * get_monthly_range_origin_date.
	 *
	 * @version 4.5.0
	 * @since   3.8.4
	 *
	 * @param $args
	 *
	 * @return string Date in strtotime format.
	 */
	function get_monthly_range_origin_date( $args ) {
		$origin_date = get_option( 'alg_wc_mppu_date_range_origin_date', 'user_register_date' );
		$date        = $this->get_current_time();
		switch ( $origin_date ) {
			case 'user_register_date':
				if (
					isset( $args['current_user_id'] ) &&
					! empty( $user_id = $args['current_user_id'] ) &&
					is_a( $user = get_userdata( $user_id ), 'WP_User' )
				) {
					$date = strtotime( $user->user_registered );
				}
				break;
		}
		return apply_filters( 'wpfmppu_get_monthly_range_origin_date', $date, $origin_date, $args );
	}

	/**
	 * get_time_offset.
	 *
	 * @version 3.7.0
	 * @since   3.7.0
	 *
	 * @return mixed|string|void
	 */
	function get_time_offset() {
		if ( is_null( $this->time_offset ) ) {
			$this->time_offset = get_option( 'alg_wc_mppu_time_offset', '' );
		}
		return $this->time_offset;
	}

	/**
	 * get_current_time_offset.
	 *
	 * @version 4.1.5
	 * @since   4.1.5
	 *
	 * @return false|mixed|string|null
	 */
	function get_current_time_offset(){
		if ( is_null( $this->current_time_offset ) ) {
			$this->current_time_offset = get_option( 'alg_wc_mppu_current_time_offset', '' );
		}
		return $this->current_time_offset;
	}

	/**
	 * compensate_date_to_check_time.
	 *
	 * @version 3.7.0
	 * @since   3.7.0
	 *
	 * @param $date
	 *
	 * @return false|int
	 */
	function compensate_date_to_check_time( $date ) {
		if ( ! empty( $time_offset = $this->get_time_offset() ) ) {
			$date = strtotime( $time_offset, $date );
		}
		return $date;
	}

	/**
	 * compensate_datetime_to_compare.
	 *
	 * @version 4.1.5
	 * @since   4.1.5
	 *
	 * @param $datetime
	 *
	 * @return mixed
	 */
	function compensate_current_time_to_compare( $datetime ) {
		if ( ! empty( $time_offset = $this->get_current_time_offset() ) ) {
			$datetime = strtotime( $time_offset, $datetime );
		}
		return $datetime;
	}

	/**
	 * init_bkg_process.
	 *
	 * @version 4.5.0
	 * @since   3.6.4
	 */
	function init_bkg_process() {
		require_once( 'background-process/class-wpfmppu-bkg-process.php' );
		add_filter( 'wpfmppu_bkg_process_email_params', array( $this, 'change_bkg_process_params' ) );
		new WPFMPPU_Bkg_Process();
	}

	/**
	 * change_bkg_process_email_params.
	 *
	 * @version 3.6.4
	 * @since   3.6.4
	 *
	 * @param $email_params
	 *
	 * @return mixed
	 */
	function change_bkg_process_params( $email_params ) {
		$email_params['send_email_on_task_complete'] = 'yes' === get_option( 'alg_wc_mppu_bkg_process_send_email', 'yes' );
		$email_params['send_to']                     = get_option( 'alg_wc_mppu_bkg_process_email_to', get_option( 'admin_email' ) );
		return $email_params;
	}

	/**
	 * set_guest_user_bought_to_zero
	 *
	 * @version 4.5.0
	 * @since   3.5.3
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	function set_guest_user_bought_to_zero( $data ) {
		if (
			! wpfmppu_is_user_logged_in()
			&& 'block_beyond_limit' == get_option( 'alg_wc_mppu_block_guests' )
		) {
			$data['bought'] = 0;
		}
		return $data;
	}

	/**
	 * filter_customer_message_shortcode.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 *
	 * @param $atts
	 *
	 * @return mixed
	 */
	function filter_customer_message_shortcode( $atts ) {
		if (
			is_null( $atts['bought'] )
			&& isset( $this->placeholders['%bought%'] )
		) {
			$atts['bought'] = $this->placeholders['%bought%'];
		}
		return $atts;
	}

	/**
	 * add_to_log.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function add_to_log( $message ) {
		if ( function_exists( 'wc_get_logger' ) && ( $log = wc_get_logger() ) ) {
			$log->log( 'info', $message, array( 'source' => 'alg-wc-mppu' ) );
		}
	}

	/**
	 * count_by_current_payment_method.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @todo    [maybe] customizable `return false;` (i.e. optional `return true;` on `empty( $chosen_payment_method )`)?
	 */
	function count_by_current_payment_method( $do_count, $order_id, $order_data ) {
		$chosen_payment_method = $this->get_chosen_payment_method();
		if ( ! empty( $chosen_payment_method ) ) {
			if ( isset( $order_data['payment_method'] ) ) {
				return ( $order_data['payment_method'] === $chosen_payment_method );
			} else { // fallback for < MPPU v3.5.0
				$order = wc_get_order( $order_id );
				return ( $order && $order->get_payment_method() === $chosen_payment_method );
			}
		}
		return false;
	}

	/**
	 * get_current_user_id.
	 *
	 * @version 3.8.8
	 * @since   3.4.0
	 * @todo    [next] identify guests: by address (shipping/billing); by email (also in `WPFMPPU_Data::get_user_id_from_order()`)
	 * @todo    [later] identify guests: IP: option to block "unidentifiable" users (i.e. when `'' === WC_Geolocation::get_ip_address()`)?
	 * @todo    [maybe] identify guests: IP: fallback to `WC_Geolocation::get_external_ip_address()`?
	 */
	function get_current_user_id() {
		if ( ! isset( $this->current_user_id ) ) {
			if ( ! function_exists( 'get_current_user_id' ) ) {
				return 0;
			}

			$this->current_user_id = get_current_user_id();

			if ( 0 == $this->current_user_id && $this->do_identify_guests_by_ip ) {
				$this->current_user_id = 'ip:' . WC_Geolocation::get_ip_address();
			}

			if ( 0 == $this->current_user_id && $this->do_identify_by_checkout_email ) {
				$this->current_user_id = WC()->checkout->get_value('billing_email');
			}
		}
		return $this->current_user_id;
	}

	/**
	 * product_visibility.
	 *
	 * @version 4.5.0
	 * @since   3.4.0
	 * @todo    [next] (fix) `Showing all X results` (try filtering `product_visibility` taxonomy) (already tried `woocommerce_product_get_catalog_visibility` filter (returning `hidden`) - didn't help)
	 * @todo    [next] add option to count current `$cart_item_quantities` as well (i.e. pass `$cart_item_quantities` as 6th param in `check_quantities_for_product()`, and `$cart_item_quantities[ $product_id ] + 1` instead of `1`)
	 * @todo    [next] add similar option for `is_purchasable`?
	 * @todo    [next] (feature) hide via query (i.e. no direct link access)
	 * @todo    [later] (feature) option to hide everything for guests?
	 */
	function product_visibility( $visible, $product_id ) {
		if (
			'yes' === wpfmppu_get_option( 'alg_wc_mppu_hide_products', 'no' )
			&& wpfmppu_is_user_logged_in()
			&& ( $current_user_id = $this->get_current_user_id() )
			&& ! $this->check_quantities_for_product( $product_id, array(
				'_cart_item_quantity' => 1,
				'do_add_notices'      => false,
				'current_user_id'     => $current_user_id
			) )
		) {
			return false;
		}
		if (
			'yes' === get_option( 'alg_wc_mppu_hide_guest_blocked_products', 'no' )
			&& ! wpfmppu_is_user_logged_in()
			&& $this->is_product_blocked_for_guests( $product_id )
		) {
			return false;
		}
		return $visible;
	}

	/**
	 * remove_products_from_catalog.
	 *
	 * @version 4.5.0
	 * @since   3.6.7
	 *
	 * @param $posts
	 * @param $query
	 *
	 * @return array
	 */
	function hide_products_from_search_and_direct_links( $posts, $query ) {
		$can_remove = false;
		if (
			! is_admin() ||
			( isset( $_POST['action'] ) && 'is_ajax_load_posts' === $_POST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		) {
			$can_remove = true;
		}
		$keys_to_remove = array();
		// Select products to remove for logged in users.
		if (
			$can_remove &&
			'yes' === wpfmppu_get_option( 'alg_wc_mppu_hide_products_on_search_and_direct_links', 'no' ) &&
			wpfmppu_is_user_logged_in() &&
			( $current_user_id = $this->get_current_user_id() )
		) {
			foreach ( $posts as $i => $post ) {
				if ( 'product' === get_post_type( $post ) ) {
					if ( ! $this->check_quantities_for_product( $post->ID, array(
						'_cart_item_quantity' => 1,
						'do_add_notices'      => false,
						'current_user_id'     => $current_user_id
					) ) ) {
						$keys_to_remove[] = $i;
					}
				}
			}
		}
		// Select products to remove for guest users.
		if (
			$can_remove &&
			'yes' === wpfmppu_get_option( 'alg_wc_mppu_hide_products_on_search_and_direct_links', 'no' ) &&
			//! is_singular() && // Remove or comment to hide it even on direct links.
			'yes' === get_option( 'alg_wc_mppu_hide_guest_blocked_products', 'no' )
			&& ! wpfmppu_is_user_logged_in()
		) {
			$keys_to_remove = array();
			foreach ( $posts as $i => $post ) {
				if ( 'product' === get_post_type( $post ) ) {
					if ( $this->is_product_blocked_for_guests( $post->ID ) ) {
						$keys_to_remove[] = $i;
					}
				}
			}
		}
		// Remove products.
		if (
			$can_remove &&
			! empty( $keys_to_remove )
		) {
			$posts = array_diff_key( $posts, array_flip( $keys_to_remove ) );
			$posts = array_values( $posts );
		}
		return $posts;
	}

	/**
	 * get_permanent_notice.
	 *
	 * @see https://stackoverflow.com/a/18081767/1193038
	 *
	 * @version 4.5.0
	 * @since   3.2.0
	 */
	function get_permanent_notice( $args = null ) {
		$args                           = wp_parse_args( $args, array(
			'current_product_limit_args' => array(
				'empty_msg_removes_template' => 'woocommerce_before_single_product' === current_filter() ? true : false
			),
		) );
		$current_product_limit_args_arr = $args['current_product_limit_args'];
		$current_product_limit_args     = join( ' ', array_map( function ( $key ) use ( $current_product_limit_args_arr ) {
			return $key . "='" . $current_product_limit_args_arr[ $key ] . "'";
		}, array_keys( $current_product_limit_args_arr ) ) );
		return ( is_singular( array( 'product' ) ) && ( $message = do_shortcode( '[wpfmppu_current_product_limit ' . $current_product_limit_args . ']' ) ) ? $message : false );
	}

	/**
	 * permanent_notice_text_content.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function permanent_notice_text_content( $content ) {
		if ( $message = $this->get_permanent_notice() ) {
			$content = '<p>' . $message . '</p>' . $content;
		}
		return $content;
	}

	/**
	 * permanent_notice_text.
	 *
	 * @version 4.5.0
	 * @since   2.5.1
	 */
	function permanent_notice_text() {
		if ( $message = $this->get_permanent_notice() ) {
			echo wp_kses_post( $message );
		}
	}

	/**
	 * permanent_notice.
	 *
	 * @version 3.2.0
	 * @since   2.5.0
	 * @todo    [later] customizable type (and type depending on `$remaining`, i.e. `notice` vs `error`)
	 */
	function permanent_notice() {
		if ( $message = $this->get_permanent_notice() ) {
			wc_print_notice( $message, 'notice' );
		}
	}

	/**
	 * get_cart_item_quantities.
	 *
	 * @version 4.5.0
	 * @since   2.4.2
	 */
	function get_cart_item_quantities() {
		return apply_filters( 'wpfmppu_get_cart_item_quantities', WC()->cart->get_cart_item_quantities() );
	}

	/**
	 * get_order_item_quantities.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	function get_order_item_quantities( $order ) {
		$quantities = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product                = $item->get_product();
			$prod_id                = $product->get_id();
			$quantities[ $prod_id ] = $item->get_quantity();
		}
		return $quantities;
	}

	/**
	 * handle_user_roles.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function handle_user_roles( $role ) {
		return ( '' == $role ? 'guest' : ( 'super_admin' == $role ? 'administrator' : $role ) );
	}

	/**
	 * get_max_qty_for_user_role.
	 *
	 * @version 3.8.8
	 * @since   2.2.0
	 * @todo    [maybe] (feature) per user (currently can be done via "Formula" section)
	 */
	function get_max_qty_for_user_role( $args = null ) {
		$args = wp_parse_args( $args, array(
			'type'               => '',
			'product_or_term_id' => 0,
			'user_id'            => 0,
		) );
		$type = $args['type'];
		$product_or_term_id = $args['product_or_term_id'];
		$user_id = $args['user_id'];
		$current_user = empty( $user_id ) ? wp_get_current_user() : get_user_by( 'ID', $user_id );
		// Get qty data
		switch ( $type ) {
			case 'per_product':
				$user_roles_data = ( '' != ( $data = get_post_meta( $product_or_term_id, '_alg_wc_mppu_user_roles_max_qty', true ) ) ? $data : array() );
				break;
			case 'all_products':
				$user_roles_data = get_option( 'alg_wc_mppu_user_roles_max_qty', array() );
				break;
			case 'per_term':
				$user_roles_data = ( '' != ( $data = get_term_meta( $product_or_term_id, '_alg_wc_mppu_user_roles_max_qty', true ) ) ? $data : array() );
				break;
			default: // 'formula'
				return 0;
		}
		// Get current user roles
		if ( ! isset( $current_user->roles ) || empty( $current_user->roles ) ) {

			if ( !$current_user ) {
				$current_user = new \stdClass();
			}

			$current_user->roles = array( 'guest' );
		}
		$current_user->roles = array_map( array( $this, 'handle_user_roles' ), $current_user->roles );
		// Return result
		foreach ( $current_user->roles as $role ) {
			if ( ! empty( $user_roles_data[ $role ] ) && $this->is_user_role_enabled( $role ) ) {
				return $user_roles_data[ $role ];
			}
		}
		// Nothing found for the user role
		return 0;
	}

	/**
	 * get_max_qty.
	 *
	 * @version 4.5.0
	 * @since   1.0.0
	 * @todo    [later] (feature) `per_product`: add "enabled/disabled" option
	 * @todo    [later] (feature) `all_products`: apply only to selected products (i.e. include/exclude products, cats, tags)
	 * @todo    [maybe] rename filters, e.g. `wpfmppu_max_qty_by_formula` to `alg_wc_mppu_limits_by_formula` etc.
	 */
	function get_max_qty( $args = null ) {
		$args = wp_parse_args( $args, array(
			'type'               => '',
			'product_or_term_id' => '',
			'user_id'            => 0
		) );
		$type = $args['type'];
		$product_or_term_id = $args['product_or_term_id'];
		if ( wpfmppu()->core->multilanguage->get_product_id_from_main_language() ) {
			$product_or_term_id = apply_filters( 'wpfmppu_data_product_or_term_id', $product_or_term_id, 'per_product' === $type );
		}
		$user_id = $args['user_id'];
		if ( 0 != ( $max_qty_by_formula = apply_filters( 'wpfmppu_max_qty_by_formula', 0, $type, $product_or_term_id ) ) ) {
			return apply_filters( 'wpfmppu_get_max_qty', $max_qty_by_formula, $product_or_term_id, $type );
		}

		if ( $this->do_use_user_roles && 0 != ( $max_qty_for_user_role = $this->get_max_qty_for_user_role( array( 'type' => $type, 'product_or_term_id' => $product_or_term_id, 'user_id' => $user_id ) ) ) ) {
			return apply_filters( 'wpfmppu_get_max_qty', $max_qty_for_user_role, $product_or_term_id, $type );
		}
		switch ( $type ) {
			case 'per_product':
				return apply_filters( 'wpfmppu_get_max_qty', ( ( $qty = get_post_meta( $product_or_term_id, '_wpjup_wc_maximum_products_per_user_qty', true ) ) ? $qty : 0 ), $product_or_term_id, $type );
			case 'all_products':
				return apply_filters( 'wpfmppu_get_max_qty', get_option( 'wpjup_wc_maximum_products_per_user_global_max_qty', 1 ), $product_or_term_id, $type );
			case 'per_term':
				return apply_filters( 'wpfmppu_get_max_qty', ( ( $qty = get_term_meta( $product_or_term_id, '_alg_wc_mppu_qty', true ) ) ? $qty : 0 ), $product_or_term_id, $type );
		}
		return apply_filters( 'wpfmppu_get_max_qty', 0, $product_or_term_id, $type ); // 'formula'
	}

	/**
	 * get_block_guests_method.
	 *
	 * Normalizes the option value in case it was stored as an array by chosen_select.
	 *
	 * @version 4.5.1
	 * @since   4.5.1
	 *
	 * @return string
	 */
	function get_block_guests_method() {
		$method = get_option( 'alg_wc_mppu_block_guests_method', 'all_products' );
		if ( is_array( $method ) ) {
			$method = reset( $method );
		}
		return (string) $method;
	}

	/**
	 * is_product_blocked_for_guests.
	 *
	 * @version 4.5.1
	 * @since   3.5.1
	 *
	 * @param $product_id
	 * @param null $args
	 *
	 * @return boolean
	 */
	function is_product_blocked_for_guests( $product_id, $args = null ) {
		$is_blocked = false;
		if ( isset( $this->product_blocked_for_guests[ $product_id ] ) ) {
			$is_blocked = apply_filters( 'wpfmppu_is_product_blocked_for_guests', $this->product_blocked_for_guests[ $product_id ], $product_id, $args );
			return $is_blocked;
		}
		if ( 'yes' !== get_option( 'alg_wc_mppu_block_guests', 'no' ) ) {
			$is_blocked = $this->product_blocked_for_guests[ $product_id ] = apply_filters( 'wpfmppu_is_product_blocked_for_guests', false, $product_id, $args );
			return $is_blocked;
		}
		if ( 'all_products' === ( $block_method = $this->get_block_guests_method() ) ) {
			$is_blocked = $this->product_blocked_for_guests[ $product_id ] = apply_filters( 'wpfmppu_is_product_blocked_for_guests', true, $product_id, $args );
			return $is_blocked;
		}

		$is_blocked = apply_filters( 'wpfmppu_is_product_blocked_for_guests_by_limit_options', $is_blocked, $product_id, $args, $this );

		return $this->product_blocked_for_guests[ $product_id ] = apply_filters( 'wpfmppu_is_product_blocked_for_guests', $is_blocked, $product_id, $args );
	}

	/**
	 * block_checkout.
	 *
	 * @version 3.6.1
	 * @since   1.0.0
	 */
	function block_checkout() {
		if (
			! function_exists( 'is_checkout' )
			|| ! is_checkout()
			|| WC()->cart->get_cart_contents_count() == 0
			|| isset( $_POST['woocommerce_checkout_place_order'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			|| defined( 'WOOCOMMERCE_CHECKOUT' )
		) {
			return;
		}
		if (
			! is_order_received_page()
			&& ! $this->check_quantities( array(
				'do_add_notices' => false
			) )
		) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
	}

	/**
	 * validate_on_add_to_cart.
	 *
	 * @version 4.5.0
	 * @since   2.0.0
	 * @todo    [later] `alg_wc_mppu_block_guests`: add "Block products with max qty" option (`yes_max_qty`), i.e. check for `0 != $this->get_max_qty_for_product( $_product_id, $product_id )`
	 * @todo    [maybe] different message (i.e. different from "cart" message)?
	 * @todo    [maybe] perform check only `$passed` is `true`?
	 */
	function validate_on_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0 ) {
		$cart_item_quantities = $this->get_cart_item_quantities();
		if ( 0 != $variation_id ) {
			$product_id = $variation_id;
		}
		$quantity = apply_filters( 'wpfmppu_validate_on_add_to_cart_quantity', $quantity, $product_id, $cart_item_quantities );
		$adding   = $quantity;
		if (
			apply_filters( 'wpfmppu_validate_on_add_to_cart_quantity_do_add', true ) &&
			! empty( $cart_item_quantities[ $product_id ] )
		) {
			$quantity += $cart_item_quantities[ $product_id ];
		}
		if ( $current_user_id = $this->get_current_user_id() ) {
			if ( ! $this->check_quantities_for_product( $product_id, array(
				'_cart_item_quantity'  => $quantity,
				'current_user_id'      => $current_user_id,
				'cart_item_quantities' => $cart_item_quantities,
				'adding'               => $adding,
				'notice_type'          => 'error'
			) ) ) {
				return false;
			}
		} elseif ( 'yes' === get_option( 'alg_wc_mppu_block_guests', 'no' ) ) {
			if ( $this->is_product_blocked_for_guests( $product_id ) ) {
				if ( ! wp_doing_ajax() ) {
					do_action( 'wpfmppu_before_block_guest_on_add_to_cart' );
					$this->output_guest_notice( false );
				} else {
					add_filter( 'woocommerce_cart_redirect_after_error', array( $this, 'block_guest_add_to_cart_ajax_redirect' ), PHP_INT_MAX, 2 );
				}
				return false;
			}
		} elseif ( 'block_beyond_limit' == get_option( 'alg_wc_mppu_block_guests' ) ) {
			if ( ! $this->check_quantities_for_product( $product_id, array(
				'_cart_item_quantity'  => $quantity,
				'check_guest_blocking' => false,
				'current_user_id'      => $current_user_id,
				'cart_item_quantities' => $cart_item_quantities,
				'adding'               => $adding,
				'notice_type'          => 'error'
			) ) ) {
				return false;
			}
		}
		return $passed;
	}

	/**
	 * block_guest_add_to_cart_ajax_redirect.
	 *
	 * @version 3.5.5
	 * @since   3.1.1
	 */
	function block_guest_add_to_cart_ajax_redirect( $url, $product_id ) {
		$url = apply_filters(
			'woocommerce_cart_redirect_after_block_guest_error', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			add_query_arg( 'alg_wc_mppu_guest', true, $url ),
			$product_id
		);
		return $url;
	}

	/**
	 * block_guest_add_to_cart_ajax_error.
	 *
	 * @version 3.1.1
	 * @since   3.1.1
	 */
	function block_guest_add_to_cart_ajax_error() {
		if ( isset( $_GET['alg_wc_mppu_guest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->output_guest_notice( false );
		}
	}

	/**
	 * output_guest_notice.
	 *
	 * @version 3.5.2
	 * @since   2.6.0
	 * @todo    [next] (fix) AJAX add to cart
	 * @todo    [later] customizable notice type
	 */
	function output_guest_notice( $is_cart ) {
		$default_msg = sprintf(
			/* Translators: %1$s: URL, %2$s: Link text, %3$s: Message. */
			'<a href="%1$s" class="button wc-forward">%2$s</a> %3$s',
			esc_url( wc_get_page_permalink( 'myaccount' ) ),
			__( 'Login', 'maximum-products-per-user-for-woocommerce' ),
			__( 'You need to register to buy products.', 'maximum-products-per-user-for-woocommerce' )
		);
		$message = get_option( 'alg_wc_mppu_block_guests_message', $default_msg );
		$message = do_shortcode( $message );
		if ( $is_cart ) {
			wc_print_notice( $message, 'notice' );
		} else {
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * check_cart_quantities.
	 *
	 * @version 3.6.1
	 * @since   1.0.0
	 */
	function check_cart_quantities() {
		$this->check_quantities( array(
			'do_add_notices' => true
		) );
	}

	/**
	 * check_cart_quantities_and_add_errors_to_cart.
	 *
	 * @version 4.5.0
	 * @since   4.2.0
	 *
	 * @param $cart_errors
	 * @param $cart
	 *
	 * @return void
	 */
	function check_cart_quantities_and_add_errors_to_cart( $cart_errors, $cart ) {
		wpfmppu()->core->check_quantities( array( 'return_notices' => true ) );
		$errors = wpfmppu()->core->error_messages;
		if ( ! empty( $errors ) ) {
			$cart_errors->add( 'alg_wc_mppu_order_above_limit', $errors[0] );
		}
	}

	/**
	 * get_current_time.
	 *
	 * @version 3.4.0
	 * @since   3.2.0
	 */
	function get_current_time() {
		if ( ! isset( $this->current_time ) ) {
			$this->current_time = ( 'time' === get_option( 'alg_wc_mppu_time_func', 'current_time' ) ? time() : ( int ) current_time( 'timestamp' ) );
		}
		return $this->current_time;
	}

	/**
	 * get_custom_date_range_in_seconds.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    [maybe] add approximate values, i.e. 'months' (`MONTH_IN_SECONDS`), 'years' (`YEAR_IN_SECONDS`)?
	 */
	function get_custom_date_range_in_seconds() {
		$value = get_option( 'alg_wc_mppu_date_range_custom', 3600 );
		switch ( get_option( 'alg_wc_mppu_date_range_custom_unit', 'seconds' ) ) {
			case 'minutes':
				return $value * MINUTE_IN_SECONDS;
			case 'hours':
				return $value * HOUR_IN_SECONDS;
			case 'days':
				return $value * DAY_IN_SECONDS;
			case 'weeks':
				return $value * WEEK_IN_SECONDS;
			default: // 'seconds'
				return $value;
		}
	}

	/**
	 * get_date_to_check.
	 *
	 * @version 4.5.0
	 * @since   2.4.0
	 * @todo    [maybe] add `alg_wc_mppu_date_to_check_custom` filter
	 * @todo    [maybe] add more predefined ranges, e.g. `last_14_days`, `last_45_days`, `last_60_days`, `MINUTE_IN_SECONDS`
	 *
	 * @param null $args
	 *
	 * @return mixed
	 */
	function get_date_to_check( $args = null ) {
		$args = wp_parse_args( $args, array(
			'date_range'          => '',
			'product_or_term_id'  => null,
			'current_user_id'     => null,
			'is_product'          => null,
			'datetime_to_compare' => $this->get_current_time()
		) );
		$date_range          = $args['date_range'];
		$product_or_term_id  = $args['product_or_term_id'];
		$current_user_id     = $args['current_user_id'];
		$is_product          = $args['is_product'];
		$date_to_check       = 0;
		$datetime_to_compare = $args['datetime_to_compare'];
		$datetime_to_compare = apply_filters( 'wpfmppu_datetime_to_compare', $datetime_to_compare, $date_range, $product_or_term_id, $current_user_id, $is_product );
		switch ( $date_range ) {
			case 'lifetime':
				$date_to_check = 0;
				break;
			case 'this_hour':
				$date_to_check = strtotime( gmdate( 'Y-m-d H:00:00', $datetime_to_compare ) );
				break;
			case 'this_day':
				$date_to_check = strtotime( gmdate( 'Y-m-d 00:00:00', $datetime_to_compare ) );
				break;
			case 'this_week':
                $week_start_day = wpfmppu()->core->weekdays->get_week_starts_on_option();
				$week_start_day_formatted = $week_start_day['name_slug'];
				$date_to_check = strtotime( "{$week_start_day_formatted} this week", $datetime_to_compare );
				break;
			case 'this_month':
				$date_to_check = strtotime( gmdate( 'Y-m-01', $datetime_to_compare ) );
				break;
			case 'this_year':
				$date_to_check = strtotime( gmdate( 'Y-01-01', $datetime_to_compare ) );
				break;
			case 'last_hour':
				$date_to_check = ( $datetime_to_compare - HOUR_IN_SECONDS );
				break;
			case 'last_24_hours':
				$date_to_check = ( $datetime_to_compare - DAY_IN_SECONDS );
				break;
			case 'last_7_days':
				$date_to_check = ( $datetime_to_compare - WEEK_IN_SECONDS );
				break;
			case 'last_30_days':
				$date_to_check = ( $datetime_to_compare - MONTH_IN_SECONDS );
				break;
			case 'last_365_days':
				$date_to_check = ( $datetime_to_compare - YEAR_IN_SECONDS );
				break;
			case 'custom':
				$date_to_check = ( $datetime_to_compare - $this->get_custom_date_range_in_seconds() );
				break;
			case 'fixed_date':
				$fixed_date = get_option( 'alg_wc_mppu_date_range_fixed_date', '' );
				if ( ! empty( $fixed_date ) && $datetime_to_compare > strtotime( $fixed_date ) ) {
					$date_to_check = strtotime( $fixed_date );
				}
				break;
			case 'monthly':
				$datetime_to_compare_info         = getdate( $this->get_monthly_range_origin_date( $args ) );
				$current_time                     = $this->get_current_time();
				$current_time_info                = getdate( $current_time );
				$last_date_of_previous_month_info = getdate( strtotime( 'last day of previous month', $current_time ) );
				if ( $current_time_info['mday'] >= $datetime_to_compare_info['mday'] ) {
					$date_to_check_string = $current_time_info['year'] . '-' . $current_time_info['mon'] . '-' . $datetime_to_compare_info['mday'];
				} else {
					$month_day_to_compare = $datetime_to_compare_info['mday'] > $last_date_of_previous_month_info['mday'] ? $last_date_of_previous_month_info['mday'] : $datetime_to_compare_info['mday'];
					$date_to_check_string = $last_date_of_previous_month_info['year'] . '-' . $last_date_of_previous_month_info['mon'] . '-' . $month_day_to_compare;
				}
				$date_to_check = strtotime( $date_to_check_string );
				break;
			default:
				$date_to_check = false !== ( $date_time = DateTime::createFromFormat( 'Y-m-d', $date_range ) ) ? $date_time->getTimestamp() : $date_to_check;
				break;
		}


		return apply_filters( 'wpfmppu_date_to_check', $date_to_check, $date_range, $datetime_to_compare, $product_or_term_id, $current_user_id, $is_product );
	}

	/**
	 * get_date_range.
	 *
	 * @version 4.5.0
	 * @since   2.1.0
	 * @todo    [later] (feature) `alg_wc_mppu_date_range_per_product_or_term_id` ("Date range per product / term ID")
	 */
	function get_date_range( $product_or_term_id, $is_product ) {
		$date_range = get_option( 'alg_wc_mppu_date_range', 'lifetime' );
		/**
		 * Date range per product / term ID.
		 *
		 * if ( 'yes' === get_option( 'alg_wc_mppu_date_range_per_product_or_term_id', 'no' ) ) {
		 *     $date_range_per_product_or_term = $this->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_date_range' );
		 *     if ( ! empty( $date_range_per_product_or_term ) && 'default' != $date_range_per_product_or_term ) {
		 *         $date_range = $date_range_per_product_or_term;
		 *     }
		 * }
		 */
		return apply_filters( 'wpfmppu_date_range', $date_range, $product_or_term_id, $is_product );
		/**
		 * For example: Custom date range for product ID 100.
		 *
		 * As of v2.1.0, possible date range values are:
		 * 'lifetime', 'this_hour', 'this_day', 'this_week', 'this_month', 'this_year', 'last_hour', 'last_24_hours', 'last_7_days', 'last_30_days', 'last_365_days'
		 *
		 * add_filter( 'wpfmppu_date_range', 'my_custom_alg_wc_mppu_date_range', 10, 3 );
		 * function my_custom_alg_wc_mppu_date_range( $date_range, $product_or_term_id, $is_product ) {
		 *     return ( $is_product && 100 == $product_or_term_id ? 'lifetime' : $date_range );
		 * }
		 */
	}

	/**
	 * get_order_date.
	 *
	 * @version 4.5.0
	 * @since   3.2.3
	 */
	function get_order_date( $date_utc ) {
		return ( 'time' === get_option( 'alg_wc_mppu_time_func', 'current_time' ) ? $date_utc : strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $date_utc ) ) ) );
	}

	/**
	 * get_user_already_bought_qty.
	 *
	 * @version 4.5.1
	 * @since   2.0.0
	 * @todo    [next] completely remove separate `lifetime` calculation (i.e. `$this->do_get_lifetime_from_totals` always `false`)
	 * @todo    [maybe] add option to use e.g. order date completed (instead of `date_created`)
	 */
	function get_user_already_bought_qty( $product_or_term_id, $current_user_id, $is_product, $date_range = false ) {

		// check if guest user register himself also.

		$current_user_email = '';
		if ( is_user_logged_in() ) {
			$user = get_user_by( 'id', $current_user_id );
			if ( $user ) {
				$current_user_email = $user->user_email;
			}
		}

		$product_or_term_id  = apply_filters( 'wpfmppu_data_product_or_term_id', $product_or_term_id, $is_product );
		$user_already_bought = 0;
		$first_order_date    = false;
		$first_order_amount  = false;
		$date_range          = ( false === $date_range ? $this->get_date_range( $product_or_term_id, $is_product ) : $date_range );
		$cached_obj_name     = md5( maybe_serialize( array(
			'product_or_term_id' => $product_or_term_id,
			'current_user_id'    => $current_user_id,
			'is_product'         => $is_product,
			'date_range'         => $date_range,
		) ) );
		if ( ! isset( $this->user_already_bought_qty_cache[ $cached_obj_name ] ) ) {
			// Skip data lookup for unidentified guests (user_id = 0).
			// Data stored under key 0 belongs to other unidentified guests and
			// should not be attributed to the current guest, as there is no way
			// to tell them apart without IP or email identification enabled.
			if ( 0 !== $current_user_id || ( is_string( $current_user_id ) && '0' !== $current_user_id ) ) {
				if ( $this->do_get_lifetime_from_totals && 'lifetime' === $date_range ) {
					$users_quantities = $this->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_totals_data' );
					if ( $users_quantities && isset( $users_quantities[ $current_user_id ] ) ) {
						$user_already_bought = $users_quantities[ $current_user_id ];
					}

					// check if guest user register himself also.
					if ( $users_quantities && ! empty( $current_user_email ) && isset( $users_quantities[ $current_user_email ] ) ) {
						$user_already_bought = $user_already_bought + $users_quantities[ $current_user_email ];
					}

				} else {
					$users_quantities = $this->get_post_or_term_meta( ( $is_product ? 'product' : 'term' ), $product_or_term_id, '_alg_wc_mppu_orders_data' );
					if ( $users_quantities && isset( $users_quantities[ $current_user_id ] ) ) {
						foreach ( $users_quantities[ $current_user_id ] as $order_id => $order_data ) {
							$order_date = $this->get_order_date( $order_data['date_created'] );
							if (
								true === apply_filters( 'wpfmppu_user_already_bought_validation', true, array(
									'order_date'         => $order_date,
									'date_range'         => $date_range,
									'product_or_term_id' => $product_or_term_id,
									'current_user_id'    => $current_user_id,
									'is_product'         => $is_product
								) ) &&
								$order_date >= $this->get_date_to_check( array(
									'date_range'         => $date_range,
									'product_or_term_id' => $product_or_term_id,
									'current_user_id'    => $current_user_id,
									'is_product'         => $is_product
								) ) &&
								apply_filters( 'wpfmppu_user_already_bought_do_count_order', true, $order_id, $order_data )
							) {
								$user_already_bought += $order_data['qty'];
								if ( false === $first_order_date || $order_date < $first_order_date ) {
									$first_order_date   = $order_date;
									$first_order_amount = $order_data['qty'];
								}
							}
						}
					}

					// check if guest user register himself also.
					if ( $users_quantities && ! empty( $current_user_email ) && isset( $users_quantities[ $current_user_email ] ) ) {
						foreach ( $users_quantities[ $current_user_email ] as $order_id => $order_data ) {
							$order_date = $this->get_order_date( $order_data['date_created'] );
							if (
								true === apply_filters( 'wpfmppu_user_already_bought_validation', true, array(
									'order_date'         => $order_date,
									'date_range'         => $date_range,
									'product_or_term_id' => $product_or_term_id,
									'current_user_id'    => $current_user_email,
									'is_product'         => $is_product
								) ) &&
								$order_date >= $this->get_date_to_check( array(
									'date_range'         => $date_range,
									'product_or_term_id' => $product_or_term_id,
									'current_user_id'    => $current_user_email,
									'is_product'         => $is_product
								) ) &&
								apply_filters( 'wpfmppu_user_already_bought_do_count_order', true, $order_id, $order_data )
							) {
								$user_already_bought += $order_data['qty'];
								if ( false === $first_order_date || $order_date < $first_order_date ) {
									$first_order_date   = $order_date;
									$first_order_amount = $order_data['qty'];
								}
							}
						}
					}

				}
			} // End unidentified guest check.
			$this->user_already_bought_qty_cache[ $cached_obj_name ] = $user_already_bought;
		} else {
			$user_already_bought = $this->user_already_bought_qty_cache[ $cached_obj_name ];
		}

		return apply_filters( 'wpfmppu_user_already_bought', array(
			'bought'             => ( $user_already_bought ? $user_already_bought : 0 ),
			'first_order_date'   => $first_order_date,
			'first_order_amount' => $first_order_amount,
			'date_range'         => $date_range,
			'product_or_term_id' => $product_or_term_id,
			'current_user_id'    => $current_user_id,
			'is_product'         => $is_product
		) );
	}

	/**
	 * get_date_format.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 */
	function get_date_format() {
		return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	/**
	 * get_first_order_date_exp.
	 *
	 * @version 4.5.0
	 * @since   3.2.0
	 * @todo    [maybe] customizable date/time format
	 * @todo    [maybe] alternative to `human_time_diff()`?
	 */
	function get_first_order_date_exp( $first_order_date, $date_range, $do_timeleft = false ) {
		$current_time = $this->get_current_time(); // needed for the `this_` part, and for the final filters
		if ( false !== $first_order_date ) {
			$offset = 0;
			if ( 'this_' === substr( $date_range, 0, 5 ) ) {
				switch ( $date_range ) {
					case 'this_hour':
						$offset = strtotime( gmdate( 'Y-m-d H:00:00' ) . ' + 1 hour', $current_time );
						break;
					case 'this_day':
						$offset = strtotime( gmdate( 'Y-m-d 00:00:00' ) . ' + 1 day', $current_time );
						break;
					case 'this_week':
						$offset = strtotime( 'monday this week' . ' + 1 week', $current_time );
						break;
					case 'this_month':
						$offset = strtotime( gmdate( 'Y-m-01' ) . ' + 1 month', $current_time );
						break;
					case 'this_year':
						$offset = strtotime( gmdate( 'Y-01-01' ) . ' + 1 year', $current_time );
						break;
				}
			} else { // 'last_', 'custom'
				switch ( $date_range ) {
					case 'last_hour':
						$offset = HOUR_IN_SECONDS;
						break;
					case 'last_24_hours':
						$offset = DAY_IN_SECONDS;
						break;
					case 'last_7_days':
						$offset = WEEK_IN_SECONDS;
						break;
					case 'last_30_days':
						$offset = MONTH_IN_SECONDS;
						break;
					case 'last_365_days':
						$offset = YEAR_IN_SECONDS;
						break;
					case 'custom':
						$offset = $this->get_custom_date_range_in_seconds();
						break;
					case 'fixed_date':
						$fixed_date = get_option( 'alg_wc_mppu_date_range_fixed_date', '' );
						$offset     = $fixed_date ? strtotime( $fixed_date ) : 0;
						break;
				}
				$offset += ( 'fixed_date' !== $date_range ? $first_order_date : 0 );
			}
			return apply_filters( 'wpfmppu_get_first_order_date_exp', ( $do_timeleft ? human_time_diff( $offset, $current_time ) : date_i18n( $this->get_date_format(), $offset ) ),
				$first_order_date, $date_range, $do_timeleft, $offset, $current_time );
		}
		return apply_filters( 'wpfmppu_get_first_order_date_exp', '', $first_order_date, $date_range, $do_timeleft, 0, $current_time );
	}

	/**
	 * get_notice_placeholders.
	 *
	 * @version 4.5.0
	 * @since   3.1.1
	 * @todo    [next] `%product_title%`: `get_the_title( $product_id )`?
	 */
	function get_notice_placeholders( $product_id, $limit, $bought_data, $in_cart_plus_adding, $adding, $term = false ) {
		$product   = wc_get_product( $product_id );
		$remaining = ( $limit - $bought_data['bought'] );
		$in_cart   = ( $in_cart_plus_adding - $adding );
		$this->placeholders = apply_filters( 'wpfmppu_get_notice_placeholders', array(
			'%limit%'                                => max( $limit, 0 ),
			'%max_qty%'                              => max( $limit, 0 ),            // deprecated
			'%bought%'                               => $bought_data['bought'],
			'%qty_already_bought%'                   => $bought_data['bought'],      // deprecated
			'%remaining%'                            => max( $remaining, 0 ),
			'%remaining_qty%'                        => max( $remaining, 0 ),        // deprecated
			'%in_cart%'                              => $in_cart,
			'%bought_plus_in_cart%'                  => ( $bought_data['bought'] + $in_cart ),
			'%remaining_minus_in_cart%'              => max( ( $remaining - $in_cart ), 0 ),
			'%adding%'                               => $adding,
			'%in_cart_plus_adding%'                  => $in_cart_plus_adding,
			'%bought_plus_in_cart_plus_adding%'      => ( $bought_data['bought'] + $in_cart_plus_adding ),
			'%remaining_minus_in_cart_minus_adding%' => max( ( $remaining - $in_cart_plus_adding ), 0 ),
			'%product_title%'                        => method_exists( $product, 'get_name' ) ? $product->get_name() : $product->get_title(),
			'%term_name%'                            => ( $term ? $term->name : '' ),
			'%first_order_date%'                     => ( false !== $bought_data['first_order_date'] ? date_i18n( $this->get_date_format(), $bought_data['first_order_date'] ) : '' ),
			'%first_order_amount%'                   => ( false !== $bought_data['first_order_amount'] ? $bought_data['first_order_amount'] : '' ),
			'%first_order_date_exp%'                 => $this->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'] ),
			'%first_order_date_exp_timeleft%'        => $this->get_first_order_date_exp( $bought_data['first_order_date'], $bought_data['date_range'], true ),
			'%payment_method_title%'                 => $this->get_chosen_payment_method_title(),
		), $product_id, $limit, $bought_data, $in_cart_plus_adding, $adding, $term );
		return $this->placeholders;
	}

	/**
	 * apply_placeholders.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function apply_placeholders( $message ) {
		$result = do_shortcode( str_replace( array_keys( $this->placeholders ), $this->placeholders, $message ) );
		unset( $this->placeholders );
		return $result;
	}

	/**
	 * output_notice.
	 *
	 * @version 4.5.0
	 * @since   2.0.0
	 * @todo    [maybe] customizable notice type in `wc_add_notice()`?
	 */
	function output_notice( $args = null ) {
		$args                = wp_parse_args( $args, array(
			'product_id'          => '',
			'limit'               => '',
			'bought_data'         => '',
			'in_cart_plus_adding' => false,
			'adding'              => '',
			'return'              => false,
			'term'                => false,
			'msg'                 => false,
			'output_function'     => 'wc_add_notice', // wc_print_notice || wc_add_notice
			'notice_type'         => ( function_exists( 'is_checkout' ) && is_checkout() ) ? 'error' : get_option( 'alg_wc_mppu_cart_notice_type', 'notice' ), // notice || error
		) );
		$args                = apply_filters( 'wpfmppu_output_notices_args', $args );
		$product_id          = $args['product_id'];
		$limit               = (float) $args['limit'];
		$bought_data         = $args['bought_data'];
		$output_function     = $args['output_function'];
		$notice_type         = $args['notice_type'];
		$in_cart_plus_adding = $args['in_cart_plus_adding'];
		$adding              = $args['adding'];
		$term                = $args['term'];
		$msg                 = $args['msg'];
		$return              = $args['return'];
		$this->get_notice_placeholders( $product_id, $limit, $bought_data, $in_cart_plus_adding, $adding, $term );
		$message = (
		$msg ? $msg :
			( $term ?
				get_option( 'wpjup_wc_maximum_products_per_term_message',
					sprintf(
						'[wpfmppu_customer_msg bought_msg="%s" not_bought_msg="%s"]',
						/* Translators: %limit%: Maximum limit, %term_name%: Term name, %bought%: Bought quantity.  */
						__( "You can only buy maximum %limit% of %term_name% (you've already bought %bought%).", 'maximum-products-per-user-for-woocommerce' ),
						/* Translators: %limit%: Limit,  %term_name%: Term name. */
						__( "You can only buy maximum %limit% of %term_name%.", 'maximum-products-per-user-for-woocommerce' )
					)
				) :
				get_option( 'wpjup_wc_maximum_products_per_user_message',
					sprintf(
						'[wpfmppu_customer_msg bought_msg="%s" not_bought_msg="%s"]',
						/* Translators: %limit%: Maximum limit, %product_title%: Product title, %bought%: Bought quantity.  */
						__( "You can only buy maximum %limit% of %product_title% (you've already bought %bought%).", 'maximum-products-per-user-for-woocommerce' ),
						/* Translators: %limit%: Maximum limit,  %product_title%: Product title. */
						__( "You can only buy maximum %limit% of %product_title%.", 'maximum-products-per-user-for-woocommerce' )
					)
				)
			)
		);
		$message = $this->apply_placeholders( do_shortcode( $message ) );
		if ( ! $return ) {
			if ( ! empty( $output_function ) ) {
				call_user_func_array( $output_function, array( $message, $notice_type ) );
			} else {
				echo wp_kses_post( $message );
			}
		} else {
			$this->error_messages[] = $message;
			return $message;
		}
	}

	/**
	 * get_cart_item_quantity_by_term.
	 *
	 * @version 4.5.0
	 * @since   2.0.0
	 */
	function get_cart_item_quantity_by_term( $current_product_id, $current_cart_item_quantity, $cart_item_quantities, $current_term_id, $taxonomy ) {
		$result = $current_cart_item_quantity;
		if ( ! empty( $cart_item_quantities ) ) {
			foreach ( $cart_item_quantities as $product_id => $product_qty ) {
				if ( $product_id != $current_product_id ) {
					if ( 0 != ( $parent_product_id = wp_get_post_parent_id( $product_id ) ) ) {
						$product_id = $parent_product_id;
					}
					$terms = get_the_terms( $product_id, $taxonomy );
					if ( $terms && ! is_wp_error( $terms ) ) {
						foreach( $terms as $term ) {
							if ( $term->term_id === $current_term_id ) {
								$result += $product_qty;
								$term_id_amount[ $term->term_id ] = $result;
								break;
							}
						}
					}
				}
			}
		}
		return apply_filters( 'wpfmppu_get_cart_item_amount_by_term', $result, array(
			'product_id'           => $current_product_id,
			'cart_item_quantity'   => $current_cart_item_quantity,
			'cart_item_quantities' => $cart_item_quantities,
			'term_id'              => $current_term_id,
			'taxonomy'             => $taxonomy
		) );
	}

	/**
	 * get_cart_item_quantity_by_parent.
	 *
	 * @version 4.5.0
	 * @since   3.3.0
	 */
	function get_cart_item_quantity_by_parent( $current_product_id, $current_cart_item_quantity, $cart_item_quantities, $parent_product_id ) {
		$result = $current_cart_item_quantity;
		if ( ! empty( $cart_item_quantities ) ) {
			foreach ( $cart_item_quantities as $product_id => $product_qty ) {
				if ( $product_id != $current_product_id && 0 != ( $_parent_product_id = wp_get_post_parent_id( $product_id ) ) && $_parent_product_id === $parent_product_id ) {
					$result += $product_qty;
				}
			}
		}
		return apply_filters( 'wpfmppu_get_cart_item_amount_by_parent', $result );
	}

	/**
	 * get_max_qty_for_product.
	 *
	 * @version 4.5.0
	 * @since   2.5.0
	 * @todo    [next] use this inside the `check_quantities_for_product()` function
	 */
	function get_max_qty_for_product( $product_id, $parent_product_id = false ) {
		$cached_obj_name = md5( maybe_serialize( array(
			'product_id'        => $product_id,
			'parent_product_id' => $parent_product_id,
		) ) );
		if ( isset( $this->products_max_qty[ $cached_obj_name ] ) ) {
			return $this->products_max_qty[ $cached_obj_name ];
		}

		// Maybe exclude products
		$exclude_products = get_option( 'alg_wc_mppu_exclude_products', array() );
		if ( ! empty( $exclude_products ) && in_array( $product_id, $exclude_products ) ) {
			$this->products_max_qty[ $cached_obj_name ] = 0;
			return 0;
		}

		$max_qty = apply_filters( 'wpfmppu_get_max_qty_for_product', 0, $product_id, $parent_product_id, $this );
		if ( 0 != $max_qty ) {
			$this->products_max_qty[ $cached_obj_name ] = $max_qty;
			return $max_qty;
		}

		// All products
		if (
			'yes' === get_option( 'wpjup_wc_maximum_products_per_user_global_enabled', 'no' ) &&
			0 != ( $max_qty = $this->get_max_qty( array( 'type' => 'all_products' ) ) )

		) {
			$this->products_max_qty[ $cached_obj_name ] = $max_qty;

			return $max_qty;
		}
		// No max qty for the current product
		$this->products_max_qty[ $cached_obj_name ] = 0;
		return 0;
	}

	/**
	 * do_use_variations.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    [next] no `$parent_product_id`? (for `get_products()`, i.e. for products list display)
	 * @todo    [maybe] add filter
	 */
	function do_use_variations( $parent_product_id ) {
		return ( 'yes' === ( 0 != $parent_product_id && '' != ( $per_product = get_post_meta( $parent_product_id, '_alg_wc_mppu_use_variations', true ) ) ?
			$per_product : get_option( 'alg_wc_mppu_use_variations', 'no' ) ) );
	}

	/**
	 * check_quantities_for_product.
	 *
	 * @version 4.5.0
	 * @since   2.0.0
	 * @todo    [maybe] add `alg_wc_mppu_check_quantities_for_product_product_id` filter?
	 */
	function check_quantities_for_product( $_product_id, $args ) {
		$args = wp_parse_args( $args, array(
			'_cart_item_quantity'               => '',
			'cart_item_quantities'              => array(),
			'output_function'                   => 'wc_add_notice', // wc_print_notice || wc_add_notice
			'do_add_notices'                    => true,
			'return_notices'                    => false,
			'notice_type'                       => ( function_exists( 'is_checkout' ) && is_checkout() ) ? 'error' : get_option( 'alg_wc_mppu_cart_notice_type', 'notice' ), // notice || error
			'current_user_id'                   => 0,
			'check_guest_blocking'              => true,
			'adding'                            => 0,
			'get_product_id_from_main_language' => $this->multilanguage->get_product_id_from_main_language()
		) );
		// Variables from $args
		$_cart_item_quantity               = $args['_cart_item_quantity'];
		$cart_item_quantities              = $args['cart_item_quantities'];
		$output_function                   = $args['output_function'];
		$do_add_notices                    = $args['do_add_notices'];
		$current_user_id                   = $args['current_user_id'];
		$adding                            = $args['adding'];
		$get_product_id_from_main_language = $args['get_product_id_from_main_language'];
		$return_notices                    = $args['return_notices'];
		$notice_type                       = $args['notice_type'];
		// Dynamic values
		$parent_product_id  = $this->get_parent_product_id( wc_get_product( $_product_id ) );
		$use_parent         = ( $parent_product_id != $_product_id && ! $this->do_use_variations( $parent_product_id ) );
		$product_id         = $maybe_translated_product_id = ( ! $use_parent ? $_product_id : $parent_product_id );
		$cart_item_quantity = ( ! $use_parent ? $_cart_item_quantity : $this->get_cart_item_quantity_by_parent( $_product_id, $_cart_item_quantity, $cart_item_quantities, $parent_product_id ) );
		$cart_item_quantity = apply_filters( 'wpfmppu_cart_item_amount', $cart_item_quantity, array( 'product_id' => $product_id ) );
		if ( $get_product_id_from_main_language ) {
			$maybe_translated_product_id = apply_filters( 'wpfmppu_data_product_or_term_id', $product_id, true );
		}
		$args               = array_merge( $args, array(
			'parent_product_id'  => $parent_product_id,    // can be the same as product ID (for non-variable products)
			'product_id'         => $maybe_translated_product_id, // product or (maybe) parent ID
			'cart_item_quantity' => $cart_item_quantity,   // product or (maybe) parent cart qty
		) );
		// Maybe exclude products
		$exclude_products = get_option( 'alg_wc_mppu_exclude_products', array() );
		if ( ! empty( $exclude_products ) && in_array( $product_id, $exclude_products ) ) {
			return apply_filters( 'wpfmppu_check_quantities_for_product', true, $this, $args );
		}
		// Block guest by limit
		if (
			$args['check_guest_blocking']
			&& empty( $current_user_id )
			&& $this->is_product_blocked_for_guests( $maybe_translated_product_id )
		) {
			return apply_filters( 'wpfmppu_check_quantities_for_product', false, $this, $args );
		}

		$is_valid = apply_filters( 'wpfmppu_check_quantities_for_product_valid', true, $_product_id, $args, $this );
		$skip_all_products_validation = apply_filters( 'wpfmppu_skip_all_products_for_product', false, $_product_id, $args, $this );
		$max_qty = $this->get_max_qty(
			array(
				'type'    => 'all_products',
				'user_id' => $current_user_id
			)
		);

		if (
			! $skip_all_products_validation &&
			'yes' === get_option( 'wpjup_wc_maximum_products_per_user_global_enabled', 'no' ) &&
			0 != $max_qty
		) {

			$bought_data         = $this->get_user_already_bought_qty( $maybe_translated_product_id, $current_user_id, true );
			$user_already_bought = $bought_data['bought'];
			if ( ( $user_already_bought + $cart_item_quantity ) > $max_qty ) {
				if ( $do_add_notices ) {
					$this->output_notice( array(
						'product_id'          => $product_id,
						'limit'               => $max_qty,
						'bought_data'         => $bought_data,
						'notice_type'         => $notice_type,
						'output_function'     => $output_function,
						'in_cart_plus_adding' => $cart_item_quantity,
						'adding'              => $adding,
						'return'              => $return_notices
					) );
				}
				$is_valid = false;
			}
		}
		return apply_filters( 'wpfmppu_check_quantities_for_product', $is_valid, $this, $args );
	}

	/**
	 * check_quantities.
	 *
	 * @version 4.5.0
	 * @since   1.0.0
	 *
	 * @param null $args
	 *
	 * @return bool
	 */
	function check_quantities( $args = null ) {
		$args = wp_parse_args( $args, array(
			'do_add_notices' => true,
			'return_notices' => false,
		) );
		$do_add_notices = $args['do_add_notices'];
		$return_notices = $args['return_notices'];
		if ( ! isset( WC()->cart ) ) {
			return true;
		}
		$is_cart         = ( function_exists( 'is_cart' ) && is_cart() );
		$output_function = 'wc_add_notice';
		if ( $is_cart ) {
			$output_function = 'yes' === $this->cart_notice ? 'wc_print_notice' : '';
		}
		$notice_type = ( function_exists( 'is_checkout' ) && is_checkout() ) ?
			'error' :
			get_option( 'alg_wc_mppu_cart_notice_type', 'notice' );

		if ( ! ( $current_user_id = $this->get_current_user_id() ) ) {
			if (
				'yes' === ( $block_guests_opt = get_option( 'alg_wc_mppu_block_guests', 'no' ) )
				&& 'all_products' === $this->get_block_guests_method()
			) {
				if ( $do_add_notices ) {
					$this->output_guest_notice( $is_cart );
				}
				return false;
			} elseif ( 'block_beyond_limit' !== $block_guests_opt ) {
				$guest_limits_check = apply_filters( 'wpfmppu_check_quantities_guest_limits', null, $block_guests_opt, $this, $args );
				if ( null !== $guest_limits_check ) {
					return (bool) $guest_limits_check;
				}
				return true;
			}
		}
		if (
			( $gateways = get_option( 'alg_wc_mppu_payment_gateways', array() ) ) &&
			! empty( $gateways )
		) {
			if (
				( $chosen_payment_method = $this->get_chosen_payment_method() ) &&
				! in_array( $chosen_payment_method, $gateways )
			) {
				return true;
			}
		}
		$cart_item_quantities = $this->get_cart_item_quantities();
		if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
			return true;
		}
		$result = true;
		foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
			if ( ! $this->check_quantities_for_product( $_product_id, array(
				'_cart_item_quantity'  => $cart_item_quantity,
				'notice_type'          => $notice_type,
				'output_function'      => $output_function,
				'do_add_notices'       => $do_add_notices,
				'return_notices'       => $return_notices,
				'current_user_id'      => $current_user_id,
				'cart_item_quantities' => $cart_item_quantities
			) ) ) {
				if ( $do_add_notices && 'yes' === get_option( 'alg_wc_mppu_multiple_notices', 'yes' ) ) {
					$result = false;
				} else {
					return false;
				}
			}
		}

		return $result;
	}

	/**
	 * get_chosen_payment_method.
	 *
	 * @version 4.5.0
	 * @since   3.5.0
	 */
	function get_chosen_payment_method() {
		if ( ! empty( $_REQUEST['payment_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( $_REQUEST['payment_method'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( isset( WC()->session->chosen_payment_method ) ) {
			return WC()->session->chosen_payment_method;
		}
		return false;
	}

	/**
	 * get_chosen_payment_method_title.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function get_chosen_payment_method_title() {
		if ( $chosen_payment_method = $this->get_chosen_payment_method() ) {
			$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( ! empty( $available_payment_gateways[ $chosen_payment_method ] ) ) {
				$payment_gateway = $available_payment_gateways[ $chosen_payment_method ];
				return $payment_gateway->get_title();
			}
		}
		return '';
	}

	/**
	 * Gets parent product id based on `$this->do_use_variations()`.
	 *
	 * If it's a variation and `false === $this->do_use_variations()`, gets the parent id.
	 * In all other circumstances, gets id from current product, regardless if it's a variation or not.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 *
	 * @param $_product
	 *
	 * @return int
	 */
	function get_parent_or_product_id( $_product ) {
		if ( is_numeric( $_product ) ) {
			$_product = wc_get_product( $_product );
		}
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		$final_product_id = $_product->get_id();
		if (
			! $this->do_use_variations( ( $parent_id = $this->get_parent_product_id( $_product ) ) )
			&& $_product->is_type( 'variation' )
		) {
			$final_product_id = $_product->get_parent_id();
		}
		return $final_product_id;
	}

	/**
	 * Gets parent product id from a variation or the current product id from a not variation product.
	 *
	 * @version 3.5.5
	 * @since   1.0.0
	 */
	function get_parent_product_id( $_product ) {
		if ( is_numeric( $_product ) ) {
			$_product = wc_get_product( $_product );
		}
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( $this->is_wc_version_below_3_0_0 ) {
			return $_product->id;
		} else {
			return ( $_product->is_type( 'variation' ) ) ? $_product->get_parent_id() : $_product->get_id();
		}
	}

	/**
	 * get_product_id.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_product_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( $this->is_wc_version_below_3_0_0 ) {
			return ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
		} else {
			return $_product->get_id();
		}
	}

	/**
	 * get_user_roles.
	 *
	 * @version 3.5.0
	 * @since   2.2.0
	 * @todo    [maybe] `$role = ( 'super_admin' == $role ? 'administrator' : $role );`
	 */
	function get_user_roles( $is_enabled_roles_only = true ) {
		global $wp_roles;
		$all_roles = apply_filters(
			'editable_roles', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			( isset( $wp_roles ) && is_object( $wp_roles ) ? $wp_roles->roles : array() )
		);
		$roles     = ( ! empty( $all_roles ) ? wp_list_pluck( $all_roles, 'name' ) : array() );
		if ( $is_enabled_roles_only ) {
			$enabled_roles = get_option( 'alg_wc_mppu_enabled_user_roles', array() );
			if ( ! empty( $enabled_roles ) ) {
				$roles = array_intersect_key( $roles, array_flip( $enabled_roles ) );
			}
		}
		return $roles;
	}

	/**
	 * is_user_role_enabled.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function is_user_role_enabled( $role ) {
		if ( ! isset( $this->enabled_user_roles ) ) {
			$this->enabled_user_roles = get_option( 'alg_wc_mppu_enabled_user_roles', array() );
		}
		return ( empty( $this->enabled_user_roles ) || in_array( $role, $this->enabled_user_roles ) );
	}

	/**
	 * get_post_or_term_meta.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function get_post_or_term_meta( $product_or_term, $product_or_term_id, $key, $single = true ) {
		$func = ( 'product' === $product_or_term ? 'get_post_meta' : 'get_term_meta' );
		return $func( $product_or_term_id, $key, $single );
	}

	/**
	 * update_post_or_term_meta.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function update_post_or_term_meta( $product_or_term, $product_or_term_id, $key, $value ) {
		$func = ( 'product' === $product_or_term ? 'update_post_meta' : 'update_term_meta' );
		return $func( $product_or_term_id, $key, $value );
	}

	/**
	 * get_error_messages.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 *
	 * @return array
	 */
	public function get_error_messages() {
		return $this->error_messages;
	}

	/**
	 * get_orders_data_products_from_user.
	 *
	 * @version 4.5.0
	 * @since   3.6.7
	 *
	 * @param null $args
	 *
	 * @return array
	 */
	function get_orders_data_products_from_user( $args = null ) {
		$args = wp_parse_args( $args, array(
			'user_id' => $this->get_current_user_id()
		) );
		global $wpdb;
		$user_id     = intval( $args['user_id'] );
		$product_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_alg_wc_mppu_orders_data' 
				AND meta_value REGEXP %s",
				'i:' . $user_id . ';a:[0-9]+:{i:[0-9]+;a:[0-9]+:'
			)
		);

		return $product_ids;
	}

	/**
	 * get_allowed_form_html.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 */
	function get_allowed_form_html() {
		$allowed_html = array(
			'input'  => array(
				'type'     => true,
				'id'       => true,
				'name'     => true,
				'class'    => true,
				'value'    => true,
				'checked'  => true,
				'disabled' => true,
				'min'      => true,
				'max'      => true,
			),
			'select' => array(
				'id'    => true,
				'name'  => true,
				'class' => true
			),
			'option' => array(
				'value'    => true,
				'selected' => true,
			),
		);
		return array_merge(
			wp_kses_allowed_html( 'post' ),
			$allowed_html
		);
	}

	/**
	 * get_allowed_style.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 */
	function get_allowed_style() {
		return array(
			'style' => array()
		);
	}

}

endif;

return new WPFMPPU_Core();
