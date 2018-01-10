<?php
add_action('after_setup_theme', 'uncode_language_setup');
function uncode_language_setup()
{
	load_child_theme_textdomain('uncode', get_stylesheet_directory() . '/languages');
}

function theme_enqueue_styles()
{
	$production_mode = ot_get_option('_uncode_production');
	$resources_version = ($production_mode === 'on') ? null : rand();
	$parent_style = 'uncode-style';
	$child_style = array('uncode-custom-style');
	wp_enqueue_style($parent_style, get_template_directory_uri() . '/library/css/style.css', array(), $resources_version);
	wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', $child_style, $resources_version);
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');

// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
add_filter( 'woocommerce_default_address_fields' , 'custom_remove_unwanted_form_fields' );
add_filter('woocommerce_save_account_details_required_fields', 'woocommerce_save_account_details_required_fields', 10, 1);
add_filter('woocommerce_billing_fields', 'custom_woocommerce_billing_fields');
add_filter('woocommerce_shipping_fields', 'custom_woocommerce_shipping_fields');

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
	unset($fields['billing']['billing_last_name']);	
	unset($fields['billing']['billing_company']);
	unset($fields['billing']['billing_city']);
	unset($fields['billing']['billing_country']);
	unset($fields['billing']['billing_state']);

	return $fields;
}

function custom_remove_unwanted_form_fields($fields) {
	unset($fields['last_name']);
	unset($fields['company']);
	unset($fields['country']);
	unset($fields['shipping_city']);
	unset($fields['billing_city']);
	
	return $fields;
}

function woocommerce_save_account_details_required_fields($fields = array()) {
    unset($fields['account_last_name']);
	unset($fields['shipping_city']);
	unset($fields['billing_city']);

    return $fields;
}

function custom_woocommerce_billing_fields( $fields ) {

    $fields['billing_first_name']['class'] = array( 'form-row-wide' );
	unset($fields['billing_city']);
	
    return $fields;
}

function custom_woocommerce_shipping_fields( $fields ) {

    $fields['shipping_first_name']['class'] = array( 'form-row-wide' );
	
    return $fields;
}

add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
add_filter( 'wc_product_sku_enabled', '__return_false' );

add_filter( 'woocommerce_min_password_strength', 'reduce_woocommerce_min_strength_requirement' );
function reduce_woocommerce_min_strength_requirement( $strength ) {
    return 2;
}

// remove ratings from product page
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);

?>