<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WC_Gateway_Inicis_Update' ) ) {
    class WC_Gateway_Inicis_Update
    {
        //버전별 업그레이드 처리 기술 파일 명시
        private static $db_updates = array(
            '4.4.0' => 'updates/inicis-update-4.4.0.php',
            '4.4.1' => 'updates/inicis-update-4.4.1.php',
            '5.0.0' => 'updates/inicis-update-5.0.0.php',
        );
        public function __construct()
        {
            add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
        }
        public function inicis_upgrade_notice($plugin_data, $r) {
        }
        public static function check_version() {
            global $inicis_payment;

            if( get_option( 'ifw_ver', true ) !== $inicis_payment->version ) {
                self::check_update();
                do_action( 'inicis_for_woocommerce_updated' );
            }
        }
        public static function check_update() {

            $current_version = get_option( 'ifw_ver', true );

            if ( ! is_null( $current_version ) && version_compare( $current_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
                self::do_update();
            }

        }
        public function do_update() {
            $current_version = get_option( 'ifw_ver', true );
            foreach ( self::$db_updates as $version => $updater ) {
                if ( version_compare( $current_version, $version, '<' ) ) {
                    include( $updater );
                    self::update_db_version( $version );
                }
            }
        }
        public function update_db_version() {
            global $inicis_payment;
            update_option( 'ifw_ver', $inicis_payment->version );
        }

    }

    $inicis_update = new WC_Gateway_Inicis_Update();
}
