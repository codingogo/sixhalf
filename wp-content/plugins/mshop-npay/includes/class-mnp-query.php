<?php



if ( ! defined( 'ABSPATH' ) ){
    exit;
}

if ( ! class_exists( 'MNP_Query' ) ) {

    class MNP_Query{

        static function init() {
	        add_filter( 'pre_get_posts', __CLASS__ . '::pre_get_posts', 100 );
        }
        public static function pre_get_posts( $q ) {
	        global $typenow;

	        if( 'shop_order' != $typenow ) {
		        return ;
	        }

	        if ( ! is_feed() && is_admin() && $q->is_main_query() ) {

		        if ( ! empty( $_REQUEST['paymethod'] ) ) {
			        $meta_query = $q->get( 'meta_query' );

			        $meta_query[] = array(
				        'key'     => '_payment_method',
				        'value'   => $_REQUEST['paymethod'],
				        'compare' => '='
			        );

			        $q->set( 'meta_query', $meta_query );
		        }

		        if ( ! empty( $_REQUEST['naverpay_order_id'] ) ) {
			        $meta_query = $q->get( 'meta_query' );

			        $meta_query[] = array(
				        'relation' => 'OR',
				        array(
					        'key'     => '_naverpay_order_id',
					        'value'   => $_REQUEST['naverpay_order_id'],
					        'compare' => '='
				        ),
				        array(
					        'key'     => '_naverpay_product_order_id',
					        'value'   => $_REQUEST['naverpay_order_id'],
					        'compare' => 'LIKE'
				        )
			        );

			        $q->set( 'meta_query', $meta_query );
		        }
	        }
        }
    }

    MNP_Query::init();
}