<?php
/*
Plugin Name: NPay for WooCommerce
Plugin URI: 
Description: NPay를 이용한 결재를 지원합니다.
Version: 1.2.6
Author: CodeMShop
Author URI: www.codemshop.com
License: GPLv2 or later
*/




if ( ! class_exists( 'MSHOP_NPay' ) ) {

	final class MSHOP_NPay {

		protected $slug;

		protected static $_instance = null;
		public $version = '1.2.6';
		public $plugin_url;
		public $naverpay_register_order_url;
		public $naverpay_ordersheet_url;
		public $naverpay_wishlist_url;
		public $naverpay_wishlist_popup_url;
		public $plugin_path;
		public $template_url;
		public function __construct() {

			$this->define( 'MNP_VERSION', $this->version );
			$this->define( 'MNP_PLUGIN_FILE', __FILE__ );

			$this->slug = 'mshop-npay';

			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );
			add_action( 'wp', array( $this, 'setup_schedule' ) );
			add_action( 'naverpay_cron', array( $this, 'naverpay_cron' ) );
		}

		public function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

        function slug(){
            return $this->slug;
        }

		function setup_schedule() {
			if (!wp_next_scheduled('naverpay_cron')) {
				wp_schedule_event(time(), 'hourly', 'naverpay_cron');
			}
		}

		function naverpay_cron()
		{
			MNP_Comments::sync_review();
		}

		public function plugin_url() {
			if ( $this->plugin_url )
				return $this->plugin_url;

			return $this->plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		public function plugin_path() {
			if ( $this->plugin_path )
				return $this->plugin_path;

			return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
		}
		public function template_path() {
			return $this->plugin_path() . '/templates/';
		}

		function includes() {
			include_once( 'includes/class-mnp-wcs.php' );

			if ( is_admin() ) {
				$this->admin_includes();
			}

			if ( defined( 'DOING_AJAX' ) ) {
				$this->ajax_includes();
			}

			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				$this->frontend_includes();
			}
		}

		public function admin_includes() {
			include_once( 'includes/admin/class-mnp-admin.php' );
			include_once( 'includes/admin/class-mnp-admin-post-types.php' );
			include_once( 'includes/admin/class-mnp-admin-dashboard.php' );
			include_once( 'includes/admin/class-mnp-admin-notices.php' );
			include_once( 'includes/class-mnp-query.php' );
		}

		public function ajax_includes() {
			require_once( 'includes/class-mnp-ajax.php' );
		}


		public function frontend_includes() {

		}
		public function woocommerce_init() {
			require_once( 'includes/class-mnp-autoloader.php' );
			require_once( 'includes/mnp-functions.php' );
			require_once( 'includes/class-mnp-post-types.php' );
			require_once( 'includes/class-mnp-callback.php' );

			$this->includes();
		}
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'mshop-npay', false, dirname( plugin_basename(__FILE__) ) . "/languages/" );
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
	}

	function MNP() {
		return MSHOP_NPay::instance();
	}


	return MNP();
}