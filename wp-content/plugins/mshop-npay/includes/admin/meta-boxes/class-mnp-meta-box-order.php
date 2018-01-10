<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MNP_Meta_Box_Order {
	public static function add_meta_boxes() {
		global $post;

		$order = wc_get_order( $post->ID );

		if ( $order && MNP_Manager::PAYMENT_GATEWAY_NAVERPAY == mnp_get_object_property( $order, 'payment_method' ) ) {
			mnp_migrate_order( $order );
			add_meta_box( 'mnp-npay-order-info', __( '<div class="npay-logo"></div>&nbsp;', 'mshop-npay' ), __CLASS__ . '::output_npay_order_info', 'shop_order', 'side', 'default' );
		}
	}

	public static function output_npay_order_info( $post ) {
		global $wp_scripts;
		$order = wc_get_order( $post->ID );

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
		);
		wp_register_script( 'naverpay-admin-order', MNP()->plugin_url() . '/assets/js/admin-order-wc.js' );
		wp_localize_script( 'naverpay-admin-order', 'naverpay_admin_order', array (
			'ajax_url'         => admin_url( 'admin-ajax.php', 'relative' ),
			'order_id'         => mnp_get_object_property( $order, 'id' ),
			'product_order_id' => mnp_get_meta( $order, '_naverpay_product_order_id' ),
			'slug'             => MNP()->slug(),
			'order_action'     => MNP()->slug() . '-order_action'
		) );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'naverpay-admin-order' );
		wp_enqueue_script( 'jquery-block-ui', MNP()->plugin_url() . '/assets/js/jquery.blockUI.js', $dependencies );

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
		wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array (), $jquery_version );
		wp_enqueue_style( 'naverpay-admin', MNP()->plugin_url() . '/assets/css/naverpay-admin.css' );

		$order_info = mnp_get_meta( $order, '_npay_order', true );

		if ( empty( $order_info ) ) {
			$order_items = $order->get_items();

			foreach ( $order_items as $item_id => $item ) {
				if ( ! empty( $item['npay_order'] ) ) {
					$product_order_info = json_decode( $item['npay_order'] );
					$order_info         = $product_order_info->Order;

					break;
				}
			}
		}

		if ( ! empty( $order_info ) ) {
			include( 'views/html-order-info-wc.php' );
		}

		?>
		<div class="button-wrapper">
			<button class="button button-primary refresh-npay-order">주문정보 새로고침</button>
		</div>
		<?php
	}
}
