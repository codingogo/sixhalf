<?php



if ( ! class_exists( 'MNP_Order' ) ) {
	class MNP_Order {

		protected static $customer_info = array ();
		protected static $iv_delivery_fee = 0;
		public static function wc_order_statuses( $order_statuses ) {

			return array_merge( $order_statuses, array (
				'wc-place-order'      => _x( '발주확인', 'Order status', 'mshop-npay' ),
				'wc-shipping'         => _x( '배송중', 'Order status', 'mshop-npay' ),
				'wc-cancel-request'   => _x( '취소요청', 'Order status', 'mshop-npay' ),
				'wc-exchange-request' => _x( '교환신청', 'Order status', 'mshop-npay' ),
				'wc-return-request'   => _x( '반품신청', 'Order status', 'mshop-npay' ),
			) );
		}
		public static function update_delivery_info( $order, $npay_product_order ) {

			$ShippingAddress = $npay_product_order->ProductOrder->ShippingAddress;

			mnp_update_meta_data( $order, '_shipping_first_name', $ShippingAddress->Name );
			mnp_update_meta_data( $order, '_shipping_address_1', $ShippingAddress->BaseAddress );
			mnp_update_meta_data( $order, '_shipping_address_2', $ShippingAddress->DetailedAddress );
			mnp_update_meta_data( $order, '_shipping_postcode', $ShippingAddress->ZipCode );
			mnp_save_meta_data( $order );

			if ( is_callable( array ( $order, 'set_customer_note' ) ) ) {
				$order->set_customer_note( $ShippingAddress->Name . '(' . $ShippingAddress->Tel1 . ')' . ( isset( $npay_product_order->ProductOrder->ShippingMemo ) ? ', ' . $npay_product_order->ProductOrder->ShippingMemo : '' ) );
				$order->save();
			} else {
				$post               = get_post( $order->id );
				$post->post_excerpt = $ShippingAddress->Name . '(' . $ShippingAddress->Tel1 . ')' . ( isset( $npay_product_order->ProductOrder->ShippingMemo ) ? ', ' . $npay_product_order->ProductOrder->ShippingMemo : '' );
				wp_update_post( $post );
			}
		}

		static function bulk_action_place_product_order( $order_ids ) {
			$count       = 0;
			$order_info  = array ();
			$_order_info = array ();

			foreach ( $order_ids as $order_id ) {
				$order             = wc_get_order( $order_id );
				$product_order_ids = array ();

				if ( 'naverpay' == mnp_get_object_property( $order, 'payment_method' ) ) {
					foreach ( $order->get_items() as $item_id => $item ) {
						$npay_product_order_id = $item['npay_product_order_id'];
						$npay_order            = json_decode( $item['npay_order'] );
						$npay_product_order    = $npay_order->ProductOrder;

						if ( $npay_order && $npay_product_order_id && MNP_Order_Item::can_order_action( $npay_order, $npay_product_order, 'place-product-order' ) ) {
							$product_order_ids[] = $npay_product_order_id;
						}
					}

					if ( ! empty( $product_order_ids ) ) {
						$order_info[]                                           = array (
							'order_id'               => mnp_get_object_property( $order, 'id' ),
							'npay_order_id'          => mnp_get_meta( $order, '_naverpay_order_id' ),
							'npay_product_order_ids' => implode( ',', $product_order_ids )
						);
						$_order_info[ mnp_get_object_property( $order, 'id' ) ] = implode( ',', $product_order_ids );
					}
				}
			}

			if ( ! empty( $order_info ) ) {
				$params = array (
					'command'    => 'bulk_place_product_order',
					'order_info' => $order_info
				);

				$response = MNP_API::call( http_build_query( array_merge( MNP_Manager::default_args(), $params ) ) );

				foreach ( $response as $order_id => $order_response ) {
					$order = wc_get_order( $order_id );

					// process result
					if ( $order_response && property_exists( $order_response, 'success' ) && property_exists( $order_response, 'error' ) ) {
						$success_count = count( (array) $order_response->success );
						$error_count   = count( (array) $order_response->error );

						if ( $success_count > 0 ) {
							self::update_npay_orders( $order, array_values( (array) $order_response->success ) );
						}

						if ( $error_count > 0 ) {
							$msg[] = sprintf( __( '발주처리 - 실패 : %d/%d건', 'mshop-npay' ), $error_count, $success_count + $error_count );
							foreach ( $order_response->error as $key => $error ) {
								$msg[] = sprintf( __( '%s, %s, %s', 'mshop-npay' ), $key, $error->Code, $error->Message );
							}
							$order->add_order_note( '<span style="font-size: 0.85em">[NPay] ' . implode( '<br>', $msg ) . '</span>' );
						} else {
							$order->add_order_note( sprintf( __( '<span style="font-size: 0.85em">[NPay] 발주처리 완료 [%s]</span>', 'mshop-npay' ), $_order_info[ $order_id ] ) );
							$order->update_status( 'place-order', __( 'Order status changed by bulk edit:', 'woocommerce' ), true );
							$count ++;
						}
					} else if ( $order instanceof WC_Abstract_Order ) {
						$order->add_order_note( '<span style="font-size: 0.85em">[NPay] ' . __( '발주 처리중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 'mshop-npay' ) . '</span>' );
					}
				}
			}

			return $count;
		}

		static function order_action() {

			// validate request
			if ( empty( $_REQUEST['params'] ) ) {
				wp_send_json_error( '잘못된 요청입니다.' );
			}

			$params = $_REQUEST['params'];
			if ( empty( $params['command'] ) || empty( $params['order_id'] ) || empty( $params['product_order_id'] ) ) {
				wp_send_json_error( '잘못된 요청입니다.' );
			}

			// get mandatory fields
			$order            = wc_get_order( $params['order_id'] );
			$product_order_id = $params['product_order_id'];
			$command          = $params['command'];
			$command_desc     = MNP_API::get_command_desc( $command );

			// call npay api
			$response = MNP_API::call( http_build_query( array_merge( MNP_Manager::default_args(), $params ) ) );

			// process result
			if ( $response && property_exists( $response, 'success' ) && property_exists( $response, 'error' ) ) {
				$success_count = count( (array) $response->success );
				$error_count   = count( (array) $response->error );

				if ( $success_count > 0 ) {
					self::update_npay_orders( $order, array_values( (array) $response->success ) );
				}

				if ( $error_count > 0 ) {
					$msg[] = sprintf( __( '%s처리 - 실패 : %d/%d건', 'mshop-npay' ), $command_desc, $error_count, $success_count + $error_count );
					foreach ( $response->error as $key => $error ) {
						$msg[] = sprintf( __( '%s, %s, %s', 'mshop-npay' ), $key, $error->Code, $error->Message );
					}
					$order->add_order_note( '<span style="font-size: 0.85em">[NPay] ' . implode( '<br>', $msg ) . '</span>' );
					wp_send_json_error( $msg[0] . ' ' . $msg[1] );
				} else {
					$order->add_order_note( sprintf( __( '<span style="font-size: 0.85em">[NPay] %s처리 완료 [%s]</span>', 'mshop-npay' ), $command_desc, $product_order_id ) );
					wp_send_json_success();
				}
			} else {
				wp_send_json_error( __( '요청 처리중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 'mshop-npay' ) );
			}
		}

		static function get_purchase_review_list() {
			$search_date = $_REQUEST['param']['search_date'];

			$from = $search_date . 'T00:00:00+09:00';
			$to   = $search_date . 'T23:59:59+09:00';

			$result = MNP_API::get_purchase_review_list( $from, $to );

			$response = $result->response;

//			if( "SUCCESS" == $response->ResponseType && $response->ReturnedDataCount > 0 ){
//				if( is_array( $response->PurchaseReviewList) ){
//					foreach( $response->PurchaseReviewList as $PurchaseReview ){
//						$this->insert_comment( $PurchaseReview );
//					}
//				}else{
//					$this->insert_comment( $response->PurchaseReviewList );
//				}
//			}

			wp_send_json_success( array (
				"request"  => print_r( $result->request, true ),
				"response" => print_r( $result->response, true )
			) );
		}

		static function answer_customer_inquiry() {
			$InquiryID       = $_REQUEST['param']['InquiryID'];
			$AnswerContent   = $_REQUEST['param']['AnswerContent'];
			$AnswerContentID = $_REQUEST['param']['AnswerContentID'];

			$result   = MNP_API::answer_customer_inquiry( $InquiryID, $AnswerContent, $AnswerContentID );
			$response = $result->response;

			if ( 'SUCCESS' == $response->ResponseType ) {
				wp_send_json_success( array ( "request" => $result->request, "response" => $result->response ) );
			} else {
				wp_send_json_error( array ( "request" => $result->request, "response" => $result->response ) );
			}
		}
		public static function woocommerce_hidden_order_itemmeta( $metas ) {
			return array_merge( $metas, array ( '_npay_order', '_npay_product_order_id', '_npay_product_order_status' ) );
		}
		public static function get_npay_orders( $order_id ) {
			$order = wc_get_order( $order_id );

			$params = array (
				'command'          => 'refresh_npay_order',
				'order_id'         => $order_id,
				'product_order_id' => mnp_get_meta( $order, '_naverpay_product_order_id' ),
				'mall_id'          => MNP_Manager::merchant_id()
			);

			// call npay api
			$response = MNP_API::call( http_build_query( array_merge( MNP_Manager::default_args(), $params ) ) );

			if ( $response && property_exists( $response, 'success' ) && property_exists( $response, 'error' ) ) {
				$success_count = count( (array) $response->success );
				$error_count   = count( (array) $response->error );


				if ( $error_count > 0 ) {
					$msg[] = sprintf( __( '주문정보 새로고침 - 실패 : %d/%d건', 'mshop-npay' ), $error_count, $success_count + $error_count );
					foreach ( $response->error as $key => $error ) {
						$msg[] = sprintf( __( '%s, %s, %s', 'mshop-npay' ), $key, $error->Code, $error->Message );
					}
					throw new Exception( $msg[0] . ' ' . $msg[1] );
				} else if ( $success_count > 0 ) {
					return $response->success;
				}
			}

			throw new Exception( __( 'NPay 주문정보를 확인하는 과정에서 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 'mshop-npay' ) );
		}
		protected static function delete_npay_order_items( $order ) {
			foreach ( $order->get_items() as $item_id => $values ) {
				if ( ! empty( $values['npay_product_order_id'] ) ) {
					wc_delete_order_item( $item_id );
				}
			}
			foreach ( $order->get_refunds() as $refund ) {
				if ( 'yes' == mnp_get_meta( $refund, 'is_npay_order', true ) ) {
					$refund_id = mnp_get_object_property( $refund, 'id' );
					$order_id  = wp_get_post_parent_id( $refund_id );
					wc_delete_shop_order_transients( $order_id );
					wp_delete_post( $refund_id );
					do_action( 'woocommerce_refund_deleted', $refund_id, $order_id );
				}
			}
			$order->remove_order_items( 'shipping' );
		}
		protected static function has_refund_order( $order, $item_id ) {
			foreach ( $order->get_refunds() as $refund ) {
				if ( 'yes' == mnp_get_meta( $refund, 'is_npay_order', true ) && $item_id == mnp_get_meta( $refund, 'npay_order_item_id', true ) ) {
					return true;
				}
			}

			return false;
		}
		protected static function woocommerce_cart_calculate_fees( $cart ) {
			if ( floatval( self::$iv_delivery_fee ) > 0 ) {
				$cart->add_fee( '도서산간 배송비', self::$iv_delivery_fee );
			}
		}
		public static function create_npay_order( $npay_orders ) {
			self::get_customer_info( $npay_orders );
			MNP_Cart::generate_cart( $npay_orders );
			$npay_order         = $npay_orders[0];
			$npay_product_order = $npay_order->ProductOrder;

			if ( $npay_order->ProductOrder->SectionDeliveryFee > 0 ) {
				self::$iv_delivery_fee = $npay_order->ProductOrder->SectionDeliveryFee;
				add_action( 'woocommerce_cart_calculate_fees', __CLASS__ . '::woocommerce_cart_calculate_fees' );
			}

			WC()->cart->calculate_totals();

			remove_action( 'woocommerce_cart_calculate_fees', __CLASS__ . '::woocommerce_cart_calculate_fees' );
			$data                              = array ();
			$data['billing_first_name']        = $npay_order->Order->OrdererName;
			$data['billing_phone']             = preg_replace( '/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $npay_order->Order->OrdererTel1 );
			$data['ship_to_different_address'] = true;
			$data['shipping_first_name']       = $npay_product_order->ShippingAddress->Name;
			$data['shipping_address_1']        = $npay_product_order->ShippingAddress->BaseAddress;
			$data['shipping_address_2']        = $npay_product_order->ShippingAddress->DetailedAddress;
			$data['shipping_postcode']         = $npay_product_order->ShippingAddress->ZipCode;
			$data['order_comments']            = $npay_product_order->ShippingAddress->Name . '(' . $npay_product_order->ShippingAddress->Tel1 . ')' . ( isset( $npay_product_order->ShippingMemo ) ? ', ' . $npay_product_order->ShippingMemo : '' );

			if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
				$order_id = WC()->checkout()->create_order( $data );
			} else {
				WC()->checkout()->posted = $data;
				$order_id                = WC()->checkout()->create_order();
			}
			$order = wc_get_order( $order_id );

			if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
				$order->set_payment_method( 'naverpay' );
				$order->set_payment_method_title( 'NPay' );
				if ( ! $order->get_date_paid( 'edit' ) ) {
					$order->set_date_paid( current_time( 'timestamp', true ) );
				}

				$order->save();
			} else {
				update_post_meta( $order_id, '_payment_method', 'naverpay' );
				update_post_meta( $order_id, '_payment_method_title', 'NPay' );
				if ( isset( self::$customer_info['user_id'] ) && is_numeric( self::$customer_info['user_id'] ) ) {
					update_post_meta( $order_id, '_customer_user', self::$customer_info['user_id'] );
				}
			}
			$product_order_ids = array ();
			foreach ( $npay_orders as $npay_order ) {
				$product_order_ids[] = $npay_order->ProductOrder->ProductOrderID;
			}
			mnp_update_meta_data( $order, '_npay_version', MNP()->version );
			mnp_update_meta_data( $order, '_npay_order', $npay_order->Order );
			mnp_update_meta_data( $order, '_naverpay_order_id', $npay_order->Order->OrderID );
			mnp_update_meta_data( $order, '_naverpay_product_order_id', implode( ',', $product_order_ids ) );
			MNP_Order::update_stock( $order, $npay_orders );
			MNP_Order::update_order_status( $order, $npay_orders );
			self::save_custom_data( $order, $npay_orders );

			self::remove_npay_membership_filter();

			return $order;
		}
		protected static function get_order_item( $order, $product_order_id ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( ! empty( $item['npay_product_order_id'] ) && $item['npay_product_order_id'] == $product_order_id ) {
					return array ( 'item_id' => $item_id, 'item' => $item );
				}
			}

			return null;
		}
		public static function update_npay_orders( $order, $npay_orders ) {
			self::get_customer_info( $npay_orders );
			foreach ( $npay_orders as $npay_order ) {
				$order_item = self::get_order_item( $order, $npay_order->ProductOrder->ProductOrderID );

				if ( ! is_null( $order_item ) ) {
					$item_id = $order_item['item_id'];
					$item = $order_item['item'];
					mnp_update_order_item_meta_data( $item_id, $item, '_npay_order', json_encode( $npay_order, JSON_UNESCAPED_UNICODE ) );
					mnp_save_meta_data( $item );

					if ( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $npay_order->ProductOrder->ProductOrderStatus ) {
						mnp_update_order_item_meta_data( $item_id, $item, '_qty', 0 );
						mnp_update_order_item_meta_data( $item_id, $item, '_line_total', 0 );
						mnp_update_order_item_meta_data( $item_id, $item, '_line_subtotal', 0 );
						mnp_update_order_item_meta_data( $item_id, $item, '_line_tax', 0 );
						mnp_update_order_item_meta_data( $item_id, $item, '_line_tax_data', array (
							'total'    => array (),
							'subtotal' => array ()
						) );
						mnp_save_meta_data( $item );
					} else if ( MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED == $npay_order->ProductOrder->ProductOrderStatus || MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED == $npay_order->ProductOrder->ProductOrderStatus ) {
						if ( ! self::has_refund_order( $order, $item_id ) ) {
							$item['refund_total'] = $item['line_total'];
							$item['refund_tax']   = $item['line_tax'];
							$refund_order         = wc_create_refund( array (
								'amount'     => $item['line_total'],
								'order_id'   => mnp_get_object_property( $order, 'id' ),
								'line_items' => array (
									$item_id => $item
								)
							) );

							mnp_update_meta_data( $refund_order, 'is_npay_order', 'yes' );
							mnp_update_meta_data( $refund_order, 'npay_order_item_id', $item_id );
							mnp_save_meta_data( $refund_order );
						}
					}
					mnp_update_meta_data( $order, '_npay_order', $npay_order->Order );
				}
			}
			MNP_Order::update_stock( $order, $npay_orders );
			$all_npay_orders = array ();
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( ! empty( $item['npay_product_order_id'] ) && ! empty( $item['npay_order'] ) ) {
					$all_npay_orders[] = json_decode( $item['npay_order'] );
				}
			}

			MNP_Order::update_order_status( $order, $all_npay_orders );
			self::update_delivery_info( $order, $npay_orders[0] );

			self::remove_npay_membership_filter();
		}
		protected static function add_npay_items_to_order( $order, $npay_orders ) {
			MNP_Cart::generate_cart( $npay_orders );
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				if ( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $values['_npay_product_order_status'] ) {
					$item_id = $order->add_product(
						$values['data'],
						0,
						array (
							'variation' => $values['variation'],
							'totals'    => array (
								'subtotal'     => 0,
								'subtotal_tax' => 0,
								'total'        => 0,
								'tax'          => 0,
								'tax_data'     => array ( 'total' => array (), 'subtotal' => array () )
							)
						)
					);
				} else {
					$item_id = $order->add_product(
						$values['data'],
						$values['quantity'],
						array (
							'variation' => $values['variation'],
							'totals'    => array (
								'subtotal'     => $values['line_subtotal'],
								'subtotal_tax' => $values['line_subtotal_tax'],
								'total'        => $values['line_total'],
								'tax'          => $values['line_tax'],
								'tax_data'     => $values['line_tax_data'] // Since 2.2
							)
						)
					);
				}

				if ( ! $item_id ) {
					throw new Exception( "주문 정보를 생성할 수 없습니다." );
				}

				do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
			}
			foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
				$free_shipping = array_filter( $package['rates'], function ( $rate ) {
					return 'free_shipping' == $rate->method_id;
				} );

				$flat_rate = array_filter( $package['rates'], function ( $rate ) {
					return 'flat_rate' == $rate->method_id;
				} );


				if ( empty( $free_shipping ) && ! empty( $flat_rate ) ) {
					$order->add_shipping( current( $flat_rate ) );
				}
			}

			$order->calculate_shipping();
			$order->calculate_totals();

			wc_update_order( $order_args = array ( 'order_id' => mnp_get_object_property( $order, 'id' ) ) );
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED == $item['npay_product_order_status'] || MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED == $item['npay_product_order_status'] ) {
					$item['refund_total'] = $item['line_total'];
					$item['refund_tax']   = $item['line_tax'];
					$refund_order         = wc_create_refund( array (
						'amount'     => $item['line_total'],
						'order_id'   => mnp_get_object_property( $order, 'id' ),
						'line_items' => array (
							$item_id => $item
						)
					) );

					mnp_update_meta_data( $refund_order, 'is_npay_order', 'yes' );
					mnp_update_meta_data( $refund_order, 'npay_order_item_id', $item_id );
				}
			}
		}
		protected static function add_npay_items_to_order_wc30( $order, $npay_orders ) {
			MNP_Cart::generate_cart( $npay_orders );
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$product                    = $values['data'];
				$item                       = new WC_Order_Item_Product();
				$item->legacy_values        = $values; // @deprecated For legacy actions.
				$item->legacy_cart_item_key = $cart_item_key; // @deprecated For legacy actions.

				if ( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED == $values['_npay_product_order_status'] ) {
					$item->set_props( array (
						'quantity'     => $values['quantity'],
						'variation'    => $values['variation'],
						'subtotal'     => 0,
						'total'        => 0,
						'subtotal_tax' => 0,
						'total_tax'    => 0,
						'taxes'        => array ( 'total' => array (), 'subtotal' => array () ),
					) );
				} else {
					$item->set_props( array (
						'quantity'     => $values['quantity'],
						'variation'    => $values['variation'],
						'subtotal'     => $values['line_subtotal'],
						'total'        => $values['line_total'],
						'subtotal_tax' => $values['line_subtotal_tax'],
						'total_tax'    => $values['line_tax'],
						'taxes'        => $values['line_tax_data'],
					) );
				}
				if ( $product ) {
					$item->set_props( array (
						'name'         => $product->get_name(),
						'tax_class'    => $product->get_tax_class(),
						'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
						'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
					) );
				}
				$item->set_backorder_meta();
				do_action( 'woocommerce_checkout_create_order_line_item', $item, $cart_item_key, $values, $order );

				// Add item to order and save.
				if ( false === $order->add_item( $item ) ) {
					throw new Exception( "주문 정보를 생성할 수 없습니다." );
				}
			}
			foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
				$free_shipping = array_filter( $package['rates'], function ( $rate ) {
					return 'free_shipping' == $rate->method_id;
				} );

				$flat_rate = array_filter( $package['rates'], function ( $rate ) {
					return 'flat_rate' == $rate->method_id;
				} );


				if ( empty( $free_shipping ) && ! empty( $flat_rate ) ) {
					$shipping_rate            = current( $flat_rate );
					$item                     = new WC_Order_Item_Shipping();
					$item->legacy_package_key = $package_key; // @deprecated For legacy actions.
					$item->set_props( array (
						'method_title' => $shipping_rate->label,
						'method_id'    => $shipping_rate->id,
						'total'        => wc_format_decimal( $shipping_rate->cost ),
						'taxes'        => array (
							'total' => $shipping_rate->taxes,
						),
					) );

					foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
						$item->add_meta_data( $key, $value, true );
					}
					do_action( 'woocommerce_checkout_create_order_shipping_item', $item, $package_key, $package, $order );

					// Add item to order and save.
					$order->add_item( $item );
				}
			}

			$order->calculate_shipping();
			$order->calculate_totals();

			$order->save();
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED == $item['npay_product_order_status'] || MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED == $item['npay_product_order_status'] ) {
					$item['refund_total'] = $item['line_total'];
					$item['refund_tax']   = $item['line_tax'];
					$refund_order         = wc_create_refund( array (
						'amount'     => $item['line_total'],
						'order_id'   => mnp_get_object_property( $order, 'id' ),
						'line_items' => array (
							$item_id => $item
						)
					) );

					mnp_update_meta_data( $refund_order, 'is_npay_order', 'yes' );
					mnp_update_meta_data( $refund_order, 'npay_order_item_id', $item_id );
				}
			}
		}
		protected static function update_order_status( $order, $npay_orders ) {

			if ( empty( $npay_orders ) ) {
				return;
			}

			$to_state     = '';
			$order_status = array ();

			$is_cancel_reqeust   = false;
			$is_return_request   = false;
			$is_exchange_request = false;

			foreach ( $npay_orders as $npay_order ) {
				$order_status[] = $npay_order->ProductOrder->ProductOrderStatus;

				$is_cancel_reqeust |= MNP_Order_Item::is_cancel_request( $npay_order );
				$is_return_request |= MNP_Order_Item::is_return_request( $npay_order );
				$is_exchange_request |= MNP_Order_Item::is_exchange_request( $npay_order );
			}

			if ( $is_cancel_reqeust ) {
				$to_state = 'cancel-request';
			} else if ( $is_return_request ) {
				$to_state = 'return-request';
			} else if ( $is_exchange_request ) {
				$to_state = 'exchange-request';
			} else if ( in_array( MNP_Manager::PRODUCT_ORDER_STATUS_PAYED, $order_status ) ||
			            in_array( MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERING, $order_status ) ||
			            in_array( MNP_Manager::PRODUCT_ORDER_STATUS_DELIVERED, $order_status )
			) {
				$to_state = $order->get_status();
				if ( in_array( $to_state, array (
					'on-hold',
					'pending',
					'return-request',
					'exchange-request',
					'cancel-request'
				) ) ) {
					$to_state = 'processing';
				}
			} else {
				$waiting   = in_array( MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING, $order_status );
				$cancelled = in_array( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED, $order_status ) || in_array( MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED_BY_NOPAYMENT, $order_status );
				$returned  = in_array( MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED, $order_status );
				$completed = in_array( MNP_Manager::PRODUCT_ORDER_STATUS_PURCHASE_DECIDED, $order_status ) || in_array( MNP_Manager::PRODUCT_ORDER_STATUS_EXCHANGED, $order_status );

				if ( $waiting ) {
					$to_state = 'on-hold';
				} else if ( $completed ) {
					$to_state = 'completed';
				} else if ( $cancelled ) {
					$to_state = 'cancelled';
				} else if ( $returned ) {
					$to_state = 'refunded';
				}
			}

			if ( $order->get_status() != $to_state ) {
				$order->update_status( $to_state );
			}
		}
		public static function update_stock( $order, $npay_orders ) {
			$stock = json_decode( mnp_get_meta( $order, '_naverpay_manage_stock' ), true );

			if ( empty( $stock ) ) {
				$stock = array ();
			}

			foreach ( $npay_orders as $npay_order ) {
				$product = wc_get_product( $npay_order->ProductOrder->SellerProductCode );

				if ( $product && $product->managing_stock() ) {
					switch ( $npay_order->ProductOrder->ProductOrderStatus ) {
						case MNP_Manager::PRODUCT_ORDER_STATUS_PAYMENT_WAITING :
						case MNP_Manager::PRODUCT_ORDER_STATUS_PAYED :
							if ( 'reduced' != $stock[ $product->get_id() ] ) {
								$stock_change                = $npay_order->ProductOrder->Quantity;
								$new_stock                   = $product->reduce_stock( $stock_change );
								$stock[ $product->get_id() ] = 'reduced';

								$order->add_order_note( sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $npay_order->ProductOrder->SellerProductCode, $new_stock + $stock_change, $new_stock ) );
								$order->send_stock_notifications( $product, $new_stock, $npay_order->ProductOrder->Quantity );
							}
							break;
						case MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED :
						case MNP_Manager::PRODUCT_ORDER_STATUS_RETURNED :
						case MNP_Manager::PRODUCT_ORDER_STATUS_CANCELED_BY_NOPAYMENT :
							if ( 'reduced' == $stock[ $product->get_id() ] ) {
								$old_stock    = $product->stock;
								$stock_change = $npay_order->ProductOrder->Quantity;
								$new_quantity = $product->increase_stock( $stock_change );

								$stock[ $product->get_id() ] = '';
								$order->add_order_note( sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $product->get_id(), $old_stock, $new_quantity ) );
							}
							break;
					}
				}
			}

			mnp_update_meta_data( $order, '_naverpay_manage_stock', json_encode( $stock ) );
		}

		public static function migrate_npay_order( $order_id ) {

			$order    = wc_get_order( $order_id );

			try {
				add_filter( 'woocommerce_product_is_in_stock', '__return_true' );
				add_filter( 'woocommerce_product_backorders_allowed', '__return_true' );

				MNP()->define( 'WOOCOMMERCE_CART', true );

				MNP_Cart::backup_cart();
				$npay_orders = self::get_npay_orders( $order_id );
				self::delete_npay_order_items( $order );
				// Reload order for support WC 3.0
				$order = wc_get_order( $order_id );

				$order->remove_order_items();
				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					self::add_npay_items_to_order_wc30( $order, $npay_orders );
					$order->set_payment_method( 'naverpay' );
					$order->set_payment_method_title( 'NPay' );
					$order->save();
				} else {
					self::add_npay_items_to_order( $order, $npay_orders );
					update_post_meta( $order_id, '_payment_method', 'naverpay' );
					update_post_meta( $order_id, '_payment_method_title', 'NPay' );
				}
				self::update_order_status( $order, $npay_orders );
				$product_order_ids = array ();
				foreach ( $npay_orders as $npay_order ) {
					$product_order_ids[] = $npay_order->ProductOrder->ProductOrderID;
				}
				$npay_order = current( $npay_orders );
				mnp_update_meta_data( $order, '_npay_version', MNP()->version );
				mnp_update_meta_data( $order, '_npay_order', $npay_order->Order );
				mnp_update_meta_data( $order, '_naverpay_order_id', $npay_order->Order->OrderID );
				mnp_update_meta_data( $order, '_naverpay_product_order_id', implode( ',', $product_order_ids ) );

				MNP_Cart::recover_cart();

				remove_filter( 'woocommerce_product_is_in_stock', '__return_true' );
				remove_filter( 'woocommerce_product_backorders_allowed', '__return_true' );
				self::save_custom_data( $order, $npay_orders );

			} catch ( Exception $e ) {
			}
		}

		public static function refresh_npay_order() {

			$order_id = $_REQUEST['order_id'];
			$order    = wc_get_order( $order_id );

			try {
				do_action( 'mnp_before_refresh_npay_order', $order );
				add_filter( 'woocommerce_product_is_in_stock', '__return_true' );
				add_filter( 'woocommerce_product_backorders_allowed', '__return_true' );

				MNP()->define( 'WOOCOMMERCE_CART', true );

				MNP_Cart::backup_cart();
				$npay_orders = self::get_npay_orders( $order_id );
				self::delete_npay_order_items( $order );
				// Reload order for support WC 3.0
				$order = wc_get_order( $order_id );

				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					self::add_npay_items_to_order_wc30( $order, $npay_orders );
					$order->set_payment_method( 'naverpay' );
					$order->set_payment_method_title( 'NPay' );
					$order->save();
				} else {
					self::add_npay_items_to_order( $order, $npay_orders );
					update_post_meta( $order_id, '_payment_method', 'naverpay' );
					update_post_meta( $order_id, '_payment_method_title', 'NPay' );
				}
				self::update_order_status( $order, $npay_orders );
				$product_order_ids = array ();
				foreach ( $npay_orders as $npay_order ) {
					$product_order_ids[] = $npay_order->ProductOrder->ProductOrderID;
				}
				$npay_order = current( $npay_orders );
				mnp_update_meta_data( $order, '_npay_version', MNP()->version );
				mnp_update_meta_data( $order, '_npay_order', $npay_order->Order );
				mnp_update_meta_data( $order, '_naverpay_order_id', $npay_order->Order->OrderID );
				mnp_update_meta_data( $order, '_naverpay_product_order_id', implode( ',', $product_order_ids ) );

				MNP_Cart::recover_cart();

				remove_filter( 'woocommerce_product_is_in_stock', '__return_true' );
				remove_filter( 'woocommerce_product_backorders_allowed', '__return_true' );
				self::save_custom_data( $order, $npay_orders );

				do_action( 'mnp_after_refresh_npay_order', $order, $npay_orders );

			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}

			wp_send_json_success();
		}


		public static function get_customer_info( $npay_orders ) {
			self::$customer_info = array ();

			if ( ! empty( $npay_orders ) ) {
				$npay_order = $npay_orders[0];

				if ( property_exists( $npay_order->ProductOrder, 'MerchantCustomCode1' ) ) {
					parse_str( $npay_order->ProductOrder->MerchantCustomCode1, self::$customer_info );
				}
			}

			if ( isset( self::$customer_info['user_id'] ) ) {
				wp_set_current_user( self::$customer_info['user_id'] );
			} else {
				wp_set_current_user( 0 );
			}

			if ( isset( self::$customer_info['user_role'] ) ) {
				add_filter( 'mshop_membership_get_user_role', __CLASS__ . '::npay_membership', 10, 2 );
			}
		}

		public static function save_custom_data( $order, $npay_orders ) {
			$params = array ();

			if ( ! empty( $npay_orders ) ) {
				$npay_order = current( $npay_orders );

				if ( property_exists( $npay_order->ProductOrder, 'MerchantCustomCode2' ) ) {
					parse_str( $npay_order->ProductOrder->MerchantCustomCode2, $params );

					foreach ( $params as $key => $value ) {
						mnp_update_meta_data( $order, '_' . $key, $value );
					}
				}
			}
		}
		public static function npay_membership( $role, $user_id ) {
			if ( isset( self::$customer_info['user_role'] ) ) {
				$role = self::$customer_info['user_role'];
			}

			return $role;
		}

		public static function remove_npay_membership_filter() {
			remove_filter( 'mshop_membership_get_user_role', __CLASS__ . '::npay_membership', 10 );
		}

		public static function woocommerce_admin_order_items_after_line_items( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( 'naverpay' == mnp_get_object_property( $order, 'payment_method' ) && ! in_array( $order->get_status(), array (
					'cancelled',
					'refunded'
				) )
			) {
				echo '<tr class="npay"><td colspan="10" class="naverpay-admin"><div>';
				include( 'admin/meta-boxes/views/html-order-items-wc.php' );
				echo '</div></td></tr>';
			}
		}
		public static function woocommerce_add_order_item_meta( $item_id, $values, $cart_item_key ) {

			if ( ! empty( $values['_npay_product_order_id'] ) ) {
				wc_add_order_item_meta( $item_id, '_npay_product_order_id', $values['_npay_product_order_id'] );
			}

			if ( ! empty( $values['_npay_product_order_status'] ) ) {
				wc_add_order_item_meta( $item_id, '_npay_product_order_status', $values['_npay_product_order_status'] );
			}

			if ( ! empty( $values['_npay_order'] ) ) {
				wc_add_order_item_meta( $item_id, '_npay_order', $values['_npay_order'] );
			}
		}
		public static function woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {

			$npay_keys = array ( '_npay_product_order_id', '_npay_product_order_status', '_npay_order' );

			$props = array_intersect_key( $values, array_flip( $npay_keys ) );

			if ( ! empty( $props ) ) {
				foreach ( $props as $key => $value ) {
					$item->add_meta_data( $key, $value, true );
				}
			}
		}
		public static function woocommerce_cancel_unpaid_order( $flag, $order ) {
			if ( 'naverpay' == mnp_get_object_property( $order, 'payment_method' ) ) {
				$flag = false;
			}

			return $flag;
		}

	}
}

