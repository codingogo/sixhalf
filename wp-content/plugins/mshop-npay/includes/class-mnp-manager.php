<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MNP_Manager' ) ) {

	include_once( 'naverpay/ShippingPolicy.php' );

	class MNP_Manager {
		const MODE_NONE = 'None';
		const MODE_SANDBOX = 'SandBox';
		const MODE_PRODUCTION = 'Production';

		const ADDITIONAL_FEE_REGION = 'additional-fee-region';
		const ADDITIONAL_FEE_API = 'additional-fee-api';

		const PAYMENT_GATEWAY_NAVERPAY = 'naverpay';

		const CLAIM_TYPE_CANCEL = 'CANCEL';
		const CLAIM_TYPE_RETURN = 'RETURN';
		const CLAIM_TYPE_EXCHANGE = 'EXCHANGE';
		const CLAIM_TYPE_PURCHASE_DECISION_HOLDBACK = 'PURCHASE_DECISION_HOLDBACK';
		const CLAIM_ADMIN_CANCEL = 'ADMIN_CANCEL';

		const CLAIM_STATUS_CANCEL_CANCEL_REQUEST = 'CANCEL_REQUEST';
		const CLAIM_STATUS_CANCEL_CANCELING = 'CANCELING';
		const CLAIM_STATUS_CANCEL_CANCEL_DONE = 'CANCEL_DONE';
		const CLAIM_STATUS_CANCEL_CANCEL_REJECT = 'CANCEL_REJECT';
		const CLAIM_STATUS_RETURN_RETURN_REQUEST = 'RETURN_REQUEST';
		const CLAIM_STATUS_RETURN_COLLECTING = 'COLLECTING';
		const CLAIM_STATUS_RETURN_COLLECT_DONE = 'COLLECT_DONE';
		const CLAIM_STATUS_RETURN_RETURN_DONE = 'RETURN_DONE';
		const CLAIM_STATUS_RETURN_RETURN_REJECT = 'RETURN_REJECT';
		const CLAIM_STATUS_EXCHANGE_EXCHANGE_REQUEST = 'EXCHANGE_REQUEST';
		const CLAIM_STATUS_EXCHANGE_COLLECTING = 'COLLECTING';
		const CLAIM_STATUS_EXCHANGE_COLLECT_DONE = 'COLLECT_DONE';
		const CLAIM_STATUS_EXCHANGE_EXCHANGE_REDELIVERING = 'EXCHANGE_REDELIVERING';
		const CLAIM_STATUS_EXCHANGE_EXCHANGE_DONE = 'EXCHANGE_DONE';
		const CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT = 'EXCHANGE_REJECT';
		const CLAIM_STATUS_PURCHASE_DECISION_HOLDBACK = 'PURCHASE_DECISION_HOLDBACK';
		const CLAIM_STATUS_PURCHASE_DECISION_HOLDBACK_REDELIVERING = 'PURCHASE_DECISION_HOLDBACK_REDELIVERING';
		const CLAIM_STATUS_PURCHASE_DECISION_REQUEST = 'PURCHASE_DECISION_REQUEST';
		const CLAIM_STATUS_PURCHASE_DECISION_HOLDBACK_RELEASE = 'PURCHASE_DECISION_HOLDBACK_RELEASE';
		const CLAIM_STATUS_ADMIN_CANCELING = 'ADMIN_CANCELING';
		const CLAIM_STATUS_ADMIN_CANCEL_DONE = 'ADMIN_CANCEL_DONE';
		const STATUS_CHANGED_PAY_WAITING = 'PAY_WAITING';
		const STATUS_CHANGED_PAYED = 'PAYED';
		const STATUS_CHANGED_DISPATCHED = 'DISPATCHED';
		const STATUS_CHANGED_CANCEL_REQUESTED = 'CANCEL_REQUESTED';
		const STATUS_CHANGED_RETURN_REQUESTED = 'RETURN_REQUESTED';
		const STATUS_CHANGED_EXCHANGE_REQUESTED = 'EXCHANGE_REQUESTED';
		const STATUS_CHANGED_EXCHANGE_REDELIVERY_READY = 'EXCHANGE_REDELIVERY_READY';
		const STATUS_CHANGED_HOLDBACK_REQUESTED = 'HOLDBACK_REQUESTED';
		const STATUS_CHANGED_CANCELED = 'CANCELED';
		const STATUS_CHANGED_RETURNED = 'RETURNED';
		const STATUS_CHANGED_EXCHANGED = 'EXCHANGED';
		const STATUS_CHANGED_PURCHASE_DECIDED = 'PURCHASE_DECIDED';
		const PRODUCT_ORDER_STATUS_PAYMENT_WAITING = 'PAYMENT_WAITING';
		const PRODUCT_ORDER_STATUS_PAYED = 'PAYED';
		const PRODUCT_ORDER_STATUS_DELIVERING = 'DELIVERING';
		const PRODUCT_ORDER_STATUS_DELIVERED = 'DELIVERED';
		const PRODUCT_ORDER_STATUS_PURCHASE_DECIDED = 'PURCHASE_DECIDED';
		const PRODUCT_ORDER_STATUS_EXCHANGED = 'EXCHANGED';
		const PRODUCT_ORDER_STATUS_CANCELED = 'CANCELED';
		const PRODUCT_ORDER_STATUS_RETURNED = 'RETURNED';
		const PRODUCT_ORDER_STATUS_CANCELED_BY_NOPAYMENT = 'CANCELED_BY_NOPAYMENT';
		const DELAY_RESAON_PRODUCT_PREPARE = 'PRODUCT_PREPARE';
		const DELAY_RESAON_CUSTOMER_REQUEST = 'CUSTOMER_REQUEST';
		const DELAY_RESAON_CUSTOM_BUILD = 'CUSTOM_BUILD';
		const DELAY_RESAON_RESERVED_DISPATCH = 'RESERVED_DISPATCH';
		const DELAY_RESAON_ETC = 'ETC';
		const DELIVERY_METHOD_DELIVERY = 'DELIVERY';
		const DELIVERY_METHOD_GDFW_ISSUE_SVC = 'GDFW_ISSUE_SVC';
		const DELIVERY_METHOD_VISIT_RECEIPT = 'VISIT_RECEIPT';
		const DELIVERY_METHOD_DIRECT_DELIVERY = 'DIRECT_DELIVERY';
		const DELIVERY_METHOD_QUICK_SVC = 'QUICK_SVC';
		const DELIVERY_METHOD_NOTHING = 'NOTHING';

		const ORDER_STATUS_PAY_WAITING = 'PAYMENT_WAITING';
		const ORDER_STATUS_PAYED = 'PAYED';
		const ORDER_STATUS_DISPATCHED = 'DISPATCHED';
		const ORDER_STATUS_DELAYED = 'DELAYED';
		const ORDER_STATUS_CANCEL_REQUESTED = 'CANCEL_REQUESTED';
		const ORDER_STATUS_RETURN_REQUESTED = 'RETURN_REQUEST';
		const ORDER_STATUS_EXCHANGE_REQUESTED = 'EXCHANGE_REQUESTED';
		const ORDER_STATUS_EXCHANGE_REDELIVERY_READY = 'EXCHANGE_REDELIVERY_READY';
		const ORDER_STATUS_HOLDBACK_REQUESTED = 'HOLDBACK_REQUESTED';
		const ORDER_STATUS_CANCELED = 'CANCELED';
		const ORDER_STATUS_RETURNED = 'RETURNED';
		const ORDER_STATUS_EXCHANGED = 'EXCHANGED';
		const ORDER_STATUS_PURCHASE_DECIDED = 'PURCHASE_DECIDED';

		private static $order_status_description = array (
			self::ORDER_STATUS_PAY_WAITING               => '입금 대기',
			self::ORDER_STATUS_PAYED                     => '결제 완료',
			self::ORDER_STATUS_DISPATCHED                => '발송 처리',
			self::ORDER_STATUS_DELAYED                   => '발송 지연',
			self::ORDER_STATUS_CANCEL_REQUESTED          => '취소 요청',
			self::ORDER_STATUS_RETURN_REQUESTED          => '반품 요청',
			self::ORDER_STATUS_EXCHANGE_REQUESTED        => '교환 요청',
			self::ORDER_STATUS_EXCHANGE_REDELIVERY_READY => '교환 재배송 준비',
			self::ORDER_STATUS_HOLDBACK_REQUESTED        => '구매 확정 보류 요청',
			self::ORDER_STATUS_CANCELED                  => '취소',
			self::ORDER_STATUS_RETURNED                  => '반품',
			self::ORDER_STATUS_EXCHANGED                 => '교환',
			self::ORDER_STATUS_PURCHASE_DECIDED          => '구매 확정'
		);

		private static $order_status = array (
			self::STATUS_CHANGED_PAY_WAITING               => 'pending',
			self::STATUS_CHANGED_PAYED                     => 'processing',
			self::STATUS_CHANGED_DISPATCHED                => 'processing',
			self::STATUS_CHANGED_CANCEL_REQUESTED          => 'processing',
			self::STATUS_CHANGED_RETURN_REQUESTED          => 'processing',
			self::STATUS_CHANGED_EXCHANGE_REQUESTED        => 'processing',
			self::STATUS_CHANGED_EXCHANGE_REDELIVERY_READY => 'processing',
			self::STATUS_CHANGED_HOLDBACK_REQUESTED        => 'processing',
			self::STATUS_CHANGED_CANCELED                  => 'cancelled',
			self::STATUS_CHANGED_RETURNED                  => 'refunded',
			self::STATUS_CHANGED_EXCHANGED                 => 'completed',
			self::STATUS_CHANGED_PURCHASE_DECIDED          => 'completed'
		);
		const PLACE_ORDER_STATUS_NOT_YET = 'NOT_YET';
		const PLACE_ORDER_STATUS_OK = 'OK';
		const PLACE_ORDER_STATUS_CANCEL = 'CANCEL';

		private static $is_sandbox = null;
		private static $is_production = null;

		private static $merchant_id = null;
		private static $auth_key = null;
		private static $button_auth_key = null;
		private static $common_auth_key = null;

		public static function init() {
		}

		public static function return_reason() {
			return apply_filters( 'naverpay_return_reason', array (
				'INTENT_CHANGED'      => '구매 의사 취소',
				'COLOR_AND_SIZE'      => '색상 및 사이즈 변경',
				'WRONG_ORDER'         => '다른 상품 잘못 주문',
				'PRODUCT_UNSATISFIED' => '서비스 및 사품 불만족',
				'DELAYED_DELIVERY'    => '배송 지연',
				'SOLD_OUT'            => '상품 품절',
				'DROPPED_DELIVERY'    => '배송 누락',
				'BROKEN'              => '상품 파손',
				'INCORRECT_INFO'      => '상품 정보 상이',
				'WRONG_DELIVERY'      => '오배송',
				'WRONG_OPTION'        => '색상등이 다른 상품을 잘못 배송',
				'ETC'                 => '기타',
			) );
		}

		public static function claim_cancel_status() {
			return apply_filters( 'naverpay_claim_cancel_status', array (
				'CANCEL_REQUEST' => '취소 요청',
				'CANCELING'      => '취소 처리 중',
				'CANCEL_DONE'    => '취소 처리 완료',
				'CANCEL_REJECT'  => '취소 철회'
			) );
		}

		public static function claim_exchange_status() {
			return apply_filters( 'naverpay_claim_exchange_status', array (
				'EXCHANGE_REQUEST'      => '교환 요청',
				'COLLECTING'            => '수거 처리 중',
				'COLLECT_DONE'          => '수거 완료',
				'EXCHANGE_REDELIVERING' => '교환 재배송 중',
				'EXCHANGE_DONE'         => '교환 완료',
				'EXCHANGE_REJECT'       => '교환 거부'
			) );
		}

		private static function mode_suffix() {
			if ( self::is_sandbox() ) {
				return '-sandbox';
			} else {
				return '-production';
			}
		}

		public static function default_args() {
			$license_info = json_decode( get_option( 'msl_license_' . MNP()->slug(), null ) );

			return array (
				'service' => 'naverpay',
				'version' => '3.0',
				'api_key' => MNP_Manager::get_api_key(),
				'domain'  => home_url(),
				'mode'    => MNP_Manager::operation_mode()
			);
		}

		public static function get_order_status( $status_changed_code ) {
			return self::$order_status[ $status_changed_code ];
		}

		public static function get_order_status_description( $order_status ) {
			return isset( self::$order_status_description[ $order_status ] ) ? self::$order_status_description[ $order_status ] : '';
		}

		private static function url_prefix() {
			if ( self::is_sandbox() ) {
				return 'test-';
			} else {
				return '';
			}
		}

		public static function set_service_status( $status ) {
			update_option( 'mshop-naverpay-status', $status );
		}

		public static function is_operable() {
			if( has_filter( 'wpml_object_id') ) {
				global $sitepress;
				if( 'ko' != $sitepress->get_current_language() ) {
					return false;
				}
			}

			return 'active' == get_option( 'mshop-naverpay-status' ) && ( MNP_Manager::is_sandbox() && MNP_Manager::is_test_user() ) || self::is_production();
		}

		public static function operation_mode() {
			return get_option( 'mshop-naverpay-operation-mode', self::MODE_NONE );
		}

		public static function get_api_key() {
			return get_option( 'mshop-naverpay-api-key', '' );
		}

		public static function set_api_key( $api_key ) {
			return update_option( 'mshop-naverpay-api-key', $api_key );
		}

		public static function is_sandbox() {
			if ( empty( self::$is_sandbox ) ) {
				self::$is_sandbox = ( self::MODE_SANDBOX == get_option( 'mshop-naverpay-operation-mode', self::MODE_NONE ) );
			}

			return self::$is_sandbox;
		}

		public static function is_production() {
			if ( empty( self::$is_production ) ) {
				self::$is_production = ( self::MODE_PRODUCTION == get_option( 'mshop-naverpay-operation-mode', self::MODE_NONE ) );
			}

			return self::$is_production;
		}

		public static function merchant_id() {
			if ( empty( self::$merchant_id ) ) {
				self::$merchant_id = get_option( 'mshop-naverpay-merchant-id', '' );
			}

			return self::$merchant_id;
		}

		public static function auth_key() {
			if ( empty( self::$auth_key ) ) {
				self::$auth_key = get_option( 'mshop-naverpay-auth-key' );
			}

			return self::$auth_key;
		}

		public static function button_auth_key() {
			if ( empty( self::$button_auth_key ) ) {
				self::$button_auth_key = get_option( 'mshop-naverpay-button-auth-key' );
			}

			return self::$button_auth_key;
		}

		public static function common_auth_key() {
			if ( empty( self::$common_auth_key ) ) {
				self::$common_auth_key = get_option( 'mshop-naverpay-common-auth-key' );
			}

			return self::$common_auth_key;
		}

		public static function is_test_user() {
			$user = wp_get_current_user();

			if ( current_user_can( 'manage_options' ) || ( ! empty( $user ) && get_option( 'mshop-naverpay-test-user-id' ) == $user->user_login ) ) {
				return true;
			} else {
				return false;
			}
		}

		public static function register_order_url() {
			return 'https://' . self::url_prefix() . 'api.pay.naver.com/o/customer/api/order/v20/register';
		}

		public static function wishlist_url() {
			return 'https://' . self::url_prefix() . 'pay.naver.com/customer/api/wishlist.nhn';
		}

		public static function ordersheet_url( $device = 'mobile' ) {
			if ( 'mobile' == $device ) {
				return 'https://' . self::url_prefix() . 'm.pay.naver.com/o/customer/buy/';
			} else {
				return 'https://' . self::url_prefix() . 'order.pay.naver.com/customer/buy/';
			}
		}

		public static function wishlist_popup_url() {
			if ( wp_is_mobile() ) {
				return 'https://' . self::url_prefix() . 'm.pay.naver.com/mobile/customer/wishList.nhn';
			} else {
				return 'https://' . self::url_prefix() . 'pay.naver.com/customer/wishlistPopup.nhn';
			}
		}

		public static function button_js_url( $device = 'mobile' ) {
			if ( 'mobile' == $device ) {
				return 'https://' . self::url_prefix() . 'pay.naver.com/customer/js/mobile/naverPayButton.js';
			} else {
				return 'https://' . self::url_prefix() . 'pay.naver.com/customer/js/naverPayButton.js';
			}
		}

		public static function button_type_pc() {
			return get_option( 'mshop-naverpay-button-type-pc', 'A' );
		}

		public static function button_type_mobile() {
			return get_option( 'mshop-naverpay-button-type-mobile', 'MA' );
		}

		public static function button_color() {
			return get_option( 'mshop-naverpay-button-color-pc', '1' );
		}

		public static function button_count() {
			return get_option( 'mshop-naverpay-button-count-' . ( wp_is_mobile() ? 'mobile' : 'pc' ), '2' );
		}

		public static function sync_review() {
			return ! empty( self::merchant_id() ) && 'yes' == get_option( 'mshop-naverpay-sync-review', 'yes' );
		}

		public static function sync_normal_review() {
			return self::sync_review() && 'yes' == get_option( 'mshop-naverpay-sync-normal-review', 'yes' );
		}

		public static function sync_premium_review() {
			return self::sync_review() && 'yes' == get_option( 'mshop-naverpay-sync-premium-review', 'no' );
		}

		public static function generate_shipping_policy_surcharge_by_area() {
			include_once( 'naverpay/ShippingPolicySurchargeByArea.php' );

			if ( 'yes' == get_option( 'mshop-naverpay-use-additional-fee', 'no' ) ) {
				$fee_mode = get_option( 'mshop-naverpay-additional-fee-mode', MNP_Manager::ADDITIONAL_FEE_REGION );

				if ( MNP_Manager::ADDITIONAL_FEE_REGION == $fee_mode ) {
					$splitUnit  = get_option( 'mshop-naverpay-additional-fee-region' );
					$area2Price = get_option( 'mshop-naverpay-additional-fee-region-2' );
					$area3Price = get_option( 'mshop-naverpay-additional-fee-region-3' );

					return new ShippingPolicySurchargeByArea( false, $splitUnit, $area2Price, $area3Price );
				} else if ( MNP_Manager::ADDITIONAL_FEE_API == $fee_mode ) {
					return new ShippingPolicySurchargeByArea( true, null, null, null );
				}
			}

			return null;
		}

		public static function is_purchasable( $product_id ) {
			if ( 'yes' == get_post_meta( $product_id, '_naverpay_unavailable', 'no' ) ) {
				return false;
			}

			$terms = get_the_terms( $product_id, 'product_cat' );
			$cats  = json_decode( get_option( 'mshop-naverpay-except-category' ) );

			if ( ! empty( $terms ) && ! empty( $cats ) ) {
				foreach ( $terms as $term ) {
					if ( in_array( $term->term_id, $cats ) ) {
						return false;
					}
				}
			}

			return true;
		}

		public static function request_connect( $api_key ) {
			$result   = MNP_API::connect_key( $api_key );
			$response = $result->response;

			if ( $response->ResponseType == "SUCCESS" ) {
				self::set_api_key( $api_key );
				self::set_service_status( 'active' );
				return true;
			}

			self::set_service_status( 'inactive' );
			return false;
		}

	}
}

