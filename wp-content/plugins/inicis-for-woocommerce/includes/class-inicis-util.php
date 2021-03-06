<?php
/*
=====================================================================================
                INICIS for WooCommerce / Copyright 2014 - 2016 by CodeM
=====================================================================================

  [ 우커머스 버전 지원 안내 ]

    워드프레스 버전 : WordPress 4.6.0

    우커머스 버전 : WooCommerce 2.6.0


  [ 코드엠 플러그인 라이센스 규정 ]

    1. 코드엠에서 개발한 워드프레스 우커머스용 결제 플러그인의 저작권은 ㈜코드엠에게 있습니다.

    2. 당사의 플러그인의 설치, 인증에 따른 절차는 플러그인 라이센스 규정에 동의하는 것으로 간주합니다.

    3. 결제 플러그인의 사용권은 쇼핑몰 사이트의 결제 서비스 사용에 국한되며, 그 외의 상업적 사용을 금지합니다.

    4. 결제 플러그인의 소스 코드를 복제 또는 수정 및 재배포를 금지합니다. 이를 위반 시 민형사상의 책임을 질 수 있습니다.

    5. 플러그인 사용에 있어 워드프레스, 테마, 플러그인과의 호환 및 버전 관리의 책임은 사이트 당사자에게 있습니다.

    6. 위 라이센스는 개발사의 사정에 의해 임의로 변경될 수 있으며, 변경된 내용은 해당 플러그인 홈페이지를 통해 공개합니다.

=====================================================================================
*/
if ( ! defined( 'ABSPATH' ) ) exit;

if( !function_exists( 'ifw_get' ) ) {
    function ifw_get($obj, $string) {

        if( !empty($obj) ) {
            if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                return $obj->$string;
            } else {
                $string = 'get_' . $string;
                return $obj->$string();
            }
        }

    } //end ifw_get() function

}

if( !function_exists( 'ifw_reduce_order_stock' ) ) {
    function ifw_reduce_order_stock($order) {

        if( !empty($order) ) {
            if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                $order->reduce_order_stock();
            } else {
                //재고 차감 여부 확인 후, 재고 조정 처리 진행
                if( !$order->get_data_store()->get_stock_reduced( $order->get_id() ) ) {
                    wc_reduce_stock_levels( $order->get_id() );
                }

            }
        }

    } //end ifw_get() function

}