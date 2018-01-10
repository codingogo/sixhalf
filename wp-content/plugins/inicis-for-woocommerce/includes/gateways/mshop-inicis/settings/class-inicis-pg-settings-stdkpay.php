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

if ( ! class_exists( 'INICIS_PG_Settings_Stdkpay' ) ) {
	class INICIS_PG_Settings_Stdkpay extends INICIS_PG_Settings{
		protected $id = 'inicis_stdkpay';
		static function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => 'KPAY 간편결제 기본 설정',
					'elements' => array(
						array(
							'id'        => 'inicis_pg_inicis_stdkpay_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => 'KPAY 간편결제',
							'tooltip'   => array(
								'title' => array(
									'content' => '사용자들이 체크아웃(결제진행)시에 나타나는 이름으로 사용자들에게 보여지는 이름입니다.'
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdkpay_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => '모바일 및 PC에서 KPAY 를 통해 간편결제를 이용할 수 있습니다.',
							'tooltip'   => array(
								'title' => array(
									'content' => '사용자들이 체크아웃(결제진행)시에 나타나는 설명글로 사용자들에게 보여지는 내용입니다.'
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdhpp_direct_run',
							'title'     => '모바일 KPAY 자동실행',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'desc'      => '모바일 KPAY 결제시, KPAY 앱을 바로 실행하도록 설정합니다.'
						),
					)
				),
				array(
					'type'     => 'Section',
					'title'    => '고급 설정',
					'elements' => array(
						array(
							'id'        => 'inicis_pg_inicis_stdkpay_use_advanced_setting',
							'title'     => '사용',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'desc'      => '고급 설정 기능을 사용합니다.'
						),
						array(
							'id'        => 'inicis_pg_inicis_stdkpay_order_status_after_payment',
							'title'     => __( '결제완료시 변경될 주문상태', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_inicis_stdkpay_use_advanced_setting' => 'yes' ),
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
							'id'        => 'inicis_pg_inicis_stdkpay_order_status_after_refund',
							'title'     => __( '환불처리시 변경될 주문상태', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_inicis_stdkpay_use_advanced_setting' => 'yes' ),
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
							'id'        => 'inicis_pg_inicis_stdkpay_possible_refund_status_for_mypage',
							'title'     => __( '사용자 주문취소 가능상태', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_inicis_stdkpay_use_advanced_setting' => 'yes' ),
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
							'id'        => 'inicis_pg_inicis_stdkpay_possible_refund_status_for_admin',
							'title'     => __( '관리자 주문취소 가능상태', 'inicis-for-woocommerce' ),
							'showIf'   => array( 'inicis_pg_inicis_stdkpay_use_advanced_setting' => 'yes' ),
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
						),
					)
				)
			);
		}
	}
}
