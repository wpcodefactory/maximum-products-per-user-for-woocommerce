<?php
/**
 * Maximum Products per User for WooCommerce - Core Class.
 *
 * @version 3.9.6
 * @since   3.9.6
 * @author  WPFactory
 */

if ( ! class_exists( 'Alg_WC_MPPU' ) ) :

	/**
	 * Main Alg_WC_MPPU Class.
	 *
	 * @class   Alg_WC_MPPU
	 * @version 3.5.0
	 * @since   1.0.0
	 */
	class Alg_WC_MPPU {

		/**
		 * Plugin version.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public $version = '3.9.9';

		/**
		 * @since 1.0.0
		 * @var   Alg_WC_MPPU The single instance of the class
		 */
		protected static $_instance = null;

		/**
		 * core.
		 *
		 * @since 3.6.4
		 *
		 * @var Alg_WC_MPPU_Core
		 */
		public $core;

		/**
		 * pro.
		 *
		 * @since 3.6.9
		 *
		 * @var Alg_WC_MPPU_Pro
		 */
		public $pro;

		/**
		 * $file_system_path.
		 *
		 * @since 3.9.6
		 */
		protected $file_system_path;

		/**
		 * $free_version_file_system_path.
		 *
		 * @since 3.9.6
		 */
		protected $free_version_file_system_path;

		/**
		 * Main Alg_WC_MPPU Instance
		 *
		 * Ensures only one instance of Alg_WC_MPPU is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @return  Alg_WC_MPPU - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Initializer.
		 *
		 * @version 3.9.6
		 * @since   3.9.6
		 * @access  public
		 */
		function init() {

			// Set up localisation
			add_action( 'init', array( $this, 'localize' ) );

			// Adds compatibility with HPOS.
			add_action( 'before_woocommerce_init', function () {
				$this->declare_compatibility_with_hpos( $this->get_filesystem_path() );
				if ( ! empty( $this->get_free_version_filesystem_path() ) ) {
					$this->declare_compatibility_with_hpos( $this->get_free_version_filesystem_path() );
				}
			} );

			// Pro
			if ( 'maximum-products-per-user-for-woocommerce-pro.php' === basename( $this->get_filesystem_path() ) ) {
				$this->pro = require_once( 'pro/class-alg-wc-mppu-pro.php' );
			}

			// Include required files
			$this->includes();

			// Admin
			if ( is_admin() ) {
				$this->admin();
			}
		}

		/**
		 * Declare compatibility with custom order tables for WooCommerce.
		 *
		 * @version 3.9.6
		 * @since   3.9.6
		 *
		 * @param $filesystem_path
		 *
		 * @return void
		 * @link    https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
		 *
		 */
		function declare_compatibility_with_hpos( $filesystem_path ) {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $filesystem_path, true );
			}
		}

		/**
		 * localize.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 */
		function localize() {
			load_plugin_textdomain( 'maximum-products-per-user-for-woocommerce', false, dirname( plugin_basename( $this->get_filesystem_path() ) ) . '/langs/' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @version 3.8.2
		 * @since   1.0.0
		 */
		function includes() {
			// Functions
			require_once( 'alg-wc-mppu-functions.php' );
			// Core
			$this->core = require_once( 'class-alg-wc-mppu-core.php' );
		}

		/**
		 * admin.
		 *
		 * @version 2.4.1
		 * @since   1.1.1
		 */
		function admin() {
			// Action links
			add_filter( 'plugin_action_links_' . plugin_basename( $this->get_filesystem_path() ), array(
				$this,
				'action_links'
			) );
			// Settings
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
			// Version update
			if ( $this->version !== get_option( 'alg_wc_mppu_version', '' ) ) {
				add_action( 'admin_init', array( $this, 'version_updated' ) );
			}
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @version 2.6.0
		 * @since   1.0.0
		 *
		 * @param   mixed  $links
		 *
		 * @return  array
		 */
		function action_links( $links ) {
			$custom_links = array();
			if ( ! in_array( $settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>', $links ) ) {
				$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_mppu' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
			}
			if (
				'maximum-products-per-user-for-woocommerce.php' === basename( $this->get_filesystem_path() ) &&
				! in_array( $go_pro = '<a target="_blank" style="font-weight: bold; color: green;" href="https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/">' . __( 'Go Pro', 'maximum-products-per-user-for-woocommerce' ) . '</a>', $links )
			) {
				$custom_links[] = $go_pro;
			}

			return array_merge( $custom_links, $links );
		}

		/**
		 * Add Maximum Products per User settings tab to WooCommerce settings.
		 *
		 * @version 2.0.0
		 * @since   1.0.0
		 */
		function add_woocommerce_settings_tab( $settings ) {
			$settings[] = require_once( 'settings/class-alg-wc-mppu-settings.php' );

			return $settings;
		}

		/**
		 * version_updated.
		 *
		 * @version 2.0.0
		 * @since   1.1.0
		 */
		function version_updated() {
			update_option( 'alg_wc_mppu_version', $this->version );
		}

		/**
		 * Get the plugin url.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		function plugin_url() {
			return untrailingslashit( plugin_dir_url( $this->get_filesystem_path() ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		function plugin_path() {
			return untrailingslashit( plugin_dir_path( $this->get_filesystem_path() ) );
		}

		/**
		 * get_filesystem_path.
		 *
		 * @version 3.9.6
		 * @since   3.7.4
		 *
		 * @return string
		 */
		function get_filesystem_path() {
			return $this->file_system_path;
		}

		/**
		 * set_filesystem_path.
		 *
		 * @version 3.9.6
		 * @since   3.9.6
		 *
		 * @param   mixed  $file_system_path
		 */
		public function set_filesystem_path( $file_system_path ) {
			$this->file_system_path = $file_system_path;
		}

		/**
		 * get_free_version_filesystem_path.
		 *
		 * @version 3.9.6
		 * @since   3.9.6
		 *
		 * @return mixed
		 */
		public function get_free_version_filesystem_path() {
			return $this->free_version_file_system_path;
		}

		/**
		 * set_free_version_filesystem_path.
		 *
		 * @version 3.9.6
		 * @since   3.9.6
		 *
		 * @param   mixed  $free_version_file_system_path
		 */
		public function set_free_version_filesystem_path( $free_version_file_system_path ) {
			$this->free_version_file_system_path = $free_version_file_system_path;
		}

	}

endif;