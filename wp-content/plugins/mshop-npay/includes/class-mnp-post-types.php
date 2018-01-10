<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class MNP_Post_types {
	public static function init() {
		add_filter( 'woocommerce_register_shop_order_post_statuses', array ( __CLASS__, 'register_order_status' ) );
	}
	public static function register_order_status( $order_statuses ) {

		$order_statuses = array_merge( $order_statuses,
			array (
				'wc-place-order'      => array (
					'label'                     => _x( '발주확인', 'Order status', 'mshop-npay' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '발주확인 <span class="count">(%s)</span>', '발주확인 <span class="count">(%s)</span>', 'mshop-npay' )
				),
				'wc-shipping' => array (
					'label'                     => _x( '배송중', 'Order status', 'mshop-npay' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '배송중 <span class="count">(%s)</span>', '배송중 <span class="count">(%s)</span>', 'mshop-npay' )
				),
				'wc-cancel-request'   => array (
					'label'                     => _x( '취소요청', 'Order status', 'mshop-npay' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '취소요청 <span class="count">(%s)</span>', '취소요청 <span class="count">(%s)</span>', 'mshop-npay' )
				),
				'wc-exchange-request' => array (
					'label'                     => _x( '교환요청', 'Order status', 'mshop-npay' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '교환요청 <span class="count">(%s)</span>', '교환요청 <span class="count">(%s)</span>', 'mshop-npay' )
				),
				'wc-return-request'   => array (
					'label'                     => _x( '반품요청', 'Order status', 'mshop-npay' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '반품요청 <span class="count">(%s)</span>', '반품요청 <span class="count">(%s)</span>', 'mshop-npay' )
				)
			)
		);

		return $order_statuses;
	}

}

MNP_Post_types::init();
