<?php
if ( ! empty( $_POST['submit'] ) ) {
	update_option( 'mshop-naverpay-except-category', json_encode( $_POST['tax_input']['product_cat'] ) );
}

wp_enqueue_style( 'naverpay-admin', MNP()->plugin_url() . '/assets/css/naverpay-admin.css' );

$selected_cats = json_decode( get_option( 'mshop-naverpay-except-category' ) );
$args          = array (
	'descendants_and_self' => 0,
	'selected_cats'        => $selected_cats,
	'popular_cats'         => false,
	'walker'               => null,
	'taxonomy'             => 'product_cat',
	'checked_ontop'        => true
);
?>

<div class="wrap">
	<h1 class="wp-heading-inline">NPay 결제 버튼을 비노출 할 카테고리를 지정할 수 있습니다. (예시. 개인결제창, 해외배송상품 등)</h1>
	<div class="naverpay-category-setting">
		<form method="post">
			<?php
			wp_terms_checklist( 0, $args );
			?>
			<input type="submit" name="submit" class="button button-primary" value="변경 사항 저장">
		</form>
	</div>
</div>
