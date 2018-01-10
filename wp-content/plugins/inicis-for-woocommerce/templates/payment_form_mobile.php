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
?>

<form id="form1" name="ini" method="post" action="" accept-charset="EUC-KR">
	<input type="hidden" name="inipaymobile_type" id="select" value="web"/>
	<input type="hidden" name="P_OID" value="<?php echo $txnid; ?>"/>
	<input type="hidden" name="P_GOODS" value="<?php echo esc_attr($productinfo); ?>"/>
	<input type="hidden" name="P_AMT" value="<?php echo $this->inicis_get_order_total($order); ?>"/>
	<input type="hidden" name="P_UNAME" value="<?php echo ifw_get($order, 'billing_last_name') . ifw_get($order, 'billing_first_name'); ?>"/>
	<input type="hidden" name="P_MNAME" value="<?php echo get_bloginfo('name'); ?>"/>
	<input type="hidden" name="P_MOBILE" value="<?php echo preg_replace("/[^0-9]*/s", "", ifw_get($order, 'billing_phone') ); ?>"/>
	<input type="hidden" name="P_EMAIL" value="<?php echo ifw_get($order, 'billing_email'); ?>" />
	<?php if( $this->id == 'inicis_stdescrow_bank') { ?>
		<input type="hidden" name="P_MID" value="<?php echo $this->settings['escrow_merchant_id']; ?>">
	<?php } else { ?>
		<input type="hidden" name="P_MID" value="<?php echo $this->settings['merchant_id']; ?>">
	<?php } ?>
	<?php
		if (defined('ICL_LANGUAGE_CODE')) {
			$lang_code = ICL_LANGUAGE_CODE;
			if ($use_ssl == 'yes'){
				$flag_ssl = true;
			} else {
				$flag_ssl = false;
			}
			$next_url 	= WC()->api_request_url(get_class($this), $flag_ssl) . '?lang='.$lang_code.'&type=mobile_next';
			$return_url = WC()->api_request_url(get_class($this), $flag_ssl) . '?lang='.$lang_code.'&type=mobile_return,oid=' . $txnid ;
			$noti_url 	= WC()->api_request_url(get_class($this), $flag_ssl) . '?lang='.$lang_code.'&type=mobile_noti';
			$cancel_url = WC()->api_request_url(get_class($this), $flag_ssl) . '?land='.$lang_code.'&type=mobile_return,oid=' . $txnid ;
		} else {
			if ($use_ssl == 'yes'){
				$flag_ssl = true;
			} else {
				$flag_ssl = false;
			}
			$next_url 	= WC()->api_request_url(get_class($this), $flag_ssl) . '?type=mobile_next';
			$return_url = WC()->api_request_url(get_class($this), $flag_ssl) . '?type=mobile_return,oid=' . $txnid ;
			$noti_url 	= WC()->api_request_url(get_class($this), $flag_ssl) . '?type=mobile_noti';
			$cancel_url = WC()->api_request_url(get_class($this), $flag_ssl) . '?type=mobile_return,oid=' . $txnid ;
		}
	?>
    <input type="hidden" name="P_NEXT_URL" value="<?php echo $next_url; ?>">
	<input type="hidden" name="P_RETURN_URL" value="<?php echo $return_url; ?>">
	<input type="hidden" name="P_NOTI_URL" value="<?php echo $noti_url; ?>">
	<input type="hidden" name="P_CANCEL_URL" value="<?php echo $cancel_url; ?>">
	
    <input type="hidden" name="P_NOTI" value="<?php echo $notification; ?>">
    <input type="hidden" name="P_HPP_METHOD" value="<?php echo empty($this->settings['hpp_method']) ? '2' : $this->settings['hpp_method']; ?>">
	<input type="hidden" name="P_APP_BASE" value="">

	<?php
	if( strtolower($this->settings['paymethod']) == 'vbank' ) {
		if( !empty( $this->settings['account_date_limit'] ) ) {
			//가상계좌 입금기한 설정이 된경우 설정값으로 표기
			$add_date = $this->settings['account_date_limit'];
			$date = date('Y-m-d');
			$date = strtotime($date);
			$date = strtotime("+{$add_date} day", $date);
			?>
			<input type="hidden" name="P_VBANK_DT" value="<?php echo date('Ymd', $date); ?>">
			<?php
		} else {
			//가상계좌 입금기한 설정값이 없는 경우 기본값인 3일로 처리
			$date = date('Y-m-d');
			$date = strtotime($date);
			$date = strtotime("+3 day", $date);
			?>
			<input type="hidden" name="P_VBANK_DT" value="<?php echo date('Ymd', $date); ?>">
			<?php
		}
	}
	//모바일 신용카드 할부 설정
	if( strtolower($this->settings['paymethod']) == 'wcard' ) {
		if( !empty( $this->settings['quotabase'] ) ) {
			$quotabase_arr = explode(',', $this->settings['quotabase']);
			$quotabase_option = array();

			$quotabase_option[] = '01'; //기본으로 일시불 추가
			foreach($quotabase_arr as $item) {
				$quotabase_option[] = sprintf('%02d', (int)$item);
			}
			sort($quotabase_option);
			$quotabase_option = implode(':', $quotabase_option);
			?>
			<input type="hidden" name="P_QUOTABASE" value="<?php echo $quotabase_option; ?>">
		<?php } else { ?>
			<input type="hidden" name="P_QUOTABASE" value="01">
		<?php }
	}
	?>
	<input type="hidden" name="P_RESERVED" value="<?php echo htmlentities($acceptmethod); ?>">
    <input type="hidden" name="paymethod" size=20 value="<?php echo $this->settings['paymethod']; ?>" />
	<img id="inicis_image_btn" src="<?php echo plugins_url("../assets/images/button_03.gif", __FILE__) ?>" width="63" height="25" style="width:63px;height:25px;border:none;padding:0px;margin:8px 0px;" onclick="javascript:onSubmit();"/>
	<?php echo apply_filters('inicis_payment_form_mobile_template', '', $order); ?>
</form>