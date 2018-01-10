<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
	<div class="woocommerce_order_items_wrapper mnp-order-items-editable">
		<table cellpadding="0" cellspacing="0" class="woocommerce_order_items mnp-order-items">
			<thead>
			<tr>
				<th><input type="checkbox" class="check-column"/></th>
				<th><?php _e( '상품주문번호', 'woocommerce' ); ?></th>
				<th class="item sortable" colspan="2" data-sort="string-ins"><?php _e( '상품정보', 'woocommerce' ); ?></th>
				<th class="cost"><?php _e( '가격', 'woocommerce' ); ?></th>
				<th class="qty"><?php _e( '수량', 'woocommerce' ); ?></th>
				<th><?php _e( '주문상태', 'woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody id="order_line_items">
			<?php
			foreach ( $ProductOrderInfoList as $ProductOrderInfo ) {
				$ProductOrderInfo          = json_decode( json_encode( $ProductOrderInfo ) );
				$mnp_order_status_template = array ();
				$ProductOrder              = $ProductOrderInfo->ProductOrder;
				$_product                  = wc_get_product( $ProductOrder->SellerProductCode );

				include( 'html-order-item.php' );
				include( 'html-order-item-status.php' );
			}
			?>
			</tbody>
		</table>
	</div>

	<div class="mnp-order-data-row mnp-order-bulk-actions">
		<table>
			<tr>
				<td>발주/배송</td>
				<td>
					<button type="button" disabled='disabled' class="button place-order-items"
					        data-action-panel="mnp-order-place-order-items"><?php _e( '발주', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button delay-product-order-items"
					        data-action-panel="mnp-order-delay-product-order-items"><?php _e( '발송지연', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button ship-product-order-items"
					        data-action-panel="mnp-order-ship-product-order-items"><?php _e( '배송', 'woocommerce' ); ?></button>
				</td>
			</tr>
			<tr>
				<td>주문취소</td>
				<td>
					<button type="button" disabled='disabled' class="button cancel-sale-items"
					        data-action-panel="mnp-order-cancel-sale-items"><?php _e( '주문취소', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button approve-cancel-application-items"
					        data-action-panel="mnp-order-approve-cancel-application-items"><?php _e( '취소승인', 'woocommerce' ); ?></button>
				</td>
			</tr>
			<tr>
				<td>반품</td>
				<td>
					<button type="button" disabled='disabled' class="button request-return-items"
					        data-action-panel="mnp-order-request-return-items"><?php _e( '반품접수', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button approve-return-application-items"
					        data-action-panel="mnp-order-approve-return-application-items"><?php _e( '반품승인', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button withhold-return-items"
					        data-action-panel="mnp-order-withhold-return-items"><?php _e( '반품보류', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button release-return-hold-items"
					        data-action-panel="mnp-order-release-return-hold-items"><?php _e( '반품보류해제', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button reject-return-items"
					        data-action-panel="mnp-order-reject-return-items"><?php _e( '반품거절', 'woocommerce' ); ?></button>
				</td>
			</tr>
			<tr>
				<td>교환</td>
				<td>
					<button type="button" disabled='disabled' class="button approve-collected-exchange-items"
					        data-action-panel="mnp-order-approve-collected-exchange-items"><?php _e( '교환수거완료', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button withhold-exchange-items"
					        data-action-panel="mnp-order-withhold-exchange-items"><?php _e( '교환보류', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button release-exchange-hold-items"
					        data-action-panel="mnp-order-release-exchange-hold-items"><?php _e( '교환보류해제', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button reject-exchange-items"
					        data-action-panel="mnp-order-reject-exchange-items"><?php _e( '교환거절', 'woocommerce' ); ?></button>
					<button type="button" disabled='disabled' class="button redelivery-exchange-items"
					        data-action-panel="mnp-order-redelivery-exchange-items"><?php _e( '재발송', 'woocommerce' ); ?></button>
				</td>
			</tr>
		</table>
	</div>

<?php
include( 'place-product-order.php' );
include( 'delay-product-order.php' );
include( 'ship-product-order.php' );
include( 'cancel-sale.php' );
include( 'approve-cancel-application.php' );
include( 'return.php' );
include( 'exchange.php' );
