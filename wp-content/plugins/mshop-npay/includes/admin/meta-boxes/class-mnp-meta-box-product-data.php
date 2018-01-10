<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MNP_Meta_Box_Product_Data {

	static function woocommerce_product_options_inventory_product_data() {
		woocommerce_wp_checkbox( array( 'id' => '_naverpay_unavailable', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'NPay 구매불가', 'mshop-npay' ), 'description' => __( 'NPay 구매불가 상품 여부를 설정합니다.', 'mshop-npay' ) ) );
	}

	static function woocommerce_process_product_meta( $post_id ){
		update_post_meta( $post_id, '_naverpay_unavailable', $_POST['_naverpay_unavailable'] );
	}

}