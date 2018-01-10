<?php

include_once('Product.php');
include_once('Interface.php');

class Order
{
	public $merchantId;
	public $certiKey;
	public $product = null;
	public $backUrl = null;
	public $interface = null;
	public function __construct($product, $backUrl, $custom_data )
	{
		$this->merchantId = MNP_Manager::merchant_id();
		$this->certiKey = MNP_Manager::auth_key();
		$this->product = $product;
		$this->backUrl = $backUrl;
		$this->interface = new OrderInterface( $custom_data );
	}

}
