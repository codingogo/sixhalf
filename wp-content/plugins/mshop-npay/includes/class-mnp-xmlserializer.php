<?php



if ( ! class_exists( 'MNP_XMLSerializer' ) ) {

	class MNP_XMLSerializer {

		public static function generateValidXmlFromObj(stdClass $obj, $node_block='nodes', $node_name='node') {
			$arr = get_object_vars($obj);
			return self::generateValidXmlFromArray($arr, $node_block, $node_name);
		}

		public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
			$xml = '';

			$xml .= '<' . $node_block . '>';
			$xml .= self::generateXmlFromArray($array, $node_name);
			$xml .= '</' . $node_block . '>';

			return $xml;
		}

		private static function generateXmlFromArray($array, $node_name) {
			$xml = '';

			if (is_array($array) || is_object($array)) {
				foreach ($array as $key=>$value) {
					if (is_numeric($key)) {
						$key = $node_name;
					}

					if( 'product' == $key ){
						$xml .= self::generateProduct($value);
					}else if( 'selectedItem' == $key ){
						$xml .= self::generateSelectedItem($value);
					}else if( 'optionItem' == $key ){
						$xml .= self::generateOptionItem($value, $node_name);
					}else if( 'supplement' == $key ){
						$xml .= self::generateSupplement($value, $node_name);
					}else if( 'combination' == $key ){
						$xml .= self::generateCombination($value, $node_name);
					}else if( 'ProductOptionItemValue' == $node_name && 'value' == $key ){
						$xml .= self::generateOptionItemValue($value, $node_name);
					}else if( 'ProductCombination' == $node_name && 'options' == $key ){
						$xml .= self::generateCombinationOptionItemValue($value, $node_name);
					}else{
						$xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
					}
				}
			} else {
				$xml = htmlspecialchars($array, ENT_QUOTES);
			}

			return $xml;
		}

		private static function generateProduct($array) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<product>' . self::generateXmlFromArray( $value, 'option' ) . '</product>';
				}
			}

			return $xml;
		}

		private static function generateSelectedItem($array) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<selectedItem>' . self::generateXmlFromArray( $value, 'option' ) . '</selectedItem>';
				}
			}

			return $xml;
		}

		private static function generateOptionItem($array, $node_name) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<optionItem>' . self::generateXmlFromArray( $value, 'ProductOptionItemValue' ) . '</optionItem>';
				}
			}

			return $xml;
		}

		private static function generateSupplement($array, $node_name) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<supplement>' . self::generateXmlFromArray( $value, '' ) . '</supplement>';
				}
			}

			return $xml;
		}

		private static function generateOptionItemValue($array, $node_name) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<value>' . self::generateXmlFromArray( $value, 'value' ) . '</value>';
				}
			}

			return $xml;
		}

		private static function generateCombinationOptionItemValue($array, $node_name) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<options>' . self::generateXmlFromArray( $value, 'value' ) . '</options>';
				}
			}

			return $xml;
		}

		private static function generateCombination($array, $node_name) {
			$xml = '';

			if( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {
					$xml .= '<combination>' . self::generateXmlFromArray( $value, 'ProductCombination' ) . '</combination>';
				}
			}

			return $xml;
		}
	}
}

