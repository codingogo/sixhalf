<?php
?>

<?php $delay_reason = MNP_Message::delay_reason(); ?>
<table class="mnp-product-order-info" cellpadding="0" cellspacing="0">
	<tr>
		<td class="title">총 상품금액</td>
		<td><?= wc_price( $npay_product_order->TotalProductAmount ) ?></td>
	</tr>
	<?php if ( ! empty( $npay_product_order->ProductDiscountAmount ) ) : ?>
		<tr>
			<td class="title">상품별 할인액</td>
			<td><?= wc_price( $npay_product_order->ProductDiscountAmount ) ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( ! empty( $npay_product_order->TotalPaymentAmount ) ) : ?>
		<tr>
			<td class="title">총 결제금액</td>
			<td><?= wc_price( $npay_product_order->TotalPaymentAmount ) ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( ! empty( $npay_product_order->DeliveryDiscountAmount ) ) : ?>
		<tr>
			<td class="title">배송비 할인액</td>
			<td><?= wc_price( $npay_product_order->DeliveryDiscountAmount ) ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( ! empty( $npay_product_order->SellerBurdenDiscountAmount ) ) : ?>
		<tr>
			<td class="title">판매자 부담 할인액</td>
			<td><?= wc_price( $npay_product_order->SellerBurdenDiscountAmount ) ?></td>
		</tr>
	<?php endif; ?>
</table>
