<?php

if ( ! class_exists( 'MNP_Message' ) ) {

	class MNP_Message {

		public static function get_fields(){}
		public static function get_field_value( $key, $value ){}
		public static function action_button( $ReturnInfo ){}

		public static function product_order_status(){
			return apply_filters( 'naverpay_product_order_status', array(
				'PAYMENT_WAITING'       => '입금 대기',
				'PAYED'                 => '결제 완료',
				'DELIVERING'            => '배송중',
				'DELIVERED'             => '배송 완료',
				'PURCHASE_DECIDED'      => '구매 확정',
				'EXCHANGED'             => '교환',
				'CANCELED'              => '취소',
				'RETURNED'              => '반품',
				'CANCELED_BY_NOPAYMENT' => '미입금 취소',
			));
		}

		public static function get_product_order_status_description( $ProductOrderStatus ){
			$OrderStatus = self::product_order_status();
			return isset( $OrderStatus[ $ProductOrderStatus ] ) ? $OrderStatus[ $ProductOrderStatus ] : '';
		}

		public static function delay_reason(){
			return apply_filters( 'naverpay_delay_reason', array(
				'PRODUCT_PREPARE'   => '상품 준비 중',
				'CUSTOMER_REQUEST'  => '고객 요청',
				'CUSTOM_BUILD'      => '주문 제작',
				'RESERVED_DISPATCH' => '예약 발송',
				'ETC'               => '기타',
			));
		}

		public static function delivery_method(){
			return apply_filters( 'naverpay_delivery_method', array(
				'DELIVERY'          => '택배,등기,소포',
				'GDFW_ISSUE_SVC'    => '굿스플로 송장출력',
				'VISIT_RECEIPT'     => '방문 수령',
				'DIRECT_DELIVERY'   => '직접 전달',
				'QUICK_SVC'         => '퀵서비스',
				'NOTHING'           => '배송없음',
			));
		}

		public static function delivery_method_for_exchange(){
			return apply_filters( 'naverpay_delivery_method_for_exchange', array(
				'RETURN_DESIGNATED' => '지정 반품 택배',
				'RETURN_DELIVERY'   => '일반 반품 택배',
				'RETURN_INDIVIDUAL' => '직접 반송',
			));
		}

		public static function delivery_method_for_return(){
			return apply_filters( 'naverpay_delivery_method_for_exchange', array(
				'RETURN_INDIVIDUAL' => '직접 반송',
			));
		}

		public static function delivery_company() {
			return apply_filters( 'naverpay_delivery_company', array (
				'CJGLS'      => 'CJ 대한통운',
				'KGB'        => '로젠택배',
				'DONGBU'     => 'KG 로지스',
				'EPOST'      => '우체국택배',
				'REGISTPOST' => '우편등기',
				'HANJIN'     => '한진택배',
				'HYUNDAI'    => '롯데택배',
				'KGBLS'      => 'KGB 택배',
				'INNOGIS'    => 'GTX로지스',
				'DAESIN'     => '대신택배',
				'ILYANG'     => '일양로지스',
				'KDEXP'      => '경동택배',
				'CHUNIL'     => '천일택배',
				'CH1'        => '기타택배',
				'HDEXP'      => '합동택배',
				'CVSNET'     => '편의점택배',
				'DHL'        => 'DHL',
				'FEDEX'      => 'FEDEX',
				'GSMNTON'    => 'GSMNTON',
				'WARPEX'     => 'WarpEx',
				'WIZWA'      => 'WIZWA',
				'EMS'        => 'EMS',
				'DHLDE'      => 'DHL(독일)',
				'ACIEXPRESS' => 'ACI',
				'EZUSA'      => 'EZUSA',
				'PANTOS'     => '범한판토스',
				'UPS'        => 'UPS',
				'HLCGLOBAL'  => '롯데택배(국제택배)',
				'KOREXG'     => 'CJ대한통운(국제택배)',
				'TNT'        => 'TNT',
				'SWGEXP'     => '성원글로벌',
				'DAEWOON'    => '대운글로벌',
				'USPS'       => 'USPS',
				'IPARCEL'    => 'i-parcel',
				'KUNYOUNG'   => '건영택배',
				'HPL'        => '한의사랑택배',
				'DADREAM'    => '다드림',
				'SLX'        => 'SLX 택배',
				'HONAM'      => '호남택배',
			) );
		}

		public static function cancel_reason(){
			return apply_filters( 'naverpay_message_cancel_reason', array(
				'PRODUCT_UNSATISFIED' => '서비스 및 상품 불만족',
				'DELAYED_DELIVERY'    => '배송 지연',
				'SOLD_OUT'            => '상품 품절'
			));
		}

		public static function claim_request_reason(){
			return apply_filters( 'naverpay_message_claim_request_reason', array(
				'INTENT_CHANGED'      => '구매 의사 취소',
				'COLOR_AND_SIZE'      => '색상 및 사이즈 변경',
				'WRONG_ORDER'         => '다른 상품 잘못 주문',
				'PRODUCT_UNSATISFIED' => '서비스 및 상품 불만족',
				'DELAYED_DELIVERY'    => '배송 지연',
				'SOLD_OUT'            => '상품 품절',
				'DROPPED_DELIVERY'    => '배송 누락',
				'BROKEN'              => '상품 파손',
				'INCORRECT_INFO'      => '상품 정보 상이',
				'WRONG_DELIVERY'      => '오배송',
				'WRONG_OPTION'        => '색상등이 다른 상품을 잘못 배송',
				'ETC'                 => '기타',
			));
		}

		public static function claim_request_reason_return(){
			return apply_filters( 'naverpay_message_claim_request_reason_return', array(
				'INTENT_CHANGED'      => '구매 의사 취소',
				'COLOR_AND_SIZE'      => '색상 및 사이즈 변경',
				'WRONG_ORDER'         => '다른 상품 잘못 주문',
				'PRODUCT_UNSATISFIED' => '서비스 및 상품 불만족',
				'DELAYED_DELIVERY'    => '배송 지연',
				'SOLD_OUT'            => '상품 품절',
				'DROPPED_DELIVERY'    => '배송 누락',
				'BROKEN'              => '상품 파손',
				'INCORRECT_INFO'      => '상품 정보 상이',
				'WRONG_DELIVERY'      => '오배송',
				'WRONG_OPTION'        => '색상등이 다른 상품을 잘못 배송'
			));
		}

		public static function claim_status(){
			return apply_filters( 'naverpay_return_claim_status', array(
				'CANCEL_REQUEST' => '취소 요청',
				'CANCELING'      => '취소 처리 중',
				'CANCEL_DONE'    => '취소 처리 완료',
				'CANCEL_REJECT'  => '최소 철회',
				'RETURN_REQUEST' => '반품 요청',
				'COLLECTING'     => '수거 처리 중',
				'COLLECT_DONE'   => '수거 완료',
				'RETURN_DONE'    => '반품 완료',
				'RETURN_REJECT'  => '반품 철회',
				'EXCHANGE_REQUEST'       => '교환 요청',
				'EXCHANGE_REDELIVERING'  => '교환 재배송중',
				'EXCHANGE_DONE'          => '교환 완료',
				'EXCHANGE_REJECT'        => '교환 거부',
				'PURCHASE_DECISION_HOLDBACK'              => '구매 확정 보류',
				'PURCHASE_DECISION_HOLDBACK_REDELIVERING' => '구매 확정 보류 재배송 중',
				'PURCHASE_DECISION_REQUEST'               => '구매 확정 요청',
				'PURCHASE_DECISION_HOLDBACK_RELEASE'      => '구매 확정 보류 해재',
				'ADMIN_CANCELING'   => '직권 취소 중',
				'ADMIN_CANCEL_DONE' => '직권 취소 완료',
			));
		}

		public static function holdback_status(){
			return apply_filters( 'naverpay_holdback_status', array(
				'NOT_YET'  => '미보류',
				'HOLDBACK' => '보류 중',
				'RELEASED' => '보류 해제'
			));
		}

		public static function holdback_reason(){
			return apply_filters( 'naverpay_holdback_reason', array(
				'SELLER_CONFIRM_NEED'    => '판매자 확인 필요',
				'PURCHASER_CONFIRM_NEED' => '구매자 확인 필요',
				'SELLER_REMIT'           => '판매자 직접 송금'
			));
		}

		public static function exchange_holdback_reason(){
			return apply_filters( 'naverpay_exchange_holdback_reason', array(
				'EXCHANGE_DELIVERYFEE'           => '교환 배송비 청구',
				'EXCHANGE_EXTRAFEE'              => '기타 교환 비용 청구',
				'EXCHANGE_PRODUCT_READY'         => '교환 상품 준비 중',
				'EXCHANGE_PRODUCT_NOT_DELIVERED' => '교환 상품 미입고',
				'EXCHANGE_HOLDBACK'              => '교환 구매 확정 보류',
				'ETC'                            => '기타 사유'
			));
		}

		public static function return_holdback_reason(){
			return apply_filters( 'naverpay_ereturn_holdback_reason', array(
				'RETURN_DELIVERYFEE'               => '반품 배송비 청구',
				'EXTRAFEEE'                        => '기타 반품 비용 청구',
				'RETURN_DELIVERYFEE_AND_EXTRAFEEE' => '반품 배송비 및 기타 반품 비용 청구',
				'RETURN_PRODUCT_NOT_DELIVERED'     => '반품 상품 미입고',
				'ETC'                              => '기타 사유'
			));
		}

		protected static function get_field_value_address( $address ){
			$text = array();
			$text[] = empty( $address->BaseAddress ) ? '' : $address->BaseAddress;
			$text[] = empty( $address->DetailedAddress ) ? '' : $address->DetailedAddress;
			$result = implode( ' ', $text ) . '<br>';
			$text = array();
			$text[] = empty( $address->Name ) ? '' : $address->Name;
			$text[] = empty( $address->Tel1 ) ? '' : $address->Tel1;
			$text[] = empty( $address->Tel2 ) ? '' : $address->Tel2;
			$result .= implode( ',', $text );
			return $result;
		}

		protected  static function get_field_value_date( $value ){
			return (new DateTime( $value ))->add(new DateInterval('PT9H'))->format('Y-m-d H:i:s');
		}
	}
}

