<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$item_classes = array (
	$ProductOrder->ProductOrderStatus,
	'place-product-order',
	'delay-product-order',
	'ship-product-order',
	'cancel-sale',
	'approve-cancel-application',
	'approve-return-application',
	'request-return',
	'withhold-return',
	'release-return-hold',
	'reject-return',
	'approve-collected-exchange',
	'withhold-exchange',
	'release-exchange-hold',
	'reject-exchange',
	'redelivery-exchange',

);

if ( MNP_Manager::PLACE_ORDER_STATUS_NOT_YET != $ProductOrder->PlaceOrderStatus ) {
	$item_classes = array_diff( $item_classes, array ( 'place-product-order' ) );
}

if ( ! empty( $ProductOrderInfo->ProductOrder->DelayedDispatchReason ) ) {
	$item_classes = array_diff( $item_classes, array ( 'delay-product-order' ) );
}

if ( ! empty( $ProductOrderInfo->Delivery ) ) {
	$item_classes = array_diff( $item_classes, array ( 'cancel-sale', 'ship-product-order', 'delay-product-order' ) );
} else {
	$item_classes = array_diff( $item_classes, array ( 'request-return' ) );
}

if ( MNP_Manager::PRODUCT_ORDER_STATUS_PAYED != $ProductOrder->ProductOrderStatus ) {
	$item_classes = array_diff( $item_classes, array ( 'cancel-sale' ) );
}

if ( empty( $ProductOrderInfo->CancelInfo ) || MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST != $ProductOrderInfo->CancelInfo->ClaimStatus ) {
	$item_classes = array_diff( $item_classes, array ( 'approve-cancel-application' ) );
}

