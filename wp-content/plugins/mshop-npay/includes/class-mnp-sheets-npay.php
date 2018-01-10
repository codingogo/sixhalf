<?php



if ( ! class_exists( 'MNP_Sheets_Npay' ) ) {

	class MNP_Sheets_Npay {
		static $orders = array ();
		static $order_id_by_product_order_id = array ();
		static $messages = array ();

		protected static function get_order( $sheet_key, $sheet_key_type ) {
			if ( 'order_id' == $sheet_key_type ) {
				$order_id = $sheet_key;
			} else {
				$order_id = mnp_get_order_id_by_order_item_id( $sheet_key );
			}

			if ( ! isset( self::$orders[ $order_id ] ) ) {
				self::$orders[ $order_id ] = wc_get_order( $order_id );
			}

			return self::$orders[ $order_id ];
		}

		public static function mnp_process_sheet_info( $sheet_infos, $sheet_key_type ) {
			$params       = array ();
			$payment_type = get_option( 'mnp_sheet_payment_type', 'naverpay' );
			foreach ( $sheet_infos as $sheet_key => &$sheet_info ) {

				$order = self::get_order( $sheet_key, $sheet_key_type );

				if ( $order && 'naverpay' == mnp_get_object_property( $order, 'payment_method' ) ) {

					foreach ( $sheet_info as &$sheet_data ) {

						$order_item_ids = array ();

						if ( 'order_id' == $sheet_key_type ) {
							$order_item_ids = array_keys( $order->get_items() );
						} else {
							$order_item_ids[] = $sheet_data['order_item_id'];
						}

						foreach ( $order_item_ids as $order_item_id ) {
							$npay_order = json_decode( wc_get_order_item_meta( $order_item_id, '_npay_order', true ), true );

							if ( $npay_order && empty( $npay_order['Delivery'] ) ) {
								$product_order_id = $npay_order['ProductOrder']['ProductOrderID'];

								$params[ $product_order_id ] = array (
									'delivery_method_code'  => 'DELIVERY',
									'delivery_company_code' => $sheet_data['dlv_company_code'],
									'tracking_number'       => $sheet_data['sheet_no'],
									'dispatch_date'         => gmdate( 'Y-m-d\TH:i:s' ) . date( 'P' ),
									'sheet_key'             => $sheet_key,
									'order_id'              => mnp_get_object_property( $order, 'id' ),
									'order_item_id'         => $order_item_id
								);
							} else {
								$sheet_data['valid'] = false;
							}
						}
					}
				} else if ( 'naverpay' == $payment_type ) {
					foreach ( $sheet_info as &$sheet_data ) {
						$sheet_data['valid'] = false;
					}
				}
			}

			if ( ! empty( $params ) ) {
				$bulk_request_params = array (
					'command' => 'bulk_ship_product_order',
					'params'  => $params,
				);

				// call npay api
				$response = MNP_API::call( http_build_query( array_merge( MNP_Manager::default_args(), $bulk_request_params ) ) );

				// process result
				if ( $response && property_exists( $response, 'success' ) && property_exists( $response, 'error' ) ) {
					$success_count = count( (array) $response->success );
					$error_count   = count( (array) $response->error );

					if ( $success_count > 0 ) {
						foreach ( $response->success as $product_order_id => $npay_order ) {
							$params[ $product_order_id ]['success'] = true;

							$order_item_id = $params[ $product_order_id ]['order_item_id'];

							wc_update_order_item_meta( $order_item_id, '_npay_order', json_encode( $npay_order, JSON_UNESCAPED_UNICODE ) );

						}
					}

					if ( $error_count > 0 ) {
						foreach ( $response->error as $product_order_id => $error ) {
							$sheet_key = $params[ $product_order_id ]['sheet_key'];
							$params[ $product_order_id ]['success'] = false;
							$params[ $product_order_id ]['error']   = sprintf( __( '%s, %s, %s', 'mshop-npay' ), $product_order_id, $error->Code, $error->Message );

							foreach ( $sheet_infos[ $sheet_key ] as &$sheet_data ) {
								$sheet_data['valid'] = false;
							}
						}
					}
				} else {
					throw new Exception( __( '네이버페이 배송처리중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 'mshop-npay' ) );
				}
				$results = array ();
				foreach ( $params as $product_order_id => $param ) {

					$order_id = $param['order_id'];

					if ( empty( $results[ $order_id ] ) ) {
						$results[ $order_id ] = array (
							'success' => array (),
							'error'   => array ()
						);
					}

					if ( $param['success'] ) {
						$results[ $order_id ]['success'][] = $product_order_id;
					} else {
						$results[ $order_id ]['error'][] = $param['error'];
					}
				}

				foreach ( $results as $order_id => $result ) {
					$order = self::$orders[ $order_id ];

					if ( ! empty( $results[ $order_id ]['success'] ) ) {
						$order->add_order_note( sprintf( __( '<span style="font-size: 0.85em">[NPay] 배송처리 완료 [%s]</span>', 'mshop-npay' ), implode( ',', $results[ $order_id ]['success'] ) ) );
					} else {
						$order->add_order_note( sprintf( __( '<span style="font-size: 0.85em">[NPay] 배송처리 오류<br>%s</span>', 'mshop-npay' ), implode( '<br>', $results[ $order_id ]['error'] ) ) );
					}
				}
				self::update_order_status( $params );
			}

			return $sheet_infos;
		}
		static function update_order_status( $params ) {
			$to_state = get_option( 'mnp_order_status_after_shipping', 'wc-shipping' );

			$order_ids = array ();
			foreach ( $params as $param ) {
				if ( empty( $param['error'] ) && ! empty( $param['success'] ) ) {
					$order_ids[] = $param['order_id'];
				}
			}
			$order_ids = array_unique( $order_ids );

			foreach ( $order_ids as $order_id ) {
				$shipped = true;
				$order   = wc_get_order( $order_id );
				foreach ( $order->get_items() as $order_item_id => $order_item ) {
					if ( is_array( $order_item ) ) {
						$npay_order = json_decode( wc_get_order_item_meta( $order_item_id, '_npay_order', true ) );
					} else {
						$npay_order = json_decode( $order_item->get_meta( '_npay_order', true ) );
					}
					if ( is_null( $npay_order ) || !property_exists( $npay_order, 'Delivery' ) ) {
						$shipped = false;
						break;
					}
				}

				if ( $shipped ) {
					$order->update_status( $to_state );
				}

			}
		}
		static function mnp_sheet_update_order_status( $flag, $order ) {

			if ( $order && 'naverpay' == mnp_get_object_property( $order, 'payment_method' ) ) {
				$flag = false;
			}

			return $flag;
		}
		static function mnp_bulk_ship_order( $sheet_datas ) {
			$params = array ();

			foreach ( $sheet_datas as $sheet_data ) {
				$npay_order = json_decode( wc_get_order_item_meta( $sheet_data['order_item_id'], '_npay_order', true ), true );

				if ( $npay_order && empty( $npay_order['Delivery'] ) ) {
					$product_order_id = $npay_order['ProductOrder']['ProductOrderID'];

					$params[ $product_order_id ] = array (
						'delivery_method_code'  => 'DELIVERY',
						'delivery_company_code' => $sheet_data['dlv_company_code'],
						'tracking_number'       => $sheet_data['sheet_no'],
						'dispatch_date'         => gmdate( 'Y-m-d\TH:i:s' ) . date( 'P' ),
						'order_id'              => $sheet_data['order_id'],
						'order_item_id'         => $sheet_data['order_item_id']
					);
				}
			}

			if ( ! empty( $params ) ) {
				$bulk_request_params = array (
					'command' => 'bulk_ship_product_order',
					'params'  => $params,
				);

				// call npay api
				$response = MNP_API::call( http_build_query( array_merge( MNP_Manager::default_args(), $bulk_request_params ) ) );

				// process result
				if ( $response && property_exists( $response, 'success' ) && property_exists( $response, 'error' ) ) {
					$success_count = count( (array) $response->success );
					$error_count   = count( (array) $response->error );

					if ( $success_count > 0 ) {
						foreach ( $response->success as $product_order_id => $npay_order ) {
							$params[ $product_order_id ]['success'] = true;

							$order_item_id = $params[ $product_order_id ]['order_item_id'];

							wc_update_order_item_meta( $order_item_id, '_npay_order', json_encode( $npay_order, JSON_UNESCAPED_UNICODE ) );

						}
					}

					if ( $error_count > 0 ) {
						foreach ( $response->error as $product_order_id => $error ) {
							$params[ $product_order_id ]['success'] = false;
							$params[ $product_order_id ]['error']   = sprintf( __( '%s, %s, %s', 'mshop-npay' ), $product_order_id, $error->Code, $error->Message );
						}
					}
				} else {
					throw new Exception( __( '네이버페이 배송처리중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 'mshop-npay' ) );
				}
				$results = array ();
				foreach ( $params as $product_order_id => $param ) {

					$order_id = $param['order_id'];

					if ( empty( $results[ $order_id ] ) ) {
						$results[ $order_id ] = array (
							'success' => array (),
							'error'   => array ()
						);
					}

					if ( $param['success'] ) {
						$results[ $order_id ]['success'][] = $product_order_id;
					} else {
						$results[ $order_id ]['error'][] = $param['error'];
					}
				}

				foreach ( $results as $order_id => $result ) {
					if ( ! isset( self::$orders[ $order_id ] ) ) {
						self::$orders[ $order_id ] = wc_get_order( $order_id );
					}

					$order = self::$orders[ $order_id ];

					if ( ! empty( $results[ $order_id ]['success'] ) ) {
						$order->add_order_note( sprintf( __( '<span style="font-size: 0.85em">[NPay] 배송처리 완료 [%s]</span>', 'mshop-npay' ), implode( ',', $results[ $order_id ]['success'] ) ) );
					} else {
						$order->add_order_note( sprintf( __( '<span style="font-size: 0.85em">[NPay] 배송처리 오류<br>%s</span>', 'mshop-npay' ), implode( '<br>', $results[ $order_id ]['error'] ) ) );
					}
				}
				self::update_order_status( $params );
			}
		}
	}

}

