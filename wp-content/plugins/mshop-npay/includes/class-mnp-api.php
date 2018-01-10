<?php



if ( ! class_exists( 'MNP_API' ) ) {

	class MNP_API {

		const COMMAND_GET_PRODUCT_ORDER_INFO_LIST = 'get_product_order_info_list';
		const COMMAND_GET_CHANGED_PRODUCT_ORDER_LIST = 'get_changed_product_order_list';
		const COMMAND_CANCEL_SALE = 'cancel_sale';
		const COMMAND_APPROVE_CANCEL_APPLICATION = 'approve_cancel_application';
		const COMMAND_PLACE_PRODUCT_ORDER = 'place_product_order';
		const COMMAND_DELAY_PRODUCT_ORDER = 'delay_product_order';
		const COMMAND_SHIP_PRODUCT_ORDER = 'ship_product_order';
		const COMMAND_REQUEST_RETURN = 'request_return';
		const COMMAND_APPROVE_RETURN_APPLICATION = 'approve_return_application';
		const COMMAND_APPROVE_COLLECTED_EXCHANGE = 'approve_collected_exchange';
		const COMMAND_REDELIVERY_EXCHANGE = 'redelivery_exchange';
		const COMMAND_RELEASE_RETURN_HOLD = 'release_return_hold';
		const COMMAND_WITHHOLD_RETURN = 'withhold_return';
		const COMMAND_REJECT_RETURN = 'reject_return';
		const COMMAND_REJECT_EXCHANGE = 'reject_exchange';
		const COMMAND_WITHHOLD_EXCHANGE = 'withhold_exchange';
		const COMMAND_RELEASE_EXCHANGE_HOLD = 'release_exchange_hold';
		const COMMAND_GET_CUSTOMER_INQUIRY_LIST = 'get_customer_inquiry_list';
		const COMMAND_ANSWER_CUSTOMER_INQUIRY = 'answer_customer_inquiry';
		const COMMAND_GET_PURCHASE_REVIEW_LIST = 'get_purchase_review_list';
		const COMMAND_REGISTER_SERVICE = 'register_service';
		const COMMAND_REGISTER_ORDER = 'register_order';

		const COMMAND_DASHBOARD = 'dashboard';

		const COMMAND_BULK_PLACE_PRODUCT_ORDER = 'bulk_place_product_order';

		const COMMAND_GET_STATUS = 'get_status';
		const COMMAND_CONNECT_KEY = 'connect_key';
		const COMMAND_RESET_KEY = 'reset_key';

		public static function call( $query ) {
			$cl = curl_init();

			if ( MNP_Manager::MODE_PRODUCTION == MNP_Manager::operation_mode() ) {
				$mnp_url = 'http://npay-api.codemshop.com/';
			} else {
				$mnp_url = 'http://npay-api-test.codemshop.com/';
			}

			curl_setopt( $cl, CURLOPT_URL, $mnp_url );
			curl_setopt( $cl, CURLOPT_POST, 1 );
			curl_setopt( $cl, CURLOPT_POSTFIELDS, $query );
			curl_setopt( $cl, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $cl, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $cl, CURLOPT_RETURNTRANSFER, true );

			$result = curl_exec( $cl );

			curl_close( $cl );

			return json_decode( $result );
		}

		public static function get_command_desc( $command ) {
			$command_desc = array (
				// 발주 / 배송
				MNP_API::COMMAND_PLACE_PRODUCT_ORDER        => __( '발주', 'mshop-npay' ),
				MNP_API::COMMAND_DELAY_PRODUCT_ORDER        => __( '발송지연', 'mshop-npay' ),
				MNP_API::COMMAND_SHIP_PRODUCT_ORDER         => __( '배송', 'mshop-npay' ),
				// 주문취소
				MNP_API::COMMAND_CANCEL_SALE                => __( '주문취소', 'mshop-npay' ),
				MNP_API::COMMAND_APPROVE_CANCEL_APPLICATION => __( '취소승인', 'mshop-npay' ),
				// 반품
				MNP_API::COMMAND_REQUEST_RETURN             => __( '반품접수', 'mshop-npay' ),
				MNP_API::COMMAND_APPROVE_RETURN_APPLICATION => __( '반품승인', 'mshop-npay' ),
				MNP_API::COMMAND_WITHHOLD_RETURN            => __( '반품보류', 'mshop-npay' ),
				MNP_API::COMMAND_RELEASE_RETURN_HOLD        => __( '반품보류해제', 'mshop-npay' ),
				MNP_API::COMMAND_REJECT_RETURN              => __( '반품거절', 'mshop-npay' ),
				// 교환
				MNP_API::COMMAND_APPROVE_COLLECTED_EXCHANGE => __( '교환수거완료', 'mshop-npay' ),
				MNP_API::COMMAND_WITHHOLD_EXCHANGE          => __( '교환보류', 'mshop-npay' ),
				MNP_API::COMMAND_RELEASE_EXCHANGE_HOLD      => __( '교환보류해제', 'mshop-npay' ),
				MNP_API::COMMAND_REJECT_EXCHANGE            => __( '교환거절', 'mshop-npay' ),
				MNP_API::COMMAND_REDELIVERY_EXCHANGE        => __( '재발송', 'mshop-npay' ),

				MNP_API::COMMAND_GET_CUSTOMER_INQUIRY_LIST   => __( '상품주문조회', 'mshop-npay' ),
				MNP_API::COMMAND_ANSWER_CUSTOMER_INQUIRY     => __( '상품주문조회', 'mshop-npay' ),
				MNP_API::COMMAND_GET_PURCHASE_REVIEW_LIST    => __( '상품주문조회', 'mshop-npay' ),
				MNP_API::COMMAND_REGISTER_SERVICE            => __( '상품주문조회', 'mshop-npay' ),
				MNP_API::COMMAND_REGISTER_ORDER              => __( '상품주문조회', 'mshop-npay' ),
				MNP_API::COMMAND_GET_PURCHASE_REVIEW_LIST    => __( '상품주문조회', 'mshop-npay' ),
				MNP_API::COMMAND_GET_PRODUCT_ORDER_INFO_LIST => __( '상품주문 상세내역 조회', 'mshop-npay' ),
			);

			return $command_desc[ $command ];
		}
		public static function get_customer_inquiry_list( $inquiry_time_from, $inquiry_time_to, $is_answered ) {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command'           => self::COMMAND_GET_CUSTOMER_INQUIRY_LIST,
						'mall_id'           => MNP_Manager::merchant_id(),
						'inquiry_time_from' => $inquiry_time_from,
						'inquiry_time_to'   => $inquiry_time_to,
						'is_answered'       => $is_answered,
					)
				)
			);

			return self::call( $query );
		}
		public static function answer_customer_inquiry( $inquiry_id, $answer_content, $answer_content_id ) {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command'           => self::COMMAND_ANSWER_CUSTOMER_INQUIRY,
						'mall_id'           => MNP_Manager::merchant_id(),
						'inquiry_id'        => $inquiry_id,
						'answer_content'    => $answer_content,
						'answer_content_id' => $answer_content_id,
					)
				)
			);

			return self::call( $query );
		}
		public static function get_purchase_review_list( $inquiry_time_from, $inquiry_time_to, $purchase_review_class_type ) {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command'                    => self::COMMAND_GET_PURCHASE_REVIEW_LIST,
						'mall_id'                    => MNP_Manager::merchant_id(),
						'inquiry_time_from'          => $inquiry_time_from,
						'inquiry_time_to'            => $inquiry_time_to,
						'purchase_review_class_type' => $purchase_review_class_type
					)
				)
			);

			return self::call( $query );
		}

		public static function register_service() {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command'   => self::COMMAND_REGISTER_SERVICE,
						'mall_id'   => MNP_Manager::merchant_id(),
						'certi_key' => MNP_Manager::auth_key(),
					)
				)
			);

			return self::call( $query );
		}

		public static function register_order( $order_data, $extra_info = '' ) {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command'    => self::COMMAND_REGISTER_ORDER,
						'mall_id'    => MNP_Manager::merchant_id(),
						'order_data' => $order_data,
						'extra_info' => $extra_info
					)
				)
			);

			return self::call( $query );
		}

		public static function get_status() {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command' => self::COMMAND_GET_STATUS,
					)
				)
			);

			return self::call( $query );
		}

		public static function connect_key( $key ) {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command' => self::COMMAND_CONNECT_KEY,
						'api_key' => $key,
					)
				)
			);

			return self::call( $query );
		}

		public static function reset_key() {
			$query = http_build_query(
				array_merge( MNP_Manager::default_args(),
					array (
						'command' => self::COMMAND_RESET_KEY
					)
				)
			);

			return self::call( $query );
		}
	}
}


