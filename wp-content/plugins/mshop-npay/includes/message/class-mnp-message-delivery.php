<?php

if ( ! class_exists( 'MNP_Message_Delivery' ) ) {

	class MNP_Message_Delivery extends MNP_Message{

		public static function get_fields() {
			return array(
				'DeliveryStatus'                    => '배송상태',
				'DeliveryMethod'                    => '배송방법',
				'DeliveryCompany'                   => '택배사',
				'TrackingNumber'                    => '송장 번호',
				'SendDate'                          => '발송 일시',
				'PickupDate'                        => '집화 일시',
				'DeliveredDate'                     => '배송 완료 일시',
				'IsWrongTrackingNumber'             => '오류 송장 여부',
				'WrongTrackingNumberRegisteredDate' => '오류 송장 등록 일시',
				'WrongTrackingNumberType'           => '오류 사유'
			);
		}

		public static function get_field_value( $key, $value ){
			switch( $key ){
				case 'SendDate' :
				case 'PickupDate' :
				case 'DeliveredDate' :
					return self::get_field_value_date( $value );
				case 'DeliveryMethod' :
					$list = self::delivery_method();
					return $list[ $value ];
				case 'DeliveryCompany' :
					$list = self::delivery_company();
					return $list[ $value ];
				default :
					return $value;
			}
		}

		public static function action_button( $ReturnInfo ){
		}
	}
}


