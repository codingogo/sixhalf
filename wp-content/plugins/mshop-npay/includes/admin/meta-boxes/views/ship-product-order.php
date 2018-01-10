<div class="mnp-order-data-row mnp-order-ship-product-order-items" style="display: none;">
	<h4>선택한 주문건에 대해 발송처리를 진행합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">배송방법</td>
			<td>
				<select class="DeliveryMethodCode">
					<?php
					$delivery_method = MNP_Message::delivery_method();
					foreach ($delivery_method as $key => $value) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">택배사</td>
			<td>
				<select class="DeliveryCompanyCode">
					<?php
					$delivery_company = MNP_Message::delivery_company();
					foreach ($delivery_company as $key => $value) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">송장번호</td>
			<td><input type="text" class="w6 TrackingNumber" type="text" placeholder="송장번호를 입력하세요."></td>
		</tr>
		<tr>
			<td class="naverpay-action-title">배송일</td>
			<td><input type="text" class="w6 datepicker DispatchDate" type="text"
			           value="<?php echo date('Y-m-d'); ?>"></td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-ship-product-order">배송 처리 </button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>
