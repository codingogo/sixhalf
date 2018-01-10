<?php

include_once( 'ProductCombinationOptions.php' );

class ProductCombination
{
	public $manageCode = null;
	public $price = 0;
	public $options = null;
	public function __construct( $manageCode, $price, $options )
	{
		$this->manageCode = $manageCode;
		$this->price = $price;
		$this->options = $options;
	}

}
