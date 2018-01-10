<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
//
//$item_classes = array(
//	$npay_product_order->ProductOrderStatus,
//	'place-product-order',
//	'delay-product-order',
//	'ship-product-order',
//	'cancel-sale',
//	'approve-cancel-application',
//	'approve-return-application',
//	'request-return',
//	'withhold-return',
//	'release-return-hold',
//	'reject-return',
//	'approve-collected-exchange',
//	'withhold-exchange',
//	'release-exchange-hold',
//	'reject-exchange',
//	'redelivery-exchange',
//
//);
//
//if( MNP_Manager::PLACE_ORDER_STATUS_NOT_YET != $npay_product_order->PlaceOrderStatus ) {
//	$item_classes = array_diff( $item_classes, array('place-product-order') );
//}
//
//if( ! empty( $npay_order->ProductOrder->DelayedDispatchReason ) ) {
//	$item_classes = array_diff( $item_classes, array('delay-product-order') );
//}
//
//if( ! empty( $npay_order->Delivery ) ) {
//	$item_classes = array_diff( $item_classes, array('cancel-sale', 'ship-product-order', 'delay-product-order') );
//}else{
//	$item_classes = array_diff( $item_classes, array('request-return') );
//}
//
//if( MNP_Manager::PRODUCT_ORDER_STATUS_PAYED != $npay_product_order->ProductOrderStatus ){
//	$item_classes = array_diff( $item_classes, array('cancel-sale') );
//}
//
//if( empty($npay_order->CancelInfo) || MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST != $npay_order->CancelInfo->ClaimStatus){
//	$item_classes = array_diff( $item_classes, array( 'approve-cancel-application' ) );
//}
//
//if( !empty($npay_order->CancelInfo) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST == $npay_order->CancelInfo->ClaimStatus){
//	$item_classes = array_diff( $item_classes, array( 'cancel-sale', 'delay-product-order' ) );
//}
//
//if( empty( $npay_order->ReturnInfo ) || in_array( $npay_order->ReturnInfo->ClaimStatus, array( MNP_Manager::CLAIM_STATUS_RETURN_RETURN_DONE, MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REJECT ) )){
//	$item_classes = array_diff( $item_classes, array( 'approve-return-application', 'withhold-return', 'release-return-hold', 'reject-return', ) );
//}else{
//	$item_classes = array_diff( $item_classes, array('request-return') );
//	if( empty( $npay_order->ReturnInfo->HoldbackStatus ) || 'RELEASED' == $npay_order->ReturnInfo->HoldbackStatus ){
//		$item_classes = array_diff( $item_classes, array( 'release-return-hold' ) );
//	}else if( 'HOLDBACK' == $npay_order->ReturnInfo->HoldbackStatus ){
//		$item_classes = array_diff( $item_classes, array( 'approve-return-application', 'withhold-return', 'reject-return' ) );
//	}
//}
//
//if( empty( $npay_order->ExchangeInfo ) || in_array( $npay_order->ExchangeInfo->ClaimStatus, array( MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_DONE, MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT ) )){
//	$item_classes = array_diff( $item_classes, array( 'approve-collected-exchange', 'withhold-exchange', 'release-exchange-hold', 'reject-exchange', 'redelivery-exchange') );
//}else{
//	if( empty( $npay_order->ExchangeInfo->HoldbackStatus ) || 'RELEASED' == $npay_order->ExchangeInfo->HoldbackStatus ){
//		$item_classes = array_diff( $item_classes, array( 'release-exchange-hold' ) );
//	}else if( 'HOLDBACK' == $npay_order->ExchangeInfo->HoldbackStatus ){
//		$item_classes = array_diff( $item_classes, array( 'approve-collected-exchange', 'withhold-exchange', 'reject-exchange', 'redelivery-exchange' ) );
//	}
//	if( MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECT_DONE == $npay_order->ExchangeInfo->ClaimStatus ){
//		$item_classes = array_diff( $item_classes, array( 'approve-collected-exchange', 'withhold-exchange', 'release-exchange-hold', 'reject-exchange' ) );
//	}else{
//		$item_classes = array_diff( $item_classes, array( 'redelivery-exchange' ) );
//	}
//}
//
//if( !empty( $npay_order->ExchangeInfo ) && !in_array( $npay_order->ExchangeInfo->ClaimStatus, array( MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT ) ) ){
//	$item_classes = array_diff( $item_classes, array('request-return') );
//}
//
//if( !in_array( $npay_product_order->ProductOrderStatus, array( MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERING, MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERED )) ){
//	$item_classes = array_diff( $item_classes, array('request-return') );
//}
//
//// 입금대기 상태에서는 주문관련처리가 불가능함
//if( MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING == $npay_product_order->ProductOrderStatus ){
//	$item_classes = array();
//}
//// 교환재배송중 상태에서는 주문관련처리가 불가능함
//if( !empty( $npay_order->ExchangeInfo) && MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REDELIVERING == $npay_order->ExchangeInfo->ClaimStatus ){
//	$item_classes = array();
//}
//
//if( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $npay_product_order->ProductOrderStatus ){
//	$item_classes = array();
//}

