<?php
?>

<table class="mnp-order-info" cellpadding="0" cellspacing="0">
	<tr>
		<td class="title">발주처리일</td>
		<td><?php echo ( new DateTime( $npay_product_order->PlaceOrderDate ) )->add( new DateInterval( 'PT9H' ) )->format( 'Y-m-d H:i:s' ); ?></td>
	</tr>
</table>