if ( ! empty( $ProductOrderInfo->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST == $ProductOrderInfo->CancelInfo->ClaimStatus ) {
	$item_classes = array_diff( $item_classes, array ( 'cancel-sale', 'delay-product-order' ) );
}

if ( empty( $ProductOrderInfo->ReturnInfo ) || in_array( $ProductOrderInfo->ReturnInfo->ClaimStatus, array (
		MNP_Manager::CLAIM_STATUS_RETURN_RETURN_DONE,
		MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REJECT
	) )
) {
	$item_classes = array_diff( $item_classes, array (
		'approve-return-application',
		'withhold-return',
		'release-return-hold',
		'reject-return',
	) );
} else {
	$item_classes = array_diff( $item_classes, array ( 'request-return' ) );
	if ( empty( $ProductOrderInfo->ReturnInfo->HoldbackStatus ) || 'RELEASED' == $ProductOrderInfo->ReturnInfo->HoldbackStatus ) {
		$item_classes = array_diff( $item_classes, array ( 'release-return-hold' ) );
	} else if ( 'HOLDBACK' == $ProductOrderInfo->ReturnInfo->HoldbackStatus ) {
		$item_classes = array_diff( $item_classes, array (
			'approve-return-application',
			'withhold-return',
			'reject-return'
		) );
	}
}

if ( empty( $ProductOrderInfo->ExchangeInfo ) || in_array( $ProductOrderInfo->ExchangeInfo->ClaimStatus, array (
		MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_DONE,
		MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT
	) )
) {
	$item_classes = array_diff( $item_classes, array (
		'approve-collected-exchange',
		'withhold-exchange',
		'release-exchange-hold',
		'reject-exchange',
		'redelivery-exchange'
	) );
} else {
	if ( empty( $ProductOrderInfo->ExchangeInfo->HoldbackStatus ) || 'RELEASED' == $ProductOrderInfo->ExchangeInfo->HoldbackStatus ) {
		$item_classes = array_diff( $item_classes, array ( 'release-exchange-hold' ) );
	} else if ( 'HOLDBACK' == $ProductOrderInfo->ExchangeInfo->HoldbackStatus ) {
		$item_classes = array_diff( $item_classes, array (
			'approve-collected-exchange',
			'withhold-exchange',
			'reject-exchange',
			'redelivery-exchange'
		) );
	}
	if ( MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECT_DONE == $ProductOrderInfo->ExchangeInfo->ClaimStatus ) {
		$item_classes = array_diff( $item_classes, array (
			'approve-collected-exchange',
			'withhold-exchange',
			'release-exchange-hold',
			'reject-exchange'
		) );
	} else {
		$item_classes = array_diff( $item_classes, array ( 'redelivery-exchange' ) );
	}
}

if ( ! empty( $ProductOrderInfo->ExchangeInfo ) && ! in_array( $ProductOrderInfo->ExchangeInfo->ClaimStatus, array ( MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT ) ) ) {
	$item_classes = array_diff( $item_classes, array ( 'request-return' ) );
}

if ( ! in_array( $ProductOrder->ProductOrderStatus, array (
	MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERING,
	MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERED
) )
) {
	$item_classes = array_diff( $item_classes, array ( 'request-return' ) );
}

// 입금대기 상태에서는 주문관련처리가 불가능함
if ( MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING == $ProductOrder->ProductOrderStatus ) {
	$item_classes = array ();
}
// 교환재배송중 상태에서는 주문관련처리가 불가능함
if ( ! empty( $ProductOrderInfo->ExchangeInfo ) && MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REDELIVERING == $ProductOrderInfo->ExchangeInfo->ClaimStatus ) {
	$item_classes = array ();
}

?>
<tr class="item <?php echo implode( ' ', $item_classes ); ?>"
    data-product_order_id="<?php echo $ProductOrder->ProductOrderID; ?>">
	<td class="check-column">
		<?php if ( ! in_array( $ProductOrder->ProductOrderStatus, array (
			MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED,
			MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED_BY_NOPAYMENT,
			MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED,
			MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED,
			MNP_Manager::PRODUCT_ORDER_STATUS_PURCHASE_DECIDED
		) )
		) : ?>
			<input type="checkbox"/>
		<?php endif ?>
	</td>

	<td class="product_order_id">
		<?php echo $ProductOrder->ProductOrderID; ?>
	</td>

	<td class="thumb">
		<?php /** @var WC_Product $_product */ ?>
		<?php if ( $_product ) : ?>
			<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $_product->get_id() ) . '&action=edit' ) ); ?>"
			   class="tips" data-tip="<?php

			echo '<strong>' . __( 'Product ID:', 'woocommerce' ) . '</strong> ' . absint( $_product->get_id() );

			if ( $_product->is_type( 'variation' ) ) {
				echo '<br/><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong> ' . absint( $_product->variation_id );
			}

			if ( $_product && $_product->get_sku() ) {
				echo '<br/><strong>' . __( 'Product SKU:', 'woocommerce' ) . '</strong> ' . esc_html( $_product->get_sku() );
			}

			if ( $_product && isset( $_product->variation_data ) ) {
				echo '<br/>' . wc_get_formatted_variation( $_product->variation_data, true );
			}

			?>"><?php echo $_product->get_image( 'shop_thumbnail', array ( 'title' => '' ) ); ?></a>
		<?php else : ?>
			<?php echo wc_placeholder_img( 'shop_thumbnail' ); ?>
		<?php endif; ?>
	</td>

	<td class="name" data-sort-value="<?php echo esc_attr( $_product->get_title() ); ?>">
		<?php echo ( $_product && $_product->get_sku() ) ? esc_html( $_product->get_sku() ) . ' &ndash; ' : ''; ?>

		<a target="_blank"
		   href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $_product->get_id() ) . '&action=edit' ) ); ?>">
			<?php echo esc_html( $_product->get_title() ); ?>
			<?php if ( ! empty( $ProductOrder->ProductOption ) ) {
				echo ' (' . $ProductOrder->ProductOption . ')';
			} ?>
		</a>
	</td>

	<td class="cost">
		<?php
		$price = apply_filters( 'mnp_get_product_price_by_id', $_product->get_price(), $ProductOrder->ProductID, $ProductOrder->SellerProductCode );
		echo wc_price( $price, array ( 'currency' => $order->get_order_currency() ) );
		?>
	</td>

	<td class="qty">
		<?php echo ( isset( $ProductOrder->Quantity ) ) ? esc_html( $ProductOrder->Quantity ) : ''; ?>
	</td>

	<td class="product_order_status">
		<?php
		$mnp_order_status_template = array ( 'order-item-status/product-order-info.php' );

		if ( MNP_Manager::PRODUCT_ORDER_STATUS_PURCHASE_DECIDED == $ProductOrder->ProductOrderStatus ) {
			$mnp_order_status_template[] = 'order-item-status/purchase-decided.php';
			$mnp_order_status_template[] = 'order-item-status/ship-product-order.php';
			$status                      = "구매확정";
		} else if ( MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING == $ProductOrder->ProductOrderStatus ) {
			$status = "입금대기";
		} else if ( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $ProductOrder->ProductOrderStatus ) {
			$mnp_order_status_template[] = 'order-item-status/cancelled.php';
			$status                      = "취소완료";
		} else if ( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED_BY_NOPAYMENT == $ProductOrder->ProductOrderStatus ) {
			$mnp_order_status_template[] = 'order-item-status/cancelled.php';
			$status                      = "미입금 취소";
		} else if ( MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED == $ProductOrder->ProductOrderStatus ) {
			$mnp_order_status_template[] = 'order-item-status/return.php';
			$status                      = '반품환불완료';
		} else if ( MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED == $ProductOrder->ProductOrderStatus ) {
			$mnp_order_status_template = array ( 'order-item-status/exchange.php' );
			$status                    = '교환 완료';
		} else {
			if ( ! empty( $ProductOrderInfo->ReturnInfo ) && in_array( $ProductOrderInfo->ReturnInfo->ClaimStatus, array (
					MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REQUEST,
					MNP_Manager::CLAIM_STATUS_RETURN_COLLECTING
				) )
			) {
				$status = '반품요청';
				$mnp_order_status_template[] = 'order-item-status/return.php';
				if ( ! empty( $ProductOrderInfo->ReturnInfo->HoldbackStatus ) && 'HOLDBACK' == $ProductOrderInfo->ReturnInfo->HoldbackStatus ) {
					$status .= ' (환불보류)';
				}
			} else if ( ! empty( $ProductOrderInfo->Delivery ) ) {
				$mnp_order_status_template[] = 'order-item-status/ship-product-order.php';
				$status                      = '배송중';
			} else if ( ! empty( $ProductOrderInfo->ProductOrder->DelayedDispatchReason ) ) {
				$mnp_order_status_template[] = 'order-item-status/delay-product-order.php';
				$due_date                    = ( new DateTime( $ProductOrderInfo->ProductOrder->ShippingDueDate ) )->add( new DateInterval( 'PT9H' ) )->format( 'm/d' );
				$status                      = '배송 준비중 (' . $due_date . '까지 발송예정)';
			} else if ( MNP_Manager::PLACE_ORDER_STATUS_OK == $ProductOrder->PlaceOrderStatus ) {
				$mnp_order_status_template[] = 'order-item-status/place-product-order.php';
				$status                      = '배송 준비중';
			} else if ( MNP_Manager::ORDER_STATUS_PAYED == $ProductOrder->ProductOrderStatus ) {
				$status = '결제 완료';
			}

			if ( ! empty( $ProductOrderInfo->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST == $ProductOrderInfo->CancelInfo->ClaimStatus ) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/cancelled.php'
				);
				$status                    = '취소 요청';
			} else if ( ! empty( $ProductOrderInfo->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCELING == $ProductOrderInfo->CancelInfo->ClaimStatus ) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/cancelled.php'
				);
				$status                    = '취소 처리 중';
			}

			if ( ! empty( $ProductOrderInfo->ExchangeInfo ) && in_array( $ProductOrderInfo->ExchangeInfo->ClaimStatus, array (
					MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REQUEST,
					MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECTING
				) )
			) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/exchange.php'
				);
				$status                    = '교환요청';
				if ( 'HOLDBACK' == $ProductOrderInfo->ExchangeInfo->HoldbackStatus ) {
					$status .= ' (교환보류)';
				}
			} else if ( ! empty( $ProductOrderInfo->ExchangeInfo ) && MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECT_DONE == $ProductOrderInfo->ExchangeInfo->ClaimStatus ) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/exchange.php'
				);
				$status                    = '교환수거완료';
			} else if ( ! empty( $ProductOrderInfo->ExchangeInfo ) && MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REDELIVERING == $ProductOrderInfo->ExchangeInfo->ClaimStatus ) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/exchange.php'
				);
				$status                    = '교환재배송 중';
			}

			if ( ! empty( $ProductOrderInfo->ProductOrder->ClaimType ) && $ProductOrderInfo->ProductOrder->ClaimType == MNP_Manager::CLAIM_TYPE_EXCHANGE &&
			     $ProductOrderInfo->ProductOrder->ClaimStatus == MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT
			) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/ship-product-order.php',
					'order-item-status/exchange.php'
				);
				$status .= ' (교환거절)';
			}

			if ( ! empty( $ProductOrderInfo->ProductOrder->ClaimType ) && $ProductOrderInfo->ProductOrder->ClaimType == MNP_Manager::CLAIM_TYPE_RETURN &&
			     $ProductOrderInfo->ProductOrder->ClaimStatus == MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REJECT
			) {
				$mnp_order_status_template = array (
					'order-item-status/product-order-info.php',
					'order-item-status/ship-product-order.php',
					'order-item-status/return.php'
				);
				$status .= ' (반품거절)';
			}

		}

		?>
		<?php echo $status; ?>
		<?php if ( count( $mnp_order_status_template ) > 0 ) : ?>
			<img src="<?php echo MNP()->plugin_url() . '/assets/images/detail-info.png'; ?>"
			     class="nmp-order-detail-info" style="width:20px;height:20px;vertical-align: middle;">
		<?php endif ?>
	</td>
</tr>
