<?php

include_once( 'ProductOptionItem.php' );
include_once( 'ProductCombination.php' );

class ProductOption
{
	public $optionItem = null;
	public $combination = null;
	public function __construct( $optionItem, $combination )
	{
		$this->optionItem = $optionItem;
		$this->combination = $combination;
	}

}
