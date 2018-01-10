<div class="mnp-order-data-row mnp-order-approve-return-application-items" style="display: none;">
	<h4>선택한 주문건에 대해 반품 승인 및 환불처리를 진행합니다.</h4>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-approve-return">반품승인</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-withhold-return-items" style="display: none;">
	<h4>반품 진행중인 주문을 반품 보류 처리합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">보류 사유</td>
			<td>
				<select class="ReturnHoldCode">
					<?php
					$return_holdback_reason = MNP_Message::return_holdback_reason();
					foreach ( $return_holdback_reason as $key => $value ) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">상세 사유</td>
			<td><textarea class="w6 ReturnHoldDetailContent" placeholder="(필수)반품 상세 사유를 입력하세요."></textarea></td>
		</tr>
		<tr>
			<td class="naverpay-action-title">기타 반품 비용</td>
			<td><input type="text" class="w6 EtcFeeDemandAmount" type="text" placeholder="반품 비용을 입력하세요." value="0"></td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-withhold-return">반품보류</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-release-return-hold-items" style="display: none;">
	<h4>반품 보류중인 주문의 반품 보류를 해제합니다.</h4>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-release-return-hold">보류해제</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-reject-return-items" style="display: none;">
	<h4>선택한 주문건에 대해 반품 불가사유를 구매자에게 알리고, 반품처리를 철회합니다</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">반품 거절 사유</td>
			<td><textarea class="w12 RejectDetailContent" placeholder="(필수) 반품 거절 사유를 입력하세요."></textarea></td>
		</tr>

	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-reject-return">반품철회</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<div class="mnp-order-data-row mnp-order-request-return-items" style="display: none;">
	<h4>선택한 주문건에 대한 반품을 접수합니다.</h4>
	<table style="width: 100%">
		<tr>
			<td class="naverpay-action-title">반품 사유</td>
			<td>
				<select class="ReturnReasonCode">
					<?php
					$claim_request_reason = MNP_Message::claim_request_reason_return();
					foreach ( $claim_request_reason as $key => $value ) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">수거 배송 방법</td>
			<td>
				<select class="CollectDeliveryMethodCode">
					<?php
					$delivery_method = MNP_Message::delivery_method_for_return();
					foreach ( $delivery_method as $key => $value ) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">수거 택배사</td>
			<td>
				<select class="CollectDeliveryCompanyCode">
					<?php
					$delivery_company = MNP_Message::delivery_company();
					foreach ( $delivery_company as $key => $value ) {
						echo '<option value="' . $key . '">' . $value . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="naverpay-action-title">송장번호</td>
			<td><input type="text" class="w6 CollectTrackingNumber" type="text" placeholder="송장번호를 입력하세요."></td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="place-order-actions">
		<button type="button" class="button button-primary do-request-return">반품접수</button>
		<button type="button" class="button cancel-action"><?php _e( '취소', 'mshop-npay' ); ?></button>
		<div class="clear"></div>
	</div>
</div>