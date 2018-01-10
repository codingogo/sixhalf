<div class="mnp-order-data-row mnp-order-cancel-sale-items" style="display: none;">
	<h4>선택한 주문건에 대해 취소처리를 진행합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">취소사유</td>
			<td>
				<select class="CancelReasonCode">
					<?php
					$cancel_reason = MNP_Message::cancel_reason();
					foreach ($cancel_reason as $key => $value) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-cancel-sale">취소처리</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>
