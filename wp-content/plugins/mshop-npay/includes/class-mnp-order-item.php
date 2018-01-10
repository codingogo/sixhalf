<?php



if ( ! class_exists( 'MNP_Order_Item' ) ) {
	class MNP_Order_Item {
		public static function is_cancel_request( $npay_order ) {
			if ( ! empty( $npay_order->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST == $npay_order->CancelInfo->ClaimStatus ) {
				return true;
			}

			return false;
		}
		public static function is_return_request( $npay_order ) {
			if ( !empty( $npay_order->ReturnInfo ) && ! in_array( $npay_order->ReturnInfo->ClaimStatus, array (
					MNP_Manager::CLAIM_STATUS_RETURN_RETURN_DONE,
					MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REJECT
				) )
			) {
				return true;
			}

			return false;
		}
		public static function is_exchange_request( $npay_order ) {
			if ( ! empty( $npay_order->ExchangeInfo ) && ! in_array( $npay_order->ExchangeInfo->ClaimStatus, array (
					MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_DONE,
					MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT
				) )
			) {
				return true;
			}

			return false;
		}
		public static function get_classes( $npay_order, $npay_product_order ) {
			$classes = array (
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

			if ( MNP_Manager::PLACE_ORDER_STATUS_NOT_YET != $npay_product_order->PlaceOrderStatus ) {
				$classes = array_diff( $classes, array ( 'place-product-order' ) );
			}

			if ( ! empty( $npay_order->ProductOrder->DelayedDispatchReason ) ) {
				$classes = array_diff( $classes, array ( 'delay-product-order' ) );
			}

			if ( ! empty( $npay_order->Delivery ) ) {
				$classes = array_diff( $classes, array (
					'cancel-sale',
					'ship-product-order',
					'delay-product-order'
				) );
			} else {
				$classes = array_diff( $classes, array ( 'request-return' ) );
			}

			if ( MNP_Manager::PRODUCT_ORDER_STATUS_PAYED != $npay_product_order->ProductOrderStatus ) {
				$classes = array_diff( $classes, array ( 'cancel-sale' ) );
			}

			if ( empty( $npay_order->CancelInfo ) || MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST != $npay_order->CancelInfo->ClaimStatus ) {
				$classes = array_diff( $classes, array ( 'approve-cancel-application' ) );
			}

			if ( ! empty( $npay_order->CancelInfo ) && MNP_Manager::CLAIM_STATUS_CANCEL_CANCEL_REQUEST == $npay_order->CancelInfo->ClaimStatus ) {
				$classes = array_diff( $classes, array ( 'cancel-sale', 'delay-product-order' ) );
			}

			if ( empty( $npay_order->ReturnInfo ) || in_array( $npay_order->ReturnInfo->ClaimStatus, array (
					MNP_Manager::CLAIM_STATUS_RETURN_RETURN_DONE,
					MNP_Manager::CLAIM_STATUS_RETURN_RETURN_REJECT
				) )
			) {
				$classes = array_diff( $classes, array (
					'approve-return-application',
					'withhold-return',
					'release-return-hold',
					'reject-return',
				) );
			} else {
				$classes = array_diff( $classes, array ( 'request-return' ) );
				if ( empty( $npay_order->ReturnInfo->HoldbackStatus ) || 'RELEASED' == $npay_order->ReturnInfo->HoldbackStatus ) {
					$classes = array_diff( $classes, array ( 'release-return-hold' ) );
				} else if ( 'HOLDBACK' == $npay_order->ReturnInfo->HoldbackStatus ) {
					$classes = array_diff( $classes, array (
						'approve-return-application',
						'withhold-return',
						'reject-return'
					) );
				}
			}

			if ( empty( $npay_order->ExchangeInfo ) || in_array( $npay_order->ExchangeInfo->ClaimStatus, array (
					MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_DONE,
					MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT
				) )
			) {
				$classes = array_diff( $classes, array (
					'approve-collected-exchange',
					'withhold-exchange',
					'release-exchange-hold',
					'reject-exchange',
					'redelivery-exchange'
				) );
			} else {
				if ( empty( $npay_order->ExchangeInfo->HoldbackStatus ) || 'RELEASED' == $npay_order->ExchangeInfo->HoldbackStatus ) {
					$classes = array_diff( $classes, array ( 'release-exchange-hold' ) );
				} else if ( 'HOLDBACK' == $npay_order->ExchangeInfo->HoldbackStatus ) {
					$classes = array_diff( $classes, array (
						'approve-collected-exchange',
						'withhold-exchange',
						'reject-exchange',
						'redelivery-exchange'
					) );
				}
				if ( MNP_Manager::CLAIM_STATUS_EXCHANGE_COLLECT_DONE == $npay_order->ExchangeInfo->ClaimStatus ) {
					$classes = array_diff( $classes, array (
						'approve-collected-exchange',
						'withhold-exchange',
						'release-exchange-hold',
						'reject-exchange'
					) );
				} else {
					$classes = array_diff( $classes, array ( 'redelivery-exchange' ) );
				}
			}

			if ( ! empty( $npay_order->ExchangeInfo ) && ! in_array( $npay_order->ExchangeInfo->ClaimStatus, array ( MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REJECT ) ) ) {
				$classes = array_diff( $classes, array ( 'request-return' ) );
			}

			if ( ! in_array( $npay_product_order->ProductOrderStatus, array (
				MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERING,
				MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERED
			) )
			) {
				$classes = array_diff( $classes, array ( 'request-return' ) );
			}

			if ( MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING == $npay_product_order->ProductOrderStatus ) {
				$classes = array ();
			}
			if ( ! empty( $npay_order->ExchangeInfo ) && MNP_Manager::CLAIM_STATUS_EXCHANGE_EXCHANGE_REDELIVERING == $npay_order->ExchangeInfo->ClaimStatus ) {
				$classes = array ();
			}

			if ( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $npay_product_order->ProductOrderStatus ) {
				$classes = array ();
			}

			return $classes;
		}
		public static function can_order_action( $npay_order, $npay_product_order, $action ) {
			return in_array( $action, self::get_classes( $npay_order, $npay_product_order ) );
		}
		public static function woocommerce_after_order_itemmeta( $item_id, $item, $product ) {

			if ( ! is_null( $product ) && ! empty( $item['npay_product_order_id'] ) ) {

				$npay_order         = json_decode( $item['npay_order'] );
				$npay_product_order = $npay_order->ProductOrder;
				$_product           = wc_get_product( $npay_product_order->SellerProductCode );

				echo '<div class="npay-line-item">';
				include( 'admin/meta-boxes/views/html-order-item-wc.php' );
				include( 'admin/meta-boxes/views/html-order-item-status-wc.php' );
				echo '</div>';
			}
		}
	}
}

