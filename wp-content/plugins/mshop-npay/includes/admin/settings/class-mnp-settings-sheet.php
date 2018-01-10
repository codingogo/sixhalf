<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MNP_Settings_Sheet' ) ) :

	class MNP_Settings_Sheet {


		static function update_settings() {
			include_once MNP()->plugin_path() . '/includes/admin/setting-manager/mshop-setting-helper.php';

			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			MSSHelper::update_settings( self::get_setting_fields() );

			wp_send_json_success();
		}

		static function get_setting_fields() {
			return array (
				'type'     => 'Tab',
				'id'       => 'mnp-setting-tab',
				'elements' => array (
					self::get_basic_setting(),
					self::get_setting_actions(),
				)
			);
		}

		static function get_default_sheet_fields() {
			return array (
				array (
					'type'                => 'order_id',
					'name'                => '주문번호',
					'order_meta_key'      => '',
					'order_item_meta_key' => ''
				),
				array (
					'type'                => 'order_item_id',
					'name'                => '주문아이템번호',
					'order_meta_key'      => '',
					'order_item_meta_key' => ''
				),
				array (
					'type'                => 'dlv_company_code',
					'name'                => '택배사코드',
					'order_meta_key'      => '_mnp_dlv_company_code',
					'order_item_meta_key' => '_mnp_dlv_company_code'
				),
				array (
					'type'                => 'dlv_company_name',
					'name'                => '택배사명',
					'order_meta_key'      => '_mnp_dlv_company_name',
					'order_item_meta_key' => '_mnp_dlv_company_name'
				),
				array (
					'type'                => 'sheet_no',
					'name'                => '송장번호',
					'order_meta_key'      => '_mnp_sheet_no',
					'order_item_meta_key' => '_mnp_sheet_no'
				),
			);
		}

		static function get_basic_setting() {
			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'mshop-npay' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '동작 설정', 'mshop-npay' ),
						'elements' => array (
							array(
								'id'        => 'mnp_order_status_after_shipping',
								'title'     => __( '배송처리 후 변경될 주문상태', 'mshop-npay' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'wc-shipping',
								'options'   => wc_get_order_statuses(),
								'tooltip'   => array(
									'title' => array(
										'content' => __( '모든 주문 아이템에 송장정보가 등록되면 주문 상태를 지정된 상태로 변경합니다., ', 'mshop-npay' ),
									)
								)
							),
							array (
								"id"        => "mnp_sheet_payment_type",
								"title"     => "결제수단",
								"className" => "",
								"type"      => "Select",
								"options"   => array (
									"naverpay" => __( "NPay", 'mshop-npay' ),
									"all"      => __( "모든 결제수단", 'mshop-npay' )
								),
								"tooltip"   => array (
									"title" => array (
										"title"   => __( "", 'mshop-npay' ),
										"content" => __( "지정된 결제수단의 주문건만 송장번호를 등록합니다.", 'mshop-npay' )
									)
								),
							),
							array (
								"id"        => "mnp_sheet_order_field_type",
								"title"     => "송장정보 저장 객체",
								"className" => "",
								"type"      => "Select",
								"options"   => array (
									"order_item" => __( "주문아이템", 'mshop-npay' ),
									"order"      => __( "주문", 'mshop-npay' ),
									"all"        => __( "모두", 'mshop-npay' )
								),
								"tooltip"   => array (
									"title" => array (
										"title"   => __( "주의사항", 'mshop-npay' ),
										"content" => __( "<ul><li>'모두' 또는 '주문'을 선택한 경우, CSV 파일에 '주문번호'가 필수입니다.</li><li>'모두' 또는 '주문아이템'을 선택한 경우, CSV 파일에 '주문아이템번호'가 필수입니다.</li><li>'모두'를 선택한 경우에는 마지막 주문아이템의 송장정보가 주문에 저장됩니다.</li></ul>", 'mshop-npay' )
									)
								),
							),
							array (
								'id'             => 'mnp_reset_sheet_fields',
								'title'          => '필드 설정 초기화',
								'label'          => '초기화',
								'iconClass'      => 'icon settings',
								'className'      => '',
								'type'           => 'Button',
								'default'        => '',
								'confirmMessage' => __( 'CSV 필드 설정을 초기화하시겠습니까?', 'mshop-npay' ),
								'actionType'     => 'ajax',
								'ajaxurl'        => admin_url( 'admin-ajax.php' ),
								'action'         => MNP()->slug() . '-reset_sheet_fields',
								"desc"           => "CSV 필드 설정을 초기화합니다."
							),
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( 'CSV 필드 설정', 'mshop-npay' ),
						'elements' => array (
							array (
								"id"        => "mnp_sheet_fields",
								"className" => "",
								"type"      => "SortableTable",
								"template"  => array (
									'name'  => '',
									'ratio' => '',
									'fixed' => ''
								),
								"editable"  => true,
								"sortable"  => true,
								"default"   => self::get_default_sheet_fields(),
								"elements"  => array (
									array (
										"id"          => "type",
										"title"       => __( "필드 타입", 'mshop-npay' ),
										"className"   => " three wide column fluid",
										"type"        => "Select",
										"placeholder" => __( "필드 타입을 선택하세요.", 'mshop-npay' ),
										"options"     => array (
											"order_id"         => __( "주문번호", 'mshop-npay' ),
											"order_item_id"    => __( "주문아이템번호", 'mshop-npay' ),
											"dlv_company_code" => __( "택배사코드", 'mshop-npay' ),
											"dlv_company_name" => __( "택배사명", 'mshop-npay' ),
											"sheet_no"         => __( "송장번호", 'mshop-npay' ),
											"custom"           => __( "커스텀", 'mshop-npay' ),
										)
									),
									array (
										"id"          => "name",
										"title"       => __( "CSV 파일 컬럼명", 'mshop-npay' ),
										"className"   => " four wide column fluid",
										"type"        => "Text",
										"placeholder" => ""
									),
									array (
										"id"          => "order_meta_key",
										"title"       => __( "주문 메타", 'mshop-npay' ),
										"className"   => " four wide column fluid",
										"type"        => "Text",
										"showIf"      => array ( "type" => "dlv_company_code,dlv_company_name,sheet_no,custom" ),
										"placeholder" => ""
									),
									array (
										"id"          => "order_item_meta_key",
										"title"       => __( "주문아이템 메타", 'mshop-npay' ),
										"className"   => " four wide column fluid",
										"type"        => "Text",
										"showIf"      => array ( "type" => "dlv_company_code,dlv_company_name,sheet_no,custom" ),
										"placeholder" => ""
									),
								)
							)
						)
					),

				)
			);
		}


		public function get_setting_actions() {
			return array (
				'type'     => 'Page',
				'title'    => '송장 업로드',
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => '송장 업로드',
						'elements' => array (
							array (
								'id'         => 'msm_import_forms2',
								'title'      => 'CSV 파일 선택',
								'label'      => '업로드',
								'iconClass'  => 'icon settings',
								'className'  => '',
								'type'       => 'Upload',
								'default'    => '',
								'actionType' => 'ajax',
								'ajaxurl'    => admin_url( 'admin-ajax.php' ),
								'action'     => MNP()->slug() . '-upload_sheets'
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
				'jquery-ui-core'
			) );
		}
		public static function output() {
			require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );

			wp_dashboard_setup();

			require_once MNP()->plugin_path() . '/includes/admin/setting-manager/mshop-setting-helper.php';

			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => MNP()->slug() . '-update_sheet_settings',
				'settings' => $settings
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
	}
endif;



