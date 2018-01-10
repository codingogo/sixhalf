<?php

$id = $inside_column = '';

extract(shortcode_atts(array(
	'id' => '',
	'inside_column' => '',
) , $atts));

$id = apply_filters( 'wpml_object_id', $id, 'post' );
$the_content = get_post_field('post_content', $id);

if ($inside_column === 'yes') {
	$the_content = str_replace('vc_row ', 'vc_row_inner ', $the_content);
	$the_content = str_replace('vc_row]', 'vc_row_inner]', $the_content);
} else {
	$the_content = $the_content;
}

echo uncode_remove_wpautop($the_content);