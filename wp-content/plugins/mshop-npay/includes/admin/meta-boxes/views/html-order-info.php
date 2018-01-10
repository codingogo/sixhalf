<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div>
	<button class="button button-primary refresh-npay-order" style="float:right; margin-bottom: 8px;">주문정보 새로고침</button>
</div>
<?php
if( count( $ProductOrderInfoList ) > 0 ){
	$OrderInfo = $ProductOrderInfoList[0]->Order;
}else{
	return;
}

?>

<?php if( !empty( $OrderInfo ) ) : ?>
<div class="woocommerce_order_items_wrapper">
	<table cellpadding="0" cellspacing="0" class="woocommerce_order_items mnp-order-info">
		<tr class="item">
			<td class="mnp-order-info-title">주문번호</td>
			<td class="mnp-order-info-text"><span><?php echo $OrderInfo->OrderID;?></span></td>
		</tr>
		<?php if( !empty( $OrderInfo->GeneralPaymentAmount ) ) : ?>
		<tr class="item">
			<td class="mnp-order-info-title">결제금액</td>
			<td class="mnp-order-info-text"><?php echo wc_price( $OrderInfo->GeneralPaymentAmount );?></td>
		</tr>
		<?php endif; ?>
		<?php if ( !empty( $OrderInfo->NaverMileagePaymentAmount ) ) : ?>
		<tr class="item">
			<td class="mnp-order-info-title">NPay 포인트</td>
			<td class="mnp-order-info-text"><?php echo wc_price( $OrderInfo->NaverMileagePaymentAmount );?></td>
		</tr>
		<?php endif; ?>
		<?php if( !empty( $OrderInfo->OrderDiscountAmount ) ) : ?>
		<tr class="item">
			<td class="mnp-order-info-title">주문할인액</td>
			<td class="mnp-order-info-text"><?php echo wc_price( $OrderInfo->OrderDiscountAmount ); ?></td>
		</tr>
		<?php endif; ?>
		<tr class="item">
			<td class="mnp-order-info-title">결제수단</td>
			<td class="mnp-order-info-text"><?php echo $OrderInfo->PaymentMeans;?></td>
		</tr>
	</table>
</div>

<?php endif ?>