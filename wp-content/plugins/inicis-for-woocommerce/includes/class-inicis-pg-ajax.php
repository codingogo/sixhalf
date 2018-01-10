<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class INICIS_PG_Ajax {
	static $slug;
	public static function init() {
		self::$slug = INICIS_PG()->slug();
		self::add_ajax_events();
	}
	public static function add_ajax_events() {

		$ajax_events = array();

		if ( is_admin() ) {
			$ajax_events = array_merge( $ajax_events, array(
				'update_settings' => false,
			) );
		}

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_' . self::$slug . '-' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . self::$slug . '-' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}

	}
	public static function update_settings() {
		INICIS_PG_Gateway_Settings::update_settings();
	}

}

//초기화 수행
INICIS_PG_Ajax::init();