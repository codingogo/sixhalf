<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php if ( ! empty( $order_info ) ) : ?>
	<div class="woocommerce_order_items_wrapper">
		<table cellpadding="0" cellspacing="0" class="woocommerce_order_items mnp-order-info">
			<tr class="item">
				<td class="mnp-order-info-title">주문번호</td>
				<td class="mnp-order-info-text"><span><?php echo $order_info->OrderID; ?></span></td>
			</tr>
			<?php if ( ! empty( $order_info->GeneralPaymentAmount ) ) : ?>
				<tr class="item">
					<td class="mnp-order-info-title">결제금액</td>
					<td class="mnp-order-info-text"><?php echo wc_price( $order_info->GeneralPaymentAmount ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( ! empty( $order_info->NaverMileagePaymentAmount ) ) : ?>
				<tr class="item">
					<td class="mnp-order-info-title">NPay 포인트</td>
					<td class="mnp-order-info-text"><?php echo wc_price( $order_info->NaverMileagePaymentAmount ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( ! empty( $order_info->OrderDiscountAmount ) ) : ?>
				<tr class="item">
					<td class="mnp-order-info-title">주문할인액</td>
					<td class="mnp-order-info-text"><?php echo wc_price( $order_info->OrderDiscountAmount ); ?></td>
				</tr>
			<?php endif; ?>
			<tr class="item">
				<td class="mnp-order-info-title">결제수단</td>
				<td class="mnp-order-info-text"><?php echo $order_info->PaymentMeans; ?></td>
			</tr>
		</table>
	</div>

<?php endif ?>


