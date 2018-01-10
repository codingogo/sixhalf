<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MNP_Dashboard {
	public static $client = null;

	public static function dashboard_action() {
		switch ( $_REQUEST['command'] ) {
			case 'dashboard_data' :
				self::get_dashboard_data();
				break;
		}
	}

	public static function get_dashboard_data() {
		$actions             = array (
			'order_stat',
			'order_stat_by_status',
			'claim_stat',
			'order_stat_by_date',
			'payed_stat_by_date',
			'sales_stat_by_product'
		);
		$params['command']   = MNP_API::COMMAND_DASHBOARD;
		$params['date_from'] = $_REQUEST['date_from'];
		$params['date_to']   = $_REQUEST['date_to'];
		$params['db-action'] = implode( ',', $actions );
		$params['interval']  = $_REQUEST['interval'];

		$response = MNP_API::call( http_build_query( array_merge( MNP_Manager::default_args(), $params ) ) );

		$response->sales_stat_by_product = self::process_sales_stat_by_product( $response->sales_stat_by_product );

		wp_send_json_success( $response );
	}

	public static function process_sales_stat_by_product( $response ) {
		if ( empty( $response ) ) {
			return $response;
		}

		foreach ( $response->order_by_amount as &$item ) {
			$product = wc_get_product( $item->id );

			if ( $product ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$product_id = $product->is_type( 'simple' ) ? $product->get_id() : $product->get_parent_id();
				} else {
					$product_id = $product->is_type( 'simple' ) ? $product->get_id() : $product->parent->get_id();
				}

				$item->name = sprintf( "<a href='%s' target='_blank'>[ID: %s] </a>%s", get_edit_post_link( $product_id ), $item->id, $product->get_title() );
			} else {
				$item->name = '상품정보가 없습니다.';
			}

			$item->value = number_format( $item->value );
		}
		foreach ( $response->order_by_qty as &$item ) {
			$product = wc_get_product( $item->id );

			if ( $product ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$product_id = $product->is_type( 'simple' ) ? $product->get_id() : $product->get_parent_id();
				} else {
					$product_id = $product->is_type( 'simple' ) ? $product->get_id() : $product->parent->get_id();
				}

				$item->name = sprintf( "<a href='%s' target='_blank'>[ID: %s] </a>%s", get_edit_post_link( $product_id ), $item->id, $product->get_title() );
			} else {
				$item->name = '상품정보가 없습니다.';
			}

			$item->value = number_format( $item->value );
		}

		return $response;
	}
}
