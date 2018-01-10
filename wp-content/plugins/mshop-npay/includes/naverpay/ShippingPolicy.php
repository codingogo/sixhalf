<?php

include_once('ShippingPolicyConditionalFree.php');

class ShippingPolicy
{
	const FEE_TYPE_FREE = 'FREE';
	const FEE_TYPE_CHARGE = 'CHARGE';
	const FEE_TYPE_CONDITIONAL_FREE = 'CONDITIONAL_FREE';
	const FEE_TYPE_CHARGE_BY_QUANTITY = 'CHARGE_BY_QUANTITY';

	const FEE_PAY_TYPE_FREE = 'FREE';
	const FEE_PAY_TYPE_PREPAYED = 'PREPAYED';
	const FEE_PAY_TYPE_CASH_ON_DELIVERY = 'CASH_ON_DELIVERY';
	public $feeType = null;
	public $feePayType = null;
	public $feePrice = null;


    public $surchargeByArea = null;
	public function __construct($feeType, $feePayType, $feePrice, $surchargeByArea, $conditionalFreeBaseAmount = null)
	{
		$this->groupId = 1;
		$this->feeType = $feeType;
		$this->feePayType = $feePayType;
		$this->feePrice = $feePrice;
		$this->surchargeByArea = $surchargeByArea;

		if( !empty( $conditionalFreeBaseAmount ) ){
			$this->conditionalFree = new ShippingPolicyConditionalFree( $conditionalFreeBaseAmount );
		}
	}

}