$item_classes = MNP_Order_Item::get_classes( $npay_order, $npay_product_order );

$mnp_order_status_template = array('order-item-status/product-order-info.php');

if( MNP_Manager::PRODUCT_ORDER_STATUS_PURCHASE_DECIDED == $npay_product_order->ProductOrderStatus ){
	$mnp_order_status_template[] = 'order-item-status/purchase-decided.php';
	$mnp_order_status_template[] = 'order-item-status/ship-product-order.php';
	$status = __( "구매확정", 'mshop-npay' );
}else if( MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING == $npay_product_order->ProductOrderStatus ){
	$status = __( "입금대기", 'mshop-npay' );
}else if( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $npay_product_order->ProductOrderStatus ){
	$mnp_order_status_template[] = 'order-item-status/cancelled.php';
	$status = __( "취소완료", 'mshop-npay' );
}else if( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED_BY_NOPAYMENT == $npay_product_order->ProductOrderStatus ){
	$mnp_order_status_template[] = 'order-item-status/cancelled.php';
	$status = __( "미입금 취소", 'mshop-npay' );
}else if( MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED == $npay_product_order->ProductOrderStatus ){
	$mnp_order_status_template[] = 'order-item-status/return.php';
	$status = __( "반품환불완료", 'mshop-npay' );
}else if( MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED == $npay_product_order->ProductOrderStatus ){
	$mnp_order_status_template = array('order-item-status/exchange.php');
	$status = __( "교환 완료", 'mshop-npay' );
}else{
	if( !empty( $npay_order->ReturnInfo) && in_array( $npay_order->ReturnInfo->ClaimStatus, array( MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REQUEST, MNP_Manager::CLAIM_STATUS_RETURN_COLLECTING ) ) ){
		$status = __( "반품요청", 'mshop-npay' );
		$mnp_order_status_template[] = 'order-item-status/return.php';
		if( ! empty( $npay_order->ReturnInfo->HoldbackStatus ) && 'HOLDBACK' == $npay_order->ReturnInfo->HoldbackStatus){
			$status .= ' ' . __( "(환불보류)", 'mshop-npay' );
		}
	}else if( !empty( $npay_order->Delivery ) ){
		$mnp_order_status_template[] = 'order-item-status/ship-product-order.php';
		$status = __( "배송중", 'mshop-npay' );
	}else if( !empty( $npay_order->ProductOrder->DelayedDispatchReason) ){
		$mnp_order_status_template[] = 'order-item-status/delay-product-order.php';
		$due_date = (new DateTime( $npay_order->ProductOrder->ShippingDueDate ))->add(new DateInterval('PT9H'))->format('m/d');
		$status = sprintf( __( "배송 준비중 (%s까지 발송예정)", 'mshop-npay' ), $due_date );
	}else if( MNP_Manager::PLACE_ORDER_STATUS_OK == $npay_product_order->PlaceOrderStatus ){
		$mnp_order_status_template[] = 'order-item-status/place-product-order.php';
		$status = __( "배송 준비중", 'mshop-npay' );
	}else if( MNP_Manager::ORDER_STATUS_PAYED == $npay_product_order->ProductOrderStatus){
		$status = __( "결제 완료", 'mshop-npay' );
	}

	if( ! empty( $npay_order->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST == $npay_order->CancelInfo->ClaimStatus){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/cancelled.php');
		$status = __( "취소 요청", 'mshop-npay' );
	}else if( ! empty( $npay_order->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCELING == $npay_order->CancelInfo->ClaimStatus){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/cancelled.php');
		$status = __( "취소 처리중", 'mshop-npay' );
	}

	if( !empty( $npay_order->ExchangeInfo) && in_array( $npay_order->ExchangeInfo->ClaimStatus, array( MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REQUEST, MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECTING ) ) ){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/exchange.php');
		$status = __( "교환요청", 'mshop-npay' );
		if( 'HOLDBACK' == $npay_order->ExchangeInfo->HoldbackStatus){
			$status .= ' ' . __( "(교환보류)", 'mshop-npay' );
		}
	}else if( !empty( $npay_order->ExchangeInfo) && MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECT_DONE == $npay_order->ExchangeInfo->ClaimStatus ){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/exchange.php');
		$status = __( "교환수거완료", 'mshop-npay' );
	}else if( !empty( $npay_order->ExchangeInfo) && MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REDELIVERING == $npay_order->ExchangeInfo->ClaimStatus ){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/exchange.php');
		$status = __( "교환재배송 중", 'mshop-npay' );
	}

	if( ! empty( $npay_order->ProductOrder->ClaimType ) && $npay_order->ProductOrder->ClaimType == MNP_Manager::CLAIM_TYPE_EXCHANGE &&
	    $npay_order->ProductOrder->ClaimStatus == MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT ){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/ship-product-order.php', 'order-item-status/exchange.php');
		$status .= ' ' . __( "(교환거절)", 'mshop-npay' );
	}

	if( ! empty( $npay_order->ProductOrder->ClaimType ) && $npay_order->ProductOrder->ClaimType == MNP_Manager::CLAIM_TYPE_RETURN &&
	    $npay_order->ProductOrder->ClaimStatus == MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REJECT ){
		$mnp_order_status_template = array('order-item-status/product-order-info.php', 'order-item-status/ship-product-order.php', 'order-item-status/return.php');
		$status .= ' ' . __( "(반품거절)", 'mshop-npay' );
	}

}

?>
<div class="npay-item <?php echo implode(' ', $item_classes ); ?>" data-product_order_id="<?php echo $npay_product_order->ProductOrderID; ?>">
	<div>
		<div class="npay-logo"></div>
		<p class="product_order_id"><?php echo sprintf( __('주문번호 : %s', 'mshop-npay' ), $npay_product_order->ProductOrderID ); ?></p>
	</div>

	<div>
		<div class="npay-logo"></div>
		<p class="product_order_status"><?php echo sprintf( __('주문상태 : %s', 'mshop-npay' ), $status ); ?>
		<?php if( count( $mnp_order_status_template ) > 0 ) : ?>
			<img src="<?php echo MNP()->plugin_url() . '/assets/images/detail-info.png'; ?>" class="nmp-order-detail-info" style="width:20px;height:20px;vertical-align: middle;">
		<?php endif ?>
		</p>
	</div>

	<?php if( property_exists( $npay_product_order, 'IndividualCustomUniqueCode' ) ) : ?>
		<div>
			<div class="npay-logo"></div>
			<p class="product_order_id"><?php echo sprintf( __('개인통관 고유부호 : %s', 'mshop-npay' ), $npay_product_order->IndividualCustomUniqueCode ); ?></p>
		</div>
	<?php endif; ?>
</div>
