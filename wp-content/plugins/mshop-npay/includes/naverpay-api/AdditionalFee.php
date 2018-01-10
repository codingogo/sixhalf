<?php

class AdditionalFee {
	public $id = '';
	public $surprice = '';
	public function __construct( $id, $surprice ) {
		$this->id       = $id;
		$this->surprice = $surprice;
	}

}
