<?php



if ( ! class_exists( 'MNP_Cart' ) ) {
	class MNP_Cart {

		static $shippingPolicy = null;

		static $extra_info = array ();

		static function init_extra_info( $type ) {
			self::$extra_info = array (
				'CheckoutPlace' => $type,
				'IPAddr'        => $_SERVER['REMOTE_ADDR'],
				'UserAgent'     => $_SERVER['HTTP_USER_AGENT'],
				'IsMobile'      => wp_is_mobile(),
				'Products'      => array ()
			);
		}

		static function add_extra_product_info( $product_id, $merchant_product_id, $title, $price, $quantity ) {
			self::$extra_info['Products'][] = array (
				'ProductID'         => $product_id,
				'SellerProductCode' => $merchant_product_id,
				'ProductName'       => $title,
				'UnitPrice'         => $price,
				'Quantity'          => $quantity
			);
		}

		static function get_order_key() {
			$data = array (
				MNP_Manager::merchant_id(),
				date( 'Y-m-d H:i:s' ),
				self::$extra_info['CheckoutPlace'],
				self::$extra_info['Products']
			);

			self::$extra_info['OrderKey'] = date( 'YmdHis' ) . '_' . strtoupper( md5( json_encode( $data ) ) );

			return self::$extra_info['OrderKey'];
		}
		static function generate_product_info( $args ) {
			//  $product_id, $quantity, $variation = null
			$product_id          = $args['product_id'];
			$merchant_product_id = $args['product_id'];
			$quantity            = $args['quantity'];
			$variation           = $args['variations'];

			$wc_product = wc_get_product( $product_id );
			$price      = apply_filters( 'mnp_get_product_price', $wc_product->get_price(), $args );
			$tax_total = 0;
			if ( wc_tax_enabled() && ! wc_prices_include_tax() && $wc_product->is_taxable() ) {
				$tax_rates = WC_Tax::get_rates( $wc_product->get_tax_class() );
				// Base tax for line before discount - we will store this in the order data
				$taxes     = WC_Tax::calc_tax( $price, $tax_rates );
				$tax_total = array_sum( $taxes );
				if ( $tax_total == 0 ) {
					$tax = 'ZERO_TAX';
				} else {
					$tax = 'TAX';
				}
			} else {
				$tax = 'TAX';
			}
			if ( $wc_product->is_type( 'simple' ) ) {
				// 단순상품 정보를 생성한다.
				$single = new ProductSingle( $quantity );
				$option = null;
			} else if ( $wc_product->is_type( 'variation' ) ) {
				// 옵션상품 정보를 생성한다.
				$single        = null;
				$selectedItems = array ();

				if ( ! empty( $variation ) ) {
					$attributes = $variation;
				} else {
					$attributes = $wc_product->get_variation_attributes();
				}

				foreach ( $attributes as $key => $value ) {
					$option_id   = str_replace( 'attribute_', '', $key );
					$option_name = html_entity_decode( wc_attribute_label( $option_id ) );
					$term        = get_term_by( 'slug', $value, $option_id );
					$option_text = html_entity_decode( $term->name );

					$selectedItems[] = new ProductOptionSelectedItem( ProductOptionSelectedItem::TYPE_SELECT, $option_name, $term->slug, $option_text );
				}

				$option = new ProductOption( $quantity, 0, null, $selectedItems );
			} else {
				$single = apply_filters( 'mnp_generate_product_info_single', null, $args );
				$option = apply_filters( 'mnp_generate_product_info_option', null, $args );
			}

			$img_url = '';
			$images  = wp_get_attachment_image_src( $wc_product->get_image_id(), array ( 300, 300 ) );

			if ( ! empty( $images ) ) {
				$img_url = $images[0];
				if ( empty( $img_url ) && ! empty( $args['parent_product_id'] ) ) {
					$parent_product = wc_get_product( $args['parent_product_id'] );
					$images         = wp_get_attachment_image_src( $parent_product->get_image_id(), array ( 300, 300 ) );
					if ( ! empty( $images ) ) {
						$img_url = $images[0];
					}
				}
				if ( 'yes' == get_option( 'mnp-force-image-url-to-http', 'yes' ) ) {
					$img_url = preg_replace( "/^https:/i", "http:", $img_url );
				}
			}

			$img_url = apply_filters( 'mnp_product_image_url', $img_url, $product_id );

			if ( empty( $img_url ) ) {
				wp_send_json_error( array ( 'message' => '상품 이미지가 없습니다.' ) );
			}

			$product_id = apply_filters( 'mnp_product_id', $product_id, $args );
			$merchant_product_id = apply_filters( 'mnp_merchant_product_id', $merchant_product_id, $args );

			self::add_extra_product_info(
				$product_id,
				$merchant_product_id,
				html_entity_decode( $wc_product->get_title() ),
				floatval( $price ) + $tax_total,
				$quantity
			);

			$supplements = apply_filters( 'mnp_supplements', array (), $args );
			return new Product(
				$product_id, /** 상품 번호 */
				$merchant_product_id, /** 가맹점 상품 번호 */
				apply_filters( 'mnks_ecmall_product_id', null, $product_id ), /** 지식쇼핑 EP의 Mall_pid */
				html_entity_decode( $wc_product->get_title() ), /** 상품명 */
				floatval( $price ) + $tax_total, /** 상품가격 */
				$tax, /** 세금종류 */
				$wc_product->get_permalink(), /** 상품 URL */
				$img_url, /** 상품 Thumbnail URL */
				null, /** giftName */
				$single, /** 단순 상품 정보 */
				$option, /** 옵션 상품 정보 */
				self::$shippingPolicy/** 배송 정책 */,
				$supplements
			);
		}
		public static function checkout_cart() {
			do_action( 'mnp_before_checkout_cart' );

			add_filter( 'mshop_membership_skip_filter', '__return_false' );

			MNP()->define( 'WOOCOMMERCE_CART', true );

			self::init_extra_info( 'cart' );

			$support_product_types = apply_filters( 'mnp_support_product_types', array ( 'variable', 'variation' ) );

			include_once( 'naverpay/Order.php' );

			self::$shippingPolicy = MNP_Shipping::get_shipping_policy( WC()->cart );

			$products = array ();
			wc_clear_notices();

			if ( ! WC()->cart->check_cart_items() ) {
				$msg = implode( ', ', wc_get_notices( 'error' ) );
				wp_send_json_error( array ( 'message' => htmlspecialchars_decode( strip_tags( $msg ) ) ) );
			}

			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

				if ( $values['variation_id'] ) {
					$product_id = $values['variation_id'];
					$variation  = $values['variation'];
				} else {
					$product_id = $values['product_id'];
					$variation  = null;
				}

				$wc_product = wc_get_product( $product_id );

				if ( MNP_Manager::is_purchasable( $values['product_id'] ) && MNP_Manager::is_purchasable( $product_id ) && $wc_product->is_in_stock() && $wc_product->has_enough_stock( $values['quantity'] ) && $wc_product->is_purchasable() && ! $wc_product->is_virtual() && ! $wc_product->is_downloadable() &&
				     ( in_array( $wc_product->get_type(), $support_product_types ) || ( $wc_product->is_type( 'simple' ) && $wc_product->get_price() > 0 ) )
				) {
					$products[] = self::generate_product_info( array (
						'product_id'     => $product_id,
						'quantity'       => $values['quantity'],
						'variations'     => $variation,
						'cart_item_data' => apply_filters( 'mnp_get_product_cart_item_data', array (), $values )
					) );
				}
			}
			if ( 0 == count( $products ) ) {
				wp_send_json_error( array ( 'message' => '네이버페이로 구매가능한 상품이 없습니다.' ) );
			}
			$custom_data = apply_filters( 'mnp_custom_order_data', array ( 'order_key' => self::get_order_key() ) );
			$order       = new Order( $products, $_SERVER['HTTP_REFERER'], $custom_data );
			$data        = MNP_XMLSerializer::generateValidXmlFromObj( json_decode( json_encode( $order ) ), 'order' );

			$result   = MNP_API::register_order( $data, json_encode( self::$extra_info ) );
			$response = $result->response;

			do_action( 'mnp_after_checkout_cart' );

			if ( $response->ResponseType == "SUCCESS" ) {
				wp_send_json_success( array ( 'authkey' => $response->AuthKey, 'shopcode' => $response->ShopCode ) );
			} else {
				wp_send_json_error( array ( 'message' => $response->Error->Message ) );
			}
		}

		protected static function get_options( $variation_id, $variations ) {
			$wc_product = wc_get_product( $variation_id );

			if ( is_product( $wc_product ) && $wc_product->is_type( 'variation' ) ) {
				// 옵션상품 정보를 생성한다.
				$single          = null;
				$selectedOptions = array ();

				if ( ! empty( $variation ) ) {
					$attributes = $variation;
				} else {
					$attributes = $wc_product->get_variation_attributes();
				}

				foreach ( $attributes as $key => $value ) {
					$option_id   = str_replace( 'attribute_', '', $key );
					$option_name = html_entity_decode( wc_attribute_label( $option_id ) );
					$term        = get_term_by( 'slug', $value, $option_id );
					$option_text = html_entity_decode( $term->name );

					$selectedOptions[] = $option_name . ' : ' . $option_text;
				}

				return ' ( ' . implode( ', ', $selectedOptions ) . ' )';
			}
		}
		public static function create_order() {
			include_once( 'naverpay/Order.php' );

			add_filter( 'mshop_membership_skip_filter', '__return_false' );

			MNP()->define( 'WOOCOMMERCE_CART', true );

			self::init_extra_info( 'product' );

			wc_clear_notices();

			self::backup_cart();

			foreach ( $_REQUEST['products'] as $product_info ) {
				$product_id     = ! empty( $product_info['parent_product_id'] ) ? $product_info['parent_product_id'] : $product_info['product_id'];
				$variation_id   = ! empty( $product_info['parent_product_id'] ) ? $product_info['product_id'] : 0;
				$variations     = apply_filters( 'mnp_get_product_variations', $product_info['attributes'], $product_info );
				$cart_item_data = apply_filters( 'mnp_get_product_cart_item_data', array (), $product_info );
				WC()->cart->add_to_cart( $product_id, $product_info['quantity'], $variation_id, $variations, $cart_item_data );

				if ( wc_notice_count( 'error' ) > 0 ) {
					self::recover_cart();

					$notices = wc_get_notices( 'error' );
					wc_clear_notices();

					$options = self::get_options( $variation_id, $variations );

					wp_send_json_error( array ( 'message' => htmlspecialchars_decode( strip_tags( implode( "\n", $notices ) ) ) . $options ) );
				}
			}
			WC()->cart->calculate_totals();

			do_action( 'woocommerce_check_cart_items' );

			if ( wc_notice_count( 'error' ) > 0 ) {
				self::recover_cart();

				$notices = wc_get_notices( 'error' );
				wc_clear_notices();

				wp_send_json_error( array ( 'message' => htmlspecialchars_decode( strip_tags( implode( "\n", $notices ) ) ) ) );
			}
			self::$shippingPolicy = MNP_Shipping::get_shipping_policy( WC()->cart );
			$product = array ();

			foreach ( $_REQUEST['products'] as $product_info ) {
				$variations     = apply_filters( 'mnp_get_product_variations', $product_info['attributes'], $product_info );
				$cart_item_data = apply_filters( 'mnp_get_product_cart_item_data', array (), $product_info );
				$product[]      = self::generate_product_info( array (
					'product_id'        => $product_info['product_id'],
					'parent_product_id' => $product_info['parent_product_id'],
					'quantity'          => $product_info['quantity'],
					'variations'        => $variations,
					'cart_item_data'    => $cart_item_data
				) );
			}

			$npay_order_key = self::get_order_key();
			self::recover_cart();
			$custom_data = apply_filters( 'mnp_custom_order_data', array ( 'order_key' => $npay_order_key ) );
			$order       = new Order( $product, $_SERVER['HTTP_REFERER'], $custom_data );
			$data        = MNP_XMLSerializer::generateValidXmlFromObj( json_decode( json_encode( $order ) ), 'order' );

			$result   = MNP_API::register_order( $data, json_encode( self::$extra_info ) );
			$response = $result->response;

			if ( $response->ResponseType == "SUCCESS" ) {
				wp_send_json_success( array ( 'authkey' => $response->AuthKey, 'shopcode' => $response->ShopCode ) );
			} else {
				wp_send_json_error( array ( 'message' => $response->Error->Message ) );
			}
		}
		public static function add_to_wishlist() {
			global $wishlistItemId;

//			$product_id = $_POST['product_id'];
//			$wc_product = wc_get_product( $product_id );

			$queryString = 'SHOP_ID=' . urlencode( MNP_Manager::merchant_id() );
			$queryString .= '&CERTI_KEY=' . urlencode( MNP_Manager::auth_key() );

			foreach ( $_REQUEST['products'] as $product_info ) {
				$wc_product = wc_get_product( $product_info['product_id'] );

				$img_url = wp_get_attachment_image_src( $wc_product->get_image_id(), array ( 300, 300 ) )[0];
				if ( 'yes' == get_option( 'mnp-force-image-url-to-http', 'yes' ) ) {
					$img_url = preg_replace( "/^https:/i", "http:", $img_url );
				}

				$queryString .= '&ITEM_ID=' . urlencode( $product_info['product_id'] );
				$queryString .= '&ITEM_NAME=' . urlencode( $wc_product->get_title() );
				$queryString .= '&ITEM_DESC=' . urlencode( $wc_product->get_title() );
				$queryString .= '&ITEM_UPRICE=' . $wc_product->get_price();
				$queryString .= '&ITEM_IMAGE=' . urlencode( $img_url );
				$queryString .= '&ITEM_THUMB=' . urlencode( $img_url );
				$queryString .= '&ITEM_URL=' . urlencode( $wc_product->get_permalink() );
			}

			$ci      = curl_init();
			$headers = array ( 'Content-type: application/x-www-form-urlencoded; charset=utf-8;' );
			curl_setopt( $ci, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ci, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ci, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ci, CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ci, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
			curl_setopt( $ci, CURLOPT_URL, MNP_Manager::wishlist_url() );
			curl_setopt( $ci, CURLOPT_POST, true );
			curl_setopt( $ci, CURLOPT_TIMEOUT, 10 );
			curl_setopt( $ci, CURLOPT_POSTFIELDS, $queryString );
			curl_setopt( $ci, CURLOPT_HEADER, true );

			$response        = curl_exec( $ci );
			$header_size     = curl_getinfo( $ci, CURLINFO_HEADER_SIZE );
			$response_code   = curl_getinfo( $ci, CURLINFO_HTTP_CODE );
			$wishlistItemIds = substr( $response, $header_size );
			curl_close( $ci );

			if ( $response_code == 200 ) {
				ob_start();
				wc_get_template( 'wishlist-popup' . ( wp_is_mobile() ? '-mobile' : '' ) . '.php', array ( 'wishlistItemIds' => explode( ',', $wishlistItemIds ) ), '', MNP()->template_path() );
				$html = ob_get_clean();

				wp_send_json_success( array (
					'url'  => MNP_Manager::wishlist_url(),
					'html' => $html
				) );
			} else {
				wp_send_json_error( array ( 'message' => $queryString ) );
			}
		}
		public static function woocommerce_after_cart_table() {
			if ( MNP_Manager::is_operable() ) {
				$dependencies = array (
					'jquery',
					'jquery-ui-core',
					'jquery-ui-widget',
					'jquery-ui-mouse',
					'jquery-ui-position',
					'jquery-ui-draggable',
					'jquery-ui-resizable',
					'jquery-ui-button',
					'jquery-ui-dialog',
					'underscore'
				);

				wp_register_script( 'mshop-naverpay', MNP()->plugin_url() . '/assets/js/cart.js' );
				wp_localize_script( 'mshop-naverpay', 'mshop_naverpay', array (
					'ajax_url'                 => mnp_ajax_url( admin_url( 'admin-ajax.php', 'relative' ) ),
					'order_url_pc'             => MNP_Manager::ordersheet_url( 'pc' ),
					'order_url_mobile'         => MNP_Manager::ordersheet_url( 'mobile' ),
					'button_js_url_pc'         => MNP_Manager::button_js_url( 'pc' ),
					'button_js_url_mobile'     => MNP_Manager::button_js_url( 'mobile' ),
					'wishlist_url'             => MNP_Manager::wishlist_url(),
					'button_key'               => MNP_Manager::button_auth_key(),
					'button_type_pc'           => MNP_Manager::button_type_pc(),
					'button_type_mobile'       => MNP_Manager::button_type_mobile(),
					'button_color'             => MNP_Manager::button_color(),
					'checkout_cart_action'     => MNP()->slug() . '-checkout_cart',
					'hide_if_not_purchaseable' => apply_filters( 'mnp_hide_if_not_purchaseable', 'no' == get_option( 'mshop-naverpay-always-show-button', 'no' ) ),
					'transition_mode'          => get_option( 'mnp-cart-page-transition-mode', 'new-window' ),
				) );
				wp_enqueue_script( 'underscore' );
				wp_enqueue_script( 'mshop-naverpay' );
				wp_enqueue_script( 'jquery-block-ui', MNP()->plugin_url() . '/assets/js/jquery.blockUI.js', $dependencies );

				wp_register_style( 'mshop-naverpay', MNP()->plugin_url() . '/assets/css/naverpay-cart.css' );
				wp_enqueue_style( 'mshop-naverpay' );

				wc_get_template( 'cart/naverpay-button.php', array (), '', MNP()->template_path() );
			}
		}
		public static function woocommerce_after_add_to_cart_form() {
			$support_product_types = apply_filters( 'mnp_support_product_types', array ( 'variable' ) );

			$product_id = get_the_ID();

			if ( MNP_Manager::is_operable() && MNP_Manager::is_purchasable( $product_id ) ) {
				$product = wc_get_product( $product_id );

				if ( $product->is_purchasable() && ! $product->is_virtual() && ! $product->is_downloadable() &&
				     ( in_array( $product->get_type(), $support_product_types ) || ( $product->is_type( 'simple' ) && $product->get_price() > 0 ) )
				) {
					$dependencies = apply_filters( 'mnp_script_dependencies', array (
						'jquery',
						'jquery-ui-core',
						'jquery-ui-widget',
						'jquery-ui-mouse',
						'jquery-ui-position',
						'jquery-ui-draggable',
						'jquery-ui-resizable',
						'jquery-ui-button',
						'jquery-ui-dialog',
						'underscore'
					) );

					wp_register_script( 'mshop-naverpay', MNP()->plugin_url() . '/assets/js/frontend.js', $dependencies );
					wp_localize_script( 'mshop-naverpay', 'mshop_naverpay', array (
						'ajax_url'                 => mnp_ajax_url( admin_url( 'admin-ajax.php', 'relative' ) ),
						'order_url_pc'             => MNP_Manager::ordersheet_url( 'pc' ),
						'order_url_mobile'         => MNP_Manager::ordersheet_url( 'mobile' ),
						'button_js_url_pc'         => MNP_Manager::button_js_url( 'pc' ),
						'button_js_url_mobile'     => MNP_Manager::button_js_url( 'mobile' ),
						'wishlist_url'             => MNP_Manager::wishlist_url(),
						'button_key'               => MNP_Manager::button_auth_key(),
						'button_type_pc'           => MNP_Manager::button_type_pc(),
						'button_type_mobile'       => MNP_Manager::button_type_mobile(),
						'button_color'             => MNP_Manager::button_color(),
						'create_order_action'      => MNP()->slug() . '-create_order',
						'add_to_wishlist_action'   => MNP()->slug() . '-add_to_wishlist',
						'hide_if_not_purchaseable' => apply_filters( 'mnp_hide_if_not_purchaseable', 'no' == get_option( 'mshop-naverpay-always-show-button', 'no' ) ),
						'wrapper_selector'         => get_option( 'mnp-wrapper-selector', 'div[itemtype="http://schema.org/Product"]' ),
						'product_simple_class'     => get_option( 'mnp-simple-class', 'product-type-simple' ),
						'product_variable_class'   => get_option( 'mnp-variable-class', 'product-type-variable' ),
						'transition_mode'          => get_option( 'mnp-product-page-transition-mode', 'new-window' ),
					) );

					wp_enqueue_script( 'underscore' );
					wp_enqueue_script( 'mshop-naverpay' );
					wp_enqueue_script( 'jquery-block-ui', MNP()->plugin_url() . '/assets/js/jquery.blockUI.js', $dependencies );

					wp_register_style( 'mshop-naverpay', MNP()->plugin_url() . '/assets/css/naverpay-product.css' );
					wp_enqueue_style( 'mshop-naverpay' );

					wc_get_template( 'single-product/naverpay-button.php', array (), '', MNP()->template_path() );
				}
			}
		}
		public static function wc_validate() {
			if ( empty( WC()->session ) ) {
				include_once( WC()->plugin_path() . '/includes/abstracts/abstract-wc-session.php' );
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				WC()->session  = new $session_class();
			}

			if ( empty( WC()->cart ) ) {
				WC()->cart = new WC_Cart();
			}

			if ( empty( WC()->customer ) ) {
				WC()->customer = new WC_Customer();
			}
		}
		public static function backup_cart( $clear = true ) {

			self::wc_validate();

			$cart = WC()->session->get( 'cart', null );
			WC()->session->set( 'mnp-cart', $cart );

			if ( $clear ) {
				WC()->cart->empty_cart( false );
				WC()->session->set( 'cart', array () );
				WC()->cart->get_cart_from_session();
			}
		}
		public static function recover_cart() {
			$cart = WC()->session->get( 'mnp-cart', null );

			WC()->cart->empty_cart( false );
			WC()->session->set( 'cart', $cart );
			WC()->cart->get_cart_from_session();

			WC()->session->set( 'mnp-cart', null );
		}
		public static function generate_cart( $npay_orders, $cart = null ) {

			if ( is_null( $cart ) ) {
				$cart = WC()->cart;
			}

			$cart->empty_cart( false );

			foreach ( $npay_orders as $npay_order ) {
				if ( property_exists( $npay_order->ProductOrder, 'OptionManageCode' ) ) {
					$product_id = apply_filters( 'mnp_get_product_id_from_option_manage_code', $npay_order->ProductOrder->OptionManageCode, $npay_order );
				} else {
					$product_id = $npay_order->ProductOrder->SellerProductCode;
				}

				$product = wc_get_product( $product_id );

				if ( $product ) {
					if ( $product->is_type( 'variation' ) ) {
						if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
							$product_id   = $product->get_parent_id();
							$variation_id = $product->get_id();
						} else {
							$product_id   = $product->get_id();
							$variation_id = $product->variation_id;
						}
					} else {
						$product_id   = $product->get_id();
						$variation_id = '';
					}

					$np_product_id = $npay_order->ProductOrder->ProductID;
					$quantity      = $npay_order->ProductOrder->Quantity;
					$variations = array ();
					if ( ! empty( $npay_order->ProductOrder->ProductOption ) ) {
						$options = explode( '/', $npay_order->ProductOrder->ProductOption );
						foreach ( $options as $option ) {
							$values                           = explode( ':', $option );
							$variations[ trim( $values[0] ) ] = trim( $values[1] );
						}
					}

					$product_info = array (
						'product_id'    => $product_id,
						'np_product_id' => $np_product_id,
						'price'         => apply_filters( 'mnp_get_product_price_by_id', $product->get_price(), $np_product_id, $product_id, $npay_order )
					);

					$variations     = apply_filters( 'mnp_get_product_variations', $variations, $product_info, $npay_order );
					$cart_item_data = apply_filters( 'mnp_get_product_cart_item_data', array (
						'_npay_product_order_id'     => $npay_order->ProductOrder->ProductOrderID,
						'_npay_product_order_status' => $npay_order->ProductOrder->ProductOrderStatus,
						'_npay_order'                => json_encode( $npay_order, JSON_UNESCAPED_UNICODE )
					), $product_info, $npay_order );

					$cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );
				}
			}
		}
	}
}

