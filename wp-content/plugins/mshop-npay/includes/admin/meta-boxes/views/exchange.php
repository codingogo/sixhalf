<div class="mnp-order-data-row mnp-order-approve-collected-exchange-items" style="display: none;">
	<h4>선택한 주문건에 대해 교환 수거 완료를 진행합니다.</h4>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-approve-collected-exchange">교환수거완료</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-withhold-exchange-items" style="display: none;">
	<h4>교환 진행 중인 주문을 교환 보류 처리합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">보류 사유</td>
			<td>
				<select class="ExchangeHoldCode">
					<?php
					$exchange_holdback_reason = MNP_Message::exchange_holdback_reason();
					foreach ($exchange_holdback_reason as $key => $value) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">상세 사유</td>
			<td><textarea class="w6 ExchangeHoldDetailContent" placeholder="(필수)보류 상세 사유를 입력하세요."></textarea></td>
		</tr>
		<tr>
			<td class="naverpay-action-title">기타 교환 비용</td>
			<td><input type="text" class="w6 EtcFeeDemandAmount" type="text" placeholder="교환 비용을 입력하세요." value="0"></td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-withhold-exchange">교환보류</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-release-exchange-hold-items" style="display: none;">
	<h4>교환 보류 중인 주문의 교환 보류를 해제합니다.</h4>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-release-exchange-hold">보류해제</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-reject-exchange-items" style="display: none;">
	<h4>교환 진행 중인 주문을 교환 거부 처리합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">교환 거부 사유</td>
			<td><textarea class="w12 RejectDetailContent" placeholder="(필수) 교환 거부 사유를 입력하세요."></textarea></td>
		</tr>

	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-reject-exchange">교환거절</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-redelivery-exchange-items" style="display: none;">
	<h4>교환 승인된 상품 주문을 재발송 처리합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">배송방법</td>
			<td>
				<select class="ReDeliveryMethodCode">
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
				<select class="ReDeliveryCompanyCode">
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
			<td><input type="text" class="w6 ReDeliveryTrackingNumber" type="text" placeholder="송장번호를 입력하세요."></td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-redelivery-exchange">상품재발송</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>