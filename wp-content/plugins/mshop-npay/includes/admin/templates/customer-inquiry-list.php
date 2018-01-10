<?php
$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
wp_enqueue_script( 'jquery' );
wp_enqueue_script( 'jquery-ui' );
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array (), $jquery_version );
wp_enqueue_style( 'naverpay-admin', MNP()->plugin_url() . '/assets/css/naverpay-admin.css' );

wp_enqueue_script( 'jquery-block-ui', MNP()->plugin_url() . '/assets/js/jquery.blockUI.js' );
wp_enqueue_script( 'naverpay-customer-inquiry', MNP()->plugin_url() . '/assets/js/customer-inquiry.js' );
wp_localize_script( 'naverpay-customer-inquiry', '_mnp', array (
	'ajax_url'    => admin_url( 'admin-ajax.php', 'relative' ),
	'slug'        => MNP()->slug(),
	'search_date' => ! empty( $_REQUEST['search_date'] ) ? $_REQUEST['search_date'] : date( 'Y-m-d' )
) );


$list_table = new MNP_Customer_Inquiry_List_Table();

$list_table->prepare_items();
?>
<div class="wrap mshop-customer-inquiry-list">
	<h1 class="wp-heading-inline"><?php _e( 'NPay 고객문의', 'mshop-npay' ); ?></h1>
	<form method="POST">
		<?php $list_table->display() ?>
	</form>
</div>
