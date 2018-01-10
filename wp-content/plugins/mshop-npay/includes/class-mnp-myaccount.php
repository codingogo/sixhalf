<?php

if ( ! class_exists( 'MNP_Myaccount' ) ) {

	class MNP_Myaccount {
		public static function woocommerce_order_item_meta_end( $item_id, $item, $order ){
			$output = array();
			$sheet_infos = MNP_Sheets::get_sheet_data( $item_id, $item, $order );

			foreach( $sheet_infos as $label => $value ) {
				$output[] = sprintf('<span>%s : %s</span>', $label, $value);
			}

			echo '<br><small>' . implode( '<br>', $output ) . '</small>';;
		}
	}
}

