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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class IFW_Meta_Box_Refund {

    private static $repay_card_list = array();          //부분 취소 가능 카드사 및 발급사 리스트
    private static $repay_count_list = array();         //부분 취소 횟수 제한없는 카드사 정보 리스트

    public static function init_repay_metabox() {
        //부분 취소 가능 카드사 및 발급사 리스트 설정
        self::$repay_card_list = array(
            '11' => '00',   //BC
            '06' => '04',   //국민,국민은행
            '12' => '00',   //삼성
            '14' => '26',   //신한,신한은행
            '01' => '05',   //외환,외환은행
            '04' => '00',   //현대 (부분취소 횟수제한없음)
            '03' => '00',   //롯데 (부분취소 횟수제한없음)
            '17' => '81',   //하나SK,하나은행
            '16' => '11',   //NH카드,농협
            '26' => '00',   //은련
        );

        //부분 취소 횟수 제한없는 카드사 정보 리스트 설정
        self::$repay_count_list = array(
            '04' => '00',   //현대 (부분취소 횟수제한없음)
            '03' => '00',   //롯데 (부분취소 횟수제한없음)
        );
    }

    public static function output( $post ) {
        global $woocommerce, $inicis_payment;

        //메타박스에서 사용되는 초기값 추가
        self::init_repay_metabox();

        $woocommerce->payment_gateways();

        $order = new WC_Order($post->ID);

        $payment_method = get_post_meta( ifw_get($order, 'id'), '_payment_method', true);                   //결제 수단(ex : inicis_card
        $tid = get_post_meta($post->ID, '_inicis_paymethod_tid', true);                         //원거래 아이디
        $repay_info = get_post_meta($post->ID, '_inicis_repay', true);             //부분취소 가능횟수
        $repay_cnt = count( json_decode($repay_info, true) );   //부분취소 횟수 조회
        $is_cancel = get_post_meta($post->ID, '_codem_inicis_order_cancelled', true);           //전체 취소 여부
        $card_code = get_post_meta($post->ID, '_inicis_paymethod_card_code', true);             //카드사 코드
        $card_bankcode = get_post_meta($post->ID, '_inicis_paymethod_card_bankcode', true);     //카드 발급은행 코드

        //전체취소 모듈용 스크리트
        wp_register_script( 'ifw-admin-js', $inicis_payment->plugin_url() . '/assets/js/ifw_admin.js' );
        wp_enqueue_script( 'ifw-admin-js' );
        wp_localize_script( 'ifw-admin-js', '_ifw_admin', array(
            'action' =>  'refund_request_' . $payment_method ,
            'order_id' => ifw_get($order, 'id'),
            'nonce' => wp_create_nonce('refund_request'),
            'tid' => $tid
        ) );

        //사용 가능한 결제 수단일 경우에만 로드하도록 설정
        if( in_array($payment_method, array('inicis_card','inicis_stdcard') ) ) {
            //부분취소 모듈용 스크립트
            wp_register_script( 'ifw-admin-repay-js', $inicis_payment->plugin_url() . '/assets/js/ifw_admin_repay.js' );
            wp_enqueue_script( 'ifw-admin-repay-js' );
            wp_localize_script( 'ifw-admin-repay-js', '_ifw_admin_repay', array(
                'action' =>  'repay_request_' . $payment_method ,
                'order_id' => ifw_get($order, 'id'),
                'nonce' => wp_create_nonce('repay_request'),
                'tid' => $tid,
                'repaycnt' => $repay_cnt,
                'order_total' => $order->get_total(),
            ) );
        }

        //기존 환불에서 전체취소로 버튼명칭 변경
        echo '<p class="">';
        if( apply_filters( 'ifw_is_admin_refundable_' . $payment_method, false, $order ) ) {
            if( !$is_cancel && $order->get_status() !== 'refunded' && $repay_cnt == 0) {
                echo '<input style="margin-right:10px;width:45%;" type="button" class="button button-primary tips" id="ifw-refund-request" name="refund-request" data-tip="' . __('전체취소','codem_inicis') . '" value="' . __('전체취소','codem_inicis') . '">';
            } else {
                echo '<input style="margin-right:10px;width:45%;" type="button" class="button button-primary tips" id="ifw-refund-request" name="refund-request" data-tip="' . __('전체취소','codem_inicis') . '" value="' . __('전체취소','codem_inicis') . '" disabled>';
            }

        } else {
            echo '<input style="margin-right:10px;width:45%;" type="button" class="button button-primary tips" id="ifw-refund-request" name="refund-request" data-tip="' . __('전체취소','codem_inicis') . '" value="' . __('전체취소','codem_inicis') . '" disabled>';
        }

        if ( !empty($tid) ) {
            echo '<input type="button"  style="margin-left:10px;width:45%;" class="button button-primary tips" id="ifw-check-receipt" name="refund-request-check-receipt" data-tip="' . __('영수증 확인','codem_inicis') . '" value="' . __('영수증 확인','codem_inicis') . '">';
        }

        echo '</p>';

        //부분취소용 버튼 추가 (일반 카드 및 웹표준 신용카드에서만 사용 가능하도록 설정)
        if( in_array($payment_method, array('inicis_stdcard') ) && $order->get_status() !== 'refunded' ){
            //카드사, 발급사 코드 확인
            if( self::get_card_repay_status($card_code, $card_bankcode) ) {
                if( self::get_card_repay_count_check($card_code, $repay_cnt) ) {
                    //부분취소 횟수제한없거나 횟수가 미초과인경우
                    echo '<hr>';
                    echo '<p><input type="text" class="repay_price" name="repay_price" id="repay_price" value="'.( $order->get_total()-$order->get_total_refunded() ).'" style="width:100%;text-align: right;ime-mode:disabled;" onkeypress="return input_is_number_check(event)"></p>';
                    echo '<p><input type="button" class="button button-primary tips" id="ifw-repay-request" name="refund-request-repay" value="' . __('부분취소','codem_inicis') .' (누적 : ' . $repay_cnt . '회)" style="width:100%"></p>';
                } else {
                    //부분취소 횟수제한이 있고, 횟수가 초과한 경우
                    echo '<hr>';
                    echo '<p><input type="text" class="repay_price" name="repay_price" id="repay_price" value="" placeholder="0" style="width:100%;text-align: right;ime-mode:disabled;" onkeypress="return input_is_number_check(event)" disabled></p>';
                    echo '<p><input type="button" class="button button-primary tips" id="ifw-repay-request" name="refund-request-repay" value="' . __('부분취소 (최대취소횟수 제한)','codem_inicis') . '" style="width:100%" disabled></p>';
                }
            } else {
                echo '<hr>';
                echo '<p><input type="text" class="repay_price" name="repay_price" id="repay_price" value="" placeholder="0" style="width:100%;text-align: right;ime-mode:disabled;" onkeypress="return input_is_number_check(event)" disabled></p>';
                echo '<p><input type="button" class="button button-primary tips" id="ifw-repay-request" name="refund-request-repay" value="' . __('부분취소 (카드사 미지원)','codem_inicis') . '" style="width:100%" disabled></p>';
            }
        } else {
            echo '<hr>';
            echo '<p><input type="text" class="repay_price" name="repay_price" id="repay_price" value="" placeholder="0" style="width:100%;text-align: right;ime-mode:disabled;" onkeypress="return input_is_number_check(event)" disabled></p>';
            echo '<p><input type="button" class="button button-primary tips" id="ifw-repay-request" name="refund-request-repay" value="' . __('부분취소','codem_inicis') . '" style="width:100%" disabled></p>';
        }
    }
    public static function get_card_repay_status($card_code, $card_bankcode) {
        //데이터 공백 검사
        if( !empty($card_code) && !empty($card_bankcode) ) {
            //일치하는 카드사와 발급사 코드가 있는지 확인
            foreach(self::$repay_card_list as $card => $bank) {
                if( $card == $card_code && $bank == $card_bankcode ) {
                    //일치하는 정보가 있는 경우 true 리턴
                    return true;
                }
            }
            //일치하는 정보가 없는 경우 false 리턴
            return false;
        } else {
            //데이터 검사 실패시 false 리턴
            return false;
        }
    }
    public static function get_card_repay_count_check($card_code, $repay_cnt = 0) {
        //데이터 공백 검사
        if( !empty($card_code) ) {
            //일치하는 카드사 코드가 있는지 확인
            foreach(self::$repay_count_list as $card => $bank) {
                if( $card == $card_code ) {
                    //일치하는 정보가 있는 경우 true 리턴
                    return true;
                }
            }

            //제한이 있는 카드사인 경우, 횟수 조회하여 결과 리턴
            if( $repay_cnt < 100 ) {
                return true;
            } else {
                return false;
            }
        } else {
            //데이터 검사 실패시 false 리턴
            return false;
        }
    }

    public static function save( $post_id, $post ) {
    }
}