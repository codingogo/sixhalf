<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MNP_Settings' ) ) :

	class MNP_Settings {

		static $api_status = null;

		static function init_action() {
			add_filter( 'msshelper_get_mshop-naverpay-api-key', __CLASS__ . '::get_api_key' );
			add_filter( 'msshelper_get_mshop-naverpay-domain', __CLASS__ . '::get_domain' );
			add_filter( 'msshelper_get_mshop-naverpay-connected-date', __CLASS__ . '::connected_date' );
		}

		static function get_api_key(){
			return self::$api_status->api_key;
		}

		static function get_domain(){
			return self::$api_status->site_url;
		}

		static function connected_date(){
			return self::$api_status->connected_date;
		}

		static function update_settings() {
			include_once MNP()->plugin_path() . '/includes/admin/setting-manager/mshop-setting-helper.php';

			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			MSSHelper::update_settings( self::get_setting_fields() );

			MNP_API::register_service();

			wp_send_json_success();
		}

		static function get_setting_fields() {
			return array (
				'type'     => 'Tab',
				'id'       => 'mnp-setting-tab',
				'elements' => array (
					self::get_service_setting(),
					self::get_basic_setting(),
					self::get_review_setting(),
					self::get_delivery_setting(),
					self::get_advanced_setting()
				)
			);
		}

		static function get_service_setting() {
			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( 'NPAY 서비스', 'mshop-npay' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '서비스 정보', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-api-key",
								"title"     => "API KEY",
								"className" => "fluid",
								"type"      => "Label",
							),
							array (
								"id"        => "mshop-naverpay-domain",
								"title"     => "사이트 URL",
								"className" => "fluid",
								"type"      => "Label",
							),
							array (
								"id"        => "mshop-naverpay-connected-date",
								"title"     => "서비스 연동일시",
								"className" => "fluid",
								"type"      => "Label",
							),
							array (
								'id'         => 'mshop-naverpay-api-reset',
								'title'      => '서비스 연결 해제',
								'label'      => '해제하기',
								'iconClass'  => 'icon settings',
								'className'  => '',
								'type'       => 'Button',
								'default'    => '',
								'actionType' => 'ajax',
								'ajaxurl'    => admin_url( 'admin-ajax.php' ),
								'action'     => MNP()->slug() . '-api_reset',
								"desc"       => "NPAY 서비스 연결을 해제합니다."
							)
						)
					)
				)
			);
		}

		static function get_basic_setting() {
			return array (
				'type'     => 'Page',
				'title'    => __( '기본 설정', 'mshop-npay' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '상점정보', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-merchant-id",
								"title"     => "상점 아이디",
								"className" => "fluid",
								"type"      => "Text",
							),
							array (
								"id"        => "mshop-naverpay-auth-key",
								"title"     => "가맹점 인증키",
								"className" => "fluid",
								"type"      => "Text",
							),
							array (
								"id"        => "mshop-naverpay-button-auth-key",
								"title"     => "버튼 인증키",
								"className" => "fluid",
								"type"      => "Text",
							),
							array (
								"id"        => "mshop-naverpay-common-auth-key",
								"title"     => "네이버 공통 인증키",
								"className" => "fluid",
								"type"      => "Text",
							)
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '동작 설정', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-operation-mode",
								"title"     => "운영 모드",
								"className" => "",
								"type"      => "Select",
								"default"   => 'None',
								'options'   => array (
									MNP_Manager::MODE_NONE       => __( '해당없음', 'mshop-npay' ),
									MNP_Manager::MODE_SANDBOX    => __( '개발환경(SandBox)', 'mshop-npay' ),
									MNP_Manager::MODE_PRODUCTION => __( '실환경(Production)', 'mshop-npay' )
								),
							),
							array (
								"id"        => "mshop-naverpay-test-user-id",
								"showIf"    => array ( "mshop-naverpay-operation-mode" => MNP_Manager::MODE_SANDBOX ),
								"title"     => "테스트 사용자 아이디",
								"className" => "",
								"type"      => "Text",
								'desc'      => '개발환경에서 NPay 테스트를 위한 사용자 아이디를 입력하세요.'
							)
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '버튼 설정 (PC)', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-button-type-pc",
								"title"     => "버튼 종류",
								"className" => "",
								"type"      => "Select",
								"default"   => 'A',
								'options'   => array (
									'A' => __( 'A', 'mshop-npay' ),
									'B' => __( 'B', 'mshop-npay' ),
									'C' => __( 'C', 'mshop-npay' ),
									'D' => __( 'D', 'mshop-npay' ),
									'E' => __( 'E', 'mshop-npay' )
								),
							),
							array (
								"id"        => "mshop-naverpay-button-color-pc",
								"title"     => "버튼 색상",
								"className" => "",
								"type"      => "Select",
								"default"   => '1',
								'options'   => array (
									'1' => __( '1', 'mshop-npay' ),
									'2' => __( '2', 'mshop-npay' ),
									'3' => __( '3', 'mshop-npay' )
								),
							)
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '버튼 설정 (모바일)', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-button-type-mobile",
								"title"     => "버튼 종류",
								"className" => "",
								"type"      => "Select",
								"default"   => 'MA',
								'options'   => array (
									'MA' => __( 'MA', 'mshop-npay' ),
									'MB' => __( 'MB', 'mshop-npay' )
								),
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '버튼 고급설정', 'mshop-npay' ),
						'elements' => array(
							array(
								"id"        => "mshop-naverpay-always-show-button",
								"title"     => __( "항상 표시", 'mshop-npay' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "옵션상품의 옵션을 선택하지 않아도 네이버페이 버튼을 표시합니다.", 'mshop-npay' )
							),
							array(
								"id"        => "mshop-naverpay-button-cart",
								"title"     => "버튼 위치 (장바구니 페이지)",
								"className" => "fluid",
								"type"      => "Text",
								'desc2'     => __( "테마 구조에 따라 장바구니 페이지에서 네이버페이 버튼의 표시위치가 달라집니다. 테마 파일을 참고해서 위치를 지정하세요.<br>ex) 테마파일내에 do_action( 'woocommerce_after_cart' ); 코드가 있는 위치에 네이버페이 버튼을 출력하려면, woocommerce_after_cart 을 입력합니다.", "mshop-npay" ),
							)
						)
					),
				)
			);
		}

		static function get_review_setting() {
			return array (
				'type'     => 'Page',
				'title'    => __( '구매평', 'mshop-npay' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( ' 구매평 연동 기능', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-sync-review",
								"title"     => __( "활성화", 'mshop-npay' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "구매평 연동 기능을 사용합니다.", 'mshop-npay' )
							),
						),
					),
					array (
						'type'     => 'Section',
						'title'    => __( '구매평 연동 설정', 'mshop-npay' ),
						'showIf'   => array ( "mshop-naverpay-sync-review" => "yes" ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-sync-normal-review",
								"title"     => __( "일반 구매평", 'mshop-npay' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "yes",
								"desc"      => __( "일반 구매평 연동 기능을 사용합니다.", 'mshop-npay' )
							),
							array (
								"id"        => "mshop-naverpay-sync-premium-review",
								"title"     => __( "프리미엄 구매평", 'mshop-npay' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "프리미엄 구매평 연동 기능을 사용합니다.", 'mshop-npay' )
							),
						)
					)
				)
			);
		}

		static function get_delivery_setting() {
			include_once( MNP()->plugin_path() . '/includes/naverpay/ShippingPolicy.php' );

			return array (
				'type'     => 'Page',
				'title'    => __( '배송 설정', 'mshop-npay' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '배송 수단', 'mshop-npay' ),
						'showIf'   => version_compare( WOOCOMMERCE_VERSION, '2.6.0', '>=' ) ? null : array ( 'hidden' => 'hidden' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-free-shipping",
								"title"     => __( "무료배송 수단", 'mshop-npay' ),
								"className" => "",
								"type"      => "Select",
								"default"   => "",
								"options"   => MNP_Shipping::get_shipping_options( 'free_shipping', '무료배송 수단을 선택하세요.' )
							),
							array (
								"id"        => "mshop-naverpay-flat-rate",
								"title"     => __( "유료배송 수단", 'mshop-npay' ),
								"className" => "",
								"type"      => "Select",
								"default"   => "",
								"options"   => MNP_Shipping::get_shipping_options( 'flat_rate', '유료배송 수단을 선택하세요.' )
							)
						),
					),
					array (
						'type'     => 'Section',
						'title'    => __( '도서산간 배송비', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-use-additional-fee",
								"title"     => __( "활성화", 'mshop-npay' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "지역배송비 설정 기능을 사용합니다.", 'mshop-npay' )
							),
							array (
								"id"          => "mshop-naverpay-additional-fee-mode",
								"title"       => "배송비 설정 모드",
								"showIf"      => array ( "mshop-naverpay-use-additional-fee" => "yes" ),
								"className"   => "",
								"type"        => "Select",
								"placeHolder" => "배송비 모드를 선택하세요.",
								"default"     => "",
								'options'     => array (
									MNP_Manager::ADDITIONAL_FEE_REGION => __( '권역별 배송비', 'mshop-npay' ),
//														    MNP_Manager::ADDITIONAL_FEE_API    => __( '도서산간비 API', 'mshop-npay' )
								),
//												    "desc2"      => __( "'도서산간비 API'를 이용하기 위해서는 <a target='_blank' href='http://www.codemshop.com/shop/local-delivery/'>엠샵 추가배송비</a> 플러그인이 설치되어 있어야 합니다.", 'mshop-npay' )
							),
						),
					),
					array (
						'type'     => 'Section',
						'title'    => __( '권역별 배송비', 'mshop-npay' ),
						"showIf"   => array (
							array ( "mshop-naverpay-use-additional-fee" => "yes" ),
							array ( "mshop-naverpay-additional-fee-mode" => MNP_Manager::ADDITIONAL_FEE_REGION )
						),
						'elements' => array (
							array (
								"id"        => "mshop-naverpay-additional-fee-region",
								"title"     => "권역 구분",
								"className" => "",
								"type"      => "Select",
								"default"   => '2',
								'options'   => array (
									'2' => __( '2 단계 (내륙, 제주 및 도서 산간 지역)', 'mshop-npay' ),
									'3' => __( '3 단계 (내륙, 제주 외 도서 산간 지역, 제주)', 'mshop-npay' )
								),
								'desc'      => '지역별 배송비 부과 권역을 몇단계로 설정할지를 지정합니다.'
							),
							array (
								"id"          => "mshop-naverpay-additional-fee-region-2",
								"type"        => "LabeledInput",
								"className"   => "",
								'inputType'   => 'number',
								"valueType"   => "unsigned int",
								"title"       => __( "2권역 추가배송비", 'mshop-npay' ),
								"leftLabel"   => get_woocommerce_currency_symbol(),
								"default"     => "0",
								"placeholder" => "0"
							),
							array (
								"id"          => "mshop-naverpay-additional-fee-region-3",
								"showIf"      => array ( "mshop-naverpay-additional-fee-region" => '3' ),
								"type"        => "LabeledInput",
								"className"   => "",
								'inputType'   => 'number',
								"valueType"   => "unsigned int",
								"title"       => __( "3권역 추가배송비", 'mshop-npay' ),
								"leftLabel"   => get_woocommerce_currency_symbol(),
								"default"     => "0",
								"placeholder" => "0"
							)
						)
					)
				)
			);
		}

		static function get_advanced_setting() {
			return array (
				'type'     => 'Page',
				'title'    => __( '고급 설정', 'mshop-npay' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '테그 설정', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mnp-wrapper-selector",
								"title"     => "Wrapper Selector",
								"className" => "fluid",
								"type"      => "Text",
								"default"   => 'div.product.type-product'
							),
							array (
								"id"        => "mnp-simple-class",
								"title"     => "단순상품 Class",
								"className" => "fluid",
								"type"      => "Text",
								"default"   => 'product-type-simple'
							),
							array (
								"id"        => "mnp-variable-class",
								"title"     => "옵션상품 Class",
								"className" => "fluid",
								"type"      => "Text",
								"default"   => 'product-type-variable'
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '화면전환 설정', 'mshop-npay' ),
						'elements' => array(
							array(
								"id"        => "mnp-product-page-transition-mode",
								"title"     => "상품상세화면",
								"className" => "",
								"type"      => "Select",
								"default"   => 'new-window',
								'options'   => array(
									'new-window' => __( '새탭으로 열기', 'mshop-npay' ),
									'in-page' => __( '현재화면에서 열기', 'mshop-npay' )
								),
							),
							array(
								"id"        => "mnp-cart-page-transition-mode",
								"title"     => "장바구니화면",
								"className" => "",
								"type"      => "Select",
								"default"   => 'new-window',
								'options'   => array(
									'new-window' => __( '새탭으로 열기', 'mshop-npay' ),
									'in-page' => __( '현재화면에서 열기', 'mshop-npay' )
								),
							),
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '이미지 URL 설정', 'mshop-npay' ),
						'elements' => array(
							array(
								"id"        => "mnp-force-image-url-to-http",
								"title"     => "HTTP로 변경",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "yes",
								"desc"      => __( "이미지 파일의 URL을 HTTP로 강제합니다.", 'mshop-npay' )
							)
						)
					)
				)
			);
		}

		static function enqueue_scripts() {
			wp_enqueue_script( 'underscore' );
			wp_enqueue_style( 'mshop-setting-manager', MNP()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', MNP()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array (
				'jquery',
				'jquery-ui-core',
				'underscore'
			) );
		}

		public static function output_settings() {
			self::init_action();

			require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );

			wp_dashboard_setup();

			require_once MNP()->plugin_path() . '/includes/admin/setting-manager/mshop-setting-helper.php';

			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => MNP()->slug() . '-update_settings',
				'settings' => $settings,
				'slug'     => MNP()->slug(),
				'domain'   => preg_replace( '#^https?://#', '', home_url() ),
			) );

			?>
			<script>
				jQuery(document).ready(function () {
					jQuery(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( MSSHelper::get_settings( $settings ) ); ?>, null, null]);
				});
			</script>

			<div id="mshop-setting-wrapper"></div>
			<?php
		}

		public static function output_guide_page() {
			ob_start();
			wc_get_template( 'connect-key-guide.php', array (), '', MNP()->template_path() );
			echo ob_get_clean();
		}
		public static function output() {
			if ( ! empty( $_REQUEST['npay_connect'] ) && ! empty( $_REQUEST['npay_api_key'] ) ) {
				if ( MNP_Manager::request_connect( $_REQUEST['npay_api_key'] ) ) {
					mnp_admin_notice( '축하합니다. NPay 서비스에 연결되었습니다.' );
				} else {
					mnp_admin_notice( 'NPay 연동에 실패했습니다.  NPay API 키를 확인 후 다시 한번 연결을 진행 해 주세요.', 'error' );
				}
			}

			$result   = MNP_API::get_status();
			$response = $result->response;

			if ( $response->ResponseType == "SUCCESS" && 'yes' == $response->Status->connected ) {
				self::$api_status = $response->Status;
				self::output_settings();
			} else {
				self::output_guide_page();
			}
		}
	}
endif;



