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

if ( ! class_exists( 'INICIS_PG_Settings_Basic' ) ) {
	class INICIS_PG_Settings_Basic extends INICIS_PG_Settings{
		public static function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array(
						array(
							'id'        => 'inicis_pg_enabled',
							'title'     => '이니시스 결제 기능 사용',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array(
								'title' => array(
									'content' => '이니시스 결제 대행 서비스를 사용하시는 분들을 위한 설정 페이지입니다.<br>실제 서비스를 하시려면 필요 정보를 이니시스에서 키파일을 발급받아 설치하셔야 정상 사용이 가능합니다.'
								)
							)
						),
						array(
							'id'       => 'inicis_pg_pc_pay_method',
							'title'    => '결제수단',
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'default'  => '',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => self::$inicis_paymnet_methods
						),
						array(
							'id'        => 'inicis_pg_payment_tag',
							'title'     => __( '결제 페이지 태그 설정', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '#order_review input[name=payment_method]:checked',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지가 우커머스 기본 결제 태그와 다른 경우, 결제수단 확인이 가능한 별도 태그를 넣어 지정할 수 있습니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_site_logo',
							'title'     => __( '사이트 로고', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 창 왼쪽 상단에 가맹점 사이트의 로고를 표시합니다. 가맹점의 로고가 있는 URL을 정확히 입력하셔야 하며, 입력하지 않으면 표시되지 않습니다. 권장 사이즈는 89 * 18 픽셀 입니다. 해당 사이즈에 맞춰 자동 리사이즈 됩니다. (예 : http://www.aaa.com/a.jpg)', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_site_ajax_loader',
							'title'     => __( '결제 로딩 이미지', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => INICIS_PG()->plugin_url() . '/assets/images/ajax_loader.gif',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 창이 노출되기 전에 잠시 노출되는 로딩 이미지 경로를 설정할 수 있습니다. (예 : http://www.aaa.com/a.jpg)', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_skin_indx',
							'title'     => __( '스킨 설정', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '#e5493a',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제창의 스킨 색상을 설정합니다. 입력 값은 HTML 색상값 코드를 입력해주세요. 예를 들어 #000000 으로 입력하시면 검정색으로 나타나며, #0000ff 로 입력하시면 파랑색으로 표시가 됩니다. Color Picker 를 이용하시면 편리하게 색상을 설정할 수 있습니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_payment_error_email_alert',
							'title'     => __( '결제오류 메일알림', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 오류로 인해 관리자가 주문을 확인해야 할 필요가 있는 경우 발송되는 이메일 수신 여부를 설정할 수 있습니다. 사용하는 경우, 이메일로 관리자의 확인이 필요한 결제오류 발생시 메일이 전송됩니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_payment_error_email_address',
							'title'     => __( '결제오류 수신메일', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제오류를 수신할 메일주소를 지정할 수 있습니다. 미입력하거나 공백인 경우에는 사이트 관리자 이메일 주소로 발송됩니다. 이메일 주소는 한개만 입력 가능합니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_payment_hide_save_button',
							'title'     => __( '변경사항 버튼노출', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '우커머스 기본 설정 변경 버튼을 노출합니다. 설정된 경우 버튼이 노출되며 설정되지 않은 경우 버튼이 노출되지 않습니다. 특수한 경우에만 사용하도록 제공되는 옵션으로 일반적인 경우 사용하지 않아도 됩니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
					)
				),
				array(
					'type'     => 'Section',
					'title'    => '일반 결제 설정',
					'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
					'elements' => array(
						array(
							'id'        => 'inicis_pg_libfolder',
							'title'     => '이니페이 설치 경로',
							'className' => 'fluid',
							'default'   => WP_CONTENT_DIR . '/inicis',
							'type'     => 'Text',
							'desc2' => __('이니페이 설치 경로 안에 key 폴더(키파일)와 log 폴더(로그)가 위치한 경로를 입력해주세요. 키파일 폴더와 로그 폴더의 권한 설정은 가이드를 참고해주세요.', 'inicis-for-woocommerce'),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '<span style="color:red;font-weight:bold;">[ 주의사항 ]<ul><li>사용하시는 호스팅이나 서버 상태에 따라서 웹상에서 접근 불가능한 경로에 업로드 하시고 절대경로 주소를 입력해주세요.</li><li>웹상에서 접근 가능한 경로에 폴더가 위치한 경우 키파일 및 로그 파일 노출로 인한 보안사고가 발생할 수 있으며 이 경우 발생하는 문제는 상점의 책임입니다.</li></ul></span>', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_merchant_id',
							'title'     => '상점 아이디',
							'className' => '',
							'placeHolder' => '상점 아이디를 선택하세요.',
							'type'     => 'Select',
							'options'  => self::get_keyfile_list()
						),
						array(
							'id'        => 'inicis_pg_signkey',
							'title'     => '웹표준 사인키',
							'className' => 'fluid',
							'desc2' => __('웹표준 사인키는 결제시 필요한 필수 값으로 상점 관리자 페이지에서 확인이 가능합니다.<br>결제 테스트용 INIpayTest 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.', 'inicis-for-woocommerce'),
							'type'      => 'Text'
						)
					)
				),
				array(
					'type'     => 'Section',
					'title'    => '에스크로 결제 설정',
					'showIf' => array(
						array( 'inicis_pg_pc_pay_method' => 'inicis_stdescrowbank', 'inicis_pg_mobile_pay_method' => 'inicis_stdescrowbank' ),
						array( 'inicis_pg_enabled' => 'yes' ),
					),
					'elements' => array(
						array(
							'id'        => 'inicis_pg_escrow_merchant_id',
							'title'     => '상점 아이디',
							'className' => '',
							'placeHolder' => '상점 아이디를 선택하세요.',
							'type'     => 'Select',
							'options'  => self::get_keyfile_list()
						),
						array(
							'id'        => 'inicis_pg_escrow_signkey',
							'title'     => '웹표준 사인키',
							'className' => 'fluid',
							'desc2' => __('웹표준 사인키는 결제시 필요한 필수 값으로 상점 관리자 페이지에서 확인이 가능합니다.<br>결제 테스트용 INIpayTest 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.', 'inicis-for-woocommerce'),
							'type'      => 'Text'
						)
					)
				),
				array(
					'type'     => 'Section',
					'title'    => '서비스 설정',
					'showIf'   => array( 'inicis_pg_enabled' => 'yes' ),
					'elements' => array(
						array(
							'id'        => 'inicis_pg_order_status_after_payment',
							'title'     => __( '결제완료시 변경될 주문상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'processing',
							'options'   => self::get_order_status_list( array(
								'cancelled',
								'failed',
								'on-hold',
								'refunded'
							) ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '이니시스 결제건에 한해서, 결제후 주문접수가 완료된 경우 해당 주문의 상태를 지정하는 필수옵션입니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_order_status_after_refund',
							'title'     => __( '환불처리시 변경될 주문상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'refunded',
							'options'   => self::get_order_status_list( array(
								'completed',
								'on-hold',
								'pending',
								'processing'
							) ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '이니시스 결제건에 한해서, 사용자의 환불처리가 승인된 경우 해당 주문의 상태를 지정하는 필수옵션입니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_possible_refund_status_for_mypage',
							'title'     => __( '사용자 주문취소 가능상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'pending,on-hold',
							'multiple'  => true,
							'options'   => self::clean_status( wc_get_order_statuses() ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '이니시스 결제건에 한해서, 사용자가 My-Account 메뉴에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_possible_refund_status_for_admin',
							'title'     => __( '관리자 주문취소 가능상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'processing',
							'multiple'  => true,
							'options'   => self::clean_status( wc_get_order_statuses() ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '이니시스 결제건에 한해서, 관리자가 관리자 페이지 주문 상세 페이지에서 환불 처리를 할 수 있는 주문 상태를 지정합니다.', 'inicis-for-woocommerce' ),
								)
							)
						)

					)
				)
			);
		}
	}
}
