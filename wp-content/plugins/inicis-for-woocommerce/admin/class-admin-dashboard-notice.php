<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Class_PGAll_Admin_Meta_Boxes {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'add_dashboard' ), 30 );
    }

    public function add_dashboard(){
        global $pagenow, $inicis_payment;

        if ( $pagenow == 'index.php' ) {
            wp_register_style( 'Class_PGAll_Admin_Meta_Boxes', $inicis_payment->plugin_url() . '/assets/css/admin-dashboard.css', null, '1.0' );
            wp_enqueue_style( 'Class_PGAll_Admin_Meta_Boxes' );

            add_meta_box(
                'pgall_notice'
                ,'<div id="pgall_admin_meta_box_logo"><img src="' . $inicis_payment->plugin_url() . '/assets/images/admin-dashboard/codem_icon.jpg"></div>' . __('코드엠샵 워드프레스 결제 플러그인 공지사항','inicis-for-woocommerce')
                ,'Class_PGAll_Admin_Meta_Boxes::dashboard_widget_pgall_notice'
                ,'dashboard'
                ,'normal'
                ,'high'
                ,''
            );
        }
    }

    public static function dashboard_widget_pgall_notice() {
        global $inicis_payment;

        ?>
        <table class="mpg-noticewrap">
            <?php

            //XML 형태로 넘어오는 Feed 값 가져오기
            $url = "http://www.pgall.co.kr/category/notice/feed/";

            $response = wp_remote_post( $url, array(
                    'method' => 'POST',
                    'timeout' => 120,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'cookies' => array()
                )
            );

            if ( is_wp_error( $response ) ) {
                echo __("데이터를 가져올 수 없습니다. 잠시후 다시 새로고침하여 주시기 바랍니다.",'inicis-for-woocommerce');
            } else {
                try {
                    $xmldata = new SimpleXMLElement($response['body']);

                    //Feed XML 데이터 출력
                    $limit = 5; //가져올 갯수 지정
                    $maxitem = count($xmldata->channel->item);

                    for($i=0;$i<$maxitem;$i++)
                    {
                        if($i < $limit){
                            $item = $xmldata->channel->item[$i];
                            $tmp_msg = Class_PGAll_Admin_Meta_Boxes::cut_str($item->title, 100);

                            //카테고리 갯수 확인
                            $icon = '';
                            if( count($item->category) > 1 ) {
                                foreach($item->category as $category) {
                                    switch($category) {
                                        case '중요도-상':
                                            $icon = 'high.png';
                                            break;
                                        case '중요도-중':
                                            $icon = 'middle.png';
                                            break;
                                        case '중요도-하':
                                            $icon = 'low.png';
                                            break;
                                        default:
                                            break;
                                    }
                                }
                                if( empty($icon) ){
                                    $icon = 'low.png';
                                }
                            } else {
                                $icon = 'low.png';
                            }
                            ?>
                            <tr>
                                <th class="mpg-headwrap">
                                    <div class="mpg-circle">
                                        <img src="<?php echo $inicis_payment->plugin_url() . '/assets/images/admin-dashboard/' . $icon; ?>" />
                                    </div>
                                </th>
                                <td class="mpg-titlewrap">
                                    <a href="<?php echo $item->link; ?>" target="_blank" class="mpgtitle"><?php echo $tmp_msg; ?></a>
                                </td>
                                <td class="mpg-day">
                                    | <?php echo date("Y-m-d", strtotime($item->pubDate)); ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                } catch (Exception $e) {
                    echo __("데이터를 가져올 수 없습니다. 잠시후 다시 새로고침하여 주시기 바랍니다.",'inicis-for-woocommerce');
                }
            }
            ?>

        </table>

        <div class="mpg-view">
            <a href="http://www.pgall.co.kr/category/notice/" target="_blank"><?php echo __('더보기','inicis-for-woocommerce'); ?></a>
        </div>

        <?php
    }

    public static function cut_str($str, $len, $suffix="…") {

        $s = substr($str, 0, $len);
        $cnt = 0;
        for ($i=0; $i<strlen($s); $i++)
            if (ord($s[$i]) > 127)
                $cnt++;

        $s = substr($s, 0, $len - ($cnt % 3));

        if (strlen($s) >= strlen($str))
            $suffix = "";
        return $s . $suffix;
    }

}
new Class_PGAll_Admin_Meta_Boxes();