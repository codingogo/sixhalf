<?php

include_once('Product.php');

class Order
{
	public $merchantId;
	public $certiKey;
	public $product = null;
	public $backUrl = null;
	public $interface = null;
	public function __construct($product, $backUrl, $interface)
	{
		$this->merchantId = MNP_Common::merchant_id();
		$this->certiKey = MNP_Common::auth_key();
		$this->product = $product;
		$this->backUrl = $backUrl;
		$this->interface = $interface;
	}

}
