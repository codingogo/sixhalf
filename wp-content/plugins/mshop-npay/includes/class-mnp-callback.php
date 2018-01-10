<?php



if ( ! class_exists( 'MNP_Callback' ) ) {
	class MNP_Callback {

		private $iv_delivery_fee = 0;

		private $customer_info = array ();
		public function __construct() {
			add_action( 'parse_request', array ( $this, 'parse_request' ) );
		}
		public function parse_request() {

			$request = $_SERVER['REQUEST_URI'];
			$url     = parse_url( home_url() );

			if ( isset( $url['path'] ) ) {
				$request = str_replace( $url['path'], '', $request );
			}

			if ( 0 === strpos( $request, '/MShopIVShippingFee' ) ) {
				if ( empty( $_REQUEST['productId'] ) || ! isset( $_REQUEST['zipcode'] ) || ! isset( $_REQUEST['address1'] ) ) {
					die( __( '잘못된 요청입니다.', 'mshop-npay' ) );
				}

				$product_ids = $_REQUEST['productId'];
				$postalcode  = $_REQUEST['zipcode'];
				$address1    = $_REQUEST['address1'];

				$fee = MNP_Shipping::get_iv_shipping_fee( $product_ids, $postalcode, $address1 );

				ob_clean();
				ob_start();
				header( 'Content-Type: application/xml;charset=utf-8' );
				echo( '<?xml version="1.0" encoding="utf-8"?>' );

				include_once( 'naverpay-api/AdditionalFee.php' );
				echo '<additionalFees>';
				foreach ( $product_ids as $product_id ) {
					$additionalFee = new AdditionalFee( $product_id, $fee );
					echo MNP_XMLSerializer::generateValidXmlFromObj( json_decode( json_encode( $additionalFee ) ), 'additionalFee' );
				}
				echo '</additionalFees>';

				ob_get_flush();
				die();
			} else if ( 0 === strpos( $request, '/npay_product_info' ) ) {
				add_filter( 'mshop_membership_skip_filter', '__return_false' );
				$this->get_customer_info( ! empty( $_REQUEST['merchantCustomCode1'] ) ? $_REQUEST['merchantCustomCode1'] : '' );

				ob_clean();
				ob_start();
				header( 'Content-Type: application/xml;charset=utf-8' );
				echo( '<?xml version="1.0" encoding="utf-8"?>' );

				$products = $_REQUEST['product'];
				echo '<products>';
				foreach ( $products as $product ) {
					$product_id          = ! empty( $product['id'] ) ? $product['id'] : '';
					$merchant_product_id = ! empty( $product['merchantProductId'] ) ? $product['merchantProductId'] : '';
					$option_manage_codes = ! empty( $_REQUEST['optionManageCodes'] ) ? $_REQUEST['optionManageCodes'] : '';
					$option_search       = ! empty( $_REQUEST['optionSearch'] ) ? filter_var( $_REQUEST['optionSearch'], FILTER_VALIDATE_BOOLEAN ) : false;
					$supplement_search   = ! empty( $_REQUEST['supplementSearch'] ) ? filter_var( $_REQUEST['supplementSearch'], FILTER_VALIDATE_BOOLEAN ) : false;
					$supplementIds       = ! empty( $product['supplementIds'] ) ? $product['supplementIds'] : '';
					echo $this->get_product_info( $product_id, $merchant_product_id, $option_manage_codes, $option_search, $supplement_search, $supplementIds );
				}
				echo '</products>';

				ob_get_flush();
				die();
			} else if ( 0 === strpos( $request, '/npay_callback' ) ) {
				$this->process_callback();

				ob_clean();
				ob_start();
				echo "RESULT=TRUE";
				ob_get_flush();
				die();
			}
		}

		public function get_customer_info( $merchant_custom_code1 ) {
			$this->customer_info = array ();

			if ( ! empty( $merchant_custom_code1 ) ) {
				parse_str( $merchant_custom_code1, $this->customer_info );
			}

			if ( isset( $this->customer_info['user_id'] ) ) {
				wp_set_current_user( $this->customer_info['user_id'] );
			}

			add_filter( 'mshop_membership_skip_filter', '__return_false' );

			if ( isset( $this->customer_info['user_role'] ) ) {
				add_filter( 'mshop_membership_get_user_role', array ( $this, 'apply_membership' ), 10, 2 );
			}
		}

		public function apply_membership() {
			return $this->customer_info['user_role'];
		}
		function process_callback() {
			MNP()->define( 'WOOCOMMERCE_CART', true );

			add_filter( 'msgift_skip_processing', '__return_true' );

			$product_order_info_list = json_decode( json_encode( $_REQUEST['product_order_info_list'] ) );
			if ( ! empty( $product_order_info_list ) ) {
				$this->process_changed_product_order( $product_order_info_list );
			}
		}
		function process_changed_product_order( $product_order_info_list ) {
			foreach ( $product_order_info_list as $order_id => $product_info_list ) {
				$order_status = array_keys( wc_get_order_statuses() );
				$args         = array (
					'posts_per_page' => - 1,
					'post_type'      => 'shop_order',
					'post_status'    => $order_status,
					'meta_query'     => array (
						array (
							'key'   => '_naverpay_order_id',
							'value' => $order_id
						)
					)
				);

				$query = new WP_Query( $args );
				$posts = $query->get_posts();

				if ( count( $posts ) > 0 ) {
					$order = wc_get_order( $posts[0] );
					mnp_migrate_order( $order );
					$this->update_order( $order, $product_info_list );
				} else {
					$this->create_order( $product_info_list );
				}
			}
		}
		function update_order( $order, $npay_orders ) {
			MNP_Order::update_npay_orders( $order, $npay_orders );
		}
		function create_order( $npay_orders ) {

			try {
				do_action( 'mnp_before_create_npay_order', $npay_orders );

				MNP()->define( 'WOOCOMMERCE_CART', true );
				MNP_Cart::backup_cart();
				$order = MNP_Order::create_npay_order( $npay_orders );
				MNP_Order::remove_npay_membership_filter();
				MNP_Cart::recover_cart();

				do_action( 'mnp_after_create_npay_order', $order, $npay_orders );

			} catch ( Exception $e ) {
				ob_clean();
				ob_start();
				echo "RESULT=FALSE";
				ob_get_flush();
				die();
			}
		}

		function search_post_attribute( $post_id ) {
			$result = array ();

			$attribute_taxonomies = wc_get_attribute_taxonomies();

			foreach ( $attribute_taxonomies as $tax ) {
				$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
				$post_terms              = wp_get_post_terms( $post_id, $attribute_taxonomy_name );
				$has_terms               = ( is_wp_error( $post_terms ) || ! $post_terms || sizeof( $post_terms ) == 0 ) ? 0 : 1;

				if ( $has_terms ) {
					$result[] = $tax;
				}
			}

			return $result;
		}

		// 상품 옵션 정보를 생성한다.
		function generate_option_item( $post_parent ) {
			$productOptionItems   = array ();
			$attributes           = $this->search_post_attribute( $post_parent );
			$variation_attributes = wc_get_product( $post_parent )->get_variation_attributes();

			foreach ( $attributes as $attribute ) {
				$variation_attribute = $variation_attributes[ wc_attribute_taxonomy_name( $attribute->attribute_name ) ];
				if ( ! empty( $variation_attribute ) ) {
					$terms = get_terms( wc_attribute_taxonomy_name( $attribute->attribute_name ), 'orderby=name&hide_empty=0' );

					$productOptionItemValues = array ();
					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $variation_attribute ) ) {
							$productOptionItemValues[] = new ProductOptionItemValue( $term->slug, html_entity_decode( $term->name ) );
						}
					}

					$productOptionItems[] = new ProductOptionItem( ProductOptionItem::TYPE_SELECT, urldecode( $attribute->attribute_label ), $productOptionItemValues );
				}
			}

			return $productOptionItems;
		}

		// 상품 조합 정보를 생성한다.
		function generate_product_combination( $post_parent, $optionManageCodes ) {
			$targetOptions = null;
			$combination   = array ();

			if ( ! empty( $optionManageCodes ) ) {
				$targetOptions = explode( ',', $optionManageCodes );
			}

			$args = array (
				'post_type'      => 'product_variation',
				'post_parent'    => $post_parent,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => - 1
			);

			$attributes = $this->search_post_attribute( $post_parent );

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {

				while ( $query->have_posts() ) {
					$query->the_post();
					$manageCode                = array ();
					$productCombinationOptions = array ();

					foreach ( $attributes as $attr ) {
						$slug = get_post_meta( get_the_ID(), 'attribute_pa_' . $attr->attribute_name, true );
						$term = get_term_by( 'slug', $slug, 'pa_' . $attr->attribute_name );

						$productCombinationOptions[] = new ProductCombinationOptions( $slug, $attr->attribute_label );
						$manageCode[]                = $slug;
					}

					$code = implode( '|', $manageCode );

					if ( empty( $targetOptions ) || in_array( $code, $targetOptions ) ) {
						$product       = wc_get_product( get_the_ID() );
						$combination[] = new ProductCombination( $code, $product->get_price(), $productCombinationOptions );
					}
				}
			}

			return $combination;
		}

		function search_product_variation( $post_parent ) {
			$args = array (
				'post_type'      => 'product_variation',
				'post_parent'    => $post_parent,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => - 1
			);

			$attributes = $this->search_post_attribute( $post_parent );

			$query = new WP_Query( $args );

			$result = array ();

			if ( $query->have_posts() ) {
				$products = array ();
				while ( $query->have_posts() ) {
					$query->the_post();
					$product['title']                            = get_the_title();
					$product['content']                          = get_the_content();
					$product['id']                               = get_the_ID();
					$product['_regular_price']                   = get_post_meta( get_the_ID(), '_regular_price', true );
					$product['_sale_price']                      = get_post_meta( get_the_ID(), '_sale_price', true );
					$product['_epm_variable_base_regular_price'] = get_post_meta( get_the_ID(), '_epm_variable_base_regular_price', true );
					$product['_epm_variable_base_sale_price']    = get_post_meta( get_the_ID(), '_epm_variable_base_sale_price', true );

					$attrs     = array ();
					$attrs_key = array ();
					foreach ( $attributes as $attr ) {
						$slug        = get_post_meta( get_the_ID(), 'attribute_pa_' . $attr->attribute_name, true );
						$term        = get_term_by( 'slug', $slug, 'pa_' . $attr->attribute_name );
						$attrs[]     = $attr->attribute_label . ' ( ' . $term->name . ' )';
						$attrs_key[] = $attr->attribute_id . '_' . $term->term_id;
					}

					$product['attributes']     = implode( ', ', $attrs );
					$product['attributes_key'] = implode( ',', $attrs_key );

					$result[] = $product;
				}
			}

			die( json_encode( $result ) );
		}

		public function get_product_info( $product_id, $merchant_product_id, $optionManageCodes, $option_search, $supplement_search, $supplementIds ) {
			include_once( 'class-mnp-xmlserializer.php' );
			include_once( 'naverpay-api/Product.php' );
			include_once( 'naverpay-api/ProductOption.php' );
			include_once( 'naverpay-api/ProductCombination.php' );

			if ( is_numeric( $merchant_product_id ) ) {
				$wc_product = wc_get_product( $merchant_product_id );
			} else {
				$wc_product = wc_get_product( $product_id );

				if( ! $wc_product ) {
					$wc_product = wc_get_product( wc_get_product_id_by_sku( $product_id ) );
				}
			}

			if ( $wc_product ) {
				// 재고 수량 계산
				if ( $wc_product->managing_stock() ) {
					if ( 'instock' === $wc_product->stock_status ) {
						$stockQuantity = $wc_product->get_total_stock();
					} else {
						$stockQuantity = 0;
					}
				} else {
					$stockQuantity = null;
				}

				// 거래 상태
				if ( 0 === $stockQuantity ) {
					$status = 'SOLD_OUT';
				} else if ( $wc_product->is_purchasable() ) {
					$status = 'ON_SALE';
				} else {
					$status = 'NOT_SALE';
				}
				$wc_cart = WC()->cart;
				WC()->cart = new WC_Cart();
				WC()->cart->add_to_cart( $wc_product->get_id(), 1 );
				WC()->cart->calculate_totals();
				$shippingPolicy = MNP_Shipping::get_shipping_policy( WC()->cart );
				WC()->cart = $wc_cart;
				WC()->cart->calculate_totals();

				if ( $wc_product->is_type( 'variation' ) ) {
					$optionSupport = 'true';
					$option_item   = null;
					$combination   = null;

					if ( $option_search ) {

						if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
							$parent_id = $wc_product->get_parent_id();
						} else {
							$parent_id = $wc_product->id;
						}

						$option_item = $this->generate_option_item( $parent_id );
						$option      = new ProductOption( $option_item, null );
					}
				} else {
					$option        = null;
					$optionSupport = 'false';

					if ( $option_search ) {
						$optionSupport = apply_filters( 'mnp_callback_product_info_option_support', false, $product_id, $merchant_product_id );
						$option_item   = apply_filters( 'mnp_callback_product_info_option', false, $product_id, $merchant_product_id );
						$option        = new ProductOption( $option_item, null );
					}
				}

				$supplements = array ();
				if ( $supplement_search && ! empty( $supplementIds ) ) {
					$supplements = apply_filters( 'mnp_callback_product_info_get_supplements', array (), $product_id, $merchant_product_id, $supplementIds );
				}

				$price = apply_filters( 'mnp_get_product_price_by_id', $wc_product->get_price(), $product_id, $merchant_product_id );

				$img_url = '';
				$images  = wp_get_attachment_image_src( $wc_product->get_image_id(), array ( 300, 300 ) );

				if ( ! empty( $images ) ) {
					$img_url = $images[0];
					if ( 'yes' == get_option( 'mnp-force-image-url-to-http', 'yes' ) ) {
						$img_url = preg_replace( "/^https:/i", "http:", $img_url );
					}
				}

				$img_url = apply_filters( 'mnp_product_image_url', $img_url, $product_id );

				$product = new Product(
					$product_id,
					$merchant_product_id,
					null,
					html_entity_decode( $wc_product->get_title() ),
					$price,
					'TAX',
					$wc_product->get_permalink(),
					$img_url,
					null,
					$stockQuantity,
					$status,
					$optionSupport,
					$option,
					$shippingPolicy,
					$supplements
				);

				$data = MNP_XMLSerializer::generateValidXmlFromObj( json_decode( json_encode( $product ) ), 'product' );

				return $data;
			}
		}
	}

	new MNP_Callback();
}

