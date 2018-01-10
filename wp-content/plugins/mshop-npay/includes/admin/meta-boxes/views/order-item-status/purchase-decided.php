<?php
?>

<table class="mnp-order-info" cellpadding="0" cellspacing="0">
	<tr>
		<td class="title">발주처리일시</td>
		<td><?php echo ( new DateTime( $npay_product_order->PlaceOrderDate ) )->add( new DateInterval( 'PT9H' ) )->format( 'Y-m-d H:i:s' ); ?></td>
	</tr>
	<tr>
		<td class="title">구매확정일시</td>
		<td><?php echo ( new DateTime( $npay_product_order->DecisionDate ) )->add( new DateInterval( 'PT9H' ) )->format( 'Y-m-d H:i:s' ); ?></td>
	</tr>
</table>
