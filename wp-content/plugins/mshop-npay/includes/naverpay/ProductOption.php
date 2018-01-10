<?php

include_once('ProductOptionSelectedItem.php');

class ProductOption
{
	public $quantity = 1;
//	public $price = 0;
//	public $manageCode = '';
	public $selectedItem = null;
	public function __construct( $quantity, $price, $manageCode, $selectedItem )
	{
		$this->quantity = $quantity;
//		$this->price = $price;
//		$this->manageCode = $manageCode;
		$this->selectedItem = $selectedItem;
	}

}
