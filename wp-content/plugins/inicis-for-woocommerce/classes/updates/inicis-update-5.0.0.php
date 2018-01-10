<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$prev_settings = array(
	'woocommerce_inicis_escrow_bank_settings',
	'woocommerce_inicis_stdcard_settings',
);

foreach( $prev_settings as $name) {
	$option = get_option( $name, '' );
	if( !empty( $option ) ) {
		switch($name) {
			case 'woocommerce_inicis_stdcard_settings':
				//웹표준 신용카드 마이그레이션
				update_option( 'inicis_pg_merchant_id', $option['merchant_id'] );
				update_option( 'inicis_pg_libfolder', $option['libfolder'] );
				update_option( 'inicis_pg_signkey', $option['signkey'] );

				update_option( 'inicis_pg_order_status_after_payment', $option['order_status_after_payment'] );
				update_option( 'inicis_pg_order_status_after_refund', $option['order_status_after_refund'] );
				update_option( 'inicis_pg_possible_refund_status_for_mypage', implode(',', $option['possible_refund_status_for_mypage'] ) );
				update_option( 'inicis_pg_possible_refund_status_for_admin', implode(',', $option['possible_refund_status_for_admin'] ) );

				update_option( 'inicis_pg_inicis_stdcard_title', $option['title'] );
				update_option( 'inicis_pg_inicis_stdcard_description', $option['description'] );
				update_option( 'inicis_pg_inicis_stdcard_quotabase', str_replace(':', ',', $option['quotabase']) );		//할부 구매 개월수
				update_option( 'inicis_pg_inicis_stdcard_nointerest', $option['nointerest'] );							//무이자할부 설정
				update_option( 'inicis_pg_inicis_stdcard_cardpoint', $option['cardpoint'] );							//카드포인트 결제허용
				break;
			case 'woocommerce_inicis_escrow_bank_settings':
				update_option( 'inicis_pg_escrow_merchant_id', $option['merchant_id'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_title', $option['title'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_description', $option['description'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_order_status_after_payment', $option['order_status_after_payemnt'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_order_status_after_enter_shipping_number', $option['order_status_after_enter_shipping_number'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_order_status_after_refund', $option['order_status_after_refund'] );

				update_option( 'inicis_pg_inicis_stdescrowbank_possible_refund_status_for_mypage', implode(',', $option['order_status_after_payemnt'] ) );
				update_option( 'inicis_pg_inicis_stdescrowbank_possible_check_and_reject_status_for_customer', implode(',', $option['possible_check_and_reject_status_for_customer'] ) );
				update_option( 'inicis_pg_inicis_stdescrowbank_possible_register_delivery_info_status_for_admin', implode(',', $option['possible_register_delivery_info_status_for_admin'] ) );

				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_company_name', $option['delivery_company_name'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_register_name', $option['delivery_register_name'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_sender_name', $option['delivery_sender_name'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_sender_postnum', $option['delivery_sender_postnum'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_sender_addr1', $option['delivery_sender_addr1'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_sender_addr2', $option['delivery_sender_addr2'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_delivery_sender_phone', $option['delivery_sender_phone'] );
				update_option( 'inicis_pg_inicis_stdescrowbank_receipt', $option['receipt'] );
				break;
		}
	}
}

update_option( 'inicis_pg_enabled', 'no' );