<?php



if ( ! class_exists( 'MNP_Sheets' ) ) {

	class MNP_Sheets {

		static $reserved_fields = array ();
		static $custom_fields = array ();
		static $field_types = array ();
		static $field_names = array ();

		static $sheet_order_field_type = null;
		static $sheet_info_key = '';

		static $sheet_infos = array ();

		static $order_item_meta_keys = array ();

		public static function reset_sheet_fields() {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				delete_option( 'mnp_sheet_fields' );
				wp_send_json_success( array ( 'message' => __( 'CSV 필드 설정이 초기화되었습니다.', 'mshop-npay' ), 'reload' => true ) );
			}

			wp_send_json_error();
		}

		public static function get_sheet_order_field_type() {
			self::load_sheet_fields();

			return self::$sheet_info_key;
		}

		public static function get_reserved_fields() {
			self::load_sheet_fields();

			return self::$reserved_fields;
		}

		public static function get_field_key( $field ) {
			self::load_sheet_fields();

			if ( 'order' == self::$sheet_order_field_type ) {
				self::$reserved_fields[ $field ]['order_meta_key'];
			} else {
				self::$reserved_fields[ $field ]['order_item_meta_key'];
			}
		}

		public static function get_sheet_field_data( $field, $item_id, $item, $order ) {
			$results = array ();

			if ( in_array( $field, array ( 'sheet_no', 'dlv_company_name' ) ) ) {
				$fields = self::$reserved_fields;
			} else {
				$fields = self::$custom_fields;
			}

			if ( isset( $fields[ $field ] ) ) {
				if ( 'order' == self::$sheet_order_field_type ) {
					$label = $fields[ $field ]['name'];
					$value = mnp_get_meta( $order, $fields[ $field ]['order_item_meta_key'], true );
				} else {
					$label = $fields[ $field ]['name'];
					$value = wc_get_order_item_meta( $item_id, $fields[ $field ]['order_meta_key'], true );
				}

				if ( ! empty( $value ) ) {
					$results[ $label ] = $value;
				}
			}

			return apply_filters( 'mnp_get_sheet_field_data', $results, $field, $item_id, $item, $order );
		}

		public static function get_sheet_data( $item_id, $item, $order ) {
			self::load_sheet_fields();

			$results = self::get_sheet_field_data( 'sheet_no', $item_id, $item, $order );
			if ( ! empty( $results ) ) {
				$dlv_company_name = self::get_sheet_field_data( 'dlv_company_name', $item_id, $item, $order );
				$results          = array_merge( $results, $dlv_company_name );
			}

			foreach ( self::$custom_fields as $key => $field ) {
				$data = self::get_sheet_field_data( $key, $item_id, $item, $order );
				if ( ! empty( $data ) ) {
					$results = array_merge( $results, $data );
				}
			}

			return array_filter( $results );
		}

		public static function upload_sheets() {
			try {
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					throw new Exception( __( '사용 권한이 없습니다.', 'mshop-npay' ) );
				}

				if ( count( $_FILES ) <= 0 ) {
					throw new Exception( __( 'CSV 파일 정보가 없습니다.', 'mshop-npay' ) );
				}

				self::load_sheet_fields();

				$file = current( $_FILES );

				$sheet_infos = self::parse_sheet_info( $file['tmp_name'] );

				if ( empty( $sheet_infos ) ) {
					throw new Exception( __( '송장 정보가 없습니다.', 'mshop-npay' ) );
				}

				self::process_sheet_info( $sheet_infos );

				wp_send_json_success( array ( 'message' => '송장 정보가 정상적으로 등록되었습니다.' ) );

			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}
		protected static function process_sheet_info( $sheet_infos ) {
			$order_ids = array ();

			$sheet_infos = apply_filters( 'mnp_process_sheet_info', $sheet_infos, self::$sheet_info_key );

			foreach ( $sheet_infos as $sheet_no => $sheet_datas ) {

				foreach ( $sheet_datas as &$sheet_data ) {

					if ( $sheet_data['valid'] ) {
						if ( ( 'order_item' == self::$sheet_order_field_type || 'all' == self::$sheet_order_field_type ) && ! empty( $sheet_data['order_item_id'] ) ) {

							wc_update_order_item_meta( $sheet_data['order_item_id'], self::$reserved_fields['sheet_no']['order_item_meta_key'], $sheet_data['sheet_no'] );

							if ( ! empty( $sheet_data['dlv_company_code'] ) ) {
								wc_update_order_item_meta( $sheet_data['order_item_id'], self::$reserved_fields['dlv_company_code']['order_item_meta_key'], $sheet_data['dlv_company_code'] );
							}

							if ( ! empty( $sheet_data['dlv_company_name'] ) ) {
								wc_update_order_item_meta( $sheet_data['order_item_id'], self::$reserved_fields['dlv_company_name']['order_item_meta_key'], $sheet_data['dlv_company_name'] );
							}

							foreach ( self::$custom_fields as $key => $field ) {
								if ( ! empty( $sheet_data['custom'][ $key ] ) ) {
									wc_update_order_item_meta( $sheet_data['order_item_id'], $field['order_item_meta_key'], $sheet_data['custom'][ $key ] );
								}
							}
							if ( empty( $sheet_data['order_id'] ) ) {
								$sheet_data['order_id'] = mnp_get_order_id_by_order_item_id( $sheet_data['order_item_id'] );
							}

							$order_ids[] = $sheet_data['order_id'];
						}
						if ( ( 'order' == self::$sheet_order_field_type || 'all' == self::$sheet_order_field_type ) && ! empty( $sheet_data['order_id'] ) ) {

							$order = wc_get_order( $sheet_data['order_id'] );

							if ( $order instanceof WC_Abstract_Order ) {
								$order_ids[] = $sheet_data['order_id'];

								mnp_update_meta_data( $order, self::$reserved_fields['sheet_no']['order_meta_key'], $sheet_data['sheet_no'] );

								if ( ! empty( $sheet_data['dlv_company_code'] ) ) {
									mnp_update_meta_data( $order, self::$reserved_fields['dlv_company_code']['order_meta_key'], $sheet_data['dlv_company_code'] );
								}

								if ( ! empty( $sheet_data['dlv_company_name'] ) ) {
									mnp_update_meta_data( $order, self::$reserved_fields['dlv_company_name']['order_meta_key'], $sheet_data['dlv_company_name'] );
								}

								foreach ( self::$custom_fields as $key => $field ) {
									if ( ! empty( $sheet_data['custom'][ $key ] ) ) {
										wc_update_order_item_meta( $sheet_data['order_item_id'], $field['order_item_meta_key'], $sheet_data['custom'][ $key ] );
										mnp_update_meta_data( $order, $field['order_meta_key'], $sheet_data['custom'][ $key ] );
									}
								}
							}
						}
					}
				}

			}

			$order_ids = array_unique( $order_ids );
			self::update_order_status( $order_ids );

		}
		static function update_order_status( $order_ids ) {
			$to_state = get_option( 'mnp_order_status_after_shipping', 'wc-shipping' );

			foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );

				if ( apply_filters( 'mnp_sheet_update_order_status', true, $order ) ) {
					$shipped = true;

					if ( ( 'order_item' == self::$sheet_order_field_type ) ) {
						foreach ( $order->get_items() as $order_item_id => $order_item ) {
							if ( is_array( $order_item ) ) {
								$sheet_no = wc_get_order_item_meta( $order_item_id, self::$reserved_fields['sheet_no']['order_meta_key'], true );
							} else {
								$sheet_no = $order_item->get_meta( self::$reserved_fields['sheet_no']['order_meta_key'] );
							}

							if ( empty( $sheet_no ) ) {
								$shipped = false;
								break;
							}
						}
					} else {
						$sheet_no = mnp_get_meta( $order, self::$reserved_fields['sheet_no']['order_meta_key'] );

						if ( empty( $sheet_no ) ) {
							$shipped = false;
						}
					}

					if ( $shipped ) {
						$order->update_status( $to_state );
					}
				}
			}
		}
		protected static function load_sheet_fields() {
			if ( empty( self::$sheet_info_key ) ) {
				$sheet_fields          = get_option( 'mnp_sheet_fields', MNP_Settings_Sheet::get_default_sheet_fields() );
				self::$reserved_fields = array ();
				self::$custom_fields   = array ();

				foreach ( $sheet_fields as $sheet_field ) {
					if ( 'custom' == $sheet_field['type'] ) {
						self::$custom_fields[ $sheet_field['name'] ] = $sheet_field;
						self::$field_types[ $sheet_field['name'] ]   = 'custom';
					} else {
						self::$reserved_fields[ $sheet_field['type'] ] = $sheet_field;
						self::$field_types[ $sheet_field['name'] ]     = $sheet_field['type'];
					}

					self::$field_names[] = $sheet_field['name'];
				}

				self::$sheet_order_field_type = get_option( 'mnp_sheet_order_field_type', 'order_item' );

				if ( 'order' == self::$sheet_order_field_type ) {
					self::$sheet_info_key = 'order_id';
				} else {
					self::$sheet_info_key = 'order_item_id';
				}
			}
		}

		protected static function get_order_item_meta_keys() {
			if ( empty( self::$order_item_meta_keys ) ) {
				self::load_sheet_fields();
				self::$order_item_meta_keys = wp_list_pluck( self::$reserved_fields, 'order_item_meta_key' );
			}

			return self::$order_item_meta_keys;
		}
		protected static function parse_sheet_info( $filename ) {
			$sheet_infos = array ();

			require_once( MNP()->plugin_path() . '/lib/csv/class-readcsv.php' );

			// Loop through the file lines
			$file_handle = fopen( $filename, 'r' );
			$csv_reader  = new ReadCSV( $file_handle, ',', "\xEF\xBB\xBF" ); // Skip any UTF-8 byte order mark.

			$rownum         = 1;
			$column_headers = array ();
			while ( ( $line = $csv_reader->get_row() ) !== null ) {

				if ( empty( $line ) ) {
					if ( 1 == $rownum ) {
						throw new Exception( __( 'CSV 파일에 컬럼 정보가 없습니다.', 'mshop-npay' ) );
						break;
					} else {
						continue;
					}
				}

				if ( 1 == $rownum ) {
					$rownum ++;
					$column_headers = $line;
					continue;
				}

				$line_data = array (
					'valid' => true
				);
				foreach ( $line as $ckey => $column ) {
					$column_name = $column_headers[ $ckey ];
					$column      = trim( $column );

					if ( in_array( $column_name, self::$field_names ) ) {
						$type = self::$field_types[ $column_name ];

						if ( 'custom' == $type ) {
							$line_data['custom'][ $column_name ] = $column;
						} else {
							$line_data[ $type ] = $column;
						}
					}
				}
				if ( empty( $line_data['sheet_no'] ) ) {
					throw new Exception( sprintf( __( '오류 (%d행) : 송장번호가 없습니다. ', 'mshop-npay' ), $rownum ) );
				}

				if ( 'order_item' == self::$sheet_order_field_type && empty( $line_data['order_item_id'] ) ) {
					throw new Exception( sprintf( __( '오류 (%d행) : 주문아이템 번호가 없습니다.', 'mshop-npay' ), $rownum ) );
				}

				if ( 'order' == self::$sheet_order_field_type && empty( $line_data['order_id'] ) ) {
					throw new Exception( sprintf( __( '오류 (%d행) : 주문 번호가 없습니다.', 'mshop-npay' ), $rownum ) );
				}

				if ( 'all' == self::$sheet_order_field_type && empty( $line_data['order_id'] ) && empty( $line_data['order_item_id'] ) ) {
					throw new Exception( sprintf( __( '오류 (%d행) : 주문 번호 또는 주문 아이템 번호는 필수입니다.', 'mshop-npay' ), $rownum ) );
				}
				if ( empty( $sheet_infos[ $line_data[ self::$sheet_info_key ] ] ) ) {
					$sheet_infos[ $line_data[ self::$sheet_info_key ] ] = array ();
				}
				$sheet_infos[ $line_data[ self::$sheet_info_key ] ][] = $line_data;

				$rownum ++;
			}

			fclose( $file_handle );

			return $sheet_infos;
		}

		public static function woocommerce_hidden_order_itemmeta( $metas ) {
			$order = wc_get_order( get_the_ID() );

			if ( is_account_page() || ( $order && 'naverpay' == mnp_get_object_property( $order, 'payment_method' ) ) ) {
				$metas = array_merge( $metas, self::get_order_item_meta_keys() );
			}

			return $metas;
		}

		public static function woocommerce_attribute_label( $label, $name, $product ) {
			$meta_keys = self::get_order_item_meta_keys();

			if ( in_array( $name, $meta_keys ) ) {
				foreach ( self::$reserved_fields as $field ) {
					if ( $field['order_item_meta_key'] == $name ) {
						$label = $field['name'];
						break;
					}
				}
			}

			return $label;
		}
	}

}

