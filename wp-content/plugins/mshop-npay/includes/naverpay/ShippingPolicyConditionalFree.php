<?php

class ShippingPolicyConditionalFree{
	public $basePrice = 0;
	public function __construct($basePrice)
	{
		$this->basePrice = $basePrice;
	}

}
