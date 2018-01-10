<?php

include_once( 'AdditionalFee.php' );

class AdditionalFees {
	public $values = null;
	public function __construct( $values ) {
		$this->values = $values;
	}

}
