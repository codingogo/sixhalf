<div class="mnp-order-data-row mnp-order-delay-product-order-items" style="display: none;">
	<h4>선택한 주문건에 대해 발송지연 안내를 진행합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">기한</td>
			<td><input type="text" class="w3 datepicker DispatchDueDate" type="text"
			           value="<?php echo date('Y-m-d', strtotime('+1 days')); ?>"></td>
		</tr>
		<tr>
			<td class="naverpay-action-title">지연사유</td>
			<td>
				<select class="DispatchDelayReasonCode" style="width:auto;">
					<?php
					$delay_reason = MNP_Message::delay_reason();
					foreach ($delay_reason as $key => $value) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">상세사유</td>
			<td><textarea class="w12 DispatchDelayDetailReason" placeholder="발송 지연 상세 사유를 입력하세요."></textarea></td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-delay-product-order">발송 지연 처리 </button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>
