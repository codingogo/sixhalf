<?php

if ( ! class_exists( 'MNP_Message_Exchange_Info' ) ) {

	class MNP_Message_Exchange_Info extends MNP_Message {

		public static function get_fields() {
			return array(
				'ClaimStatus'                       => '처리상태',
				'ClaimRequestDate'                  => '요청일',
				'RequestChannel'                    => '접수채널',
				'ExchangeReason'                    => '교환사유',
				'ExchangeDetailedReason'            => '교환상세사유',
				'HoldbackStatus'                    => '보류상태',
				'HoldbackReason'                    => '보류 상태코드',
				'HoldbackDetailedReason'            => '보류 상세사유',
				'HoldbackConfigDate'                => '보류 설정일',
				'HoldbackConfigurer'                => '보류 설정자',
				'HoldbackReleaseDate'               => '보류 해제일',
				'HoldbackReleaser'                  => '보류 해제자',
				'CollectAddress'                    => '수거지 주소',
				'ReturnReceiveAddress'              => '수취지 주소',
				'CollectStatus'                     => '수거 상태',
				'CollectDeliveryMethod'             => '수거 방법',
				'CollectDeliveryCompany'            => '수거 택배사',
				'CollectTrackingNumber'             => '수거 송장 번호',
				'CollectCompletedDate'              => '수거 완료일',
				'ReDeliveryStatus'                  => '재배송 상태',
				'ReDeliveryMethod'                  => '재배송 방법',
				'ReDeliveryCompany'                 => '재배송 택배사',
				'ReDeliveryTrackingNumber'          => '재배송 송장 번호',
				'ReDeliveryOperationDate'           => '재배송 처리일',
				'ClaimDeliveryFeeDemandAmount'      => '교환 배송비 청구액',
				'ClaimDeliveryFeeProductOrderIds'   => '교환 배송비 묶음 청구 상품 주문 번호',
				'ClaimDeliveryFeePayMethod'         => '교환 배송비 결제 방법',
				'ClaimDeliveryFeePayMeans'          => '교환 배송비 결제 수단',
				'EtcFeeDemandAmount'                => '기타 비용 청구액',
				'EtcFeePayMethod'                   => '기타 비용 결제 방법',
				'EtcFeePayMeans'                    => '기타 비용 결제 수단',
				'ClaimDeliveryFeeDiscountAmount'    => '교환 배송비 할인액',
			);
		}

		public static function get_field_value( $key, $value ){
			switch( $key ){
				case 'ClaimRequestDate':
				case 'HoldbackConfigDate':
				case 'HoldbackReleaseDate':
				case 'CollectCompletedDate':
				case 'ReDeliveryOperationDate':
					return self::get_field_value_date( $value );
				case 'ClaimStatus' :
					$list = self::claim_status();
					return $list[ $value ];
				case 'ExchangeReason' :
					$list = self::claim_request_reason();
					return $list[ $value ];
				case 'HoldbackStatus' :
					$list = self::holdback_status();
					return $list[ $value ];
				case 'HoldbackReason' :
					$list = self::exchange_holdback_reason();
					return $list[ $value ];
				case 'CollectAddress' :
				case 'ReturnReceiveAddress' :
					return self::get_field_value_address( $value );
				case 'CollectDeliveryMethod' :
					$list = self::delivery_method_for_exchange();
					return $list[ $value ];
				case 'ReDeliveryMethod' :
					$list = self::delivery_method();
					return $list[ $value ];
				case 'CollectDeliveryCompany' :
				case 'ReDeliveryCompany' :
					$list = self::delivery_company();
					return $list[ $value ];
				default :
					return $value;
			}
		}

		public static function action_button( $ExchangeInfo ){
			if( 'EXCHANGE_REQUEST' == $ExchangeInfo->ClaimStatus){
				if( 'HOLDBACK' == $ExchangeInfo->HoldbackStatus ) {
					echo '<input class="button button-primary button-search ReleaseExchangeHold" type="button" value="보류해제">';
				}else{
					echo '<input class="button button-primary button-search ApproveCollectedExchange" type="button" value="교환수거완료">';
				}
			}
		}
	}
}

