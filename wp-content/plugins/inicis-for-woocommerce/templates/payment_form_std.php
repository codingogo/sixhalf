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
global $inicis_payment;
if (defined('ICL_LANGUAGE_CODE')) {
    $lang_code = ICL_LANGUAGE_CODE;

    if ($use_ssl == 'yes') {
        $request_url = untrailingslashit(WC()->api_request_url(get_class($this) . '?type=std&lang=' . $lang_code, true));
        $request_close_url = untrailingslashit(WC()->api_request_url(get_class($this) . '?type=std_cancel&lang=' . $lang_code, true));
        $request_popup_url = untrailingslashit(WC()->api_request_url(get_class($this) . '?type=std_popup&lang=' . $lang_code, true));
    } else {
        $request_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std&lang=' . $lang_code, false));
        $request_close_url = untrailingslashit(WC()->api_request_url(get_class($this) . '?type=std_cancel&lang=' . $lang_code, false));
        $request_popup_url = untrailingslashit(WC()->api_request_url(get_class($this) . '?type=std_popup&lang=' . $lang_code, false));
    }
} else {
    if ($use_ssl == 'yes') {
        $request_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std', true));
        $request_close_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std_cancel', true));
        $request_popup_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std_popup', true));
    } else {
        $request_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std', false));
        $request_close_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std_cancel', false));
        $request_popup_url = untrailingslashit( WC()->api_request_url(get_class($this) . '?type=std_popup', false));
    }
}
?>
<form id="SendPayForm_id" name="" method="POST">
    <!-- 필수사항 -->
    <input style="width:100%;" name="version" value="1.0">
    <?php
    if( $this->id == 'inicis_stdescrow_bank') { ?>
        <input style="width:100%;" name="mid" value="<?php echo $this->settings['escrow_merchant_id']; ?>">
    <?php } else { ?>
        <input style="width:100%;" name="mid" value="<?php echo $this->settings['merchant_id']; ?>">
    <?php } ?>
    <input style="width:100%;" name="goodsname" value="<?php echo esc_attr($productinfo); ?>">
    <input style="width:100%;" name="oid" value="<?php echo $txnid; ?>">
    <input style="width:100%;" name="price" value="<?php echo $this->inicis_get_order_total($order); ?>">
    <input style="width:100%;" name="currency" value="WON">
    <input style="width:100%;" name="buyername" value="<?php echo ifw_get($order, 'billing_last_name') . ifw_get($order, 'billing_first_name'); ?>">
    <input style="width:100%;" name="buyertel" value="<?php echo preg_replace("/[^0-9]*/s", "", ifw_get($order, 'billing_phone') ); ?>">
    <input style="width:100%;" name="buyeremail" value="<?php echo ifw_get($order, 'billing_email'); ?>">
    <input type="text" style="width:100%;" name="timestamp" value="<?php echo $timestamp; ?>">
    <input type="hidden" style="width:100%;" name="signature" value="<?php echo $sign ?>">
    <input type="hidden" name="mKey" value="<?php echo $mKey; ?>">
    <input style="width:100%;" name="gopaymethod" value="<?php echo $this->settings['gopaymethod']; ?>">
    <input style="width:100%;" name="acceptmethod" value="<?php echo $acceptmethod; ?>">
    <input style="width:100%;" name="returnUrl" value="<?php echo $request_url; ?>">
    <input style="width:100%;" name="nointerest" value="<?php echo $cardNoInterestQuota; ?>">
    <input style="width:100%;" name="quotabase" value="<?php echo $cardQuotaBase; ?>">
    <input style="width:100%;" name="merchantData" value="<?php echo $notification; ?>">
    <!-- 선택사항 -->
    <input style="width:100%;" name="offerPeriod" value="">
    <input style="width:100%;" name="languageView" value="ko">
    <input style="width:100%;" name="charset" value="UTF-8">
    <input style="width:100%;" name="payViewType" value="<?php echo $payView_type; ?>">
    <input style="width:100%;" name="closeUrl" value="<?php echo $request_close_url; ?>">
    <input style="width:100%;" name="popupUrl" value="<?php echo $request_popup_url; ?>">
    <input style="width:100%;" name="vbankRegNo" value="">
    <input style="width:100%;" name="logo_url" value="<?php echo get_option('inicis_pg_site_logo'); ?>">
    <?php echo apply_filters('inicis_payment_form_std_template', '', $order); ?>
</form>