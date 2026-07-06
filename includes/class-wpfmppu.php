<?php
/**
 * Maximum Products per User for WooCommerce - Core Class.
 *
 * @version 4.5.0
 * @since   3.9.6
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFMPPU' ) ) :

	/**
	 * Main WPFMPPU Class.
	 *
	 * @class   WPFMPPU
	 * @version 4.5.0
	 * @since   1.0.0
	 */
	class WPFMPPU {

		/**
		 * Plugin version.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public $version = '4.5.0';

		/**
		 * @since 1.0.0
		 * @var   WPFMPPU The single instance of the class
		 */
		protected static $_instance = null;

		/**
		 * core.
		 *
		 * @since 3.6.4
		 *
		 * @var WPFMPPU_Core
		 */
		public $core;

		/**
		 * pro.
		 *
		 * @since 3.6.9
		 *
		 * @var WPFMPPU_Pro
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
		 * Main WPFMPPU Instance
		 *
		 * Ensures only one instance of WPFMPPU is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @return  WPFMPPU - Main instance
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
		 * @version 4.5.0
		 * @since   3.9.6
		 * @access  public
		 */
		function init() {

			// Adds cross-selling library.
			$this->add_cross_selling_library();

			// Move WC Settings tab to WPFactory menu.
			add_action( 'init', array( $this, 'move_wc_settings_tab_to_wpfactory_menu' ) );

			// Adds compatibility with HPOS.
			add_action( 'before_woocommerce_init', function () {
				$this->declare_compatibility_with_hpos( $this->get_filesystem_path() );
				if ( ! empty( $this->get_free_version_filesystem_path() ) ) {
					$this->declare_compatibility_with_hpos( $this->get_free_version_filesystem_path() );
				}
			} );

			// Pro
			if ( 'maximum-products-per-user-for-woocommerce-pro.php' === basename( $this->get_filesystem_path() ) ) {
				$this->pro = require_once( 'pro/class-wpfmppu-pro.php' );
			}

			// Include required files
			$this->includes();

			// Admin
			if ( is_admin() ) {
				$this->admin();
			}
		}

		/**
		 * add_cross_selling_library.
		 *
		 * @version 4.5.0
		 * @since   4.2.9
		 *
		 * @return void
		 */
		function add_cross_selling_library(){
			if ( ! is_admin() ) {
				return;
			}
			// Cross-selling library.
			$cross_selling = new \WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling();
			$cross_selling->setup( array(
				'plugin_file_path'   => $this->get_filesystem_path(),
				'recommendations_box' => array(
					'enable'             => true,
					'wc_settings_tab_id' => 'wpfmppu',
				),
			) );
			$cross_selling->init();
		}

		/**
		 * move_wc_settings_tab_to_wpfactory_submenu.
		 *
		 * @version 4.5.0
		 * @since   4.2.9
		 *
		 * @return void
		 */
		function move_wc_settings_tab_to_wpfactory_menu() {
			if ( ! is_admin() ) {
				return;
			}
			// WC Settings tab as WPFactory submenu item.
			$wpf_admin_menu = \WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu::get_instance();
			$wpf_admin_menu->move_wc_settings_tab_to_wpfactory_menu( array(
				'wc_settings_tab_id' => 'wpfmppu',
				'menu_title'         => __( 'Max Products per User', 'maximum-products-per-user-for-woocommerce' ),
				'page_title'         => __( 'Maximum Products per User for WooCommerce', 'maximum-products-per-user-for-woocommerce' ),
				'plugin_icon' => array(
					'get_url_method'    => 'wporg_plugins_api',
					'wporg_plugin_slug' => 'maximum-products-per-user-for-woocommerce',
					'style'             => 'margin-left:-4px',
				)
			) );
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
		 * Include required core files used in admin and on the frontend.
		 *
		 * @version 4.5.0
		 * @since   1.0.0
		 */
		function includes() {
			// Functions
			require_once( 'functions/wpfmppu-functions.php' );
			// Core
			$this->core = require_once( 'class-wpfmppu-core.php' );
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
		 * @version 4.5.0
		 * @since   1.0.0
		 *
		 * @param   mixed  $links
		 *
		 * @return  array
		 */
		function action_links( $links ) {
			$custom_links = array();
			if ( ! in_array( $settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wpfmppu' ) . '">' . __( 'Settings', 'maximum-products-per-user-for-woocommerce' ) . '</a>', $links ) ) {
				$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wpfmppu' ) . '">' . __( 'Settings', 'maximum-products-per-user-for-woocommerce' ) . '</a>';
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
		 * @version 4.5.0
		 * @since   1.0.0
		 */
		function add_woocommerce_settings_tab( $settings ) {
			$settings[] = require_once( 'settings/class-wpfmppu-settings.php' );

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