
<?php

if ( ! class_exists( 'MNP_Message_Cancel_Info' ) ) {

	class MNP_Message_Cancel_Info extends MNP_Message{

		public static function get_fields() {
			return array(
				'ClaimStatus'                       => '처리상태',
				'ClaimRequestDate'                  => '요청일',
				'RequestChannel'                    => '접수채널',
				'CancelReason'                      => '취소사유',
				'CancelDetailedReason'              => '취소 상세 사유',
				'CancelCompletedDate'               => '취소 완료일',
				'CancelApprovalDate'                => '취소 승인일',
				'HoldbackStatus'                    => '보류상태',
				'HoldbackReason'                    => '보류 상태코드',
				'HoldbackDetailedReason'            => '보류 상세사유',
				'EtcFeeDemandAmount'                => '기타 비용 청구액',
				'EtcFeePayMethod'                   => '기타 비용 결제 방법',
				'EtcFeePayMeans'                    => '기타 비용 결제 수단',
				'RefundExpectedDate'                => '환불 예정일',
				'RefundStandbyStatus'               => '환불 대기 상태',
				'RefundStandbyReason'               => '환불 대기 사유',
				'RefundRequestDate'                 => '환불 요청일'
			);
		}

		public static function get_field_value( $key, $value ){
			switch( $key ){
				case 'ClaimRequestDate' :
				case 'CancelCompletedDate' :
				case 'CancelApprovalDate' :
				case 'RefundExpectedDate' :
				case 'RefundRequestDate' :
					return self::get_field_value_date( $value );
				case 'ClaimStatus' :
					$list = self::claim_status();
					return $list[ $value ];
				case 'CancelReason' :
					$list = self::claim_request_reason();
					return $list[ $value ];
				case 'HoldbackStatus' :
					$list = self::holdback_status();
					return $list[ $value ];
				case 'HoldbackReason' :
					$list = self::holdback_reason();
					return $list[ $value ];
				default :
					return $value;
			}
		}

		public static function action_button( $ReturnInfo ){
			if( 'CANCEL_REQUEST' == $ReturnInfo->ClaimStatus ){
				echo '<input class="button button-primary button-search ApproveCancel" type="button" value="취소승인">';
			}

		}
	}
}
