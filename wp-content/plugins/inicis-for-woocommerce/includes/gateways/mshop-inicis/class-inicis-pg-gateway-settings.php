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

if( class_exists('WC_Payment_Gateway') ) {

	if ( ! class_exists( 'INICIS_PG_Gateway_Settings' ) ) {
		class INICIS_PG_Gateway_Settings extends WC_Payment_Gateway {

			static $inicis_payment_methods = array(
				'inicis_stdcard'        	=> '신용카드',
				'inicis_stdbank'        	=> '실시간 계좌이체',
				'inicis_stdvbank'       	=> '가상계좌',
				'inicis_stdkpay'          	=> 'KPAY 간편결제',
				'inicis_stdhpp'          	=> '휴대폰 소액결제',
				'inicis_stdescrowbank' 		=> '에스크로 계좌이체',
				'inicis_stdsamsungpay' 		=> '삼성페이'
			);
			public function __construct() {
				$this->id         = 'mshop_inicis';

				$this->init_settings();

				$this->title       = __( '이니시스 결제 (' . get_option( 'inicis_pg_pc_pay_method' ) . ')', 'inicis_payment' );
				$this->description = __( '이니시스 결제대행사를 통해 결제합니다.', 'inicis_payment' );

				//Setting Helper 필터 추가
				add_filter('msshelper_get_inicis_pg_inicis_stdvbank_order_vbank_noti_url_new', array( $this , 'check_default_vbank_noti_url'), 10, 1);
			}
			public function  check_default_vbank_noti_url($value) {
				return untrailingslashit(WC()->api_request_url('WC_Gateway_Inicis_StdVbank?type=vbank_noti', false), true);
			}
			public static function update_settings() {
				include_once INICIS_PG()->plugin_path() . '/includes/admin/setting-manager/mshop-setting-helper.php';

				$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

				MSSHelper::update_settings( self::get_settings() );

				wp_send_json_success();
			}
			static function get_basic_setting() {
				return array(
					'id'       => 'basic-setting-tab',
					'title'    => '기본설정',
					'class'    => 'active',
					'type'     => 'Page',
					'elements' => INICIS_PG_Settings_Basic::get_setting_fields()
				);
			}
			static function get_inicis_stdcard_setting() {
				return array(
					'id'       => 'card-setting',
					'title'    => '신용카드',
					'type'     => 'Page',
				    'showIf' => array(
					    array( 'inicis_pg_pc_pay_method' => 'inicis_stdcard', 'inicis_pg_mobile_pay_method' => 'inicis_stdcard' ),
					    array( 'inicis_pg_enabled' => 'yes' ),
				    ),
					'elements' => INICIS_PG_Settings_Stdcard::get_setting_fields()
				);
			}
			static function get_inicis_stdbank_setting() {
				return array(
					'id'       => 'stdbank-setting',
					'title'    => '실시간 계좌이체',
					'type'     => 'Page',
				    'showIf' => array(
					    array( 'inicis_pg_pc_pay_method' => 'inicis_stdbank', 'inicis_pg_mobile_pay_method' => 'inicis_stdbank' ),
					    array( 'inicis_pg_enabled' => 'yes' ),
				    ),
					'elements' => INICIS_PG_Settings_Stdbank::get_setting_fields()
				);
			}
			static function get_inicis_stdvbank_setting() {
				return array(
					'id'       => 'stdvbank-setting',
					'title'    => '가상계좌',
					'type'     => 'Page',
					'showIf' => array(
						array( 'inicis_pg_pc_pay_method' => 'inicis_stdvbank', 'inicis_pg_mobile_pay_method' => 'inicis_stdvbank' ),
						array( 'inicis_pg_enabled' => 'yes' ),
					),
					'elements' => INICIS_PG_Settings_Stdvbank::get_setting_fields()
				);
			}
			static function get_inicis_stdescrowbank_setting() {
				return array(
					'id'       => 'stdescrowbank-setting',
					'title'    => '에스크로 계좌이체',
					'type'     => 'Page',
					'showIf' => array(
						array( 'inicis_pg_pc_pay_method' => 'inicis_stdescrowbank', 'inicis_pg_mobile_pay_method' => 'inicis_stdescrowbank' ),
						array( 'inicis_pg_enabled' => 'yes' ),
					),
					'elements' => INICIS_PG_Settings_Stdescrowbank::get_setting_fields()
				);
			}
			static function get_inicis_stdkpay_setting() {
				return array(
					'id'       => 'stdkpay-setting',
					'title'    => 'KPAY 간편결제',
					'type'     => 'Page',
					'showIf' => array(
						array( 'inicis_pg_pc_pay_method' => 'inicis_stdkpay', 'inicis_pg_mobile_pay_method' => 'inicis_stdkpay' ),
						array( 'inicis_pg_enabled' => 'yes' ),
					),
					'elements' => INICIS_PG_Settings_Stdkpay::get_setting_fields()
				);
			}
			static function get_inicis_stdhpp_setting() {
				return array(
					'id'       => 'stdhpp-setting',
					'title'    => '휴대폰 소액결제',
					'type'     => 'Page',
					'showIf' => array(
						array( 'inicis_pg_pc_pay_method' => 'inicis_stdhpp', 'inicis_pg_mobile_pay_method' => 'inicis_stdhpp' ),
						array( 'inicis_pg_enabled' => 'yes' ),
					),
					'elements' => INICIS_PG_Settings_Stdhpp::get_setting_fields()
				);
			}
			static function get_inicis_stdsamsungpay_setting() {
				return array(
					'id'       => 'stdsamsungpay-setting',
					'title'    => '삼성페이',
					'type'     => 'Page',
					'showIf' => array(
						array( 'inicis_pg_pc_pay_method' => 'inicis_stdsamsungpay', 'inicis_pg_mobile_pay_method' => 'inicis_stdsamsungpay' ),
						array( 'inicis_pg_enabled' => 'yes' ),
					),
					'elements' => INICIS_PG_Settings_Stdsamsungpay::get_setting_fields()
				);
			}
			public static function get_settings() {
				$settings = apply_filters( 'inicis-for-woocommerce-settings', array(
					self::get_basic_setting(),
					self::get_inicis_stdcard_setting(),
					self::get_inicis_stdbank_setting(),
					self::get_inicis_stdvbank_setting(),
					self::get_inicis_stdescrowbank_setting(),
					self::get_inicis_stdkpay_setting(),
					self::get_inicis_stdhpp_setting(),
					self::get_inicis_stdsamsungpay_setting()
				) );

				return
					array(
						'type'     => 'Tab',
						'id'       => 'inicis-setting-tab',
						'elements' => $settings
					);
			}
			public function enqueue_script() {
				wp_enqueue_style( 'mshop-setting-manager', INICIS_PG()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
				wp_enqueue_script( 'mshop-setting-manager', INICIS_PG()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array(
					'jquery',
					'jquery-ui-core'
				) );
			}
			public function admin_options() {
				$hide_save_button_option = get_option('inicis_pg_payment_hide_save_button', 'no');
				$hide_save_button_option = ($hide_save_button_option == 'no') ? true : false;

				$GLOBALS['hide_save_button'] = $hide_save_button_option;
				$settings                    = $this->get_settings();

				$this->enqueue_script();
				wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
					'element'     => 'mshop-setting-wrapper',
					'ajaxurl'     => admin_url( 'admin-ajax.php' ),
					'action'      => INICIS_PG()->slug() . '-update_settings',
					'settings'    => $settings,
					'slug'        => INICIS_PG()->slug(),
					'domain'      => preg_replace( '#^https?://#', '', site_url() ),
					'licenseInfo' => get_option( 'msl_license_' . INICIS_PG()->slug(), null )
				) );

				$licenseInfo = get_option( 'msl_license_' . INICIS_PG()->slug(), json_encode( array(
					'slug'   => INICIS_PG()->slug(),
					'domain' => preg_replace( '#^https?://#', '', site_url() )
				) ) );

				//키파일 업로드 처리
				if( isset($_FILES) && !empty($_FILES) ) {
					$this->keyfile_upload_process();
					?>
					<script type="text/javascript">
						window.location.reload();
					</script>
					<?php
				}
				?>
				<script>
					jQuery(document).ready(function ($) {
						$(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '200', <?php echo json_encode( MSSHelper::get_settings( $settings ) ); ?>, <?php echo $licenseInfo; ?>, null]);
					});
				</script>

				<div id="mshop-setting-wrapper"></div>

				<style type="text/css">
					.ui.segment.inicis-keyfile-wrap {
						overflow: hidden;
						margin-right: 20px;
						border-radius: 0;
					}
					.inicis-keyfile-wrap #inicis-keyfile-upload {
						float: left;
						overflow: hidden;
						font-size: .9em;
					}
					.inicis-keyfile-wrap #inicis-keyfile-upload input:last-child {
						background-color: #21ba45;
						font-weight: 700;
						transition: background .1s ease;
						border: none;
						color: #fff;
						height: 2.4em;
						padding: 0 10px;
					}
					.inicis-keyfile-wrap .submit {
						float: left;
						overflow: hidden;
						padding: 0;
						margin: 0;
						overflow: hidden;
					}
				</style>

				<div class="ui segment dimmable inicis-keyfile-wrap">
					<div id="inicis-keyfile-upload">
						상점 키파일 업로드(.zip) : <input id="upload_keyfile" type="file" size="36" name="upload_keyfile">
						<input type="submit" name="submit" value="업로드">
					</div>
				</div>
				<?php

			}
			public function keyfile_upload_process(){
				if( !empty($_FILES['upload_keyfile']) && isset($_FILES['upload_keyfile']) ) {
					if ( !file_exists( WP_CONTENT_DIR . '/inicis/upload' )) {
						$old = umask(0);
						mkdir( WP_CONTENT_DIR . '/inicis/upload', 0777, true );
						umask($old);
					}

					if( $_FILES['upload_keyfile']['size'] > 4086 ) {
						return false;
					}

					if( !class_exists('ZipArchive') ) {
						return false;
					}

					$zip = new ZipArchive();
					if(isset($_FILES['upload_keyfile']['tmp_name']) && !empty($_FILES['upload_keyfile']['tmp_name'])) {
						if($zip->open($_FILES['upload_keyfile']['tmp_name']) == TRUE) {
							for ($i = 0; $i < $zip->numFiles; $i++) {
								$filename = $zip->getNameIndex($i);
								if( !in_array( $filename, array('readme.txt', 'keypass.enc', 'mpriv.pem', 'mcert.pem') ) ) {
									return false;
								}
							}
						}

						$movefile = move_uploaded_file($_FILES['upload_keyfile']['tmp_name'], WP_CONTENT_DIR . '/inicis/upload/' . $_FILES['upload_keyfile']['name'] );
						if ( $movefile ) {
							WP_Filesystem();
							$filepath = pathinfo( WP_CONTENT_DIR . '/inicis/upload/' . $_FILES['upload_keyfile']['name'] );
							$unzipfile = unzip_file( WP_CONTENT_DIR . '/inicis/upload/' . $_FILES['upload_keyfile']['name'], WP_CONTENT_DIR . '/inicis/key/' . $filepath['filename'] );

							$this->init_form_fields();

							if ( !is_wp_error($unzipfile) ) {
								if ( !$unzipfile )  {
									return false;
								}
								return true;
							}
						} else {
							return false;
						}
					}
				}
			}

		}

	}

}