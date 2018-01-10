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
//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) exit;

if( class_exists('WC_Payment_Gateway') ) { 
    
    include_once('class-encrypt.php');
    
    class WC_Gateway_Inicis extends WC_Payment_Gateway{
        public function __construct(){
            add_filter( 'woocommerce_my_account_my_orders_actions',  array($this, 'woocommerce_my_account_my_orders_actions'), 10, 2 );
			add_filter( 'woocommerce_get_checkout_order_received_url',  array($this, 'woocommerce_get_checkout_order_received_url'), 99, 2 );
        }
		function woocommerce_get_checkout_order_received_url($order_received_url, $order_class) {
			if (defined('ICL_LANGUAGE_CODE')) {
				$checkout_pid = wc_get_page_id( 'checkout' ); 
				if( !empty($_REQUEST['lang']) ) {
					if(function_exists('icl_object_id')) {
						$checkout_pid = icl_object_id($checkout_pid, 'page', true, $_REQUEST['lang']);
					} 
				}
				$order_received_url = wc_get_endpoint_url( 'order-received', $order_class->id, get_permalink( $checkout_pid ) );
		
				if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) || is_ssl() ) {
					$order_received_url = str_replace( 'http:', 'https:', $order_received_url );
				}
		
				$order_received_url = add_query_arg( 'key', $order_class->order_key, $order_received_url );
				return $order_received_url;
				
			} else {
				return $order_received_url;
			}
		}
        function get_payment_description( $paymethod ) {
            switch($paymethod){
                case "card": 
                    return __( '신용카드(안심클릭)', 'inicis_payment' );
                    break;
                case "vcard":
                    return __( '신용카드(ISP)', 'inicis_payment' );
                    break;
                case "directbank":
                    return __( '실시간계좌이체', 'inicis_payment' );
                    break;
                case "wcard": 
                    return __( '신용카드(모바일)', 'inicis_payment' );
                    break;
                case "vbank": 
                    return __( '가상계좌 무통장입금', 'inicis_payment' );
                    break;
                case "bank":
                    return __( '실시간계좌이체(모바일)', 'inicis_payment' );
                    break;
                case "hpp":
                    return __( '휴대폰 소액결제', 'inicis_payment' );
                    break;
                case "mobile":
                    return __( '휴대폰 소액결제(모바일)', 'inicis_payment' );
                    break;
                case "kpay":
                    return __( 'KPAY 간편결제', 'inicis_payment' );
                    break;
                default:
                    return $paymethod;
                    break;
            }
        }
        public function wp_ajax_repay_request() {
            global $woocommerce;

            $order_id = $_REQUEST['order_id'];
            $repay_price = $_REQUEST['repay_price'];
            $order = new WC_Order( $order_id );

            $paymethod = get_post_meta($order_id, "_inicis_paymethod", true);
            $paymethod = strtolower($paymethod);
            $paymethod_tid = get_post_meta($order_id, "_inicis_paymethod_tid", true);

            if( empty($paymethod) || empty($paymethod_tid) ) {
                wp_send_json_error( __( '주문 정보에 오류가 있습니다.', 'inicis_payment' ) );
            }

            //부분취소 요청
            $rst = $this->repay_request($order_id, $repay_price);

            if($rst == "success"){
                if($_POST['repay_request']) {
                    unset($_POST['repay_request']);
                }

                $order->add_order_note( sprintf( __('관리자의 요청으로 주문(%s)이 부분취소 처리 되었습니다.', 'inicis_payment'), $this->get_payment_description($paymethod)) );
                update_post_meta( ifw_get($order, 'id'), '_codem_inicis_order_repayed', TRUE);
                wp_send_json_success( __( '주문이 정상적으로 부분취소되었습니다. 주문 메모 내용을 확인해 주세요.', 'inicis_payment' ) );
            } else {
                wp_send_json_error( __( '주문 부분취소 시도중 오류가 발생했습니다. 내용 : ', 'inicis_payment' ) . $rst );
                wc_add_notice( __( '주문 부분취소 시도중 오류가 발생했습니다. 관리자에게 문의해주세요. 내용 : ', 'inicis_payment' ) . $rst, 'error' );
            }


        }
        public function wp_ajax_refund_request() {
            global $woocommerce;
            $valid_order_status = $this->settings['possible_refund_status_for_admin'];
            $after_refund_order_status = $this->settings['order_status_after_refund'];

            $order_id = $_REQUEST['order_id'];
            $order = new WC_Order( $order_id );

        
            if( !in_array($order->get_status(), $valid_order_status) ){
                wp_send_json_error( __('주문을 취소할 수 없는 상태입니다.', 'inicis_payment' ) );
            }
        
            $paymethod = get_post_meta($order_id, "_inicis_paymethod", true);
            $paymethod = strtolower($paymethod); 
            $paymethod_tid = get_post_meta($order_id, "_inicis_paymethod_tid", true);

            if( empty($paymethod) || empty($paymethod_tid) ) {
                wp_send_json_error( __( '주문 정보에 오류가 있습니다.', 'inicis_payment' ) );
            }
            
            $rst = $this->cancel_request($paymethod_tid, __( '관리자 주문취소', 'inicis_payment' ), __( 'CM_CANCEL_002', 'inicis_payment' ) );
            if($rst == "success"){
                if($_POST['refund_request']) {
                    unset($_POST['refund_request']);
                }
                
                $order->update_status( $after_refund_order_status );
                $order->add_order_note( sprintf( __('관리자의 요청으로 주문(%s)이 취소 처리 되었습니다.', 'inicis_payment'), $this->get_payment_description($paymethod)) );
                update_post_meta( ifw_get($order, 'id'), '_codem_inicis_order_cancelled', TRUE);
                wp_send_json_success( __( '주문이 정상적으로 취소되었습니다.', 'inicis_payment' ) );
            } else {
                wp_send_json_error( __( "주문 취소 시도중 오류가 발생했습니다.\r\n\r\n내용 : ", 'inicis_payment' ) . $rst );
                wc_add_notice( __( "주문 취소 시도중 오류가 발생했습니다. 관리자에게 문의해주세요.\r\n\r\n내용 : ", 'inicis_payment' ) . $rst, 'error' );
            }
        }
        public function woocommerce_payment_complete_order_status($new_order_status, $id) {
            $paymethod = get_post_meta($id, '_payment_method', true);

            if($this->id == $paymethod) {
                $order_status = $this->settings['order_status_after_payment'];
                if ( !empty($order_status) ) {
                    return $order_status;
                } else {
                    return $new_order_status;
                }
            } else {
                return $new_order_status;
            }
        }
        public function inicis_mypage_cancel_order($order_id) {
            global $woocommerce;
            $order = new WC_Order($order_id);

            $valid_order_status = $this->settings['possible_refund_status_for_mypage'];
            $after_refund_order_status = $this->settings['order_status_after_refund'];

            if( $order->get_status() == 'pending') {
                $order->update_status('cancelled');
                wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'inicis_payment' ), 'success' );
                return;
            }

            if( !in_array($order->get_status(), $valid_order_status) ){
                wc_add_notice( __( '주문을 취소할 수 없는 상태입니다. 관리자에게 문의해 주세요.', 'inicis_payment' ), 'error' );
                return;
            }
            
            $paymethod = get_post_meta($order_id, "_inicis_paymethod", true);
            $paymethod = strtolower($paymethod); 
            $paymethod_tid = get_post_meta($order_id, "_inicis_paymethod_tid", true);

            if( !empty($paymethod) || !empty($paymethod_tid) ) {
                //가상계좌 취소 처리
                if($paymethod == 'vbank' && $order->get_status() == 'on-hold') {
                    $order->update_status('cancelled');
                    wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'inicis_payment' ), 'success' );
                    return;
                } else {
                    $rst = $this->cancel_request($paymethod_tid, __( '사용자 주문취소', 'inicis_payment' ), __( 'CM_CANCEL_001', 'inicis_payment' ) );
                    if($rst == "success"){
                        if($_POST['refund_request']) {
                            unset($_POST['refund_request']);
                        }
                        $order->update_status( $after_refund_order_status );
                        wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'inicis_payment' ), 'success' );
                        $order->add_order_note( sprintf( __('사용자의 요청으로 주문(%s)이 취소 처리 되었습니다.', 'inicis_payment'), $this->get_payment_description($paymethod)) );
                        update_post_meta( ifw_get($order, 'id'), '_codem_inicis_order_cancelled', TRUE);
                    } else {
                        wc_add_notice( __( '주문 취소 시도중 오류가 발생했습니다. 관리자에게 문의해주세요.', 'inicis_payment' ), 'error' );
                        $order->add_order_note( sprintf( __('사용자 주문취소 시도 실패 (에러메세지 : %s)', 'inicis_payment'), $rst) );
                    }
                }
            } else {
                wc_add_notice( __( '주문 취소 시도중 오류 (에러메시지 : 결제수단 및 거래번호 없음)가 발생했습니다. 관리자에게 문의해주세요.', 'inicis_payment' ), 'error' );
                $order->add_order_note( sprintf( __('사용자 주문취소 시도 실패 (에러메세지 : %s)', 'inicis_payment'), '결제수단 및 거래번호 없음') );
            }
        }
        public function ifw_is_admin_refundable($refundable, $order) {
            $valid_order_status = $this->settings['possible_refund_status_for_admin'];

            if( !empty($valid_order_status) && $valid_order_status != '-1' && in_array($order->get_status(), $valid_order_status) ){
                return true;
            }else{
                return false;
            }
        }
        public function woocommerce_my_account_my_orders_actions($actions, $order){
            $payment_method = get_post_meta( ifw_get($order, 'id'), '_payment_method', true);

            if($payment_method == $this->id) {
                $valid_order_status = $this->settings['possible_refund_status_for_mypage'];
            
                if( !empty($valid_order_status) && $valid_order_status != '-1' && in_array($order->get_status(), $valid_order_status) ){
                    
                    $cancel_endpoint = get_permalink( wc_get_page_id( 'cart' ) );
                    $myaccount_endpoint = esc_attr( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' )) );

                    //결제 수단과 TID(거래번호)가 없는 경우 사용자 내계정 페이지에서 취소버튼 미노출 처리 추가
                    $paymethod = get_post_meta( ifw_get($order, 'id'), "_inicis_paymethod", true);
                    $paymethod = strtolower($paymethod);
                    $paymethod_tid = get_post_meta(ifw_get($order, 'id'), "_inicis_paymethod_tid", true);

                    if( !empty($paymethod) || !empty($paymethod_tid) ) {

                        $actions['cancel'] = array(
                            'url'  => wp_nonce_url( add_query_arg( array(
                                'inicis-cancel-order' => 'true',
                                'order'               => ifw_get( $order, 'order_key' ),
                                'order_id'            => ifw_get( $order, 'id' ),
                                'redirect'            => $myaccount_endpoint
                            ), $cancel_endpoint ), 'mshop-cancel-order' ),
                            'name' => __( 'Cancel', 'woocommerce' )
                        );
                    }
                }else{
                    unset($actions['cancel']);
                }
            } 
        
            return $actions;
        }
        public function validate_ifw_order_status_field($key) {
            $option_key = $this->id . '_' . $key;
            if( empty($_POST[$option_key]) ) {
                return "-1";
            } else {
                return $_POST[$option_key];    
            }
        }
        public function validate_ifw_logo_upload_field($key) {
            return $_POST[$key];
        }
        public function validate_ifw_signkey_field($key) {
            return $_POST[$key];
        }
        public function validate_ifw_keyfile_upload_field($key) {
            if( empty($_FILES['upload_keyfile']) && !isset($_FILES['upload_keyfile']) ) {
                return; 
            }    
            if ( !file_exists( WP_CONTENT_DIR . '/inicis/upload' )) {
                $old = umask(0); 
                mkdir( WP_CONTENT_DIR . '/inicis/upload', 0777, true );
                umask($old);
            }
            
            if( $_FILES['upload_keyfile']['size'] > 4086 ) {
                return false;
            }

            if( !class_exists('ZipArchive') ) {
                return false;
            } 
            
            $zip = new ZipArchive();
            if(isset($_FILES['upload_keyfile']['tmp_name']) && !empty($_FILES['upload_keyfile']['tmp_name'])) {
                if($zip->open($_FILES['upload_keyfile']['tmp_name']) == TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if( !in_array( $filename, array('readme.txt', 'keypass.enc', 'mpriv.pem', 'mcert.pem') ) ) {
                            return false;
                        }
                    }
                }
                
                $movefile = move_uploaded_file($_FILES['upload_keyfile']['tmp_name'], WP_CONTENT_DIR . '/inicis/upload/' . $_FILES['upload_keyfile']['name'] );
                if ( $movefile ) {
                    WP_Filesystem();
                    $filepath = pathinfo( WP_CONTENT_DIR . '/inicis/upload/' . $_FILES['upload_keyfile']['name'] );
                    $unzipfile = unzip_file( WP_CONTENT_DIR . '/inicis/upload/' . $_FILES['upload_keyfile']['name'], WP_CONTENT_DIR . '/inicis/key/' . $filepath['filename'] );
    
                    $this->init_form_fields();
    
                    if ( !is_wp_error($unzipfile) ) {
                        if ( !$unzipfile )  {
                            return false;    
                        }
                        return true;
                    }
                } else {
                    return false;
                }   
            }
        }
        public function clean_status($arr_status) {
			if( !empty($arr_status) ) {
				$reoder = array();
				foreach($arr_status as $status => $status_name) {
					$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
					$reoder[$status] = $status_name;
				}
				return $reoder;
			} else {
				return $arr_status;
			}
        }
        public function generate_ifw_order_status_html($key, $value) {
            $option_key = $this->id . '_' . $key;
			
			if(version_compare( WOOCOMMERCE_VERSION, '2.2.0', '>=' )) {
				$shop_order_status = $this->clean_status(wc_get_order_statuses());	
			} else {
	            $shop_order_status = get_terms(array('shop_order_status'), array('hide_empty' => false));
			}			
						
            if( !empty( $this->settings[$key] ) ){
                $selections = $this->settings[$key];
            }

            if( empty($selections) ) {
                $selections = $value['default'];
            } else if( $selections == '-1' ) {
                $selections = null;
            }
            
            ob_start();
            ?><tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html($value); ?>
                </th>
                <td class="forminp">
                    <select multiple="multiple" name="<?php echo esc_attr( $option_key ); ?>[]" style="width:350px" data-placeholder="<?php _e( '주문 상태를 선택하세요.', 'inicis_payment' ); ?>" title="<?php _e( 'Order Status', 'inicis_payment' ); ?>" class="chosen_select">
                        <?php
                            if ( $shop_order_status ) {
								if(version_compare( WOOCOMMERCE_VERSION, '2.2.0', '>=' )) {
	                            	foreach ( $shop_order_status as $status => $status_name ) {
	                                    if( !empty($selections) ) {
	                                        $selected = selected( in_array( $status, $selections ), true, false );
	                                    } else {
	                                        $selected = '';
	                                    }
	                                    echo '<option value="' . esc_attr( $status ) . '" ' . $selected .'>' . $status_name . '</option>';
	                                }									
								} else {
	                                foreach ( $shop_order_status as $status ) {
	                                    if( !empty($selections) ) {
	                                        $selected = selected( in_array( $status->slug, $selections ), true, false );
	                                    } else {
	                                        $selected = '';
	                                    }
	                                    echo '<option value="' . esc_attr( $status->slug ) . '" ' . $selected .'>' . $status->name . '</option>';
	                                }									
								}
                            }
                        ?>
                    </select><br>
                    <a class="select_all button" href="#"><?php _e( 'Select all', 'inicis_payment' ); ?></a> <a class="select_none button" href="#"><?php _e( 'Select none', 'inicis_payment' ); ?></a>
                </td>
            </tr><?php
            return ob_get_clean();
        }
        public function generate_ifw_keyfile_upload_html($key, $value) {
            ob_start();
            ?><tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html($value); ?>
                </th>
                <td class="forminp">
                    <input id="upload_keyfile" type="file" size="36" name="upload_keyfile" />
                </td>
            </tr><?php
            return ob_get_clean();
        }
        public function generate_ifw_logo_upload_html($key, $value) {
            if( !empty( $this->settings[$key] ) ) {
                $imgsrc = $this->settings[$key];
            }

            if( empty($imgsrc) ){
                $imgsrc = $value['default'];
            }
            
            ob_start();
            ?><tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html($value); ?>
                </th>
                <td class="forminp">
                    <img src="<?php echo $imgsrc; ?>" id="upload_logo_preview" style="border: solid 1px #666;"><br>
                    <input id="upload_logo" type="text" size="36" name="<?php echo $key; ?>" value="<?php echo $imgsrc; ?>" />
                    <input class="button" id="upload_logo_button" type="button" value="<?php _e( 'Upload/Select Logo', 'inicis_payment' ); ?>" />
                    <br>                    
                </td>
            </tr><?php
            return ob_get_clean();
        }
        public function generate_ifw_signkey_html($key, $value){
            if( !empty( $this->settings[$key] ) ) {
                $data = $this->settings[$key];
            }

            if( empty($data) ){
                $data = $value['default'];
            }
            ob_start();
            ?><tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $key ); ?>"><?php echo $value['title']; ?></label>
                <?php echo $this->get_tooltip_html($value); ?>
            </th>
            <td class="forminp">
                <input id="upload_logo" type="text" size="36" name="<?php echo $key; ?>" value="<?php echo $data; ?>" placeholder="웹표준 사인키를 입력하세요." />
                <br>
                <p class="description"><?php echo $value['sub_description'];?></p>
            </td>
            </tr><?php
            return ob_get_clean();

        }
        public function generate_ifw_vbank_url_html($key, $value) {
            ob_start();
            ?><tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo $this->get_tooltip_html($value); ?>
            </th>
            <td class="forminp">
                <?php echo untrailingslashit(WC()->api_request_url(get_class($this) . '?type=vbank_noti'),true); ?><br>
            </td>
            </tr><?php
            return ob_get_clean();
        }
        function check_mid($mid){
            if(!empty($mid)) {
                $tmpmid = substr($mid, 0, 3);
                $tmpmid_escrow = substr($mid, 0, 5);

                $valid_codes = array(
                    base64_decode("SU5J"),
                    base64_decode("aW5p"),
                    base64_decode("SUVT"),
                    base64_decode("Y29k"),
                    base64_decode("Q09E"),
                    base64_decode("RVNDT0Q="),
                );
                if( ! in_array( $tmpmid, $valid_codes ) && ! in_array( $tmpmid_escrow, $valid_codes ) )  {
                    $tmparr = get_option('woocommerce_'.$this->id.'_settings');
                    $tmparr['merchant_id'] = base64_decode('SU5JcGF5VGVzdA==');
                    $this->settings['merchant_id'] = base64_decode('SU5JcGF5VGVzdA==');
                    update_option( 'woocommerce_'.$this->id.'_settings', $tmparr );
                    return false;
                }
                return true;
            }
            return false;
        }
        public function admin_options() {
            global $woocommerce, $inicis_payment;

            wp_enqueue_script( 'media-upload' );
            wp_enqueue_script( 'thickbox' );
            wp_enqueue_style( 'thickbox' );

             $inicis_payment->license_manager->load_activation_form();

            if ( isset( $this->method_description ) && $this->method_description != '' ) {
                $tip = '<img class="help_tip" data-tip="' . esc_attr( $this->method_description ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';
            } else {
                $tip = '';
            }

            if(!empty($_POST['woocommerce_'.$this->id.'_merchant_id'])) {
                $mid = trim($_POST['woocommerce_'.$this->id.'_merchant_id']);
                if(!$this->check_mid($mid)) {
                    echo '<div id="message" class="error fade"><p><strong>' . __( '상점 아이디가 정확하지 않습니다. 상점 아이디를 확인하여 주세요. 문제가 계속 된다면 메뉴얼 또는 <a href="http://www.pgall.co.kr" target="_blank">http://www.pgall.co.kr</a> 사이트에 문의하여 주세요.', 'inicis_payment' ) . '</strong></p></div>';
                }
            }
            ?>
	        <div class="mshop-setting-page-wrapper" style="display:none">
            <h3><?php echo $this->method_title; echo $tip;?></h3>

            <?php if( !$this->is_valid_for_use() ) { ?>
                <div class="inline error"><p><strong><?php _e( '해당 결제 방법 비활성화', 'inicis_payment' ); ?></strong>: <?php _e( '이니시스 결제는 KRW, USD 이외의 통화로는 결제가 불가능합니다. 상점의 통화(Currency) 설정을 확인해주세요.', 'inicis_payment' ); ?></p></div>
            <?php
            } else {
                $this->generate_pg_notice();
                ?>
                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
            <?php
            }
	        ?></div><?php
	        }
        function generate_pg_notice(){
            if(isset($_GET['noti_close'])) {
                if($_GET['noti_close'] == '1') {
                    update_option('inicis_notice_close', '1');
                } else if($_GET['noti_close'] == '0') {
                    update_option('inicis_notice_close', '0');
                }
            }

            $css = '';
            if(get_option('inicis_notice_close') == '1') {
                $css = 'display:none;';
                $admin_noti_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_'.$this->id.'&noti_close=0');
                $admin_noti_txt = __('열기', 'inicis_payment');
            }else{
                $css = '';
                $admin_noti_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_'.$this->id.'&noti_close=1');
                $admin_noti_txt = __('닫기', 'inicis_payment');
            }
            ?>
            <div id="welcome-panel" class="welcome-panel" style="padding-top:15px;">
                <div class="welcome-panel-content">
                    <h3 style="font-size:16px;font-weight:bold;margin-bottom: 15px;"><?php _e('공지사항', 'inicis_payment'); ?></h3>
                    <a class="welcome-panel-close" style="padding-top:15px;" href="<?php echo $admin_noti_url; ?>"><?php echo $admin_noti_txt; ?></a>
                    <div class="tab_contents" style="line-height:16px;<?php echo $css; ?>">
                        <ul>
            <?php
                try{
                    $url = "http://www.pgall.co.kr/category/pg_notice/feed";
                    $response = wp_remote_get($url);
                    $xmldata = new SimpleXMLElement($response['body']);
                    $limit = 5;
                    $maxitem = count($xmldata->channel->item);
                    if($maxitem <= 0) {
                        echo '
                    <li style="font-size:12px;">
                        <span>' . __( '아직 공지사항이 없거나 데이터를 가져오지 못했습니다. 페이지를 새로고침 하여 주시기 바랍니다.', 'inicis_payment') . '</span>
                    </li>';
                    }

                    for($i=0;$i<$maxitem;$i++)
                    {
                        if($i < $limit){
                            $item = $xmldata->channel->item[$i];
                            echo '<li style="font-size: 13px;font-weight: bold;">
                                <span class="label blue"><i class="icon-bullhorn"></i></span>
                                <span class="text_gray italic">'.date("Y-m-d", strtotime($item->pubDate)).'</span> |
                                <a href="'.$item->link.'" target="_blank">'.$item->title.'</a>
                              </li>';
                        }
                    }
                } catch(Exception $e){
                    echo '
                    <li style="font-size:12px;">
                        <span>' . __( '아직 공지사항이 없거나 데이터를 가져오지 못했습니다. 페이지를 새로고침 하여 주시기 바랍니다.', 'inicis_payment') . '</span>
                    </li>';
                }
            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
        }
        public function init_form_fields() {
            global $inicis_payment;

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('사용', 'inicis_payment'),
                    'type' => 'checkbox',
                    'label' => $this->title,
                    'default' => 'no'
                    ),
                'title' => array(
                    'title' => __('결제모듈 이름', 'inicis_payment'),
                    'type' => 'text',
                    'description' => __('사용자들이 체크아웃(결제진행)시에 나타나는 이름으로 사용자들에게 보여지는 이름입니다.', 'inicis_payment'),
                    'default' => $this->title,
                    'desc_tip' => true,
                    ),
                'description' => array(
                    'title' => __('결제모듈 설명', 'inicis_payment'),
                    'type' => 'textarea',
                    'description' => __('사용자들이 체크아웃(결제진행)시에 나타나는 설명글로 사용자들에게 보여지는 내용입니다.', 'inicis_payment'),
                    'default' => $this->description,
                    'desc_tip' => true,
                    ),
                'libfolder' => array(
                    'title' => __('이니페이 설치 경로', 'inicis_payment'),
                    'type' => 'text',
                    'description' => __('이니페이 설치 경로 안에 key 폴더(키파일)와 log 폴더(로그)가 위치한 경로를 입력해주세요. 키파일 폴더와 로그 폴더의 권한 설정은 가이드를 참고해주세요. <br><br><span style="color:red;font-weight:bold;">주의! 사용하시는 호스팅이나 서버 상태에 따라서 웹상에서 접근 불가능한 경로에 업로드 하시고 절대경로 주소를 입력해주세요. 웹상에서 접근 가능한 경로에 폴더가 위치한 경우 키파일 및 로그 파일 노출로 인한 보안사고가 발생할 수 있으며 이 경우 발생하는 문제는 상점의 책임입니다.</span>', 'inicis_payment'),
                    'default' => WP_CONTENT_DIR . '/inicis/',
                    'desc_tip' => true,
                    ),
                'merchant_id' => array(
                    'title' => __('상점 아이디', 'inicis_payment'),
                    'class' => 'chosen_select',
                    'type' => 'select',
                    'options' => $this->get_keyfile_list(),
                    'description' => __('이니시스 상점 아이디(MID)를 선택하세요.', 'inicis_payment'),
                    'default' => __('INIpayTest', 'inicis_payment'),
                    'desc_tip' => true,
                    ),
                'signkey' => array(
                    'title' => __('<a href="https://iniweb.inicis.com" target="_blank">웹표준 사인키</a>', 'inicis_payment'),
                    'type' => 'ifw_signkey',
                    'description' => __('웹표준 사인키는 결제시 필요한 필수 값으로 상점 관리자 페이지에서 확인이 가능합니다. INIpayTest 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.', 'inicis_payment'),
                    'default' => __('', 'inicis_payment'),
                    'sub_description' => __('결제 테스트용 INIpayTest 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.', 'inicis_payment'),
                    'desc_tip' => true,
                ),
                'possible_refund_status_for_mypage' => array(
                    'title' => __('사용자 주문취소 가능상태', 'inicis_payment'),
                    'type' => 'ifw_order_status',
                    'description' => __('이니시스 결제건에 한해서, 사용자가 My-Account 메뉴에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'inicis_payment'),
                    'default' => array(''),
                    'desc_tip' => true,
                    ),
                'possible_refund_status_for_admin' => array(
                    'title' => __('관리자 주문취소 가능상태', 'inicis_payment'),
                    'type' => 'ifw_order_status',
                    'description' => __('이니시스 결제건에 한해서, 관리자가 관리자 페이지 주문 상세 페이지에서 환불 처리를 할 수 있는 주문 상태를 지정합니다.', 'inicis_payment'),
                    'default' => array('processing'),
                    'desc_tip' => true,
                    ),
                'order_status_after_payment' => array(
                    'title' => __('결제완료시 변경될 주문상태', 'inicis_payment'),
                    'class' => 'chosen_select',
                    'type' => 'select',
                    'options' => $this->get_order_status_list( array( 'cancelled', 'failed', 'on-hold', 'refunded' ) ),
                    'default' => 'processing',
                    'description' => __('이니시스 플러그인을 통한 결제건에 한해서, 결제후 주문접수가 완료된 경우 해당 주문의 상태를 지정하는 필수옵션입니다.', 'inicis_payment'),
                    'desc_tip' => true,
                ),
                'order_status_after_refund' => array(
                    'title' => __('환불처리시 변경될 주문상태', 'inicis_payment'),
                    'class' => 'chosen_select',
                    'type' => 'select',
                    'options' => $this->get_order_status_list( array('completed','on-hold','pending','processing') ),
                    'default' => 'refunded',
                    'description' => __('이니시스 플러그인을 통한 결제건에 한해서, 사용자의 환불처리가 승인된 경우 해당 주문의 상태를 지정하는 필수옵션입니다.','inicis_payment'),
                    'desc_tip' => true,
                ),
                'logo_upload' => array(
                    'title' => __('결제 PG 로고', 'inicis_payment'),
                    'type' => 'ifw_logo_upload',
                    'description' => __('로고를 업로드 및 선택해 주세요. 128 x 40 pixels 사이즈로 지정해주셔야 하며, gif/jpg/png 확장자가 지원됩니다. 투명배경은 허용되지 않습니다. ', 'inicis_payment'),
                    'default' => $inicis_payment->plugin_url() . '/assets/images/codemshop_logo_pg.jpg',
                    'desc_tip' => true,
                    ),
                'keyfile_upload' => array(
                    'title' => __('키파일 업로드', 'inicis_payment'),
                    'type' => 'ifw_keyfile_upload',
                    'description' => __('상점 키파일을 업로드 해주세요.', 'inicis_payment'),
                    'desc_tip' => true,
                    ),
            );
        }
        function get_keyfile_list() {

            if( empty( $this->settings['libfolder'] ) ) {
                $library_path = WP_CONTENT_DIR . '/inicis';
            } else {
                $library_path = $this->settings['libfolder'];
            }

            $dirs = glob( $library_path . '/key/*', GLOB_ONLYDIR);
            if( count($dirs) > 0 ) {
                $result = array();
                foreach ($dirs as $val) {
                    $tmpmid = substr( basename($val), 0, 3 );
					$tmpmid_escrow = substr( basename($val), 0, 5 );

	                $valid_codes = array(
		                base64_decode("SU5J"),
                        base64_decode("aW5p"),
		                base64_decode("SUVT"),
		                base64_decode("Y29k"),
		                base64_decode("Q09E"),
		                base64_decode("RVNDT0Q="),
	                );
	                if( in_array( $tmpmid, $valid_codes ) || in_array( $tmpmid_escrow, $valid_codes ) )  {
                        if ( file_exists( $val . '/keypass.enc' )  && file_exists( $val . '/mcert.pem' ) && file_exists( $val . '/mpriv.pem' ) && file_exists( $val . '/readme.txt' )) {
                            $result[basename($val)] = basename($val);
                        }
                    }
                }
                return $result;
            } else {
                return array( -1 => __( '=== 키파일을 업로드 해주세요 ===', 'inicis_payment' ) );
            }
        }
        function get_order_status_list($except_list) {

            if(version_compare( WOOCOMMERCE_VERSION, '2.2.0', '>=' )) {
	            $shop_order_status = $this->clean_status(wc_get_order_statuses());

	            $reorder = array();
	            foreach ($shop_order_status as $status => $status_name) {
	                $reorder[$status] = $status_name;
	            }

	            foreach ($except_list as $val) {
	                unset($reorder[$val]);
	            }

	            return $reorder;
			} else {

	            $shop_order_status = get_terms(array('shop_order_status'), array('hide_empty' => false));

	            $reorder = array();
	            foreach ($shop_order_status as $key => $value) {
	                $reorder[$value->slug] = $value->name;
	            }

	            foreach ($except_list as $val) {
	                unset($reorder[$val]);
	            }

	            return $reorder;
			}
        }
        function is_valid_for_use() {
            if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_inicis_card_supported_currencies', array( 'USD', 'KRW' ) ) ) ) {
            	return false;
            }

            if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_inicis_bank_supported_currencies', array( 'USD', 'KRW' ) ) ) ) {
                return false;
            }

            return true;
        }
        function repay_request($order_id, $repay_price ){
            global $woocommerce, $inicis_payment;

            require_once($inicis_payment->plugin_path() . "/lib/inipay50/INILib.php");

            $inipay = new INIpay50();
            $order = new WC_Order($order_id);

            $oldtid = get_post_meta($order_id, '_inicis_paymethod_tid', true);  //기존 원거래 주문TID

            $confirm_price = ( ( $order->get_total() - $order->get_total_refunded() ) - $repay_price );    //재승인 요청금액(기존승인금액 - 취소금액)

            $tax = ($repay_price * 1.1);
            $taxfree = 0;

            $order->add_order_note( sprintf( __('관리자의 의해 부분취소 요청을 시작합니다.<br>요청금액 : %s','inicis_payment'), $repay_price ) );

            $inipay->SetField("inipayhome", $this->settings['libfolder']);
            $inipay->SetField("type", "repay");      // 고정 (절대 수정 불가)
            $inipay->SetField("pgid", "INIphpRPAY");      // 고정 (절대 수정 불가)
            $inipay->SetField("subpgip","203.238.3.10"); 				// 고정
            $inipay->SetField("debug", "false");        // 로그모드("true"로 설정하면 상세로그가 생성됨.)
            $inipay->SetField("mid", $this->settings['merchant_id']);
            $inipay->SetField("admin", "1111");         //비대칭 사용키 키패스워드
            $inipay->SetField("oldtid", $oldtid);            // 취소할 거래의 거래아이디
            $inipay->SetField("currency", 'WON');     // 화폐단위
            $inipay->SetField("price", $repay_price);      //취소금액
            $inipay->SetField("confirm_price", $confirm_price);      //승인요청금액
            $inipay->SetField("buyeremail", ifw_get($order, 'billing_email') );      // 구매자 이메일 주소
            $inipay->SetField("tax",$tax);
            $inipay->SetField("taxfree",$taxfree);

            $inipay->startAction();

            if($inipay->getResult('ResultCode') == "00"){

                $refund_reason = __('관리자의 요청에 의한 부분취소','inicis_payment');

                //부분 환불 처리
                $refund = wc_create_refund( array(
                    'amount'     => $repay_price,
                    'reason'     => $refund_reason,
                    'order_id'   => $order_id,
                    'line_items' => array(),
                ) );

                //부분취소후 재승인 금액이 0원인 경우 모든 금액을 부분환불 처리한 것으로 이경우 환불됨 상태로 변경처리.
                if( $confirm_price == 0 ) {
                    $order->update_status('refunded');
                }

                //부분환불 정보 확인
                $inicis_repay = get_post_meta( $order_id, '_inicis_repay', true);
                $inicis_repay = json_decode($inicis_repay, true);

                if( !empty( $inicis_repay ) ) {
                    //부분환불 정보가 있음. 기존 정보에 추가하여 처리
                    $repay_cnt = count($inicis_repay);
                    $inicis_repay[ ($repay_cnt+1) ] = array(
                        'newtid' => $inipay->getResult('TID'),                      //신거래번호TID
                        'oldtid' => $inipay->getResult('PRTC_TID'),                 //원거래번호TID
                        'result_code' => $inipay->getResult('ResultCode'),          //결과코드
                        'result_msg' => mb_convert_encoding($inipay->GetResult('ResultMsg'), "UTF-8", "EUC-KR"),            //결과메시지
                        'refund_price'  => $inipay->getResult('PRTC_Price'),        //부분취소요청금액
                        'remain_price'  => $inipay->getResult('PRTC_Remains'),      //최종결제금액(부분취소후 남은결제금액)
                        'type'  => $inipay->getResult('PRTC_Type'),                 //부분취소, 재승인 구분값(0:재승인,1:부분취소)
                        'req_cnt'   => $inipay->getResult('PRTC_Cnt'),              //부분취소(재승인)요청횟수
                    );
                } else {
                    //부분환불 정보가 확인안되는 경우 최초 등록시로 처리
                    $inicis_repay['1'] = array(
                        'newtid' => $inipay->getResult('TID'),                      //신거래번호TID
                        'oldtid' => $inipay->getResult('PRTC_TID'),                 //원거래번호TID
                        'result_code' => $inipay->getResult('ResultCode'),          //결과코드
                        'result_msg' => mb_convert_encoding($inipay->GetResult('ResultMsg'), "UTF-8", "EUC-KR"),            //결과메시지
                        'refund_price'  => $inipay->getResult('PRTC_Price'),        //부분취소요청금액
                        'remain_price'  => $inipay->getResult('PRTC_Remains'),      //최종결제금액(부분취소후 남은결제금액)
                        'type'  => $inipay->getResult('PRTC_Type'),                 //부분취소, 재승인 구분값(0:재승인,1:부분취소)
                        'req_cnt'   => $inipay->getResult('PRTC_Cnt'),              //부분취소(재승인)요청횟수
                    );
                }
                $order->add_order_note(
                    sprintf(__('관리자의 의해 부분취소 요청이 정상 처리되었습니다.<br>원거래TID : %s, 신거래TID : %s, 결과코드 : %s, 결과내용 : %s, 부분취소요청금액 : %s, 부분취소후남은금액 : %s, 부분취소/재승인 구분 : %s, 부분취소횟수 : %s', 'inicis_payment'),
                    $inipay->getResult('PRTC_TID'),
                    $inipay->getResult('TID'),
                    $inipay->getResult('ResultCode'),
                    mb_convert_encoding($inipay->GetResult('ResultMsg'), "UTF-8", "EUC-KR"),
                    $inipay->getResult('PRTC_Price'),
                    $inipay->getResult('PRTC_Remains'),
                    $inipay->getResult('PRTC_Type'),
                    $inipay->getResult('PRTC_Cnt')
                ) );

                update_post_meta( $order_id, '_inicis_repay', json_encode($inicis_repay, JSON_UNESCAPED_UNICODE) );

                return "success";
            }else{
                $order->add_order_note(
                    sprintf(__('관리자의 의해 부분취소 요청을 하였으나 다음의 사유로 실패하였습니다.<br>사유 : %s', 'inicis_payment'),
                    mb_convert_encoding($inipay->GetResult('ResultMsg'), "UTF-8", "EUC-KR")
                ));

                return mb_convert_encoding($inipay->GetResult('ResultMsg'), "UTF-8", "EUC-KR");
            }
        }
        function cancel_request($tid, $msg, $code="1"){
            global $woocommerce, $inicis_payment;

            require_once($inicis_payment->plugin_path() . "/lib/inipay50/INILib.php");
            $inipay = new INIpay50();

            $inipay->SetField("inipayhome", $this->settings['libfolder']);
            $inipay->SetField("type", "cancel");
            $inipay->SetField("debug", "false");

            if( $this->id == 'inicis_stdescrow_bank') {
                $inipay->SetField("mid", $this->settings['escrow_merchant_id']);
            } else {
                $inipay->SetField("mid", $this->settings['merchant_id']);
            }

            $inipay->SetField("admin", "1111");
            $inipay->SetField("tid", $tid);
            $inipay->SetField("cancelmsg", $_REQUEST['msg']);

            if($code != ""){
                $inipay->SetField("cancelcode", $code);
            }

            $inipay->startAction();

            if($inipay->getResult('ResultCode') == "00"){
                return "success";
            }else{
                return mb_convert_encoding($inipay->GetResult('ResultMsg'), "UTF-8", "EUC-KR");
            }
        }
        function successful_request_std( $posted ) {
            global $woocommerce, $inicis_payment;

            if( !file_exists($inicis_payment->plugin_path() . "/lib/inistd/INIStdPayUtil.php" ) ) {
                $this->inicis_print_log( __( '에러 : INIStdPayUtil.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ), 'INIStd' );
                wc_add_notice( __( '에러 : INIStdPayUtil.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ), 'error' );
                die('<span style="color:red;font-weight:bold;">' . __( '에러 : INIStdPayUtil.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ) . '</span>');
            }
            if( !file_exists($inicis_payment->plugin_path() . "/lib/inistd/HttpClient.php" ) ) {
                $this->inicis_print_log( __( '에러 : HttpClient.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ), 'INIStd' );
                wc_add_notice( __( '에러 : HttpClient.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ), 'error' );
                die('<span style="color:red;font-weight:bold;">' . __( '에러 : HttpClient.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ) . '</span>');
            }
            require_once($inicis_payment->plugin_path() . '/lib/inistd/INIStdPayUtil.php');
            require_once($inicis_payment->plugin_path() . '/lib/inistd/HttpClient.php');
            $util = new INIStdPayUtil();

            $this->inicis_print_log( print_r($_REQUEST, true), 'INIStd' );

            if( isset($_REQUEST['resultCode']) ) {
                switch($_REQUEST['resultCode']) {
                    case "V813":
                        $this->inicis_print_log( __( '결제 가능시간(30분) 초과로 인해 자동으로 취소되었습니다. 잠시 후 다시 시도해주세요.', 'inicis_payment' ), 'INIStd' );
                        wc_add_notice( __( '결제 가능시간(30분) 초과로 인해 자동으로 취소되었습니다. 잠시 후 다시 시도해주세요.', 'inicis_payment' ), 'error' );
                        return;
                        break;
                    case "V016":
                        $this->inicis_print_log( __( 'Signkey 가 정확하지 않습니다. 관리자에게 문의하여 주세요. (invalid signkey detected)', 'inicis_payment' ), 'INIStd' );
                        wc_add_notice( __( 'Signkey 가 정확하지 않습니다. 관리자에게 문의하여 주세요. (invalid signkey detected)', 'inicis_payment' ), 'error' );
                        return;
                        break;
                    case "V013":
                        $this->inicis_print_log( __( '존재하지 않는 상점아이디 입니다. 관리자에게 문의하여 주세요. (invalid mid detected)', 'inicis_payment' ), 'INIStd' );
                        wc_add_notice( __( '존재하지 않는 상점아이디 입니다. 관리자에게 문의하여 주세요. (invalid mid detected)', 'inicis_payment' ), 'error' );
                        return;
                        break;
                }
            }

            if( !isset( $_REQUEST['orderNumber'] ) ) {
                $this->inicis_print_log( __( '유효하지않은 주문입니다. 주문번호가 없습니다. (invalid notification)', 'inicis_payment' ), 'INIStd' );
                wc_add_notice( __( '유효하지않은 주문입니다. 주문번호가 없습니다. (invalid notification)', 'inicis_payment' ), 'error' );
                return;
            }

            if( !isset( $_REQUEST['merchantData'] ) ) {
                $this->inicis_print_log( __( '유효하지않은 주문입니다. 해시 데이터가 없습니다. (invalid notification)', 'inicis_payment' ), 'INIStd' );
                wc_add_notice( __( '유효하지않은 주문입니다. 해시 데이터가 없습니다. (invalid notification)', 'inicis_payment' ), 'error' );
                return;
            }

            $merchantData = $_REQUEST["merchantData"];
            $notification = $this->decrypt_notification($merchantData);
            if( empty($notification) ){
                $this->inicis_print_log( __( '유효하지않은 주문입니다.(01xf1)', 'inicis_payment' ), 'INIStd' );
                wc_add_notice( __( '유효하지않은 주문입니다.(01xf1)', 'inicis_payment' ), 'error' );
                return;
            }

            $txnid = $notification->txnid;
            $hash = $notification->hash;

            if(empty($txnid)){
                $this->inicis_print_log( __( '유효하지않은 주문입니다.(01xf2)', 'inicis_payment' ), 'INIStd' );
                wc_add_notice( __( '유효하지않은 주문입니다.(01xf2)', 'inicis_payment' ), 'error' );
                return;
            }

            $userid = get_current_user_id();
            $orderid = explode('_', $txnid);
            $orderid = (int)$orderid[0];
            $order = new WC_Order($orderid);

            try {
                if (strcmp("0000", $_REQUEST["resultCode"]) == 0) {
                    //성공시 이니시스로 결제 성공 전달
                    $mid = $_REQUEST["mid"];
                    if( $this->id == 'inicis_stdescrow_bank') {
                        $signKey = $this->settings['escrow_signkey'];
                    } else {
                        $signKey = $this->settings['signkey'];
                    }

                    $timestamp = $util->getTimestamp();
                    $charset = "UTF-8";
                    $format = "JSON";
                    $authToken = $_REQUEST["authToken"];
                    $authUrl = $_REQUEST["authUrl"];
                    $netCancel = $_REQUEST["netCancelUrl"];
                    $ackUrl = $_REQUEST["checkAckUrl"];

                    $signParam["authToken"] = $authToken;  // 필수
                    $signParam["timestamp"] = $timestamp;  // 필수
                    $signature = $util->makeSignature($signParam);

                    $authMap["mid"] = $mid;   // 필수
                    $authMap["authToken"] = $authToken; // 필수
                    $authMap["signature"] = $signature; // 필수
                    $authMap["timestamp"] = $timestamp; // 필수
                    $authMap["charset"] = $charset;
                    $authMap["format"] = $format;

                    try {
                        $httpUtil = new HttpClient();

                        $authResultString = "";
                        if ($httpUtil->processHTTP($authUrl, $authMap)) {
                            $authResultString = $httpUtil->body;
                        } else {
                            $this->inicis_print_log( __( '거래 서버와 통신 실패(02xf1) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요, Error : ', 'inicis_payment' ) . $httpUtil->errormsg . ', LOG : ' . print_r($_REQUEST), 'INIStd' );
                            $this->inicis_alert_mail( sprintf( __("거래 서버와 통신 실패(02xf1). 해당 거래건이 결제가 되었는지 반드시 확인해주세요.".PHP_EOL.PHP_EOL."관련 데이터 : " . print_r($_REQUEST, true)),"inicis_payment") );
                            wc_add_notice( __( '거래 서버와 통신 실패(02xf1) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요', 'inicis_payment' ) . ', Error : ' . $httpUtil->errormsg, 'error' );
                            $order->add_order_note( sprintf( __( '<font color="red">거래 서버와 통신 실패(02xf1) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요, Error : %s</font>', 'inicis_payment' ), $httpUtil->errormsg ) );
                            throw new Exception("거래 서버와 통신 실패(02xf1) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요");
                            return;
                        }

                        $resultMap = json_decode($authResultString, true);

                        if (strcmp("0000", $resultMap["resultCode"]) == 0) {
                            //우커머스 내부 결제 처리 로직 시작
                            if( empty($order) || !is_numeric($orderid) ){
                                $this->inicis_print_log( __( '유효하지않은 주문입니다.(01xf3)', 'inicis_payment' ), 'INIStd' );
                                wc_add_notice( __( '유효하지않은 주문입니다.(01xf3)', 'inicis_payment' ), 'error' );
                                throw new Exception( __( '유효하지않은 주문입니다.(01xf3)', 'inicis_payment' ) );
                                return;
                            }

                            $productinfo = $this->make_product_info($order);
                            $order_total = $this->inicis_get_order_total($order);

                            if($order->get_status() != 'on-hold' && $order->get_status() != 'pending' && $order->get_status() != 'failed'){
                                $paid_result = get_post_meta( ifw_get($order, 'id'), '_paid_date', true);
                                $postmeta_txnid = get_post_meta( ifw_get($order, 'id'), '_txnid', true);
                                $postmeta_paymethod = get_post_meta( ifw_get($order, 'id'), '_inicis_paymethod', true);
                                $postmeta_tid = get_post_meta( ifw_get($order, 'id'), '_inicis_paymethod_tid', true);

                                if(empty($paid_result)) {
                                    $this->inicis_print_log( sprintf( __('<font color="red">주문요청(#%s)에 대한 상태(%s)가 유효하지 않습니다.</font>', 'inicis_payment' ), $orderid, __($order->get_status(), 'woocommerce') ), 'INIStd' );
                                    $this->inicis_alert_mail( sprintf( __("주문요청(#%s)에 대한 상태(%s)가 유효하지 않습니다.".PHP_EOL.PHP_EOL."관련 데이터 : " . print_r($_REQUEST, true), "inicis_payment"), $orderid, __($order->get_status(), 'woocommerce') ) );
                                    wc_add_notice( __( '주문에 따른 결제대기 시간 초과로 결제가 완료되지 않았습니다. 다시 주문을 시도 해 주세요.', 'inicis_payment' ), 'error' );
                                    $order->add_order_note( sprintf( __('<font color="red">주문요청(#%s)에 대한 상태(%s)가 유효하지 않습니다.</font>', 'inicis_payment' ), $orderid, __($order->get_status(), 'woocommerce') ) );
                                    $order->add_order_note( __('결제 승인 요청 에러 : 유효하지않은 주문입니다.', 'inicis_payment' ) );
                                    $order->update_status('failed');
                                    throw new Exception( __( '결제 승인 요청 에러 : 유효하지않은 주문입니다.', 'inicis_payment' ) );
                                    return;

                                } else {
                                    $this->inicis_print_log( __( '이미 결제된 주문입니다.', 'inicis_payment' ), 'INIStd' );
                                    $this->inicis_alert_mail( sprintf( __("이미 결제된 주문(#%s)에 주문 요청이 접수되었습니다. 주문 내역을 반드시 확인하신 후 처리해주세요.".PHP_EOL.PHP_EOL."관련 데이터 : " . print_r($_REQUEST, true), "inicis_payment" ), $orderid) );
                                    wc_add_notice( __( '이미 결제된 주문입니다.', 'inicis_payment'), 'error' );
                                    $order->add_order_note( sprintf( __('<font color="blue">이미 결제된 주문(%s)에 주문 요청이 접수되었습니다. 현재 주문상태 : %s</font>', 'inicis_payment' ), $orderid, __($order->get_status(), 'woocommerce') ) );
                                    $order->add_order_note( sprintf( __('이미 주문이 완료되었습니다. 결제방법 : %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'), $postmeta_paymethod, $postmeta_tid, $postmeta_txnid));
                                    throw new Exception( __( '이미 결제된 주문입니다.', 'inicis_payment' ) );
                                    return;
                                }
                            }

                            if($this->validate_txnid($order, $txnid) == false){
                                $this->inicis_print_log( sprintf( __('유효하지 않은 주문번호(%s) 입니다.', 'inicis_payment' ), $txnid), 'INIStd' );
                                wc_add_notice( sprintf( __('유효하지 않은 주문번호(%s) 입니다.', 'inicis_payment' ), $txnid), 'error' );
                                $order->add_order_note( sprintf( __('<font color="red">유효하지 않은 주문번호(%s) 입니다.</font>', 'inicis_payment' ), $txnid) );
                                $order->update_status('failed');
                                throw new Exception( sprintf( __('유효하지 않은 주문번호(%s) 입니다.', 'inicis_payment' ), $txnid) );
                                return;
                            }

                            //WPML 사용시 처리 추가
                            if(function_exists('icl_object_id')) {
                                if( $this->id == 'inicis_stdescrow_bank') {
                                    $checkhash = hash('sha512', (string)$this->settings['escrow_merchant_id'] . "|$txnid|$userid|$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                                } else {
                                    $checkhash = hash('sha512', (string)$this->settings['merchant_id'] . "|$txnid|$userid|$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                                }
                            } else {
                                if( $this->id == 'inicis_stdescrow_bank') {
                                    $checkhash = hash('sha512', (string)$this->settings['escrow_merchant_id'] . "|$txnid|$userid|$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                                } else {
                                    $checkhash = hash('sha512', (string)$this->settings['merchant_id'] . "|$txnid|$userid|$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                                }
                            }

                            if($hash != $checkhash){
                                $this->inicis_print_log( sprintf( __( '주문요청(#%s)에 대한 위변조 검사 오류입니다.', 'inicis_payment' ), $txnid ), 'INIStd' );
                                $this->inicis_print_log( sprintf( __( '$hash = %s', 'inicis_payment' ), $hash ), 'INIStd' );
                                $this->inicis_print_log( sprintf( __( '$checkhash = %s', 'inicis_payment' ), $checkhash ), 'INIStd' );
                                wc_add_notice( sprintf( __( '주문요청(%s)에 대한 위변조 검사 오류입니다.', 'inicis_payment' ), $txnid ), 'error' );
                                $order->add_order_note( sprintf( __('<font color="red">주문요청(%s)에 대한 위변조 검사 오류입니다.</font>', 'inicis_payment' ), $txnid) );
                                $order->update_status('failed');
                                throw new Exception( sprintf( __( '주문요청(%s)에 대한 위변조 검사 오류입니다.', 'inicis_payment' ), $txnid ) );
                                return;
                            }

                            //이니시스로 결제 처리 완료 통보 시작
                            $checkMap["mid"] = $mid;
                            $checkMap["tid"] = $resultMap["tid"];
                            $checkMap["applDate"] = $resultMap["applDate"];
                            $checkMap["applTime"] = $resultMap["applTime"];
                            $checkMap["price"] = $resultMap["TotPrice"];
                            $checkMap["goodsName"] = $resultMap["goodsName"];
                            $checkMap["charset"] = $charset;
                            $checkMap["format"] = $format;

                            $ackResultString = "";
                            if ($httpUtil->processHTTP($ackUrl, $checkMap)) {
                                $ackResultString = $httpUtil->body;
                            } else {
                                $this->inicis_print_log( __( '거래 서버와 통신 실패(02xf2) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요, Error : ', 'inicis_payment' ) . $httpUtil->errormsg . ', LOG : ' . print_r($_REQUEST, true), 'INIStd' );
                                $this->inicis_alert_mail( sprintf( __("거래 서버와 통신 실패(02xf2). 해당 거래건이 결제가 되었는지 반드시 확인해주세요.".PHP_EOL.PHP_EOL."Error : %s".PHP_EOL.PHP_EOL."LOG : %s", "inicis_payment"), $httpUtil->errormsg, print_r($_REQUEST, true) ) );
                                wc_add_notice( __( '거래 서버와 통신 실패(02xf2) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요', 'inicis_payment' ) . ', Error : ' . $httpUtil->errormsg, 'error' );
                                $order->add_order_note( sprintf( __( '<font color="red">거래 서버와 통신 실패(02xf2) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요, Error : %s</font>', 'inicis_payment' ), $httpUtil->errormsg ) );

                                throw new Exception("Http Connect Error");
                            }
                            //이니시스로 결제 처리 완료 통보 종료
                            $ackMap = json_decode($ackResultString);
                            //거래 성공시

                            //우커머스 내부 결제 처리 로직 시작
                            if($resultMap["resultCode"] != "0000"){
                                $this->inicis_print_log( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 주문번호(#%s), 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), $orderid, esc_attr($resultMap["resultCode"]), esc_attr($resultMap["resultMsg"]) ), 'INIStd' );
                                wc_add_notice( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), esc_attr($resultMap["resultCode"]), esc_attr($resultMap["resultMsg"]) ), 'error' );
                                $order->add_order_note( sprintf( __('<font color="red">결제 승인 요청 과정에서 오류가 발생했습니다. 주문번호(#%s), 오류코드(%s), 오류메시지(%s)</font>', 'inicis_payment' ), $orderid, esc_attr($resultMap["resultCode"]), esc_attr($resultMap["resultMsg"]) ) );
                                $order->update_status('failed');
                                return;
                            }

                            $inistd_txnid = $resultMap['MOID'];
                            $inistd_orderid = explode('_', $inistd_txnid);
                            $inistd_orderid = (int)$inistd_orderid[0];

                            if( $txnid != $inistd_txnid || $orderid != $inistd_orderid ){
                                $this->inicis_print_log( sprintf( __( '주문요청(%s, %s, %s, %s)에 대한 위변조 검사 오류입니다. 결재는 처리되었으나, 결재요청에 오류가 있습니다. 이니시스 결재내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'inicis_payment' ), $txnid, $inistd_txnid, $orderid, $inistd_orderid ), 'INIStd' );
                                wc_add_notice( __( '주문요청에 대한 위변조 검사 오류입니다. 관리자에게 문의해주세요.', 'inicis_payment' ), 'error' );
                                $order->add_order_note( sprintf( __( '<font color="red">주문요청(%s, %s, %s, %s)에 대한 위변조 검사 오류입니다. 결재는 처리되었으나, 결재요청에 오류가 있습니다. 이니시스 결재내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.</font>', 'inicis_payment' ), $txnid, $inistd_txnid, $orderid, $inistd_orderid ) );
                                $order->update_status('failed');
                                return;
                            }

                            add_post_meta($orderid, "_inicis_paymethod", $resultMap['payMethod']);
                            add_post_meta($orderid, "_inicis_paymethod_tid",  $resultMap['tid']);

                            if( 'DirectBank' == $resultMap['payMethod'] ) {
                                if( empty($resultMap['CSHR_ResultCode']) ) {
                                    $CSHRResultCode = '없음';
                                } else {
                                    $CSHRResultCode = $resultMap['CSHR_ResultCode'];
                                }
                                if( empty($resultMap['CSHR_Type']) ) {
                                    $CSHR_Type = '없음';
                                } else {
                                    $CSHR_Type = $resultMap['CSHR_Type'];
                                }
                                $this->inicis_print_log( sprintf( __( '결제방법 : [웹표준결제] %s , 해당 결제건의 추가 정보입니다. 은행 코드 : %s, 현금영수증 발급결과 코드 : %s, 현금영수증 발급구분 코드 : %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'inicis_payment'), $resultMap['payMethod'], $resultMap['ACCT_BankCode'], $CSHRResultCode, $CSHR_Type, $resultMap['tid'], $resultMap['MOID'] ), 'INIStd' );
                                $order->add_order_note( sprintf( __( '결제방법 : [웹표준결제] %s , 해당 결제건의 추가 정보입니다. 은행 코드 : %s, 현금영수증 발급결과 코드 : %s, 현금영수증 발급구분 코드 : %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'inicis_payment'), $resultMap['payMethod'], $resultMap['ACCT_BankCode'], $CSHRResultCode, $CSHR_Type, $resultMap['tid'], $resultMap['MOID'] ) );

                                if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                                    ifw_reduce_order_stock($order);
                                    $order->payment_complete();
                                } else {
                                    $order->payment_complete();
                                }

                            } else if ('Card' == $resultMap['payMethod'] || 'VCard' == $resultMap['payMethod']) {

                                //카드관련 추가정보 추가
                                add_post_meta($orderid, "_inicis_paymethod_card_num", $resultMap['CARD_Num'] );          //카드번호
                                add_post_meta($orderid, "_inicis_paymethod_card_qouta", $resultMap['CARD_Quota'] );      //할부기간
                                add_post_meta($orderid, "_inicis_paymethod_card_interest", $resultMap['CARD_Interest'] );    //무이자할부 여부(1:무이자할부)
                                add_post_meta($orderid, "_inicis_paymethod_card_code", $resultMap['CARD_Code'] );        //신용카드사 코드
                                add_post_meta($orderid, "_inicis_paymethod_card_name", $this->get_cardname( $resultMap['CARD_Code'] ) );    //신용카드사명
                                add_post_meta($orderid, "_inicis_paymethod_card_bankcode", $resultMap['CARD_BankCode'] );    //카드발급사 코드
                                add_post_meta($orderid, "_inicis_paymethod_card_eventcode", $resultMap['EventCode'] );    //이벤트적용 여부
                                add_post_meta($orderid, "_inicis_paymethod_card_point", empty($resultMap['point']) ? '' : $resultMap['point'] );    //카드포인트 사용여부(1:사용)

                                $this->inicis_print_log( sprintf( __( '주문이 완료되었습니다. 결제방법 : [웹표준결제] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'inicis_payment'), $resultMap['payMethod'], $resultMap['tid'], $resultMap['MOID'] ), 'INIStd' );
                                $order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [웹표준결제] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s, 카드사 : %s, 카드번호 : %s', 'inicis_payment'), $resultMap['payMethod'], $resultMap['tid'], $resultMap['MOID'], $this->get_cardname( $resultMap['CARD_Code'] ), $resultMap['CARD_Num'] ) );

                                if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                                    ifw_reduce_order_stock($order);
                                    $order->payment_complete();
                                } else {
                                    $order->payment_complete();
                                }

                            } else if ('VBank' == $resultMap['payMethod'] ) {

                                wc_add_notice( __( '결제가 정상적으로 완료되었습니다.', 'inicis_payment'), 'success' );
                                $VACT_ResultMsg     = $resultMap['resultMsg'];      //결과 내용
                                $VACT_Name          = $resultMap['VACT_Name'];       //입금 계좌번호
                                $VACT_InputName     = $resultMap['VACT_InputName']; //송금자명
                                $TID                = $resultMap['tid'];            //거래번호 tid
                                $MOID               = $resultMap['MOID'];           //주문번호
                                $VACT_Num           = $resultMap['VACT_Num'];       //입금계좌번호
                                $VACT_BankCode      = $resultMap['VACT_BankCode'];      //입금은행코드
                                $VACT_BankCodeName  = $resultMap['vactBankName'];      //입금은행명
                                $VACT_Date          = $resultMap['VACT_Date'];      //송금일자
                                $VACT_Time          = $resultMap['VACT_Time'];      //송금시간

                                update_post_meta($orderid, '_VACT_Num', $VACT_Num);  //입금계좌번호
                                update_post_meta($orderid, '_VACT_BankCode', $VACT_BankCode);    //입금은행코드
                                update_post_meta($orderid, '_VACT_BankCodeName', $VACT_BankCodeName);    //입금은행명/코드
                                update_post_meta($orderid, '_VACT_Name', $VACT_Name);    //예금주
                                update_post_meta($orderid, '_VACT_InputName', $VACT_InputName);   //송금자
                                update_post_meta($orderid, '_VACT_Date', $VACT_Date);    //입금예정일

                                $resultmsg = sprintf(
                                    __( '주문이 완료되었습니다. 무통장(가상계좌) 입금을 기다려주시기 바랍니다. 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s, 가상계좌 결과메시지 : %s, 입금 계좌번호 : %s, 입금은행코드 : %s, 예금주명 : %s, 송금자명 : %s, 입금예정일 : %s', 'inicis_payment'),
                                    $TID,
                                    $MOID,
                                    $VACT_ResultMsg,
                                    $VACT_Num,
                                    $VACT_BankCodeName,
                                    $VACT_Name,
                                    $VACT_InputName,
                                    $VACT_Date
                                );
                                $order->add_order_note( $resultmsg );

                                //가상계좌 주문 접수시 재고 차감여부 확인
                                ifw_reduce_order_stock($order);

                                $order->update_status($this->settings['order_status_after_payment']);

                                //WC 3.0 postmeta update 로 인해 별도로 가상계좌 추가 처리
                                if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
                                    $order->set_date_paid( null );
                                    $order->save();
                                } else {
                                    //WC 2.6.X 처리
                                    update_post_meta($order->id, '_paid_date', null);
                                }


                            } else {

                                $this->inicis_print_log( sprintf( __( '주문이 완료되었습니다. 결제방법 : [웹표준결제] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'inicis_payment'), $resultMap['payMethod'], $resultMap['tid'], $resultMap['MOID'] ), 'INIStd' );
                                $order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [웹표준결제] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'), $resultMap['payMethod'], $resultMap['tid'], $resultMap['MOID'] ) );

                                if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                                    ifw_reduce_order_stock($order);
                                    $order->payment_complete();
                                } else {
                                    $order->payment_complete();
                                }
                            }
                            $woocommerce->cart->empty_cart();
                            //우커머스 내부 결제 처리 로직 종료

                        } else {

                            //거래 실패시
                            $this->inicis_print_log( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 주문번호(%s), 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), $order_id, esc_attr($resultMap["resultCode"]), esc_attr($resultMap["resultMsg"]) ), 'INIStd' );
                            wc_add_notice( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), esc_attr($resultMap["resultCode"]), esc_attr($resultMap["resultMsg"]) ), 'error' );
                            $order->add_order_note( sprintf( __('<font color="red">결제 승인 요청 과정에서 오류가 발생했습니다. 오류코드(%s), 오류메시지(%s)</font>', 'inicis_payment' ), esc_attr($resultMap["resultCode"]), esc_attr($resultMap["resultMsg"]) ) );
                            $order->update_status('failed');
                            return;
                        }

                    } catch (Exception $e) {

                        $this->inicis_print_log( sprintf( __( '결제 승인 요청 에러 : 주문번호(%s), 예외처리 에러 (%s)', 'inicis_payment'), $order_id, $e->getMessage() ), 'INIStd' );
                        $order->add_order_note( sprintf( __( '결제 승인 요청 에러 : 예외처리 에러 (%s)', 'inicis_payment'), $e->getMessage() ) );

                        //결제가 완료된 건의 경우 주문실패상태로 변경하지 않는다.
                        $paid_result = get_post_meta( ifw_get($order, 'id'), '_paid_date', true);
                        if( empty($paid_result)) {
                            $order->update_status('failed');
                        }

                        //망취소 처리
                        $netcancelResultString = "";
                        if ($httpUtil->processHTTP($netCancel, $authMap)) {
                            $netcancelResultString = $httpUtil->body;
                            $this->inicis_print_log( '$netcancelResultString : ' . $netcancelResultString, 'INIStd' );
                        } else {
                            $this->inicis_print_log( __( '거래 서버와 통신 실패(02xf3) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요, Error : ', 'inicis_payment' ) . $httpUtil->errormsg . ', LOG : ' . print_r($_REQUEST, true), 'INIStd' );
                            $this->inicis_alert_mail( sprintf( __("망취소 처리 도중 거래 서버와 통신 실패(02xf3) : 해당 거래(%s)건이 결제가 되었는지 반드시 확인해주세요. Error : %s, LOG : %s", 'inicis_payment' ), $order_id, $httpUtil->errormsg, print_r($_REQUEST, true) ) );
                            wc_add_notice( __( '거래 서버와 통신 실패(02xf3) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요', 'inicis_payment' ) . ', Error : ' . $httpUtil->errormsg, 'error' );
                            $order->add_order_note( sprintf( __( '<font color="red">거래 서버와 통신 실패(02xf3) : 해당 거래건이 결제가 되었는지 반드시 확인해주세요, Error : %s</font>', 'inicis_payment' ), $httpUtil->errormsg ) );

                            throw new Exception("Http Connect Error");
                        }

                        $netcancelResultString = str_replace("<", "&lt;", $netcancelResultString);
                        $netcancelResultString = str_replace(">", "&gt;", $netcancelResultString);

                        $resultMap = json_decode($netcancelResultString, true);

                        $this->inicis_print_log( sprintf( __( '자동 망취소 처리 결과 : %s', 'inicis_payment'),  print_r($resultMap, true) ), 'INIStd' );
                        $order->add_order_note( sprintf( __( '자동 망취소 처리 결과 : %s', 'inicis_payment'),  print_r($resultMap, true) ) );
                        wc_add_notice( sprintf( __( '비정상 주문으로 확인되어 자동취소가 진행되었습니다. 자동취소 처리 결과를 확인해주세요. 처리결과 : %s', 'inicis_payment'), $resultMap['resultMsg'] ), 'error' );
                    }

                } else {

                    //실패시
                    $this->inicis_print_log( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 주문번호(%s), 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), esc_attr($_REQUEST["resultCode"]), esc_attr($_REQUEST["resultMsg"]) ), 'INIStd' );
                    wc_add_notice( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), esc_attr($_REQUEST["resultCode"]), esc_attr($_REQUEST["resultMsg"]) ), 'error' );
                    $order->add_order_note( sprintf( __('<font color="red">결제 승인 요청 과정에서 오류가 발생했습니다. 오류코드(%s), 오류메시지(%s)</font>', 'inicis_payment' ), esc_attr($_REQUEST["resultCode"]), esc_attr($_REQUEST["resultMsg"]) ) );
                    $order->update_status('failed');
                    return;
                }

            } catch (Exception $e) {

                $this->inicis_print_log( sprintf( __( '결제 승인 요청 에러 : 주문번호(%s), 예외처리 에러 (%s)', 'inicis_payment'), $order_id, $e->getMessage() ), 'INIStd' );
                $order->add_order_note( sprintf( __( '결제 승인 요청 에러 : 예외처리 에러 (%s)', 'inicis_payment'), $e->getMessage() ) );
                $order->update_status('failed');
            }
        }
        function successful_request_vbank_noti( $posted ) {
            global $woocommerce;

            $TEMP_IP = getenv("REMOTE_ADDR");
            $PG_IP  = substr($TEMP_IP,0, 10);

            $this->inicis_print_log("===== [ PC VBANK NOTI START ] =====");
            $this->inicis_print_log( print_r($_SERVER, true));
            $this->inicis_print_log( print_r($_REQUEST, true));

            if( $PG_IP == "203.238.37" || $PG_IP == "210.98.138" || $PG_IP == "39.115.212" )  //PG에서 보냈는지 IP로 체크
            {
                $msg_id = $_POST['msg_id'];             //메세지 타입
                $no_tid = $_POST['no_tid'];             //거래번호
                $no_oid = $_POST['no_oid'];             //상점 주문번호
                $id_merchant = $_POST['id_merchant'];   //상점 아이디
                $cd_bank = $_POST['cd_bank'];           //거래 발생 기관 코드
                $cd_deal = $_POST['cd_deal'];           //취급 기관 코드
                $dt_trans = $_POST['dt_trans'];         //거래 일자
                $tm_trans = $_POST['tm_trans'];         //거래 시간
                $no_msgseq = $_POST['no_msgseq'];       //전문 일련 번호
                $cd_joinorg = $_POST['cd_joinorg'];     //제휴 기관 코드

                $dt_transbase = $_POST['dt_transbase']; //거래 기준 일자
                $no_transeq = $_POST['no_transeq'];     //거래 일련 번호
                $type_msg = $_POST['type_msg'];         //거래 구분 코드
                $cl_close = $_POST['cl_close'];         //마감 구분코드
                $cl_kor = $_POST['cl_kor'];             //한글 구분 코드
                $no_msgmanage = $_POST['no_msgmanage']; //전문 관리 번호
                $no_vacct = $_POST['no_vacct'];         //가상계좌번호
                $amt_input = $_POST['amt_input'];       //입금금액
                $amt_check = $_POST['amt_check'];       //미결제 타점권 금액
                $nm_inputbank = mb_convert_encoding($_POST['nm_inputbank'], "UTF-8", "CP949"); //입금 금융기관명
                $nm_input = mb_convert_encoding($_POST['nm_input'], "UTF-8", "CP949");         //입금 의뢰인
                $dt_inputstd = $_POST['dt_inputstd'];   //입금 기준 일자
                $dt_calculstd = $_POST['dt_calculstd']; //정산 기준 일자
                $flg_close = $_POST['flg_close'];       //마감 전화

                //가상계좌채번시 현금영수증 자동발급신청시에만 전달
                $dt_cshr = $_POST['dt_cshr'];       //현금영수증 발급일자
                $tm_cshr = $_POST['tm_cshr'];       //현금영수증 발급시간
                $no_cshr_appl = $_POST['no_cshr_appl'];  //현금영수증 발급번호
                $no_cshr_tid = $_POST['no_cshr_tid'];   //현금영수증 발급TID

                $this->inicis_print_log("************************************************");
                $this->inicis_print_log("DATETIME(발생시간) : ".date("Y-m-d H:i:s"));
                $this->inicis_print_log("ID_MERCHANT(상점아이디) : " . $id_merchant);
                $this->inicis_print_log("NO_TID(거래번호) : " . $no_tid);
                $this->inicis_print_log("NO_OID(상점거래번호) : " . $no_oid);
                $this->inicis_print_log("NO_VACCT(계좌번호) : " . $no_vacct);
                $this->inicis_print_log("AMT_INPUT(입금액) : " . $amt_input);
                $this->inicis_print_log("NM_INPUTBANK(입금은행명) : " . $nm_inputbank);
                $this->inicis_print_log("NM_INPUT(입금자명) : " . $nm_input);
                $this->inicis_print_log("************************************************");

                $this->inicis_print_log("전체 결과값");
                $this->inicis_print_log($msg_id);
                $this->inicis_print_log($no_tid);
                $this->inicis_print_log($no_oid);
                $this->inicis_print_log($id_merchant);
                $this->inicis_print_log($cd_bank);
                $this->inicis_print_log($dt_trans);
                $this->inicis_print_log($tm_trans);
                $this->inicis_print_log($no_msgseq);
                $this->inicis_print_log($type_msg);
                $this->inicis_print_log($cl_close);
                $this->inicis_print_log($cl_kor);
                $this->inicis_print_log($no_msgmanage);
                $this->inicis_print_log($no_vacct);
                $this->inicis_print_log($amt_input);
                $this->inicis_print_log($amt_check);
                $this->inicis_print_log($nm_inputbank);
                $this->inicis_print_log($nm_input);
                $this->inicis_print_log($dt_inputstd);
                $this->inicis_print_log($dt_calculstd);
                $this->inicis_print_log($flg_close);

                //OID 에서 주문번호 확인
                $arr_oid = explode('_', $no_oid);
                $order_id = $arr_oid[0];
                $order_date = $arr_oid[1];
                $order_time = $arr_oid[2];

                $txnid = get_post_meta($order_id, '_txnid', true);  //상점거래번호(OID)
                $order_tid = get_post_meta($order_id, '_inicis_paymethod_tid', true);  //거래번호(TID)
                $VACT_Num = get_post_meta($order_id, '_VACT_Num', true);  //입금계좌번호
                $VACT_BankCode = get_post_meta($order_id, '_VACT_BankCode', true);    //입금은행코드
                $VACT_BankCodeName = get_post_meta($order_id, '_VACT_BankCodeName', true);    //입금은행명/코드
                $VACT_Name = get_post_meta($order_id, '_VACT_Name', true);    //예금주
                $VACT_InputName = get_post_meta($order_id, '_VACT_InputName', true);   //송금자
                $VACT_Date = get_post_meta($order_id, '_VACT_Date', true);    //입금예정일

                $order = new WC_Order($order_id);
                if( !in_array($order->get_status(), array('completed', 'cancelled', 'refunded') ) ) {  //주문상태 확인
                    if($txnid != $no_oid) {    //거래번호(oid) 체크
                        $this->inicis_print_log('ERROR : FAIL_11, 거래번호 미일치');
                        echo 'FAIL_11';
                        exit();
                    }
                    if($cd_bank != $VACT_BankCode) {    //입금은행 코드 체크
                        $this->inicis_print_log('ERROR : FAIL_12, 입금은행 코드 미일치');
                        echo 'FAIL_12';
                        exit();
                    }
                    if($VACT_Num != $no_vacct) {    //입금계좌번호 체크
                        $this->inicis_print_log('ERROR : FAIL_13, 입금계좌번호 미일치');
                        echo 'FAIL_13';
                        exit();
                    }
                    if((int)$amt_input != (int)$order->get_total()) {    //입금액 체크
                        $this->inicis_print_log('ERROR : FAIL_14, 입금액 미일치');
                        echo 'FAIL_14';
                        exit();
                    }

                    update_post_meta( ifw_get($order, 'id'), '_inicis_vbank_noti_received', 'yes');
                    update_post_meta( ifw_get($order, 'id'), '_inicis_vbank_noti_received_tid', $no_tid);
                    $order->add_order_note( sprintf( __('가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'inicis_payment'), $no_tid, $no_oid ) );
                    $this->inicis_print_log( sprintf( __('가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'inicis_payment'), $no_tid, $no_oid ) );
                    $order->payment_complete();
                    $order->update_status($this->settings['order_status_after_vbank_noti']);

                    //WC3.0 관련 가상계좌 입금통보시 결제 완료 시간 갱신 처리
                    if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
                        $order->set_date_paid( current_time( 'timestamp', true ) );
                        $order->save();
                    } else {
                        //WC 2.6.X 처리
                        update_post_meta( $order->id, '_paid_date', current_time( 'mysql' ) );
                    }

                    echo 'OK';
                    exit();
                } else { //주문상태가 이상한 경우
                    $order->add_order_note( sprintf( __('입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s','inicis_payment'), $no_tid, $no_oid ) );
                    $this->inicis_print_log( sprintf( __('ERROR : FAIL_20, 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s','inicis_payment'), $no_tid, $no_oid ) );
                    $this->inicis_alert_mail( sprintf( __("가상계좌 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 반드시 주문을 확인해주세요.".PHP_EOL.PHP_EOL."주문번호 : #%s".PHP_EOL."현재 주문상태 : %s".PHP_EOL."거래번호(TID) : %s".PHP_EOL."상점거래번호(OID) : %s".PHP_EOL."은행명 : %s".PHP_EOL."계좌번호 : %s".PHP_EOL."입금액 : %s".PHP_EOL."입금자명 : %s".PHP_EOL."거래일시 : %s","inicis_payment"), $order_id, $order->get_status(), $no_tid, $no_oid, $nm_inputbank, $no_vacct, $amt_input, $nm_input, $dt_trans . ' ' . $tm_trans ) );
                    echo 'OK';    //가맹점 관리자 사이트에서 재전송 가능하나 주문건 확인 필요
                    exit();
                }
            }

            $this->inicis_print_log("===== [ VBANK NOTI END ] =====");
        }
        function successful_request_mobile_next( $posted ) {
            global $woocommerce, $inicis_payment;

            $this->inicis_print_log("===== [ MOBILE NEXT DEBUG START ] =====");
            $this->inicis_print_log( print_r($_SERVER, true));
            $this->inicis_print_log( print_r($_REQUEST, true));
            $this->inicis_print_log("===== [ MOBILE NEXT DEBUG END ] =====");

            if (!file_exists($inicis_payment->plugin_path() . "/lib/inimx/INImx.php")) {
                wc_add_notice( __( '에러 : INImx.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment' ), 'error' );
                die( __('<span style="color:red;font-weight:bold;">에러 : INImx.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.</span>', 'inicis_payment') );
            }
            require_once ($inicis_payment->plugin_path() . "/lib/inimx/INImx.php");

            if( $_REQUEST['P_STATUS'] == '00' )
            {
                $notification = $this->decrypt_notification($_REQUEST['P_NOTI']);
                if( empty($notification) ){
                    wc_add_notice( __( '유효하지않은 주문입니다.(01xf1)', 'inicis_payment' ), 'error' );
                    return;
                }

                $txnid = $notification->txnid;
                $hash = $notification->hash;

                if(empty($txnid)){
                    wc_add_notice( __( '유효하지않은 주문입니다.(01xf2)', 'inicis_payment' ), 'error' );
                    return;
                }

                $userid = get_current_user_id();
                $orderid = explode('_', $txnid);
                $orderid = (int)$orderid[0];
                $order = new WC_Order($orderid);

                if( empty($order) || !is_numeric($orderid) ){
                    wc_add_notice( __( '유효하지않은 주문입니다.(01xf3)', 'inicis_payment' ), 'error' );
                    return;
                }

                $productinfo = $this->make_product_info($order);
                $order_total = $this->inicis_get_order_total($order);

                //가상계좌 결제건인 경우 처리
                if( strtolower( ifw_get($order, 'payment_method') ) == 'inicis_stdvbank' ) {

                    //발급된 가상계좌 정보가 있는 지 확인
                    $paid_result = get_post_meta( ifw_get($order, 'id'), '_paid_date', true);
                    $postmeta_txnid = get_post_meta( ifw_get($order, 'id'), '_txnid', true);
                    $postmeta_paymethod = get_post_meta( ifw_get($order, 'id'), '_inicis_paymethod', true);
                    $postmeta_tid = get_post_meta( ifw_get($order, 'id'), '_inicis_paymethod_tid', true);
                    $postmeta_vactnum = get_post_meta( ifw_get($order, 'id'), '_VACT_Num', true);

                    if(!empty($postmeta_vactnum)) {
                        $this->inicis_alert_mail( sprintf( __("이미 가상계좌가 발급된 주문입니다. 기존에 발급된 가상계좌 정보를 확인해주세요. 주문번호(%s), 결제방법(%s), 거래번호TID(%s), 가상계좌번호(%s), 주문상태(%s)", "inicis_payment"), $orderid, $postmeta_paymethod, $postmeta_tid, $postmeta_vactnum, __($order->get_status(), 'woocommerce') ) );
                        $order->add_order_note( sprintf( __('<font color="blue">이미 가상계좌(%s)가 발급된 주문입니다. 이미 결제된 주문(%s)에 주문 요청이 접수되었습니다. 현재 주문상태 : %s</font>', 'inicis_payment' ), $postmeta_vactnum, $postmeta_txnid, __($order->get_status(), 'woocommerce') ) );
                        $order->add_order_note( sprintf( __('이미 가상계좌가 발급된 주문입니다. 결제방법 : %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'), $postmeta_paymethod, $postmeta_tid, $postmeta_txnid));
                        wc_add_notice( __( '이미 가상계좌가 발급된 주문입니다. 기존에 발급된 가상계좌 정보를 확인해주세요.', 'inicis_payment'), 'error' );
                        return;
                    }

                    //주문상태 확인
                    if($order->get_status() != 'pending' && $order->get_status() != 'failed'){
                        if(empty($paid_result)) {
                            wc_add_notice( __( '주문에 따른 결제대기 시간 초과로 결제가 완료되지 않았습니다. 다시 주문을 시도 해 주세요.', 'inicis_payment' ), 'error' );
                            $order->add_order_note( sprintf( __('<font color="red">주문요청(%s)에 대한 상태(%s)가 유효하지 않습니다.</font>', 'inicis_payment' ), $txnid, __($order->get_status(), 'woocommerce') ) );
                            $order->add_order_note( __('결제 승인 요청 에러 : 유효하지않은 주문입니다.', 'inicis_payment' ) );
                            $order->update_status('failed');
                            return;
                        } else {
                            wc_add_notice( __( '이미 결제된 주문입니다.', 'inicis_payment'), 'error' );
                            $this->inicis_alert_mail( sprintf( __("이미 결제된 주문(%s)에 주문 요청이 접수되었습니다. 주문을 확인해주세요. 현재 주문상태 : %s, 결제방법 : %s, 이니시스 거래번호(TID) : %s", "inicis_payment"), $orderid, __($order->get_status(), 'woocommerce'), $postmeta_paymethod, $postmeta_tid ) );
                            $order->add_order_note( sprintf( __('<font color="blue">이미 결제된 주문(%s)에 주문 요청이 접수되었습니다. 현재 주문상태 : %s</font>', 'inicis_payment' ), $postmeta_txnid, __($order->get_status(), 'woocommerce') ) );
                            $order->add_order_note( sprintf( __('이미 주문이 완료되었습니다. 결제방법 : %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'), $postmeta_paymethod, $postmeta_tid, $postmeta_txnid));
                            return;
                        }
                    }
                } else {
                    if($order->get_status() != 'on-hold' && $order->get_status() != 'pending' && $order->get_status() != 'failed'){
                        $paid_result = get_post_meta( ifw_get($order, 'id'), '_paid_date', true);
                        $postmeta_txnid = get_post_meta( ifw_get($order, 'id'), '_txnid', true);
                        $postmeta_paymethod = get_post_meta( ifw_get($order, 'id'), '_inicis_paymethod', true);
                        $postmeta_tid = get_post_meta( ifw_get($order, 'id'), '_inicis_paymethod_tid', true);

                        if(empty($paid_result)) {
                            wc_add_notice( __( '주문에 따른 결제대기 시간 초과로 결제가 완료되지 않았습니다. 다시 주문을 시도 해 주세요.', 'inicis_payment' ), 'error' );
                            $order->add_order_note( sprintf( __('<font color="red">주문요청(%s)에 대한 상태(%s)가 유효하지 않습니다.</font>', 'inicis_payment' ), $txnid, __($order->get_status(), 'woocommerce') ) );
                            $order->add_order_note( __('결제 승인 요청 에러 : 유효하지않은 주문입니다.', 'inicis_payment' ) );
                            $order->update_status('failed');
                            return;
                        } else {
                            wc_add_notice( __( '이미 결제된 주문입니다.', 'inicis_payment'), 'error' );
                            $this->inicis_alert_mail( sprintf( __("이미 결제된 주문(%s)에 주문 요청이 접수되었습니다. 주문을 확인해주세요. 현재 주문상태 : %s", "inicis_payment"), $orderid, __($order->get_status(), 'woocommerce') ) );
                            $order->add_order_note( sprintf( __('<font color="blue">이미 결제된 주문(%s)에 주문 요청이 접수되었습니다. 현재 주문상태 : %s</font>', 'inicis_payment' ), $orderid, __($order->get_status(), 'woocommerce') ) );
                            $order->add_order_note( sprintf( __('이미 주문이 완료되었습니다. 결제방법 : %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'), $postmeta_paymethod, $postmeta_tid, $postmeta_txnid));
                            return;
                        }
                    }
                }

                if($this->validate_txnid($order, $txnid) == false){
                    wc_add_notice( sprintf( __('유효하지 않은 주문번호(%s) 입니다.', 'inicis_payment' ), $txnid), 'error' );
                    $order->add_order_note( sprintf( __('<font color="red">유효하지 않은 주문번호(%s) 입니다.</font>', 'inicis_payment' ), $txnid) );
                    $order->update_status('failed');
                    return;
                }

                //WPML 추가처리
                if(function_exists('icl_object_id')) {
                    if( $this->id == 'inicis_stdescrow_bank') {
                        $checkhash = hash('sha512', (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                    } else {
                        $checkhash = hash('sha512', (string)$this->settings['merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                    }
                } else {
                    if( $this->id == 'inicis_stdescrow_bank') {
                        $checkhash = hash('sha512', (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                    } else {
                        $checkhash = hash('sha512', (string)$this->settings['merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                    }
                }

                if($hash != $checkhash){
                    wc_add_notice( sprintf( __( '주문요청(%s)에 대한 위변조 검사 오류입니다.', 'inicis_payment' ), $txnid ), 'error' );
                    $order->add_order_note( sprintf( __('<font color="red">주문요청(%s)에 대한 위변조 검사 오류입니다.</font>', 'inicis_payment' ), $txnid) );
                    $order->update_status('failed');
                    return;
                }

                $inimx = new INImx();
                $inimx->reqtype             = "PAY";
                $inimx->inipayhome          = $this->settings['libfolder'];
                if( $this->id == 'inicis_stdescrow_bank') {
                    $inimx->id_merchant         = $this->settings['escrow_merchant_id'];
                } else {
                    $inimx->id_merchant         = $this->settings['merchant_id'];
                }
                $inimx->status              = $P_STATUS;
                $inimx->rmesg1              = $P_RMESG1;
                $inimx->tid                 = $P_TID;
                $inimx->req_url             = $P_REQ_URL;
                $inimx->noti                = $P_NOTI;
                $inimx->startAction();
                $inimx->getResult();

                try
                {
                    if($inimx->m_resultCode != "00"){
                        wc_add_notice( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'inicis_payment' ), esc_attr($inimx->m_resultCode), esc_attr($inimx->m_resultMsg) ), 'error' );
                        $order->add_order_note( sprintf( __('<font color="red">결제 승인 요청 과정에서 오류가 발생했습니다. 오류코드(%s), 오류메시지(%s)</font>', 'inicis_payment' ), esc_attr($inimx->m_resultCode), esc_attr($inimx->m_resultMsg) ) );
                        $order->update_status('failed');
                        return;
                    }

                    $inimx_txnid = $inimx->m_moid;
                    $inimx_orderid = explode('_', $inimx_txnid);
                    $inimx_orderid = (int)$inimx_orderid[0];

                    if( $txnid != $inimx_txnid || $orderid != $inimx_orderid ){
                        wc_add_notice( __( '주문요청에 대한 위변조 검사 오류입니다. 관리자에게 문의해주세요.', 'inicis_payment' ), 'error' );
                        $this->inicis_alert_mail( sprintf( __("주문요청(%s, %s, %s, %s)에 대한 위변조 검사 오류입니다. 결재는 처리되었으나, 결재요청에 오류가 있습니다. 이니시스 결제 내역을 확인해주세요.", "inicis_payment"), $txnid, $inimx_txnid, $orderid, $inimx_orderid ) );
                        $order->add_order_note( sprintf( __( '<font color="red">주문요청(%s, %s, %s, %s)에 대한 위변조 검사 오류입니다. 결재는 처리되었으나, 결재요청에 오류가 있습니다. 이니시스 결재내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.</font>', 'inicis_payment' ), $txnid, $inimx_txnid, $orderid, $inimx_orderid ) );
                        $order->update_status('failed');
                        return;
                    }

                    add_post_meta($orderid, "_inicis_paymethod", $inimx->m_payMethod);
                    add_post_meta($orderid, "_inicis_paymethod_tid",  $inimx->m_tid);

                    if(strtolower($inimx->m_payMethod) == 'vbank') {
                        $VACT_ResultMsg     = mb_convert_encoding($inimx->m_resultMsg, "UTF-8", "CP949");
                        $VACT_Name          = mb_convert_encoding($inimx->m_nmvacct, "UTF-8", "CP949");
                        $VACT_InputName     = mb_convert_encoding($inimx->m_buyerName, "UTF-8", "CP949");
                        $TID                = $inimx->m_tid;
                        $MOID               = $inimx->m_moid;
                        $VACT_Num           = $inimx->m_vacct;
                        $VACT_BankCode      = $inimx->m_vcdbank;

                        $VACT_BankCodeName  = $this->get_bankname($VACT_BankCode);
                        $VACT_Date          = $inimx->m_dtinput;
                        $VACT_Time          = $inimx->m_tminput;

                        update_post_meta($orderid, '_VACT_Num', $VACT_Num);  //입금계좌번호
                        update_post_meta($orderid, '_VACT_BankCode', $VACT_BankCode);    //입금은행코드
                        update_post_meta($orderid, '_VACT_BankCodeName', $VACT_BankCodeName);    //입금은행명/코드
                        update_post_meta($orderid, '_VACT_Name', $VACT_Name);    //예금주
                        update_post_meta($orderid, '_VACT_InputName', $VACT_InputName);   //송금자
                        update_post_meta($orderid, '_VACT_Date', $VACT_Date);    //입금예정일

                        $resultmsg = sprintf(
                            __( '주문이 완료되었습니다. [모바일] 무통장(가상계좌) 입금을 기다려주시기 바랍니다. 입금 계좌번호 : %s, 입금은행코드 : %s, 예금주명 : %s, 송금자명 : %s, 입금예정일 : %s', 'inicis_payment'),
                            $VACT_Num,
                            $VACT_BankCodeName,
                            $VACT_Name,
                            $VACT_InputName,
                            $VACT_Date
                        );
                        $order->add_order_note( $resultmsg );

                        ifw_reduce_order_stock($order);

                        $order->update_status($this->settings['order_status_after_payment']);

                        //WC 3.0 postmeta update 로 인해 별도로 가상계좌 추가 처리
                        if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
                            $order->set_date_paid( null );
                            $order->save();
                        } else {
                            //WC 2.6.X 처리
                            update_post_meta($order->id, '_paid_date', null);
                        }

                    } else if(strtolower($inimx->m_payMethod) == 'card') {

                        //카드관련 추가정보 추가
                        add_post_meta($orderid, "_inicis_paymethod_card_num", $inimx->m_cardNumber );          //카드번호
                        add_post_meta($orderid, "_inicis_paymethod_card_qouta", $inimx->m_cardQuota );      //할부기간
                        //add_post_meta($orderid, "_inicis_paymethod_card_interest", $inimx->??? );    //무이자할부 여부(1:무이자할부)
                        add_post_meta($orderid, "_inicis_paymethod_card_code", $inimx->m_cardCode );        //신용카드사 코드
                        add_post_meta($orderid, "_inicis_paymethod_card_name", $this->get_cardname( $inimx->m_cardCode ) );    //신용카드사명
                        add_post_meta($orderid, "_inicis_paymethod_card_bankcode", $inimx->m_cardIssuerCode );    //카드발급사 코드
                        //add_post_meta($orderid, "_inicis_paymethod_card_eventcode", $resultMap['EventCode'] );    //이벤트적용 여부
                        //add_post_meta($orderid, "_inicis_paymethod_card_point", $resultMap['point'] );    //카드포인트 사용여부(1:사용)

                        $order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s, 카드사 : %s, 카드번호 : %s', 'inicis_payment'), $inimx->m_payMethod, $inimx->m_tid, $inimx->m_moid, $this->get_cardname($inimx->m_cardCode), $inimx->m_cardNumber ) );

                        if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                            ifw_reduce_order_stock($order);
                            $order->payment_complete();
                        } else {
                            $order->payment_complete();
                        }
                    } else {
                        $order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'), $inimx->m_payMethod, $inimx->m_tid, $inimx->m_moid ) );

                        if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                            ifw_reduce_order_stock($order);
                            $order->payment_complete();
                        } else {
                            $order->payment_complete();
                        }
                    }

                    $woocommerce->cart->empty_cart();
                }
                catch(Exception $e)
                {
                    $order->add_order_note( sprintf( __( '결제 승인 요청 에러 : 예외처리 에러 ( %s )', 'inicis_payment'), $e->getMessage() ) );
                    $order->update_status('failed');
                }

                delete_post_meta($orderid, "_ini_rn");
                delete_post_meta($orderid, "_ini_enctype");

            } else if( $_REQUEST['P_STATUS'] == '01' ) {

                wc_add_notice( sprintf( __('결제 진행이 중단 : 에러메시지를 확인해주세요. (ERROR: 0xF53D) 에러코드 : %s, 에러메시지 : %s', 'inicis_payment' ), $_REQUEST['P_STATUS'], mb_convert_encoding($_REQUEST['P_RMESG1'], "UTF-8", "CP949") ) , 'error' );
                wp_redirect( WC()->cart->get_checkout_url() );
                exit();

            } else {
                wc_add_notice( sprintf( __('결제 진행이 중단 : 에러메시지를 확인해주세요. (ERROR: 0xF54D) 에러코드 : %s, 에러메시지 : %s', 'inicis_payment' ), $_REQUEST['P_STATUS'], mb_convert_encoding($_REQUEST['P_RMESG1'], "UTF-8", "CP949") ) , 'error' );
                exit();
            }
        }
        function successful_request_mobile_noti( $posted ) {
            global $woocommerce;

            $this->inicis_print_log("===== [ MOBILE NOTI START ] =====");
            $this->inicis_print_log( print_r($_SERVER, true));
            $this->inicis_print_log( print_r($_REQUEST, true));

            $PGIP = $_SERVER['REMOTE_ADDR'];
            if($PGIP == "211.219.96.165" || $PGIP == "118.129.210.25" || $PGIP == "183.109.71.153")
            {
                $P_TID;
                $P_MID;
                $P_AUTH_DT;
                $P_STATUS;
                $P_TYPE;
                $P_OID;
                $P_FN_CD1;
                $P_FN_CD2;
                $P_FN_NM;
                $P_AMT;
                $P_UNAME;
                $P_RMESG1;
                $P_RMESG2;
                $P_NOTI;
                $P_AUTH_NO;

                $P_TID = $_REQUEST['P_TID'];
                $P_MID = $_REQUEST['P_MID'];
                $P_AUTH_DT = $_REQUEST['P_AUTH_DT'];
                $P_STATUS = $_REQUEST['P_STATUS'];
                $P_TYPE = $_REQUEST['P_TYPE'];
                $P_OID = $_REQUEST['P_OID'];
                $P_FN_CD1 = $_REQUEST['P_FN_CD1'];
                $P_FN_CD2 = $_REQUEST['P_FN_CD2'];
                $P_FN_NM = $_REQUEST['P_FN_NM'];
                $P_AMT = $_REQUEST['P_AMT'];
                $P_UNAME = $_REQUEST['P_UNAME'];
                $P_RMESG1 = $_REQUEST['P_RMESG1'];
                $P_RMESG2 = $_REQUEST['P_RMESG2'];
                $P_NOTI = $_REQUEST['P_NOTI'];
                $P_AUTH_NO = $_REQUEST['P_AUTH_NO'];

                //모바일 무통장입금(가상계좌) 입금통보 처리
                if($P_TYPE == "VBANK")
                {
                    $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 시작. ' . print_r($_REQUEST, true) );

                    if($P_STATUS == "02") {
                        //OID 에서 주문번호 확인
                        $arr_oid = explode('_', $P_OID);
                        $order_id = $arr_oid[0];
                        $order_date = $arr_oid[1];
                        $order_time = $arr_oid[2];

                        //$P_RMESG1 에서 입금계좌 및 입금예정일 확인
                        $arr_tmp = explode('|', $P_RMESG1);
                        $p_vacct_no_tmp = explode('=', $arr_tmp[0]);
                        $p_vacct_no = $p_vacct_no_tmp[1];
                        $p_exp_datetime_tmp = explode('=', $arr_tmp[1]);
                        $p_exp_datetime = $p_exp_datetime_tmp[1];

                        $txnid = get_post_meta($order_id, '_txnid', true);  //상점거래번호(OID)
                        $order_tid = get_post_meta($order_id, '_inicis_paymethod_tid', true);  //거래번호(TID)
                        $VACT_Num = get_post_meta($order_id, '_VACT_Num', true);  //입금계좌번호
                        $VACT_BankCode = get_post_meta($order_id, '_VACT_BankCode', true);    //입금은행코드
                        $VACT_BankCodeName = get_post_meta($order_id, '_VACT_BankCodeName', true);    //입금은행명/코드
                        $VACT_Name = get_post_meta($order_id, '_VACT_Name', true);    //예금주
                        $VACT_InputName = get_post_meta($order_id, '_VACT_InputName', true);   //송금자
                        $VACT_Date = get_post_meta($order_id, '_VACT_Date', true);    //입금예정일

                        $order = new WC_Order($order_id);

                        if( !in_array($order->get_status(), array('completed', 'cancelled', 'refunded') ) ) {  //주문상태 확인
                            if($txnid != $P_OID) {    //거래번호(oid) 체크
                                $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 거래번호 확인 실패 ' . print_r($_REQUEST, true) );
                                echo 'FAIL_M11';
                                exit();
                            }
                            if($P_FN_CD1 != $VACT_BankCode) {    //입금은행 코드 체크
                                $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 입금은행 코드 확인 실패 ' . print_r($_REQUEST, true) );
                                echo 'FAIL_M12';
                                exit();
                            }
                            if($VACT_Num != $p_vacct_no) {    //입금계좌번호 체크
                                $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 입금 계좌번호 확인 실패 ' . print_r($_REQUEST, true) );
                                echo 'FAIL_M13';
                                exit();
                            }
                            if((int)$P_AMT != (int)$order->get_total()) {    //입금액 체크
                                $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 입금액 확인 실패 ' . print_r($_REQUEST, true) );
                                echo 'FAIL_M14';
                                exit();
                            }

                            update_post_meta( ifw_get($order, 'id'), '_inicis_vbank_noti_received', 'yes');
                            update_post_meta( ifw_get($order, 'id'), '_inicis_vbank_noti_received_tid', $P_TID);
                            $order->add_order_note( sprintf( __('입금통보 내역이 수신되었습니다. 가맹점 관리자에서 주문 확인후 처리해주세요. 전송서버IP : %s, 거래번호(TID) : %s, 상점거래번호(OID) : %s, 입금은행코드 : %s, 입금은행명 : %s, 입금가상계좌번호 : %s, 입금액 : %s, 입금자명 : %s', 'inicis_payment'), $PGIP,  $P_TID, $P_OID, $P_FN_CD1, mb_convert_encoding($P_FN_NM, "UTF-8", "EUC-KR"), $p_vacct_no, number_format($P_AMT), mb_convert_encoding($P_UNAME, "UTF-8", "EUC-KR") ) );
                            $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 성공. ' . print_r($_REQUEST, true) );
                            $order->payment_complete();
                            $order->update_status($this->settings['order_status_after_vbank_noti']);
                            echo 'OK';
                            exit();
                        } else { //주문상태가 이상한 경우
                            $order->add_order_note( sprintf( __('[모바일] 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 전송서버IP : %s, 거래번호(TID) : %s, 상점거래번호(OID) : %s, 입금은행코드 : %s, 입금은행명 : %s, 입금가상계좌번호 : %s, 입금액 : %s, 입금자명 : %s','inicis_payment'), $PGIP,  $P_TID, $P_OID, $P_FN_CD1, mb_convert_encoding($P_FN_NM, "UTF-8", "EUC-KR"), $p_vacct_no, number_format($P_AMT), mb_convert_encoding($P_UNAME, "UTF-8", "EUC-KR") ) );
                            $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 주문상태 - ' . $order->get_status() . ', ' . print_r($_REQUEST, true) );
                            $this->inicis_alert_mail( sprintf( __('[모바일] 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다.'.PHP_EOL.PHP_EOL.'전송서버IP : %s'.PHP_EOL.'주문번호 : #%s'.PHP_EOL.'주문상태 : %s'.PHP_EOL.'거래번호(TID) : %s'.PHP_EOL.'상점거래번호(OID) : %s'.PHP_EOL.'입금은행코드 : %s'.PHP_EOL.'입금은행명 : %s'.PHP_EOL.'입금가상계좌번호 : %s'.PHP_EOL.'입금액 : %s'.PHP_EOL.'입금자명 : %s','inicis_payment'), $PGIP, $order_id, $order->get_status(), $P_TID, $P_OID, $P_FN_CD1, mb_convert_encoding($P_FN_NM, "UTF-8", "EUC-KR"), $p_vacct_no, number_format($P_AMT), mb_convert_encoding($P_UNAME, "UTF-8", "EUC-KR") ) );
                            echo 'OK';    //가맹점 관리자 사이트에서 재전송 가능하나 주문건 확인 필요
                            exit();
                        }
                    } else {
                        $this->inicis_print_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 결제 결과 이상 -  ' . $P_STATUS . ', ' . print_r($_REQUEST, true) );
                        echo "OK";
                        return;
                    }
                }

                $notification = $this->decrypt_notification($_POST['P_NOTI']);
                if( empty($notification) ){
                    $this->inicis_print_log( __( '유효하지않은 주문입니다. (invalid notification)', 'inicis_payment' ) );
                    echo "FAIL";
                    exit();
                }

                $txnid = $notification->txnid;
                $hash = $notification->hash;

                if( $_REQUEST['P_STATUS'] == '00' && !empty($txnid) )
                {
                    $userid = get_current_user_id();
                    $orderid = explode('_', $txnid);
                    $orderid = (int)$orderid[0];
                    $order = new WC_Order($orderid);

                    if( empty($order) || !is_numeric($orderid) ){
                        $this->inicis_print_log( __( '유효하지않은 주문입니다. (invalid orderid)', 'inicis_payment' ) );
                        echo "FAIL";
                        exit();
                    }

                    $productinfo = $this->make_product_info($order);
                    $order_total = $this->inicis_get_order_total($order);

                    if($order->get_status() == 'failed' || $order->get_status() == 'cancelled' ){

                        $this->inicis_print_log( sprintf( __('주문요청(#%s)에 대한 상태(%s)가 유효하지 않습니다.', 'inicis_payment' ), $orderid, __($order->get_status(), 'woocommerce')));
                        $this->inicis_alert_mail( sprintf( __("주문요청(#%s)에 대한 상태(%s)가 유효하지 않습니다. LOG : %s", "inicis_payment"), $orderid, __($order->get_status(), 'woocommerce'), print_r($_REQUEST, true) ) );
                        $order->add_order_note( sprintf( __('<font color="red">주문요청(#%s)에 대한 상태(%s)가 유효하지 않습니다.</font>', 'inicis_payment' ), $orderid, __($order->get_status(), 'woocommerce')));
                        $rst = $this->cancel_request($_REQUEST['P_TID'], __('주문시간 초과오류 : 자동결재취소', 'inicis_payment'), __('CM_CANCEL_100', 'inicis_payment') );

                        if($rst == "success"){
                            $order->add_order_note( sprintf( __('<font color="red">[결재알림]</font>주문시간 초과오류건(%s)에 대한 자동 결제취소가 진행되었습니다.', 'inicis_payment'), $_REQUEST['P_TYPE']) );
                            update_post_meta( ifw_get($order, 'id'), '_codem_inicis_order_cancelled', TRUE);
                        } else {
                            $order->add_order_note( sprintf( __('<font color="red">주문시간 초과오류건(%s)에 대한 자동 결제취소가 실패했습니다.</font>', 'inicis_payment'), $_REQUEST['P_TYPE']) );
                        }

                        echo "FAIL";
                        exit();
                    }

                    if($this->validate_txnid($order, $txnid) == false){
                        $this->inicis_print_log( sprintf( __( '유효하지 않은 주문번호(%s) 입니다', 'inicis_payment'), $txnid) );
                        $order->add_order_note( sprintf( __('<font color="red">유효하지 않은 주문번호(%s) 입니다.</font>', 'inicis_payment'), $txnid) );
                        echo "FAIL";
                        exit();
                    }

                    //wpml 추가
                    if(function_exists('icl_object_id')) {
                        if( $this->id == 'inicis_stdescrow_bank') {
                            $checkhash = hash('sha512', (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                        } else {
                            $checkhash = hash('sha512', (string)$this->settings['merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                        }
                    } else {
                        if( $this->id == 'inicis_stdescrow_bank') {
                            $checkhash = hash('sha512', (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                        } else {
                            $checkhash = hash('sha512', (string)$this->settings['merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                        }
                    }

                    if($hash != $checkhash){
                        if(function_exists('icl_object_id')) {
                            if( $this->id == 'inicis_stdescrow_bank') {
                                $this->inicis_print_log( (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                            } else {
                                $this->inicis_print_log( (string)$this->settings['merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                            }
                        } else {
                            if( $this->id == 'inicis_stdescrow_bank') {
                                $this->inicis_print_log( (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                            } else {
                                $this->inicis_print_log( (string)$this->settings['merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||");
                            }
                        }

                        $this->inicis_print_log( sprintf( __( '주문요청(%s)에 대한 위변조 검사 오류입니다.', 'inicis_payment'), $txnid) );
                        $order->add_order_note( sprintf( __('<font color="red">주문요청(%s)에 대한 위변조 검사 오류입니다.</font>', 'inicis_payment'), $txnid) );
                        echo "FAIL";
                        exit();
                    }

                    $inimx_txnid = $_REQUEST['P_OID'];
                    $inimx_orderid = explode('_', $inimx_txnid);
                    $inimx_orderid = (int)$inimx_orderid[0];

                    if( $txnid != $inimx_txnid || $orderid != $inimx_orderid ){
                        $this->inicis_print_log( sprintf( __( '주문요청(%s, %s, %s, %s)에 대한 위변조 검사 오류입니다. 결재는 처리되었으나, 결재요청에 오류가 있습니다. 이니시스 결재내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'inicis_payment' ), $txnid, $inimx_txnid, $orderid, $inimx_orderid) );
                        $order->add_order_note( sprintf( __('<font color="red">주문요청(%s, %s, %s, %s)에 대한 위변조 검사 오류입니다. 결재는 처리되었으나, 결재요청에 오류가 있습니다. 이니시스 결재내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.</font>', 'inicis_payment' ), $txnid, $inimx_txnid, $orderid, $inimx_orderid) );
                        echo 'OK';
                        exit();
                    }

                    add_post_meta($orderid, "_inicis_paymethod", $_REQUEST['P_TYPE']);
                    add_post_meta($orderid, "_inicis_paymethod_tid",  $_REQUEST['P_TID']);

                    if( $this->id == 'inicis_stdescrow_bank') {
                        $this->inicis_print_log( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일 에스크로] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'inicis_payment'), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'] ) );
                        $order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일 에스크로] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'),$_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'] ) );
                    } else {
                        $this->inicis_print_log( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'inicis_payment'), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'] ) );
                        $order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'inicis_payment'),$_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'] ) );
                    }

                    $order->payment_complete();

                    $woocommerce->cart->empty_cart();

                    delete_post_meta($orderid, "_ini_rn");
                    delete_post_meta($orderid, "_ini_enctype");
                    //delete_post_meta($orderid, '_txnid');

                    echo "OK";
                    exit();
                }else{
                    $txnid = $_REQUEST['P_OID'];
                    $orderid = explode('_', $txnid);
                    $orderid = (int)$orderid[0];
                    $order = new WC_Order($orderid);

                    if( $this->id == 'inicis_stdescrow_bank') {
                        $this->inicis_print_log( sprintf( __( '주문 처리 실패. 결제방법 : [모바일 에스크로] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s, 에러코드 : %s, 에러내용 : %s', 'inicis_payment'), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'], $_REQUEST['P_STATUS'], mb_convert_encoding($_REQUEST['P_RMESG1'], "UTF-8", "CP949") ) );
                        $order->add_order_note( sprintf( __( '주문 처리 실패. 결제방법 : [모바일 에스크로] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s, 에러코드 : %s, 에러내용 : %s', 'inicis_payment'), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'], $_REQUEST['P_STATUS'], mb_convert_encoding($_REQUEST['P_RMESG1'], "UTF-8", "CP949") ) );
                    } else {
                        $this->inicis_print_log( sprintf( __( '주문 처리 실패. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s, 에러코드 : %s, 에러내용 : %s', 'inicis_payment'), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'], $_REQUEST['P_STATUS'], mb_convert_encoding($_REQUEST['P_RMESG1'], "UTF-8", "CP949") ) );
                        $order->add_order_note( sprintf( __( '주문 처리 실패. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s, 에러코드 : %s, 에러내용 : %s', 'inicis_payment'), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'], $_REQUEST['P_STATUS'], mb_convert_encoding($_REQUEST['P_RMESG1'], "UTF-8", "CP949") ) );
                    }
                    $order->update_status('failed');
                    echo 'OK';
                    exit();
                }
            } else {
                $this->inicis_print_log( __( '잘못된 아이피로 접근하였습니다. IP : ' . $_SERVER['REMOTE_ADDR'], 'inicis_payment') );
                echo "FAIL";
                exit();
            }

            $this->inicis_print_log("===== [ MOBILE NOTI END ] =====");
        }
        function successful_request_mobile_return( $posted ) {
            global $woocommerce;

            if( wp_is_mobile() ) {
                if(in_array($this->id, array('inicis_stdbank', 'inicis_stdcard', 'inicis_stdkpay', 'inicis_stdhpp', 'inicis_stdvbank', 'inicis_stdescrow_bank', 'inicis_stdsamsungpay') ) ) {
                    $get_type = $_GET['type'];
                    $tmp_rst = explode(',', $get_type);
                    $tmp_oid = $tmp_rst[1];
                    $tmp_rst = explode('=', $tmp_oid);
                    $oid = $tmp_rst[1];
                    $tmp_rst = explode('_', $oid);
                    $orderid = $tmp_rst[0];

                    $order = new WC_Order($orderid);
                    if(in_array($order->get_status(), array('pending', 'failed'))){
                        wc_add_notice( __('결제를 취소하였거나 처리가 늦어지고 있습니다. 잠시만 기다리셨다가 주문 상태를 다시 확인해주세요. (ERROR: 0xF53D)', 'inicis_payment' ), 'error' );
                        wp_redirect( WC()->cart->get_checkout_url() );
                        exit();
                    }
                }
            }

        }
        function process_payment($orderid){

            global $woocommerce;

            $order = new WC_Order($orderid);

            //WooCommerce Version Check
            if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) {
                return array(
                    'result'    => 'success',
                    'redirect'  => $order->get_checkout_payment_url( true ),
                    'order_id'  => ifw_get($order, 'id'),
                    'order_key' => ifw_get($order, 'order_key'),
                );
            } else {
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('order', ifw_get($order, 'id'), add_query_arg('key', ifw_get($order, 'order_key'), get_permalink(get_option('woocommerce_pay_page_id')))),
                    'order_id'  => ifw_get($order, 'id'),
                    'order_key' => ifw_get($order, 'order_key'),
                );
            }
        }
        function receipt_page( $order ) {
        }
        function encrypt_notification($data, $hash) {
            $param = array(
                'txnid' => $data,
                'hash' => $hash
            );

            return aes256_cbc_encrypt("inicis-for-woocommerce", json_encode($param), "codemshop" );
        }
        function decrypt_notification($data) {
            return json_decode(aes256_cbc_decrypt("inicis-for-woocommerce", $data, "codemshop" ));
        }
        function make_txnid($order) {
        	$txnid = get_post_meta( ifw_get($order, 'id'), '_txnid', true);
			if( empty($txnid) ) {
	            $txnid = ifw_get($order, 'id') . '_' . date("ymd") . '_' . date("his");
	            update_post_meta( ifw_get($order, 'id'), '_txnid', $txnid);
			}
            return $txnid;
        }
        function validate_txnid($order, $txnid) {
            $org_txnid = get_post_meta( ifw_get($order, 'id'), '_txnid', true);
            return $org_txnid == $txnid;
        }
        function make_product_info($order) {
            $items = $order->get_items();

            if(count($items) == 1){
                $keys = array_keys($items);
                return $items[$keys[0]]['name'];
            }else{
                $keys = array_keys($items);
                return sprintf( __('%s 외 %d건', 'inicis_payment'), $items[$keys[0]]['name'], count($items)-1);
            }
        }
        function wp_ajax_generate_payment_form()
        {
            global $woocommerce, $inicis_payment;

            //PHP 확장 동작 여부 확인
            if( !function_exists('openssl_digest') ) {
                wp_send_json_error(__('에러 : PHP OpenSSL 확장이 설치되어 있지 않아 이용할 수 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment'));
            }

            if( !function_exists('hash') ) {
                wp_send_json_error(__('에러 : PHP MCrypt 확장이 설치되어 있지 않아 이용할 수 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment'));
            }

            if( !function_exists('mb_convert_encoding') ) {
                wp_send_json_error(__('에러 : PHP MBString 확장이 설치되어 있지 않아 이용할 수 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment'));
            }

            $orderid = $_REQUEST['orderid'];

            if( !empty($orderid) ) {
                $order = new WC_Order($orderid);
            } else {
                wp_send_json_error(__('결제오류 : 주문번호가 확인되지 않습니다. 사이트 관리자에게 문의하여 주십시오', 'inicis_payment'));
            }

            //재고 관리 사용시 처리 추가
            if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && sizeof( $order->get_items() ) > 0 ) {

                $stock_count = 0;
                $item_list = $order->get_items();
                foreach ($item_list as $item) {
                    //항목의 아이템에 상품 아이디가 존재하는 경우
                    if ($item['product_id'] > 0) {
                        $_product = $order->get_product_from_item($item);
                        if ($_product && $_product->exists() && $_product->managing_stock()) {

                            // 옵션 상품인지 확인
                            if ($item['variation_id'] > 0) {
                                $stock_status = get_post_meta($item['variation_id'], '_stock_status', true);    //instock
                                $stock_manage = get_post_meta($item['variation_id'], '_manage_stock', true);
                                $stock_backorder = get_post_meta($item['variation_id'], '_backorders', true);

                                if ($stock_manage == 'yes') {
                                    if ($stock_status == 'instock' && $stock_backorder == 'yes') {
                                        //재고있음 처리
                                        $stock_count = 1;
                                    } else {
                                        $stock_count = get_post_meta($item['variation_id'], '_stock', true);
                                    }
                                } else {
                                    $stock_count = 1;
                                }
                            } else {
                                //일반 상품
                                $stock_status = get_post_meta($item['product_id'], '_stock_status', true);    //instock
                                $stock_manage = get_post_meta($item['product_id'], '_manage_stock', true);
                                $stock_backorder = get_post_meta($item['product_id'], '_backorders', true);


                                if ($stock_manage == 'yes') {
                                    if ($stock_status == 'instock' && $stock_backorder == 'yes') {
                                        //재고있음 처리
                                        $stock_count = 1;
                                    } else {
                                        $stock_count = get_post_meta($item['product_id'], '_stock', true);
                                    }

                                } else {
                                    $stock_count = 1;
                                }
                            }
                        } else {
                            $stock_count = 1;
                        }
                    }

                    if ($stock_count < 1) {
                        wp_send_json_error(__('결제오류 : 주문하시려는 상품 중에 재고가 부족한 상품이 있습니다. 상품 재고를 확인해주세요.', 'inicis_payment'));
                    }
                }
            }

            if(!empty($this->id)){
                switch($this->id){
                    case 'inicis_stdcard':  //웹표준 신용카드
                    case 'inicis_stdbank':  //웹표준 실시간계좌이체
                    case 'inicis_stdvbank': //웹표준 가상계좌무통장입금
                    case 'inicis_stdkpay':  //웹표준 KPAY간편결제
                    case 'inicis_stdhpp':   //웹표준 휴대폰소액결제
                    case 'inicis_stdescrow_bank':   //웹표준 에스크로 실시간계좌이체
                    case 'inicis_stdsamsungpay':   //웹표준 삼성페이
                        //라이브러리 존재여부 체크
                        if (!file_exists($inicis_payment->plugin_path() ."/lib/inistd/INIStdPayUtil.php")) {
                            wp_send_json_error(__('에러 : INIStdPayUtil.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment'));
                        }

                        try {
                            require_once($inicis_payment->plugin_path() . "/lib/inistd/INIStdPayUtil.php");
                        } catch (Exception $e) {
                            wp_send_json_error(__('에러 : INIStdPayUtil.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'inicis_payment') . ' [' . $e->getMessage() . ']');
                        }
                        $use_ssl = get_option('woocommerce_force_ssl_checkout'); //SSL 체크

                        $SignatureUtil = new INIStdPayUtil();

                        if( $this->id == 'inicis_stdescrow_bank') {
                            $mid = $this->settings['escrow_merchant_id'];

                            if(empty($this->settings['escrow_signkey'])){
                                $signKey = 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS';  //INIpayTest 기본값
                            } else {
                                $signKey = $this->settings['escrow_signkey'];
                            }
                        } else {
                            $mid = $this->settings['merchant_id'];

                            if(empty($this->settings['signkey'])){
                                $signKey = 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS';  //INIpayTest 기본값
                            } else {
                                $signKey = $this->settings['signkey'];
                            }
                        }


                        $timestamp = $SignatureUtil->getTimestamp(); //타임스탬프

                        //결제옵션 가져오기
                        $acceptmethod = $this->get_accpetmethod();
                        $cardNoInterestQuota = $this->settings['nointerest'];  //카드무이자 여부 설정

                        //가맹점에서 사용할 할부 개월수 설정 (PC 웹용)
                        $quotabase_arr = explode(',', $this->settings['quotabase']);
                        $quotabase_option = array();

                        //할부 결제시 일시불 결제 표시하기 위해 추가
                        $quotabase_option[] = sprintf('%02d', (int)0);
                        foreach($quotabase_arr as $item) {
                            $quotabase_option[] = sprintf('%02d', (int)$item);
                        }
                        sort($quotabase_option);
                        $cardQuotaBase = implode(':', $quotabase_option);

                        $mKey = $SignatureUtil->makeHash($signKey, "sha256");

                        $userid = get_current_user_id();
                        $order = new WC_Order($orderid);
                        $txnid = $this->make_txnid($order);
                        $productinfo = $this->make_product_info($order);
                        $price = $this->inicis_get_order_total($order);
                        $order_total = $this->inicis_get_order_total($order);
                        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                        $order->set_payment_method( $available_gateways[ $this->id ] );

                        if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=') ) {
                            $order->save();
                        }

                        $params = array(
                            "oid" => $txnid,
                            "price" => $price,
                            "timestamp" => $timestamp
                        );
                        $sign = $SignatureUtil->makeSignature($params, "sha256");

                        //WPML 사용시 처리 추가
                        if(function_exists('icl_object_id')){
                            if (wp_is_mobile()) {
                                if( $this->id == 'inicis_stdescrow_bank') {
                                    $str = (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                } else {
                                    $str = (string)$this->settings['merchant_id'] . "|$txnid||$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                }
                                $hash = hash('sha512', $str);
                                $notification = $this->encrypt_notification($txnid, $hash);
                                ob_start();
                                include($inicis_payment->plugin_path() . '/templates/payment_form_mobile.php');
                                $form_tag = ob_get_clean();
                            } else {
                                if( $this->id == 'inicis_stdescrow_bank') {
                                    $str = (string)$this->settings['escrow_merchant_id'] . "|$txnid|$userid|$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                } else {
                                    $str = (string)$this->settings['merchant_id'] . "|$txnid|$userid|$order_total||".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                }

                                $hash = hash('sha512', $str);
                                $notification = $this->encrypt_notification($txnid, $hash);
                                $payView_type = 'overlay';

                                ob_start();
                                include($inicis_payment->plugin_path() . '/templates/payment_form_std.php');
                                $form_tag = ob_get_clean();
                            }
                        } else {
                            if (wp_is_mobile()) {
                                if( $this->id == 'inicis_stdescrow_bank') {
                                    $str = (string)$this->settings['escrow_merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                } else {
                                    $str = (string)$this->settings['merchant_id'] . "|$txnid||$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                }
                                $hash = hash('sha512', $str);
                                $notification = $this->encrypt_notification($txnid, $hash);
                                ob_start();
                                include($inicis_payment->plugin_path() . '/templates/payment_form_mobile.php');
                                $form_tag = ob_get_clean();
                            } else {
                                if( $this->id == 'inicis_stdescrow_bank') {
                                    $str = (string)$this->settings['escrow_merchant_id'] . "|$txnid|$userid|$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                } else {
                                    $str = (string)$this->settings['merchant_id'] . "|$txnid|$userid|$order_total|$productinfo|".ifw_get($order, 'billing_first_name')."|".ifw_get($order, 'billing_email')."|||||||||||";
                                }

                                $hash = hash('sha512', $str);
                                $notification = $this->encrypt_notification($txnid, $hash);
                                $payView_type = 'overlay';

                                ob_start();
                                include($inicis_payment->plugin_path() . '/templates/payment_form_std.php');
                                $form_tag = ob_get_clean();
                            }
                        }

                        break;
                    default:
                        wp_send_json_error(__('결제오류 : 결제 수단이 선택되지 않았습니다 사이트 관리자에게 문의하여 주십시오', 'inicis_payment'));
                        break;
                }
            } else {
                wp_send_json_error(__('결제오류 : 결제 수단이 선택되지 않았습니다 사이트 관리자에게 문의하여 주십시오', 'inicis_payment'));
            }

            wp_send_json_success('<div data-id="mshop-payment-form" style="display:none">' . $form_tag . '</div>');
        }
        function successful_request_cancelled( $posted ) {
            global $woocommerce, $inicis_payment;
    
            require_once($inicis_payment->plugin_path() . "/lib/inipay50/INILib.php");
            $inipay = new INIpay50();

            $inipay->SetField("inipayhome", $this->settings['libfolder']);
            $inipay->SetField("type", "cancel");
            $inipay->SetField("debug", "false");
            $inipay->SetField("mid", $_REQUEST['mid']);
            $inipay->SetField("admin", "1111");
            $inipay->SetField("tid", $_REQUEST['tid']);
            $inipay->SetField("cancelmsg", $_REQUEST['msg']);
        
            if($code != ""){
                $inipay->SetField("cancelcode", $_REQUEST['code']);
            }
    
            $inipay->startAction();
            
            if($inipay->getResult('ResultCode') == "00"){
                echo "success";
                return;
                //exit();
            }else{
                echo $inipay->getResult('ResultMsg');
                return;
                //exit();
            }
        }
        function check_inicis_payment_response() {

            $this->inicis_print_log( 'Response() 처리 시작' . print_r($_REQUEST, true) );

            if (!empty($_REQUEST)) {

                if (!empty($_REQUEST['type'])) {
                    switch($_REQUEST['type']) {
                        //웹표준 결제 추가 호출 경로
                        case "std_cancel" :
                            wp_print_scripts('jquery');
                            ?>
                            <script language="javascript">
                                parent.jQuery(parent.document.body).trigger('inicis_unblock_payment')
                            </script>
                            <script language="javascript" type="text/javascript" src="https://stdpay.inicis.com/stdjs/INIStdPay_close.js" charset="UTF-8"></script>
                            <?php
                            die();
                            break;
                        case "std_popup" :
                            echo '<script language="javascript" type="text/javascript" src="https://stdpay.inicis.com/stdjs/INIStdPay_popup.js" charset="UTF-8"></script>';
                            die();
                            break;
                        default:
                            break;
                    }
                }

                header('HTTP/1.1 200 OK');
				header("Content-Type: text; charset=euc-kr");
				header("Cache-Control: no-cache");
				header("Pragma: no-cache");

                if (!empty($_REQUEST['type'])) {
                    if(strpos($_REQUEST['type'],'?') !== false) {
                        $return_type = explode('?', $_REQUEST['type']);
                        $_REQUEST['type'] = $return_type[0];
                        $tmp_status = explode('=', $return_type[1]);
                        $_REQUEST['P_STATUS'] = $tmp_status[1];
                    } else {
                        $return_type = explode(',', $_REQUEST['type']);
                    }

                    $res_txnid = empty($_REQUEST['txnid']) ? '' : $_REQUEST['txnid'];
                    $res_p_noti = empty($_REQUEST['P_NOTI']) ? '' : $_REQUEST['P_NOTI'];
                    $res_p_oid = empty($_REQUEST['P_OID']) ? '' : $_REQUEST['P_OID'];
                    $res_oid = empty($_REQUEST['oid']) ? '' : $_REQUEST['oid'];
                    $res_no_oid = empty($_REQUEST['no_oid']) ? '' : $_REQUEST['no_oid'];
                    $res_ordernumber = empty($_REQUEST['orderNumber']) ? '' : $_REQUEST['orderNumber'];
                    $res_postid = empty($_REQUEST['postid']) ? '' : $_REQUEST['postid'];


                    if( $res_txnid ) {
                        $orderid = explode('_', $res_txnid);
                    } else if( $res_p_noti ) {
                        $notification = $this->decrypt_notification($res_p_noti);
                        $orderid = explode('_', $notification->txnid);
                    } else if( $res_p_oid ) {
                        $orderid = explode('_', $res_p_oid);
                    } else if( $res_oid ) {
                        $orderid = explode('_', $res_oid);
                    } else if( $res_no_oid ) {
                        $orderid = explode('_', $res_no_oid);
                    } else if( $res_ordernumber ) {
                        $orderid = explode('_', $res_ordernumber);
                    } else if( $res_postid ) {
                        $orderid = explode('_', $res_postid);
                    } else if ( $return_type[1] ) {
                        $temp_oid = explode('=', $return_type[1]);
                        $orderid = explode('_', $temp_oid[1]);
                    }
                    
                    if( !empty( $orderid ) ) {
                        $orderid = (int)$orderid[0];
                        $order = new WC_Order($orderid);
                    } else {
                        $this->inicis_print_log( '주문번호 없음.' . print_r($_REQUEST, true) );
                    }

                    if( !in_array($return_type[0], array('vbank_refund_add', 'vbank_refund_modify', 'vbank_noti' ) ) && !empty($order) ) {

                        $this->inicis_print_log( '모바일 가상계좌 채번 여부 확인 시작.' . print_r( $_REQUEST, true ) );

                        //모바일 가상계좌 채번시 노티가 아닌 경우 진행
                        if ( in_array( $return_type[0], array( 'mobile_noti' ) ) && $_REQUEST['P_TYPE'] == 'VBANK' && $_REQUEST['P_STATUS'] == '00' ) {
                            return;
                        }

                        if ( !in_array( $return_type[0], array( 'mobile_noti' ) ) && $_REQUEST['P_TYPE'] != 'VBANK' && $_REQUEST['P_STATUS'] != '02' ) {
                            //재고 확인 처리
                            $this->is_stock_check($order);
                        }

                        $this->inicis_print_log( '모바일 가상계좌 채번 여부 확인 종료' . print_r($_REQUEST, true) );
                    }

                    switch($return_type[0]) {
                        case "cancelled" :
                            $this->successful_request_cancelled($_POST);
                            do_action('after_successful_request_cancelled');
                            $this->inicis_redirect_page($orderid);
                            break;
                        case "vbank_noti" :
                            $this->successful_request_vbank_noti($_POST);
                            do_action('after_successful_request_vbank_noti');
                            $this->inicis_redirect_page($orderid);
                            break;
                        case "mobile_next" :
                            $this->successful_request_mobile_next($_POST);
                            do_action('after_successful_request_mobile_next');
                            $this->inicis_redirect_page($orderid);
                            break;
                        case "mobile_noti" :
                            $this->successful_request_mobile_noti($_POST);
                            do_action('after_successful_request_mobile_noti');
                            $this->inicis_redirect_page($orderid);
                            break;
                        case "mobile_return" :
                            $this->successful_request_mobile_return($_POST);
                            do_action('after_successful_request_mobile_return');
                            $this->inicis_redirect_page($orderid);
                            break;
                        case "cancel_payment" :
                            do_action("valid-inicis-request_cancel_payment", $_POST);
                            $this->inicis_redirect_page($orderid);
                            break;
                        case "delivery": 
                            if(get_class($this) == 'WC_Gateway_Inicis_StdEscrow_bank') {
                                $this->inicis_escrow_delivery_add($_POST);
                                do_action('after_inicis_escrow_delivery_add');
                            }
                            break; 
                        case "delivery_okay":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdEscrow_bank') {
                                $this->inicis_escrow_delivery_okay($_POST);
                                do_action('after_inicis_escrow_delivery_okay');
                            }
                            break; 
                        case "confirm":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdEscrow_bank') {
                                $this->inicis_escrow_request_confirm($_POST);
                                do_action('after_inicis_escrow_request_confirm');
                                $this->inicis_redirect_page($orderid);
                            }
                            break; 
                        case "denyconfirm":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdEscrow_bank') {
                                $this->inicis_escrow_request_denyconfirm($_POST);
                                do_action('after_inicis_escrow_request_denyconfirm');
                            }
                            break; 
                        case "cancel":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdEscrow_bank') {
                                $this->inicis_escrow_request_cancel_before_confirm($_POST);
                                do_action('after_inicis_escrow_request_cancel_before_confirm');
                            }
                            break;
                        case "get_order":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdEscrow_bank') {
                                $this->inicis_escrow_get_order($_POST);
                                do_action('after_inicis_escrow_get_order');
                            }
                            break;
                        case "vbank_refund_add":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdVbank') {
                                $this->inicis_vbank_refund_add($_POST);
                                do_action('after_inicis_vbank_refund_add');
                            }
                            break;
                        case "vbank_refund_modify":
                            if(get_class($this) == 'WC_Gateway_Inicis_StdVbank') {
                                $this->inicis_vbank_refund_modify($_POST);
                                do_action('after_inicis_vbank_refund_modify');
                            }
                            break;
                        case "std":
                            $this->successful_request_std($_POST);
                            do_action('after_successful_request_std');
                            $this->inicis_redirect_page($orderid);
                            break;
                        default :
                            if( empty($return_type[0]) ) {
                                $this->inicis_print_log( 'Request Type 값없음 종료.' . print_r($_REQUEST, true) );
                                wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'inicis_payment' ) );
                            } else {
                                do_action('inicis_ajax_response', $return_type[0]);
                            }
                            break;
                    }
                } else {
                    $this->inicis_print_log( 'Request Type 없음 종료.' . print_r($_REQUEST, true) );
                    wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'inicis_payment' ) );
                }
            } else {
                $this->inicis_print_log( 'Request 없음 종료.' . print_r($_REQUEST, true) );
                wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'inicis_payment' ) );
            }

            $this->inicis_print_log( 'Response() 처리 종료' . print_r($_REQUEST, true) );
        }
        function inicis_redirect_page($orderid) {

            if( !empty($orderid) ) {
                $order = wc_get_order($orderid);
            } else {
                wp_redirect( home_url() );
                die();
            }

            $this->inicis_print_log( '페이지 리다이렉트 처리 시작' . print_r($_REQUEST, true) );

            if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=') ) {
                if( isset( $order ) && !empty( $order ) ) {
                    if( $order->get_status() == 'pending' ) {
                        $this->inicis_print_log( '페이지 리다이렉트 = checkout' );
                        wp_redirect( wc_get_page_permalink( 'checkout' ) );
                    } else {
                        $this->inicis_print_log( '페이지 리다이렉트 = order_received' );
                        wp_redirect( $order->get_checkout_order_received_url() );
                    }
                } else {
                    if( is_user_logged_in() ){
                        $tmp_myaccount_pid = get_option( 'woocommerce_myaccount_page_id', true );
                        if ( empty( $tmp_myaccount_pid ) ) {
                            $this->inicis_print_log( '페이지 리다이렉트 = home_url' );
                            $myaccount_page = home_url();
                        } else {
                            $this->inicis_print_log( '페이지 리다이렉트 = my-account' );
                            $myaccount_page = get_permalink( get_option( 'woocommerce_myaccount_page_id', true ) );
                        }
                        wp_redirect( $myaccount_page );  
                    }else{
                        $this->inicis_print_log( '페이지 리다이렉트 = http referer : ' . print_r($_SERVER['HTTP_REFERER'], true) );
                        wp_redirect( $_SERVER['HTTP_REFERER'] );
                    }                 
                }
            } else {
                if( is_user_logged_in() ){
                    $tmp_myaccount_pid = get_option( 'woocommerce_myaccount_page_id', true );
                    if ( empty( $tmp_myaccount_pid ) ) {
                        $this->inicis_print_log( '페이지 리다이렉트 = home_url' );
                        $myaccount_page = home_url();
                    } else {
                        $this->inicis_print_log( '페이지 리다이렉트 = my-account' );
                        $myaccount_page = get_permalink( get_option( 'woocommerce_myaccount_page_id', true ) );
                    }
                    wp_redirect( $myaccount_page );
                }else{
                    $this->inicis_print_log( '페이지 리다이렉트 = http referer : ' . print_r($_SERVER['HTTP_REFERER'], true) );
                    wp_redirect( $_SERVER['HTTP_REFERER'] );
                }                 
            }
            $this->inicis_print_log( '페이지 리다이렉트 처리 종료' . print_r($_REQUEST, true) );
            die();
        }
        function inicis_print_log($msg, $type='ININoti')
        {
            $path = $this->settings['libfolder']."/log/";
            $file = $type . $this->settings['merchant_id'] ."_".date("Ymd").".log";
            
            if(!is_dir($path)) 
            {
                mkdir($path, 0755);
            }
            if(!($fp = fopen($path.$file, "a+"))) return 0;

            date_default_timezone_set( get_option('timezone_string') );

            if(fwrite($fp, '['.date("Y-m-d H:i:s") . '] ' . $msg . "\n") === FALSE)
            {
                fclose($fp);
                return 0;
            }
            fclose($fp);
            return 1;
        }
        function inicis_get_order_total($order) {
            if(version_compare( WOOCOMMERCE_VERSION, '2.3.0', '>=' )) {
                return $order->get_total();
            } else {
                return $order->get_order_total();
            }
        }
        function get_accpetmethod(){
            //옵션값 기준으로 옵션 설정
            $arr_accept_method = array();

            if( !wp_is_mobile() ) {
                if ($this->settings['skin_indx'] != '') {
                    $arr_accept_method[] = 'SKIN(' . $this->settings['skin_indx'] . ')';
                }
            }

            if ($this->id == 'inicis_stdcard' && !empty($this->settings)) {
                if ( wp_is_mobile() ) {
                    $arr_accept_method[] = 'twotrs_isp=Y';  //신용카드 거래 기본값
                    $arr_accept_method[] = 'block_isp=Y';   //신용카드 거래 기본값
                    $arr_accept_method[] = 'twotrs_isp_noti=N'; //신용카드 거래 기본값
                    $arr_accept_method[] = 'ismart_use_sign=Y'; //30만원 이상 결제 허용
                    $arr_accept_method[] = 'apprun_check=Y';    //카드사 앱사용 체크
                    $arr_accept_method[] = 'extension_enable=Y';    //사파리 이슈 해결 코드(제3공급자기능활성화)

                    if ( ! empty( $this->settings['nointerest'] ) ) {
                        $arr_accept_method[] = 'merc_noint=Y';  //상점 무이자할부 설정

                        $quotabase_arr    = explode( ',', $this->settings['nointerest'] );
                        $quotabase_option = array();

                        foreach ( $quotabase_arr as $item ) {
                            $quotabase_option[] = sprintf( '%02d', (int) $item );
                        }
                        sort( $quotabase_option );
                        $quotabase_option = implode( ':', $quotabase_option );


                        $noint_quota_tmp     = str_replace( ',', '^', $this->settings['nointerest'] );
                        $arr_accept_method[] = 'noint_quota=' . $noint_quota_tmp;  //상점 무이자할부 카드사별 기간값 지정
                    }

                    if ( $this->settings['cardpoint'] == 'yes' ) {
                        $arr_accept_method[] = 'cp_yn=Y';
                    }
                    $acceptmethod = implode( "&", $arr_accept_method );
                } else {
                    if ( $this->settings['cardpoint'] == 'yes' ) {
                        $arr_accept_method[] = 'cardpoint';
                    }
                    $acceptmethod = implode( ":", $arr_accept_method );
                }
            } else if ($this->id == 'inicis_stdsamsungpay' && !empty($this->settings)) {
                if( wp_is_mobile() ) {
                    //모바일 옵션
                    $arr_accept_method[] = 'twotrs_isp=Y';  //신용카드 거래 기본값
                    $arr_accept_method[] = 'block_isp=Y';   //신용카드 거래 기본값
                    $arr_accept_method[] = 'd_samsungpay=Y';    //삼성페이 활성화(모바일전용)
                    $arr_accept_method[] = 'twotrs_isp_noti=N'; //신용카드 거래 기본값
                    $arr_accept_method[] = 'ismart_use_sign=Y'; //30만원 이상 결제 허용
                    $arr_accept_method[] = 'apprun_check=Y';    //카드사 앱사용 체크
                    $arr_accept_method[] = 'extension_enable=Y';    //사파리 이슈 해결 코드(제3공급자기능활성화)

                    if ( !empty( $this->settings['nointerest'] ) ) {
                        $arr_accept_method[] = 'merc_noint=Y';  //상점 무이자할부 설정

                        $quotabase_arr = explode(',', $this->settings['nointerest']);
                        $quotabase_option = array();

                        foreach($quotabase_arr as $item) {
                            $quotabase_option[] = sprintf('%02d', (int)$item);
                        }
                        sort($quotabase_option);
                        $quotabase_option = implode(':', $quotabase_option);


                        $noint_quota_tmp = str_replace(',', '^', $this->settings['nointerest']);
                        $arr_accept_method[] = 'noint_quota=' . $noint_quota_tmp;  //상점 무이자할부 카드사별 기간값 지정
                    }

                    if ($this->settings['cardpoint'] == 'yes') {
                        $arr_accept_method[] = 'cp_yn=Y';
                    }
                    $acceptmethod = implode("&", $arr_accept_method);
                } else {
                    //PC 삼성 페이 옵션
                    $arr_accept_method[] = 'cardonly';
                    if ( $this->settings['cardpoint'] == 'yes' ) {
                        $arr_accept_method[] = 'cardpoint';
                    }
                    $acceptmethod = implode( ":", $arr_accept_method );
                }
            } else if ($this->id == 'inicis_stdvbank' && !empty($this->settings)) {
                if (wp_is_mobile()) {
                    if ($this->settings['receipt'] == 'yes') {
                        $arr_accept_method[] = 'vbank_receipt=Y';
                    }
                    $acceptmethod = implode("&", $arr_accept_method);
                } else {

                    if ($this->settings['receipt'] == 'yes') {
                        $arr_accept_method[] = 'va_receipt';    //현금영수증 발급UI 옵션
                    }
                    if ($this->settings['receipt'] == 'no') {
                        $arr_accept_method[] = 'no_receipt';    //현금영수증 미발급 옵션
                    }

                    //가상계좌 입금 기한 설정
                    if( !empty( $this->settings['account_date_limit'] ) ) {
                        //가상계좌 입금기한 설정이 된경우 설정값으로 표기
                        $add_date = $this->settings['account_date_limit'];
                        $date = date('Y-m-d');
                        $date = strtotime($date);
                        $date = strtotime("+{$add_date} day", $date);
                        $date = date('Ymd', $date);
                        $arr_accept_method[] = "vbank({$date})";

                    } else {
                        //가상계좌 입금기한 설정값이 없는 경우 기본값인 3일로 처리
                        $date = date('Y-m-d');
                        $date = strtotime($date);
                        $date = strtotime("+3 day", $date);
                        $date = date('Ymd', $date);
                        $arr_accept_method[] = "vbank({$date})";
                    }

                    $acceptmethod = implode(":", $arr_accept_method);
                }
            } else if ($this->id == 'inicis_stdhpp' && !empty($this->settings)) {
                if (!empty($this->settings['hpp_method'])) {
                    $arr_accept_method[] = 'HPP('.$this->settings['hpp_method'].')';
                } else {
                    $arr_accept_method[] = 'HPP(2)';
                }
                $acceptmethod = implode(":", $arr_accept_method);
            } else if ($this->id == 'inicis_stdkpay' && !empty($this->settings)) {
                if (wp_is_mobile()) {
                    $arr_accept_method[] = 'd_kpay=Y';
                    $arr_accept_method[] = 'kpay_siteId=KPAY';

                    if ( empty($this->settings['direct_run']) ? 'no' : $this->settings['direct_run'] == 'yes') {
                        $arr_accept_method[] = 'd_kpay_app=Y';
                    }
                    $acceptmethod = implode("&", $arr_accept_method);
                } else {
                    $acceptmethod = implode("&", $arr_accept_method);
                }
            } else if ($this->id == 'inicis_stdescrow_bank' && !empty($this->settings)) {
                if ($this->settings['receipt'] == 'no') {
                    $arr_accept_method[] = 'no_receipt';
                }
                $acceptmethod = implode(":", $arr_accept_method);
            } else if ($this->id == 'inicis_stdcard' && !empty($this->settings)) {
                if ($this->settings['cardpoint'] == 'yes') {
                    $arr_accept_method[] = 'cardpoint';
                }
                $acceptmethod = implode(":", $arr_accept_method);
            } else if ($this->id == 'inicis_stdbank' && !empty($this->settings)) {
                if ($this->settings['receipt'] == 'no') {
                    $arr_accept_method[] = 'no_receipt';
                }
            } else {
                $acceptmethod = '';
            }

            return $acceptmethod;
        }
        function get_cardname($cardcode){
            if( !empty( $cardcode ) ) {
                switch( $cardcode ) {
                    case "01": $cardname =  __('외환카드','inicis_payment'); break;
                    case "03": $cardname =  __('롯데카드','inicis_payment'); break;
                    case "04": $cardname =  __('현대카드','inicis_payment'); break;
                    case "06": $cardname =  __('국민카드','inicis_payment'); break;
                    case "11": $cardname =  __('BC카드','inicis_payment'); break;
                    case "12": $cardname =  __('삼성카드','inicis_payment'); break;
                    case "14": $cardname =  __('신한카드','inicis_payment'); break;
                    case "15": $cardname =  __('한미카드','inicis_payment'); break;
                    case "16": $cardname =  __('NH카드','inicis_payment'); break;
                    case "17": $cardname =  __('하나SK카드','inicis_payment'); break;
                    case "21": $cardname =  __('해외비자카드','inicis_payment'); break;
                    case "22": $cardname =  __('해외마스터카드','inicis_payment'); break;
                    case "23": $cardname =  __('JCB카드','inicis_payment'); break;
                    case "24": $cardname =  __('해외아멕스카드','inicis_payment'); break;
                    case "25": $cardname =  __('해외다이너스카드','inicis_payment'); break;
                    case "26": $cardname =  __('은련카드','inicis_payment'); break;
                    default: $cardname = sprintf(__('카드코드(%d)', 'inicis_payment'), $cardcode); break;
                }

                if( !empty( $cardname ) ){
                    return $cardname;
                } else {
                    return '';
                }

            } else {
                return '';
            }

        }
        function get_bankname($VACT_BankCode = ''){
            if( !empty( $VACT_BankCode ) ) {
                switch($VACT_BankCode) {
                    case "02": $VACT_BankCodeName = __('한국산업은행', 'inicis_payment'); break;
                    case "03": $VACT_BankCodeName = __('기업은행', 'inicis_payment'); break;
                    case "04": $VACT_BankCodeName = __('국민은행', 'inicis_payment'); break;
                    case "05": $VACT_BankCodeName = __('외환은행', 'inicis_payment'); break;
                    case "06": $VACT_BankCodeName = __('국민은행(구,주택은행)', 'inicis_payment'); break;
                    case "07": $VACT_BankCodeName = __('수협중앙회', 'inicis_payment'); break;
                    case "11": $VACT_BankCodeName = __('농협중앙회', 'inicis_payment'); break;
                    case "12": $VACT_BankCodeName = __('단위농협', 'inicis_payment'); break;
                    case "16": $VACT_BankCodeName = __('축협중앙회', 'inicis_payment'); break;
                    case "20": $VACT_BankCodeName = __('우리은행', 'inicis_payment'); break;
                    case "21": $VACT_BankCodeName = __('조흥은행(구)', 'inicis_payment'); break;
                    case "22": $VACT_BankCodeName = __('상업은행', 'inicis_payment'); break;
                    case "23": $VACT_BankCodeName = __('제일은행', 'inicis_payment'); break;
                    case "24": $VACT_BankCodeName = __('한일은행', 'inicis_payment'); break;
                    case "25": $VACT_BankCodeName = __('서울은행', 'inicis_payment'); break;
                    case "26": $VACT_BankCodeName = __('신한은행(구)', 'inicis_payment'); break;
                    case "27": $VACT_BankCodeName = __('씨티은행', 'inicis_payment'); break;
                    case "31": $VACT_BankCodeName = __('대구은행', 'inicis_payment'); break;
                    case "32": $VACT_BankCodeName = __('부산은행', 'inicis_payment'); break;
                    case "34": $VACT_BankCodeName = __('광주은행', 'inicis_payment'); break;
                    case "35": $VACT_BankCodeName = __('제주은행', 'inicis_payment'); break;
                    case "37": $VACT_BankCodeName = __('전북은행', 'inicis_payment'); break;
                    case "38": $VACT_BankCodeName = __('강원은행', 'inicis_payment'); break;
                    case "39": $VACT_BankCodeName = __('경남은행', 'inicis_payment'); break;
                    case "41": $VACT_BankCodeName = __('비씨카드', 'inicis_payment'); break;
                    case "45": $VACT_BankCodeName = __('새마을금고', 'inicis_payment'); break;
                    case "48": $VACT_BankCodeName = __('신용협동조합중앙회', 'inicis_payment'); break;
                    case "50": $VACT_BankCodeName = __('상초저축은행', 'inicis_payment'); break;
                    case "53": $VACT_BankCodeName = __('씨티은행', 'inicis_payment'); break;
                    case "54": $VACT_BankCodeName = __('홍콩상하이은행', 'inicis_payment'); break;
                    case "55": $VACT_BankCodeName = __('도이치은행', 'inicis_payment'); break;
                    case "56": $VACT_BankCodeName = __('ABN암로', 'inicis_payment'); break;
                    case "57": $VACT_BankCodeName = __('JP모건', 'inicis_payment'); break;
                    case "59": $VACT_BankCodeName = __('미쓰비시도쿄은행', 'inicis_payment'); break;
                    case "60": $VACT_BankCodeName = __('BOA(Bank of America)', 'inicis_payment'); break;
                    case "64": $VACT_BankCodeName = __('산림조합', 'inicis_payment'); break;
                    case "70": $VACT_BankCodeName = __('신안상호저축은행', 'inicis_payment'); break;
                    case "71": $VACT_BankCodeName = __('우체국', 'inicis_payment'); break;
                    case "81": $VACT_BankCodeName = __('하나은행', 'inicis_payment'); break;
                    case "83": $VACT_BankCodeName = __('평화은행', 'inicis_payment'); break;
                    case "87": $VACT_BankCodeName = __('신세계', 'inicis_payment'); break;
                    case "88": $VACT_BankCodeName = __('신한은행', 'inicis_payment'); break;
                    case "D1": $VACT_BankCodeName = __('동양종합금융증권', 'inicis_payment'); break;
                    case "D2": $VACT_BankCodeName = __('현대증권', 'inicis_payment'); break;
                    case "D3": $VACT_BankCodeName = __('미래에셋증권', 'inicis_payment'); break;
                    case "D4": $VACT_BankCodeName = __('한국투자증권', 'inicis_payment'); break;
                    case "D5": $VACT_BankCodeName = __('우리투자증권', 'inicis_payment'); break;
                    case "D6": $VACT_BankCodeName = __('하이투자증권', 'inicis_payment'); break;
                    case "D7": $VACT_BankCodeName = __('HMC투자증권', 'inicis_payment'); break;
                    case "D8": $VACT_BankCodeName = __('SK증권', 'inicis_payment'); break;
                    case "D9": $VACT_BankCodeName = __('대신증권', 'inicis_payment'); break;
                    case "DA": $VACT_BankCodeName = __('하나대투증권', 'inicis_payment'); break;
                    case "DB": $VACT_BankCodeName = __('굿모닝신한증권', 'inicis_payment'); break;
                    case "DC": $VACT_BankCodeName = __('동부증권', 'inicis_payment'); break;
                    case "DD": $VACT_BankCodeName = __('유진투자증권', 'inicis_payment'); break;
                    case "DE": $VACT_BankCodeName = __('메리츠증권', 'inicis_payment'); break;
                    case "DF": $VACT_BankCodeName = __('신영증권', 'inicis_payment'); break;
                    case "DG": $VACT_BankCodeName = __('대우증권', 'inicis_payment'); break;
                    case "DH": $VACT_BankCodeName = __('삼성증권', 'inicis_payment'); break;
                    case "DI": $VACT_BankCodeName = __('교보증권', 'inicis_payment'); break;
                    case "DJ": $VACT_BankCodeName = __('키움증권', 'inicis_payment'); break;
                    case "DK": $VACT_BankCodeName = __('이트레이드', 'inicis_payment'); break;
                    case "DL": $VACT_BankCodeName = __('솔로몬증권', 'inicis_payment'); break;
                    case "DM": $VACT_BankCodeName = __('한화증권', 'inicis_payment'); break;
                    case "DN": $VACT_BankCodeName = __('NH증권', 'inicis_payment'); break;
                    case "DO": $VACT_BankCodeName = __('부국증권', 'inicis_payment'); break;
                    case "DP": $VACT_BankCodeName = __('LIG증권', 'inicis_payment'); break;
                    default: $VACT_BankCodeName = sprintf(__('은행코드(%d)', 'inicis_payment'), $VACT_BankCode); break;
                }

                if( !empty( $VACT_BankCodeName ) ){
                    return $VACT_BankCodeName;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }
        function is_stock_check($order){

            //재고 관리 사용시 처리 추가
            if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && sizeof( $order->get_items() ) > 0 ) {

                $this->inicis_print_log( '재고 관리 사용시 처리 진행 시작' . print_r( $_REQUEST, true ) );

                $item_list = $order->get_items();
                foreach ( $item_list as $item ) {

                    //항목의 아이템에 상품 아이디가 존재하는 경우
                    if ( $item['product_id'] > 0 ) {

                        //재고수량 체크용 변수
                        $stock_count = 0;

                        $_product = $order->get_product_from_item( $item );

                        if ( $_product && $_product->exists() ) {

                            // 옵션 상품인지 확인
                            if ( $item['variation_id'] > 0 ) {
                                $stock_status_tmp = get_post_meta( $item['variation_id'], '_stock_status', true );
                                $stock_status     = empty( $stock_status_tmp ) ? 'instock' : $stock_status_tmp;    //instock

                                $stock_manage_tmp = get_post_meta( $item['variation_id'], '_manage_stock', true );
                                $stock_manage     = empty( $stock_manage_tmp ) ? 'no' : $stock_manage_tmp;

                                $stock_backorder_tmp = get_post_meta( $item['variation_id'], '_backorders', true );
                                $stock_backorder     = empty( $stock_backorder_tmp ) ? 'no' : $stock_backorder_tmp;

                                if ( $stock_status == 'instock' ) {
                                    if ( $stock_manage == 'yes' && $stock_backorder == 'yes' ) {
                                        $stock_count = get_post_meta( $item['variation_id'], '_stock', true );
                                    } else {
                                        $stock_count = 1;
                                    }
                                }

                                $this->inicis_print_log( '옵션 상품 재고 확인  = stock_status : ' . $stock_status . ', stock_manage : ' . $stock_manage . ', stock_backorder : ' . $stock_backorder . ', stock_count : ' . $stock_count );

                            } else {

                                //일반 상품
                                $stock_status_tmp = get_post_meta( $item['product_id'], '_stock_status', true );
                                $stock_status     = empty( $stock_status_tmp ) ? 'instock' : $stock_status_tmp;    //instock

                                $stock_manage_tmp = get_post_meta( $item['product_id'], '_manage_stock', true );
                                $stock_manage     = empty( $stock_manage_tmp ) ? 'no' : $stock_manage_tmp;

                                $stock_backorder_tmp = get_post_meta( $item['product_id'], '_backorders', true );
                                $stock_backorder     = empty( $stock_backorder_tmp ) ? 'no' : $stock_backorder_tmp;

                                if ( $stock_status == 'instock' ) {
                                    if ( $stock_manage == 'yes' && $stock_backorder == 'yes' ) {
                                        $stock_count = get_post_meta( $item['product_id'], '_stock', true );
                                    } else {
                                        $stock_count = 1;
                                    }
                                }
                                $this->inicis_print_log( '일반 상품 재고 확인  = stock_status : ' . $stock_status . ', stock_manage : ' . $stock_manage . ', stock_backorder : ' . $stock_backorder . ', stock_count : ' . $stock_count );

                            }

                            if ( $stock_count < 1 ) {

                                $p_id = ( $item['variation_id'] > 0 ) ? $item['variation_id'] : $item['product_id'];

                                $this->inicis_print_log( '재고 부족 상품(#' . $p_id . ')이 확인됨.' . print_r( $_REQUEST, true ) );
                                wc_add_notice( __( '결제오류 : 주문하시려는 상품 중에 재고가 부족한 상품(#' . $p_id . ')이 있습니다. 상품 재고를 확인해주세요.', 'inicis_payment' ), 'error' );

                                if ( ! empty( $order ) ) {
                                    $order->add_order_note( __( '결제오류 : 고객님이 주문하시려고 시도했던 상품 중에 재고가 부족한 상품(#' . $p_id . ')이 있습니다. 상품 재고를 확인해주세요.', 'inicis_payment' ) );
                                }

                                //JSON ERROR 발생 현상 수정
                                $checkout_url = wc_get_page_permalink( 'checkout' );
                                if ( ! empty( $checkout_url ) ) {
                                    $this->inicis_print_log( 'checkout 페이지로 이동 처리' );
                                    wp_redirect( $checkout_url );
                                    die();
                                } else {
                                    $cart_url = wc_get_page_permalink( 'cart' );
                                    if ( ! empty( $cart_url ) ) {
                                        $this->inicis_print_log( 'cart 페이지로 이동 처리' );
                                        wp_redirect( $cart_url );
                                        die();
                                    } else {
                                        $this->inicis_print_log( 'home_url() 페이지로 이동 처리' );
                                        wp_redirect( home_url() );
                                        die();
                                    }
                                }
                            }
                        }
                    }
                }
                $this->inicis_print_log( '재고 관리 사용시 처리 진행 종료' . print_r( $_REQUEST, true ) );
            }

        }
        function inicis_alert_mail($message ='') {
            //결제 오류 메일 발송 여부 확인
            if( get_option('inicis_pg_payment_error_email_alert', 'no') == 'yes' ) {
                $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 발송 처리 시작.');

                $from = get_bloginfo('admin_email');

                //결제오류 수신메일 주소 확인
                $to = get_option('inicis_pg_payment_error_email_address', get_bloginfo('admin_email') );
                $to = trim($to);    //공백제거

                if( is_email($to) ) {
                    $subject = __('[이니시스 결제 플러그인] 결제 오류 알림','inicis_payment');
                    $headers = array('Content-Type: text; charset=UTF-8','From: ' . $from );

                    $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 헤더 : ' . print_r($headers, true) );
                    $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 제목 : ' . print_r($subject, true) );
                    $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 내용 : ' . print_r($message, true) );

                    $result = wp_mail( $to, $subject, $message, $headers );

                    $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 발송 완료. 처리결과($result) : ' . $result);

                } else {
                    $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 확인 실패. 수신 설정된 이메일 주소 : ' . $to);
                }

                $this->inicis_print_log( '이니시스 결제 오류 알림 이메일 발송 처리 종료.');

            }
        }

    }
}