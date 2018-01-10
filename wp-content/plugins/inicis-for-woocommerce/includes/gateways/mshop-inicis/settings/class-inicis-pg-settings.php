<?php
/*
=====================================================================================
                INICIS for WooCommerce / Copyright 2014 - 2016 by CodeM
=====================================================================================

  [ 우커머스 버전 지원 안내 ]

    워드프레스 버전 : WordPress 4.6.0

    우커머스 버전 : WooCommerce 2.6.0


  [ 코드엠 플러그인 라이센스 규정 ]

    1. 코드엠에서 개발한 워드프레스 우커머스용 결제 플러그인의 저작권은 ㈜코드엠에게 있습니다.

    2. 당사의 플러그인의 설치, 인증에 따른 절차는 플러그인 라이센스 규정에 동의하는 것으로 간주합니다.

    3. 결제 플러그인의 사용권은 쇼핑몰 사이트의 결제 서비스 사용에 국한되며, 그 외의 상업적 사용을 금지합니다.

    4. 결제 플러그인의 소스 코드를 복제 또는 수정 및 재배포를 금지합니다. 이를 위반 시 민형사상의 책임을 질 수 있습니다.

    5. 플러그인 사용에 있어 워드프레스, 테마, 플러그인과의 호환 및 버전 관리의 책임은 사이트 당사자에게 있습니다.

    6. 위 라이센스는 개발사의 사정에 의해 임의로 변경될 수 있으며, 변경된 내용은 해당 플러그인 홈페이지를 통해 공개합니다.

=====================================================================================
*/
//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'INICIS_PG_Settings' ) ) {
	abstract class INICIS_PG_Settings {
		static $prefix = 'inicis_pg_';

		protected $id = null;

		protected $settings = null;

		static $inicis_paymnet_methods = array(
			'inicis_stdcard'        	=> '신용카드',
			'inicis_stdbank'        	=> '실시간 계좌이체',
			'inicis_stdvbank'       	=> '가상계좌',
			'inicis_stdkpay'          	=> 'KPAY 간편결제',
			'inicis_stdhpp'          	=> '휴대폰 소액결제',
			'inicis_stdescrowbank' 		=> '에스크로 계좌이체',
			'inicis_stdsamsungpay' 		=> '삼성페이'
		);
		static function clean_status( $arr_status ) {
			if ( ! empty( $arr_status ) ) {
				$reoder = array();
				foreach ( $arr_status as $status => $status_name ) {
					$status            = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
					$reoder[ $status ] = $status_name;
				}

				return $reoder;
			} else {
				return $arr_status;
			}
		}
		static function get_keyfile_list() {

			$library_path = get_option( 'inicis_pg_libfolder' );

			if ( empty( $library_path ) ) {
				$library_path = WP_CONTENT_DIR . '/inicis';
			}

			$dirs = glob( $library_path . '/key/*', GLOB_ONLYDIR );
			if ( count( $dirs ) > 0 ) {
				$result = array();
				foreach ( $dirs as $val ) {
					$tmpmid        = substr( basename( $val ), 0, 3 );
					$tmpmid_escrow = substr( basename( $val ), 0, 5 );

					$valid_codes = array(
						base64_decode( "SU5J" ),
						base64_decode( "aW5p" ),
						base64_decode( "SUVT" ),
						base64_decode( "Y29k" ),
						base64_decode( "Q09E" ),
						base64_decode( "RVNDT0Q=" ),
					);
					if ( in_array( $tmpmid, $valid_codes ) || in_array( $tmpmid_escrow, $valid_codes ) ) {
						if ( file_exists( $val . '/keypass.enc' ) && file_exists( $val . '/mcert.pem' ) && file_exists( $val . '/mpriv.pem' ) && file_exists( $val . '/readme.txt' ) ) {
							$result[ basename( $val ) ] = basename( $val );
						}
					}
				}

				return $result;
			} else {
				return array( - 1 => __( '=== 키파일을 업로드 해주세요 ===', 'inicis-for-woocommerce' ) );
			}
		}
		static function get_order_status_list( $except_list ) {

			if ( version_compare( WOOCOMMERCE_VERSION, '2.2.0', '>=' ) ) {
				$shop_order_status = self::clean_status( wc_get_order_statuses() );

				$reorder = array();
				foreach ( $shop_order_status as $status => $status_name ) {
					$reorder[ $status ] = $status_name;
				}

				foreach ( $except_list as $val ) {
					unset( $reorder[ $val ] );
				}

				return $reorder;
			} else {

				$shop_order_status = get_terms( array( 'shop_order_status' ), array( 'hide_empty' => false ) );

				$reorder = array();
				foreach ( $shop_order_status as $key => $value ) {
					$reorder[ $value->slug ] = $value->name;
				}

				foreach ( $except_list as $val ) {
					unset( $reorder[ $val ] );
				}

				return $reorder;
			}
		}
		function get_full_id() {
			return self::$prefix . $this->id . '_';
		}
		public function set_setting_value( $key, $value ) {
			$array_type_key = array(
				'possible_refund_status_for_mypage',
				'possible_refund_status_for_admin',
				'possible_register_delivery_info_status_for_admin',
				'possible_check_and_reject_status_for_customer'
			);

			if( in_array( $key, $array_type_key ) ) {
				$this->settings[ $key ] = explode( ',', $value );
			}else{
				$this->settings[ $key ] = $value;
			}
		}
		public function get_settings() {
			if ( is_null( $this->settings ) ) {


				$this->settings = array(
					'global'  => array(),
					'gateway' => array()
				);
				$id             = $this->get_full_id();

				$setting_fields = array_merge(
					INICIS_PG_Settings_Basic::get_setting_fields(),
					static::get_setting_fields()
				);

				$settings = MSSHelper::get_settings( array( 'elements' => $setting_fields ) );

				$tmp_settings = array(
					'global'  => array(),
					'gateway' => array()
				);

				foreach ( $settings as $key => $value ) {
					if ( 0 === strpos( $key, $id ) ) {
						$tmp_settings['gateway'][ str_replace( $id, '', $key ) ] = $value;
					} else {
						$tmp_settings['global'][ str_replace( self::$prefix, '', $key ) ] = $value;
					}
				}

				$this->settings = $tmp_settings['global'];
				foreach( $tmp_settings['global'] as $key => $value ) {
					$this->set_setting_value( $key, $value );
				}

				foreach( $tmp_settings['gateway'] as $key => $value ) {
					if( ! isset( $this->settings[ $key] ) ) {
						$this->set_setting_value( $key, $value );
					}else{
						if( ! empty( $tmp_settings['gateway']['use_advanced_setting'] ) && 'yes' == $tmp_settings['gateway']['use_advanced_setting'] ) {
							$this->set_setting_value( $key, $value );
						}
					}
				}
			}

			$available_methods = explode( ',', $this->settings['pc_pay_method'] );
			$this->settings['enabled'] = in_array( $this->id, $available_methods ) ? 'yes' : 'no';

			return $this->settings;
		}
		public function get( $key ) {
			$settings = $this->get_settings();

			if ( array_key_exists( $key, $settings['global'] ) ) {
				return $settings['global'][ $key ];
			} else if ( array_key_exists( $key, $settings['gateway'] ) ) {
				return $settings['gateway'][ $key ];
			}

			return '';
		}
		static function get_setting_fields() {
			return array();
		}
	}
}
