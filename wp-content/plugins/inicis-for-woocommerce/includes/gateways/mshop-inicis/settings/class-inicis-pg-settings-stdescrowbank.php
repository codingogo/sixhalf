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

if ( ! class_exists( 'INICIS_PG_Settings_Stdescrowbank' ) ) {
	class INICIS_PG_Settings_Stdescrowbank extends INICIS_PG_Settings{
		protected $id = 'inicis_stdescrowbank';
		static function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => __('에스크로 계좌이체 기본 설정','inicis-for-woocommerce' ),
					'elements' => array(
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_title',
							'title'     => __('결제수단 이름','inicis-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => __('에스크로 계좌이체','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('사용자들이 체크아웃(결제진행)시에 나타나는 이름으로 사용자들에게 보여지는 이름입니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_description',
							'title'     => __('결제수단 설명','inicis-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => __('이니시스 결제대행사를 통해 결제합니다. 에스크로 결제의 경우 인터넷익스플로러(IE) 환경이 아닌 경우 사용이 불가능합니다. 결제 완료시 내 계정(My-Account)에서 주문을 확인하여 주시기 바랍니다.','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('사용자들이 체크아웃(결제진행)시에 나타나는 설명글로 사용자들에게 보여지는 내용입니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_order_status_after_payment',
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
									'content' => __( '에스크로 계좌이체 결제건에 한해서, 결제후 주문접수가 완료된 경우 해당 주문의 상태를 지정하는 필수옵션입니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_order_status_after_enter_shipping_number',
							'title'     => __( '배송정보 등록후 변경될 주문상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'shipped',
							'options'   => self::get_order_status_list( array(
								'completed',
								'on-hold',
								'pending',
								'processing'
							) ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '에스크로 계좌이체 결제건에 한해서, 관리자의 배송정보가 등록된 경우 변경될 주문 상태를 지정하는 옵션입니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_order_status_after_refund',
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
									'content' => __( '에스크로 계좌이체 결제건에 한해서, 사용자의 환불처리가 승인된 경우 해당 주문의 상태를 지정하는 필수옵션입니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_possible_refund_status_for_mypage',
							'title'     => __( '사용자 주문취소 가능상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'pending',
							'multiple'  => true,
							'options'   => self::clean_status( wc_get_order_statuses() ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '에스크로 계좌이체 결제건에 한해서, 사용자가 My-Account 메뉴에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_possible_check_and_reject_status_for_customer',
							'title'     => __( '사용자 주문확인 및 거절 가능상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'shipped,cancel-request',
							'multiple'  => true,
							'options'   => self::clean_status( wc_get_order_statuses() ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '에스크로 결제건에 한해서, 사용자가 내 계정 페이지 주문 상세 페이지에서 주문 확인 및 거절 처리를 할 수 있는 주문 상태를 지정합니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_possible_register_delivery_info_status_for_admin',
							'title'     => __( '관리자 배송등록 및 환불 가능상태', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'shipped,cancel-request,processing,cancelled',
							'multiple'  => true,
							'options'   => self::clean_status( wc_get_order_statuses() ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '에스크로 결제건에 한해서, 관리자가 관리자 페이지 주문 상세에서 배송 등록 및 환불 처리를 할 수 있는 주문 상태를 지정합니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_company_name',
							'title'     => __('택배사명','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('일반택배','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('에스크로 배송시 사용하는 택배사명을 입력해주세요. 배송정보 등록시에 사용됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_register_name',
							'title'     => __('배송정보 등록자 성명','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('홍길동','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('배송정보를 등록하시는 분의 성명을 입력해주세요. 일반적으로 사이트 관리자 성명을 입력하시면 됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_sender_name',
							'title'     => __('배송정보 발신자 성명','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('홍길동','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('배송정보 등록시 사용되는 발신자의 성명으로 사이트 관리자 성명 또는 업체명을 입력하시면 됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_sender_postnum',
							'title'     => __('배송정보 발신자 우편번호','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('12345','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('배송정보 등록시 사용되는 발신자의 우편번호로 \'000-000\' 또는 \'00000\' 와 같이 입력해주시면 됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_sender_addr1',
							'title'     => __('배송정보 발신자 기본주소','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('서울시 금천구 가산동','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('배송정보 등록시 사용되는 발신자의 기본주소로 \'<strong>서울시 금천구 가산동</strong>\'과 같이 입력해주시면 됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_sender_addr2',
							'title'     => __('배송정보 발신자 상세주소','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('123-1번지','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('배송정보 등록시 사용되는 발신자의 상세주소로 \'<strong>123-1번지</strong>\' 혹은 \'<strong>A오피스텔 1동 101호</strong>\'과 같이 입력해주시면 됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_delivery_sender_phone',
							'title'     => __('배송정보 발신자 전화번호','inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Text',
							'default'   => '',
							'placeholder' => __('010-0000-0000','inicis-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __('배송정보 등록시 사용되는 발신자의 전화번호로 \'<strong>000-0000-0000</strong>\'과 같이 입력해주시면 됩니다.','inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_receipt',
							'title'     => __( '현금영수증', 'inicis-for-woocommerce' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '현금영수증 발행 여부를 선택합니다. 현금영수증 발행은 이니시스와 별도로 계약이 되어있어야 이용이 가능합니다.', 'inicis-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'inicis_pg_inicis_stdescrowbank_use_advanced_setting',
							'showIf'    => array( 'hidden' => 'hidden' ),
							'title'     => '사용',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'yes',
							'desc'      => '고급 설정 기능을 사용합니다.'
						),

					)
				),
			);
		}
	}
}
