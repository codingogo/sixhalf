<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'MNP_Admin' ) ) :

	class MNP_Admin {

		public function __construct() {

			add_action( 'admin_menu', array ( $this, 'admin_menu' ) );
		}

		function admin_menu() {
			add_menu_page( __( '엠샵 NPay', 'mshop-npay' ), __( '엠샵 NPay', 'mshop-npay' ), 'manage_woocommerce', 'mnp_settings', '', MNP()->plugin_url() . '/assets/images/mshop-icon.png', '20.9021211223' );
			add_submenu_page( 'mnp_settings', __( '기본 설정', 'mshop-npay' ), __( '기본 설정', 'mshop-npay' ), 'manage_woocommerce', 'mnp_settings', 'MNP_Settings::output' );

			if ( 'active' == get_option( 'mshop-naverpay-status' ) ) {
				add_submenu_page( 'mnp_settings', __( '카테고리 설정', 'mshop-npay' ), __( '카테고리 설정', 'mshop-npay' ), 'manage_woocommerce', 'mnp_category_settings', array (
					$this,
					'category_settings'
				) );
				add_submenu_page( 'mnp_settings', __( '주문 관리', 'mshop-npay' ), __( '주문 관리', 'mshop-npay' ), 'manage_woocommerce', 'edit.php?post_type=shop_order&paymethod=naverpay' );
				add_submenu_page( 'mnp_settings', __( '문의 관리', 'mshop-npay' ), __( '문의 관리', 'mshop-npay' ), 'manage_woocommerce', 'mnp_customer_inquiry', array (
					$this,
					'customer_inquiry_page'
				) );

				add_submenu_page( 'mnp_settings', __( '대쉬보드', 'mshop-npay' ), __( '대쉬보드', 'mshop-npay' ), 'manage_woocommerce', 'mnp_dashboard', array (
					$this,
					'mnp_dashboard_page'
				) );

				add_submenu_page( 'mnp_settings', __( '송장업로드', 'mshop-npay' ), __( '송장업로드', 'mshop-npay' ), 'manage_woocommerce', 'mnp_sheet', 'MNP_Settings_Sheet::output');
			}

			wp_enqueue_style( 'naverpay-admin', MNP()->plugin_url() . '/assets/css/naverpay-admin.css' );
		}

		function customer_inquiry_page() {
			require_once 'class-mnp-customer-inquiry-list-table.php';

			ob_start();
			include_once( 'templates/customer-inquiry-list.php' );
			echo ob_get_clean();
		}

		function category_settings() {
			ob_start();
			include_once( 'templates/category-settings.php' );
			echo ob_get_clean();
		}

		function mnp_dashboard_page() {
			ob_start();
			include_once( 'dashboard/dashboard.php' );
			echo ob_get_clean();
		}
	}

	new MNP_Admin();

endif;
