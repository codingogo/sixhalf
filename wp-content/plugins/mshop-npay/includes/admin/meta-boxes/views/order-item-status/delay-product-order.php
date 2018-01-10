<?php
?>

<?php $delay_reason = MNP_Message::delay_reason(); ?>
<table class="mnp-order-info" cellpadding="0" cellspacing="0">
	<tr>
		<td class="title">지연사유</td>
		<td><?= $delay_reason[ $npay_order->ProductOrder->DelayedDispatchReason ] ?></td>
	</tr>
	<tr>
		<td class="title">상세사유</td>
		<td><?= $npay_order->ProductOrder->DelayedDispatchDetailedReason ?></td>
	</tr>
	<tr>
		<td class="title">발송기한</td>
		<td><?php echo ( new DateTime( $npay_order->ProductOrder->ShippingDueDate ) )->add( new DateInterval( 'PT9H' ) )->format( 'Y-m-d' ); ?></td>
	</tr>
</table>
