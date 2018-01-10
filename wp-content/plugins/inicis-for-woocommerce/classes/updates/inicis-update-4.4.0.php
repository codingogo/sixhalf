<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$data = array(
	'txnid' => '_txnid',
	'ini_rn' => '_ini_rn',
	'ini_enctype' => '_ini_enctype',
	'inicis_paymethod' => '_inicis_paymethod',
	'inicis_paymethod_tid' => '_inicis_paymethod_tid',
	'inicis_paymethod_resultcode' => '_inicis_paymethod_resultcode',
	'inicis_paymethod_resultmsg' => '_inicis_paymethod_resultmsg',
	'inicis_paymethod_resultmoid' => '_inicis_paymethod_resultmoid',
	'inicis_paymethod_totprice' => '_inicis_paymethod_totprice',
	'inicis_paymethod_appldate' => '_inicis_paymethod_appldate',
	'inicis_paymethod_appltime' => '_inicis_paymethod_appltime',
	'VACT_Num' => '_VACT_Num',
	'VACT_BankCode' => '_VACT_BankCode',
	'VACT_BankCodeName' => '_VACT_BankCodeName',
	'VACT_Name' => '_VACT_Name',
	'VACT_InputName' => '_VACT_InputName',
	'VACT_Date' => '_VACT_Date',
	'inicis_paymethod_card_num' => '_inicis_paymethod_card_num',
	'inicis_paymethod_card_applnum' => '_inicis_paymethod_card_applnum',
	'inicis_paymethod_card_qouta' => '_inicis_paymethod_card_qouta',
	'inicis_paymethod_card_interest' => '_inicis_paymethod_card_interest',
	'inicis_paymethod_card_code' => '_inicis_paymethod_card_code',
	'inicis_paymethod_card_name' => '_inicis_paymethod_card_name',
	'inicis_paymethod_card_bankcode' => '_inicis_paymethod_card_bankcode',
	'inicis_paymethod_card_authtype' => '_inicis_paymethod_card_authtype',
	'inicis_paymethod_card_eventcode' => '_inicis_paymethod_card_eventcode',
	'inicis_paymethod_card_point' => '_inicis_paymethod_card_point',
	'inicis_vbank_noti_received' => '_inicis_vbank_noti_received',
	'inicis_vbank_noti_received_tid' => '_inicis_vbank_noti_received_tid',
	'codem_inicis_order_cancelled' => '_codem_inicis_order_cancelled',
	'shipping_number' => '_inicis_escrow_shipping_number',
	'inicis_paymethod_escrow_delivery_add' => '_inicis_paymethod_escrow_delivery_add',
	'inicis_escrow_order_confirm' => '_inicis_escrow_order_confirm',
	'inicis_escrow_order_confirm_reject' => '_inicis_escrow_order_confirm_reject',
	'inicis_escrow_order_cancelled' => '_inicis_escrow_order_cancelled',

);

foreach( $data as $item => $new_item) {

	$wpdb->update(
		$wpdb->postmeta,
		array(
			'meta_key'	=> $new_item,
		),
		array(
			'meta_key'	=> $item,
		)
	);

}
