<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class MNP_Admin_Post_types {

	public static function init() {
		add_filter( 'bulk_actions-edit-shop_order', __CLASS__ . '::shop_order_bulk_actions' );
		add_action( 'load-edit.php', __CLASS__ . '::do_bulk_action', 999 );
		add_action( 'restrict_manage_posts', __CLASS__ . '::mnp_restrict_manage_posts', 30 );
	}

	static function mnp_restrict_manage_posts() {
		global $typenow;

		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) {
			$paymethod = isset( $_REQUEST['paymethod'] ) ? $_REQUEST['paymethod'] : '';
			$payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

			echo '<select name="paymethod">';
			printf( '<option value="" %s>모든 결제수단</option>', $paymethod == '' ? 'selected' : '' );
			foreach ( $payment_gateways as $payment_gateway ) {
				printf( '<option value="%s" %s>%s</option>', $payment_gateway->id, $paymethod == $payment_gateway->id ? 'selected' : '', $payment_gateway->title );
			}
			printf( '<option value="naverpay" %s>NPay</option>', $paymethod == 'naverpay' ? 'selected' : '' );

			echo '<select>';

			$naverpay_order_id = isset( $_REQUEST['naverpay_order_id'] ) ? $_REQUEST['naverpay_order_id'] : '';
			?>
			<input name="naverpay_order_id"  value="<?php echo $naverpay_order_id; ?>" placeholder="(Product)Order ID">
			<?php
		}
	}

	static function shop_order_bulk_actions( $actions ) {
		$actions['npay_place-product-order'] = __( '발주확인 (NPay)', 'mshop-npay' );

		return $actions;
	}

	static function do_bulk_action() {
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();

		// Bail out if this is not a status-changing action
		if ( strpos( $action, 'npay_' ) === false ) {
			return;
		}

		$action = substr( $action, 5 ); // get the status name from action

		$changed = 0;

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		if( 'place-product-order' == $action) {
			$changed = MNP_Order::bulk_action_place_product_order( $post_ids );
		}

//		foreach ( $post_ids as $post_id ) {
//			$order = wc_get_order( $post_id );
//			$order->update_status( $new_status, __( 'Order status changed by bulk edit:', 'woocommerce' ), true );
//			do_action( 'woocommerce_order_edit_status', $post_id, $new_status );
//			$changed++;
//		}

		$sendback = add_query_arg( array( 'post_type' => 'shop_order', $action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );

		if ( isset( $_GET['post_status'] ) ) {
			$sendback = add_query_arg( 'post_status', sanitize_text_field( $_GET['post_status'] ), $sendback );
		}

		wp_redirect( esc_url_raw( $sendback ) );
		exit();
	}

	static function add_bulk_actions() {
		global $post_type, $pagenow;

		if ( 'shop_order' == $post_type && 'edit.php' == $pagenow ) {
			?>
			<script type="text/javascript">
				jQuery(function () {
					jQuery('<option>').val('npay_place-product-order').text('<?php _e( '발주확인 (NPay)', 'mshop-npay' )?>').appendTo('select[name="action"]');
					jQuery('<option>').val('npay_place-product-order').text('<?php _e( '발주확인 (NPay)', 'mshop-npay' )?>').appendTo('select[name="action2"]');
				});
			</script>
			<?php
		}
	}
}

MNP_Admin_Post_types::init();
