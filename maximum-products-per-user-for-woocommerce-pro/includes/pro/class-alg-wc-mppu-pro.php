<?php
/**
 * Maximum Products per User for WooCommerce - Pro Class
 *
 * @version 3.5.5
 * @since   2.2.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MPPU_Pro' ) ) :

class Alg_WC_MPPU_Pro {

	/**
	 * Constructor.
	 *
	 * @version 3.5.5
	 * @since   2.2.0
	 * @todo    [next] add `[alg_wc_mppu_if]` shortcode
	 * @todo    [later] better filters (e.g. move metaboxes here)!
	 */
	function __construct() {
		add_filter( 'alg_wc_mppu_settings',               array( $this, 'settings' ), 10, 3 );
		add_filter( 'alg_wc_mppu_local_enabled',          array( $this, 'local_enabled' ) );
		add_filter( 'alg_wc_mppu_product_tag_enabled',    array( $this, 'product_tag_enabled' ) );
		add_filter( 'alg_wc_mppu_product_cat_enabled',    array( $this, 'product_cat_enabled' ) );
		add_action( 'alg_wc_mppu_core_loaded',            array( $this, 'core_loaded' ) );
		if ( 'yes' === get_option( 'alg_wc_mppu_formula_enabled', 'no' ) ) {
			add_shortcode( 'alg_wc_mppu',                 array( $this, 'max_qty_shortcode' ) );
			add_shortcode( 'alg_wc_mppu_max_qty',         array( $this, 'max_qty_shortcode' ) ); // deprecated
			add_shortcode( 'alg_wc_mppu_user_bought',     array( $this, 'user_bought_shortcode' ) );
			add_filter( 'alg_wc_mppu_max_qty_by_formula', array( $this, 'max_qty_by_formula' ), 10, 3 );
		}

		// Frontend > Product limit message > Show limit message for variations
		add_action( 'woocommerce_before_single_variation', array( $this, 'handle_variations_limit_message_js' ) );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variations_limit_to_available_variations' ) );
		// Change add to cart button text for blocked guest users
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_btn_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_btn_text' ), 10, 2 );
		add_action( 'woocommerce_before_single_variation', array( $this, 'handle_variations_add_to_cart_btn_text_on_js' ) );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_add_to_cart_text_to_available_variations' ) );
		// Add to cart button redirect for blocked guest users
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'change_add_to_cart_url_on_guest_blocked_product' ), 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'remove_ajax_class_from_guest_blocked_product' ), 10, 2 );
		add_filter( 'woocommerce_cart_redirect_after_block_guest_error', array( $this, 'add_to_cart_cart_redirect_after_block_guest_error' ), 10, 2 );
		add_action( 'alg_wc_mppu_before_block_guest_on_add_to_cart', array( $this, 'add_to_cart_redirect_on_block_guest_product' ) );
		add_action( 'woocommerce_before_single_variation', array( $this, 'handle_variations_add_to_cart_btn_redirect_on_js' ) );
	}

	/**
	 * add_add_to_cart_text_to_available_variations.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 *
	 * @param $variation
	 *
	 * @return mixed
	 */
	function add_add_to_cart_text_to_available_variations( $variation ) {
		if (
			'yes' === get_option( 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_enable', 'no' )
			&& 'yes' === get_option( 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_variations', 'no' )
		) {
			$product = wc_get_product( $variation['variation_id'] );
			$variation['alg_wc_mppu_block_guests_add_to_cart_text'] = $product->single_add_to_cart_text();
		}
		return $variation;
	}

	/**
	 * handle_variations_add_to_cart_btn_text.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 */
	function handle_variations_add_to_cart_btn_text_on_js() {
		if (
			is_user_logged_in()
			|| 'no' === get_option( 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_enable', 'no' )
			|| 'no' === get_option( 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_variations', 'no' )
		) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function ($) {
				$('form.variations_form').on('show_variation', function (event, data) {
					$('.single_add_to_cart_button').html(data.alg_wc_mppu_block_guests_add_to_cart_text);
				});
			});
		</script>
		<?php
	}

	/**
	 * handle_variations_add_to_cart_btn_redirect_on_js.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 */
	function handle_variations_add_to_cart_btn_redirect_on_js() {
		if (
			is_user_logged_in()
			|| 'no' == get_option( 'alg_wc_mppu_block_guests' )
			|| 'no' === get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect', 'no' )
		) {
			return;
		}
		global $product;
		if ( ! alg_wc_mppu()->core->is_product_blocked_for_guests( $product->get_id() ) ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function ($) {
				let info = <?php echo json_encode( array( 'redirect_url' => esc_url( get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect_url', wc_get_page_permalink( 'myaccount' ) ) ) ) );?>;
				$('form.variations_form').on('hide_variation', function (e) {
					setTimeout(function () {
						$('.single_add_to_cart_button').removeClass('wc-variation-selection-needed wc-variation-is-unavailable disabled');
					}, 100);
				});
				$('form.variations_form').on('click', '.single_add_to_cart_button', function (e) {
					window.location.href = info.redirect_url;
				});
			})
		</script>
		<?php
	}

	/**
	 * add_to_cart_cart_redirect_after_block_guest_error.
	 *
	 * @version 3.5.5
	 * @since   3.5.3
	 *
	 * @param $url
	 * @param $product_id
	 *
	 * @return string
	 */
	function add_to_cart_cart_redirect_after_block_guest_error( $url, $product_id ) {
		if ( 'yes' === get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect', 'no' ) ) {
			$url = get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect_url', wc_get_page_permalink( 'myaccount' ) );
		}
		return $url;
	}

	/**
	 * change_add_to_cart_btn_text.
	 *
	 * @version 3.5.3
	 * @since   3.5.2
	 *
	 * @param $text
	 * @param $product
	 *
	 * @return string
	 */
	function change_add_to_cart_btn_text( $text, $product ) {
		if (
			! is_user_logged_in()
			&& 'yes' == get_option( 'alg_wc_mppu_block_guests' )
			&& 'yes' === get_option( 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt_enable', 'no' )
			&& alg_wc_mppu()->core->is_product_blocked_for_guests( $product->get_id() )
		) {
			$text = get_option( 'alg_wc_mppu_block_guests_custom_add_to_cart_btn_txt', __( 'Login to purchase', 'maximum-products-per-user-for-woocommerce' ) );
		}
		return $text;
	}

	/**
	 * change_add_to_cart_url.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 *
	 * @param $url
	 * @param $product
	 *
	 * @return string
	 */
	function change_add_to_cart_url_on_guest_blocked_product( $url, $product ) {
		if (
			! is_user_logged_in()
			&& 'yes' == get_option( 'alg_wc_mppu_block_guests' )
			&& 'yes' === get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect', 'no' )
			&& alg_wc_mppu()->core->is_product_blocked_for_guests( $product->get_id() )
		) {
			$url = get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect_url', wc_get_page_permalink( 'myaccount' ) );
		}
		return $url;
	}

	/**
	 * add_to_cart_redirect.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 */
	function add_to_cart_redirect_on_block_guest_product() {
		if ( 'yes' === get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect', 'no' ) ) {
			$url = get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect_url', wc_get_page_permalink( 'myaccount' ) );
			//$url = add_query_arg( 'alg_wc_mppu_guest', true, $url );
			wp_redirect( $url );
			die();
		}
	}

	/**
	 * change_loop_add_to_cart_args.
	 *
	 * @version 3.5.5
	 * @since   3.5.5
	 *
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
	function remove_ajax_class_from_guest_blocked_product( $args, $product ) {
		if (
			! is_user_logged_in()
			&& 'yes' == get_option( 'alg_wc_mppu_block_guests' )
			&& 'yes' === get_option( 'alg_wc_mppu_block_guests_add_to_cart_redirect', 'no' )
			&& alg_wc_mppu()->core->is_product_blocked_for_guests( $product->get_id() )
		) {
			$classes_arr   = array_filter( explode( ' ', $args['class'] ), function ( $var ) {
				return $var != 'ajax_add_to_cart';
			} );
			$args['class'] = implode( ' ', $classes_arr );
		}
		return $args;
	}

	/**
	 * add_variations_limit_to_available_variations.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 *
	 * @param $variation
	 *
	 * @return mixed
	 */
	function add_variations_limit_to_available_variations( $variation ) {
		if ( 'yes' === get_option( 'alg_wc_mppu_permanent_notice_handle_variations', 'no' ) ) {
			$product_limit_shortcode = do_shortcode( '[alg_wc_mppu_current_product_limit product_id="' . $variation['variation_id'] . '" output_template="{output_msg}"]' );
			$variation['alg_wc_mppu_current_product_limit'] = $product_limit_shortcode;
		}
		return $variation;
	}

	/**
	 * handle_variations_limit_message_js.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 *
	 * @see https://stackoverflow.com/a/54967694/1193038
	 */
	function handle_variations_limit_message_js() {
		if ( 'no' === get_option( 'alg_wc_mppu_permanent_notice_handle_variations', 'no' ) ) {
			return;
		}
		?>
		<script>
			(function ($) {
				let limitClass = '.alg-wc-mppu-current-product-limit';
				function handle_message_visibility() {
					let productLimitWrapper = jQuery(limitClass);
					if (productLimitWrapper.html() == '') {
						productLimitWrapper.hide();
						if (productLimitWrapper.parent().hasClass('woocommerce-info')) {
							productLimitWrapper.parent().hide();
						}
					} else {
						productLimitWrapper.show();
						if (productLimitWrapper.parent().hasClass('woocommerce-info')) {
							productLimitWrapper.parent().show();
						}
					}
				}
				$('form.variations_form').on('show_variation', function (event, data) {
					let productLimitWrapper = jQuery(limitClass);
					if (productLimitWrapper && data.alg_wc_mppu_current_product_limit) {
						productLimitWrapper.html(data.alg_wc_mppu_current_product_limit);
					} else {
						productLimitWrapper.html('');
					}
					handle_message_visibility();
				});
				$(document).ready(function () {
					handle_message_visibility();
				});
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * user_bought_shortcode.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    [next] `on_empty` must be applied only on `0 == $bought_data['bought']`?
	 * @todo    [next] separate `$atts['product_id']` and `$atts['term_id']` (no need in `$atts['is_product']` then)?
	 * @todo    [next] `$atts['is_product']`: validate boolean?
	 */
	function user_bought_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'product_or_term_id' => 0,
			'is_product'         => 'yes',
			'date_range'         => 'lifetime',
			'on_empty'           => 0,
		), $atts, 'alg_wc_mppu_user_bought' );
		$result = 0;
		if ( ! empty( $atts['product_or_term_id'] ) && ( $user_id = $this->get_max_qty_shortcode_value( 'user_id' ) ) ) {
			$bought_data = alg_wc_mppu()->core->get_user_already_bought_qty( $atts['product_or_term_id'], $user_id, ( 'yes' === $atts['is_product'] ), $atts['date_range'] );
			$result      = $bought_data['bought'];
		}
		return ( ! $result ? $atts['on_empty'] : $result );
	}

	/**
	 * max_qty_by_formula.
	 *
	 * @version 3.4.0
	 * @since   2.3.0
	 * @todo    [next] (fix) `user_id` should be passed to the function in case if we are getting data for multiple users, e.g. `[alg_wc_mppu_user_product_limits user_id="123"]`
	 * @todo    [next] find better place for `include_once( WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php' )`
	 * @todo    [next] make `math` optional?
	 */
	function max_qty_by_formula( $max_qty, $type, $product_or_term_id ) {
		include_once( WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php' );
		if ( ! isset( $this->formula ) ) {
			$this->formula = get_option( 'alg_wc_mppu_formula', '' );
			$this->formula = array_map( 'trim', explode( PHP_EOL, $this->formula ) );
		}
		$this->formula_data = array(
			'product_id' => ( 'per_product' === $type ? $product_or_term_id : 0 ),
			'term_id'    => ( 'per_term'    === $type ? $product_or_term_id : 0 ),
		);
		foreach ( $this->formula as $single_formula ) {
			if ( 0 != ( $res = WC_Eval_Math::evaluate( do_shortcode( $single_formula ) ) ) ) {
				return $res;
			}
		}
		return 0;
	}

	/**
	 * get_max_qty_shortcode_value.
	 *
	 * @version 3.5.0
	 * @since   2.3.0
	 */
	function get_max_qty_shortcode_value( $key ) {
		switch ( $key ) {
			case 'user_id':
				if ( ! isset( $this->user_id ) ) {
					if ( ! function_exists( 'get_current_user_id' ) ) {
						return 0;
					}
					$this->user_id = $this->core->get_current_user_id();
				}
				return $this->user_id;
			case 'user_role':
				if ( ! isset( $this->user_role ) ) {
					if ( ! function_exists( 'wp_get_current_user' ) ) {
						return array();
					} else {
						$user = wp_get_current_user();
						$this->user_role = ( ! isset( $user->roles ) || empty( $user->roles ) ? array( 'guest' ) : array_map( array( $this->core, 'handle_user_roles' ), $user->roles ) );
					}
				}
				return $this->user_role;
			case 'payment_method':
				if ( ! isset( $this->payment_method ) ) {
					$this->payment_method = alg_wc_mppu()->core->get_chosen_payment_method();
				}
				return $this->payment_method;
			case 'product_sku':
				if ( ! isset( $this->formula_data['product_sku'] ) ) {
					$this->formula_data['product']     = ( $this->formula_data['product_id'] ? wc_get_product( $this->formula_data['product_id'] ) : false );
					$this->formula_data['product_sku'] = ( $this->formula_data['product'] ? $this->formula_data['product']->get_sku() : '' );
				}
				return $this->formula_data['product_sku'];
			default: // 'product_id', 'term_id'
				return $this->formula_data[ $key ];
		}
	}

	/**
	 * max_qty_shortcode.
	 *
	 * @version 3.5.0
	 * @since   2.3.0
	 * @todo    [next] (feature) `meta_value` (similar to `is_downloadable`, `is_virtual`)
	 * @todo    [maybe] `is_downloadable`, `is_virtual`: use product functions instead of `get_post_meta()`?
	 * @todo    [maybe] cache result?
	 */
	function max_qty_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'user_id'         => '',
			'user_role'       => '',
			'membership_plan' => '',
			'payment_method'  => '',
			'product_id'      => '',
			'term_id'         => '',
			'product_sku'     => '',
			'is_downloadable' => 'no',
			'is_virtual'      => 'no',
			'max_qty'         => 0, // deprecated
			'limit'           => 0,
			'start_date'      => '',
			'end_date'        => '',
			'not_date_limit'  => 0,
		), $atts, 'alg_wc_mppu' );
		if (
			( ! empty( $atts['start_date'] ) && $this->core->get_current_time() < strtotime( $atts['start_date'] ) ) ||
			( ! empty( $atts['end_date'] )   && $this->core->get_current_time() > strtotime( $atts['end_date'] ) )
		) {
			return $atts['not_date_limit'];
		}
		if ( ! empty( $atts['max_qty'] ) ) {
			$atts['limit'] = $atts['max_qty'];
		}
		foreach ( array( 'user_id', 'user_role', 'membership_plan', 'payment_method', 'product_id', 'term_id', 'product_sku' ) as $key ) {
			if ( '' !== $atts[ $key ] ) {
				$haystack = array_map( 'trim', explode( ',', $atts[ $key ] ) );
				if ( 'membership_plan' === $key ) {
					if (
						! function_exists( 'wc_memberships_is_user_active_member' ) ||
						! ( $current_user_id = $this->get_max_qty_shortcode_value( 'user_id' ) ) || ! is_numeric( $current_user_id )
					) {
						return 0;
					}
					$is_user_active_member = false;
					foreach ( $haystack as $membership_plan ) {
						if ( wc_memberships_is_user_active_member( $current_user_id, $membership_plan ) ) {
							$is_user_active_member = true;
							break;
						}
					}
					if ( ! $is_user_active_member ) {
						return 0;
					}
				} else {
					$needle = $this->get_max_qty_shortcode_value( $key );
					if ( 'user_role' === $key ) {
						$intersect = array_intersect( $needle, $haystack );
						if ( empty( $intersect ) ) {
							return 0;
						}
					} else {
						if ( ! $needle || ! in_array( $needle, $haystack ) ) {
							return 0;
						}
					}
				}
			}
		}
		if ( $this->formula_data['product_id'] ) {
			foreach ( array( 'is_downloadable', 'is_virtual' ) as $key ) {
				if ( wc_string_to_bool( $atts[ $key ] ) ) {
					if ( 'yes' != get_post_meta( $this->formula_data['product_id'], str_replace( 'is_', '_', $key ), true ) ) {
						return 0;
					}
				}
			}
		}
		return $atts['limit'];
	}

	/**
	 * core_loaded.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function core_loaded( $core ) {
		$this->core = $core;
	}

	/**
	 * local_enabled.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 */
	function local_enabled( $value ) {
		return get_option( 'wpjup_wc_maximum_products_per_user_local_enabled', 'no' );
	}

	/**
	 * product_tag_enabled.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 */
	function product_tag_enabled( $value ) {
		return get_option( 'alg_wc_mppu_product_tag_enabled', 'no' );
	}

	/**
	 * product_cat_enabled.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 */
	function product_cat_enabled( $value ) {
		return get_option( 'alg_wc_mppu_product_cat_enabled', 'no' );
	}

	/**
	 * settings.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function settings( $value, $type = '', $args = array() ) {
		return '';
	}

}

endif;

return new Alg_WC_MPPU_Pro();
