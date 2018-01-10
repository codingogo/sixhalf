<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MNP_Ajax {

	static $slug;

	public static function init() {
		self::$slug = MNP()->slug();
		self::add_ajax_events();
	}
	public static function add_ajax_events() {

		$ajax_events = array (
			'create_order'    => true,
			'checkout_cart'   => true,
			'add_to_wishlist' => true,
		);

		if ( is_admin() ) {
			$ajax_events = array_merge( $ajax_events, array (
				'update_settings'         => false,
				'refresh_npay_order'      => false,
				'answer_customer_inquiry' => 'MNP_Order::answer_customer_inquiry',
				'order_action'            => 'MNP_Order::order_action',
				'api_reset'               => false,
				'dashboard_action'        => 'MNP_Dashboard::dashboard_action',
				'reset_sheet_fields'      => 'MNP_Sheets::reset_sheet_fields',
				'upload_sheets'           => 'MNP_Sheets::upload_sheets',
				'update_sheet_settings'   => 'MNP_Settings_Sheet::update_settings',
			) );
		}

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			if ( is_string( $nopriv ) ) {
				add_action( 'wp_ajax_' . self::$slug . '-' . $ajax_event, $nopriv );
			} else {
				add_action( 'wp_ajax_' . self::$slug . '-' . $ajax_event, array ( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_' . self::$slug . '-' . $ajax_event, array ( __CLASS__, $ajax_event ) );
				}
			}
		}
	}

	static function refresh_npay_order() {
		MNP_Order::refresh_npay_order();
	}

	static function api_reset() {
		MNP_API::reset_key();
		MNP_Manager::set_service_status( 'inactive' );

		wp_send_json_success( array ( 'reload' => true ) );
	}

	static function update_settings() {
		MNP_Settings::update_settings();
	}

	static function create_order() {
		MNP_Cart::create_order();
	}

	static function checkout_cart() {
		MNP_Cart::checkout_cart();
	}

	static function add_to_wishlist() {
		MNP_Cart::add_to_wishlist();
	}
}

MNP_Ajax::init();
