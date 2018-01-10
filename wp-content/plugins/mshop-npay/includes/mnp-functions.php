<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$mnp_action = get_option( 'mshop-naverpay-button-cart', 'woocommerce_after_cart' );
add_filter( 'woocommerce_after_add_to_cart_form', 'MNP_Cart::woocommerce_after_add_to_cart_form' );
add_action( trim( $mnp_action ), 'MNP_Cart::woocommerce_after_cart_table' );


add_action( 'add_meta_boxes', 'MNP_Meta_Box_Order::add_meta_boxes' );
add_action( 'woocommerce_process_product_meta_simple', 'MNP_Meta_Box_Product_Data::woocommerce_process_product_meta' );
add_action( 'woocommerce_process_product_meta_variable', 'MNP_Meta_Box_Product_Data::woocommerce_process_product_meta' );
add_action( 'woocommerce_product_options_inventory_product_data', 'MNP_Meta_Box_Product_Data::woocommerce_product_options_inventory_product_data' );

add_filter( 'wc_order_statuses', 'MNP_Order::wc_order_statuses' );
add_action( 'woocommerce_admin_order_items_after_line_items', 'MNP_Order::woocommerce_admin_order_items_after_line_items' );

if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
	add_action( 'woocommerce_checkout_create_order_line_item', 'MNP_Order::woocommerce_checkout_create_order_line_item', 10, 4 );
} else {
	add_action( 'woocommerce_add_order_item_meta', 'MNP_Order::woocommerce_add_order_item_meta', 10, 3 );
}

add_filter( 'woocommerce_hidden_order_itemmeta', 'MNP_Order::woocommerce_hidden_order_itemmeta', 10 );
add_filter( 'woocommerce_cancel_unpaid_order', 'MNP_Order::woocommerce_cancel_unpaid_order', 10, 2 );
add_action( 'woocommerce_after_order_itemmeta', 'MNP_Order_Item::woocommerce_after_order_itemmeta', 10, 3 );

add_filter( 'woocommerce_hidden_order_itemmeta', 'MNP_Sheets::woocommerce_hidden_order_itemmeta', 10 );
add_filter( 'woocommerce_attribute_label', 'MNP_Sheets::woocommerce_attribute_label', 10, 3 );

add_filter( 'mnp_process_sheet_info', 'MNP_Sheets_Npay::mnp_process_sheet_info', 10, 2 );
add_filter( 'mnp_sheet_update_order_status', 'MNP_Sheets_Npay::mnp_sheet_update_order_status', 10, 2 );
add_filter( 'mnp_bulk_ship_order', 'MNP_Sheets_Npay::mnp_bulk_ship_order', 10 );
add_action( 'woocommerce_order_item_meta_end', 'MNP_Myaccount::woocommerce_order_item_meta_end', 10, 3 );


function mnp_ajax_url( $url ) {
	if ( has_filter( 'wpml_object_id' ) ) {
		$url = add_query_arg( 'lang', 'ko', $url );
	}

	return $url;
}
function mnp_admin_notice( $msg, $type = 'success' ) {
	?>
	<div class="notice notice-<?php echo $type; ?>">
		<p><?php echo $msg; ?></p>
	</div>
	<?php
}
function mnp_get_object_property( $object, $property ) {
	$method = 'get_' . $property;

	return is_callable( array ( $object, 'get_payment_method' ) ) ? $object->$method() : $object->$property;
}
function mnp_get_meta( $object, $meta_key, $single = true, $context = 'view' ) {
	if ( is_callable( array ( $object, 'get_meta' ) ) ) {
		return $object->get_meta( $meta_key, $single, $context );
	} else {
		if ( $object instanceof WC_Abstract_Order ) {
			return get_post_meta( $object->id, $meta_key, $single );
		} else if ( is_numeric( $object ) ) {
			return wc_get_order_item_meta( $object, $meta_key, $single );
		} else if ( $object instanceof WC_Order_Item ) {
			return wc_get_order_item_meta( $object->id, $meta_key, $single );
		}
	}
}
function mnp_update_meta_data( $object, $key, $value ) {
	if ( is_callable( array ( $object, 'update_meta_data' ) ) ) {
		$object->update_meta_data( $key, $value );
		$object->save();
	} else {
		if ( $object instanceof WC_Abstract_Order ) {
			update_post_meta( $object->id, $key, $value );
		} else if ( $object instanceof WC_Order_Item ) {
			wc_update_order_item_meta( $object->id, $key, $value );
		} else if ( is_numeric( $object ) ) {
			wc_update_order_item_meta( $object, $key, $value );
		}
	}
}
function mnp_update_order_item_meta_data( $item_id, $item, $key, $value ) {
	if ( is_object( $item ) ) {
		mnp_update_meta_data( $item, $key, $value );
	} else {
		mnp_update_meta_data( $item_id, $key, $value );
	}
}
function mnp_save_meta_data( $object ) {
	if ( is_callable( array ( $object, 'save_meta_data' ) ) ) {
		$object->save_meta_data();
	}
}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'mnp_woocommerce_order_item_get_formatted_meta_data', 10, 2 );

function mnp_woocommerce_order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {
	$formatted_meta = array_filter( $formatted_meta, function ( $meta ) {
		return ! in_array( $meta->key, array (
			'_npay_order',
			'_npay_product_order_id',
			'_npay_product_order_status'
		) );
	} );

	return $formatted_meta;
}
add_filter( 'woocommerce_order_items_meta_get_formatted', 'mnp_woocommerce_order_items_meta_get_formatted', 10, 2 );

function mnp_woocommerce_order_items_meta_get_formatted( $formatted_meta, $order_item ) {
	$formatted_meta = array_filter( $formatted_meta, function ( $meta ) {

		return is_object( $meta) && ! in_array( $meta->key, array (
			'_npay_order',
			'_npay_product_order_id',
			'_npay_product_order_status'
		) );
	} );

	return $formatted_meta;
}
function mnp_migrate_order( $order ) {
	if ( $order && MNP_Manager::PAYMENT_GATEWAY_NAVERPAY == mnp_get_object_property( $order, 'payment_method' ) ) {
		$mnp_version = mnp_get_meta( $order, '_npay_version' );

		if ( version_compare( '3.0.9', $mnp_version, '>' ) ) {
			foreach ( $order->get_items() as $item_id => $item ) {

				if ( ! empty( $item['npay_order'] ) ) {
					wc_add_order_item_meta( $item_id, '_npay_order', $item['npay_order'] );
					wc_delete_order_item_meta( $item_id, 'npay_order' );
				}
				if ( ! empty( $item['npay_product_order_id'] ) ) {
					wc_add_order_item_meta( $item_id, '_npay_product_order_id', $item['npay_product_order_id'] );
					wc_delete_order_item_meta( $item_id, 'npay_product_order_id' );
				}
				if ( ! empty( $item['npay_product_order_status'] ) ) {
					wc_add_order_item_meta( $item_id, '_npay_product_order_status', $item['npay_product_order_status'] );
					wc_delete_order_item_meta( $item_id, 'npay_product_order_status' );
				}
			}

			mnp_update_meta_data( $order, '_npay_version', MNP()->version );
		}
	}
}

function mnp_get_order_id_by_order_item_id( $order_item_id ) {
	if( function_exists( 'wc_get_order_id_by_order_item_id' ) ) {
		return wc_get_order_id_by_order_item_id( $order_item_id );
	}else {
		global $wpdb;

		// Faster than get_posts()
		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %l", $order_item_id ) );

		return $order_id;
	}
}