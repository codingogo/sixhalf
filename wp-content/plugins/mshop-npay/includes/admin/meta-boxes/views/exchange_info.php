<?php if( !empty( $ProductOrderInfo->ExchangeInfo ) ) : ?>
	<?php
	include_once( MNP()->plugin_path() . '/classes/message/class-naverpay-message-exchange-info.php');

	$row_span = 1;
	$show_holdback = false;
	$show_redelivery = false;

	if( empty( $ProductOrderInfo->ExchangeInfo->HoldbackStatus ) || 'NOT_YET' == $ProductOrderInfo->ExchangeInfo->HoldbackStatus ){
		$show_holdback = true;
		$row_span++;
	}

	if( 'COLLECT_DONE' == $ProductOrderInfo->ExchangeInfo->ClaimStatus ){
		$show_redelivery = true;
		$row_span++;
	}

	if( 'EXCHANGE_REQUEST' == $ProductOrderInfo->ExchangeInfo->ClaimStatus ){
		$row_span++;
	}

	?>
	<tr class="ExchangeInfo">
		<td class="naverpay-action-title" <?=( 1 == $row_span ? '' : 'rowspan="' . $row_span . '"'); ?> >교환 정보</td>
		<td>
			<table style="width: 100%">
				<?php
				$fields = MNP_Message_Exchange_Info::get_fields();
				foreach( $fields as $key => $value ){
					if( !empty( $ProductOrderInfo->ExchangeInfo->$key ) ){
						echo '<tr><td class="naverpay-action-title">' . $value . '</td>';
						echo '<td>' . MNP_Message_Exchange_Info::get_field_value( $key, $ProductOrderInfo->ExchangeInfo->$key ) . '</td></tr>';
					}
				}
				?>
			</table>
		</td>
		<td class="naverpay-action-button"><?php MNP_Message_Exchange_Info::action_button( $ProductOrderInfo->ExchangeInfo );?></td>
	</tr>
	<?php if( 'EXCHANGE_REQUEST' == $ProductOrderInfo->ExchangeInfo->ClaimStatus ) : ?>
		<tr class="ExchangeInfo">
			<td><textarea class="w12 RejectDetailContent" placeholder="교환 거부 사유를 입력하세요."></textarea></td>
			<td class="naverpay-action-button"><input class="button button-primary button-search RejectExchange" type="button" value="교환거부"></td>
		</tr>
		<?php if( $show_holdback ) : ?>
			<tr class="ExchangeInfo">
				<td>
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
							<td><input type="text" class="w12 ExchangeHoldDetailContent" type="text" placeholder="(필수)보류 상세 사유를 입력하세요."></td>
						</tr>
						<tr>
							<td class="naverpay-action-title">기타 교환 비용</td>
							<td><input type="text" class="w6 EtcFeeDemandAmount" type="text" placeholder="교환 비용을 입력하세요." value="0"></td>
						</tr>
					</table>
				</td>
				<td class="naverpay-action-button"><input class="button button-primary button-search WithholdExchange" type="button" value="교환보류"></td>
			</tr>
		<?php endif ?>
	<?php endif ?>

	<?php if( $show_redelivery ) : ?>
		<tr class="ExchangeInfo">
			<td>
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
			</td>
			<td class="naverpay-action-button"><input class="button button-primary button-search ReDeliveryExchange" type="button" value="재발송"></td>
		</tr>
	<?php endif ?>
<?php endif ?>
