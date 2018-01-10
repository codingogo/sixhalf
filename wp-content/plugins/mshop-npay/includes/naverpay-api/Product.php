<?php

include_once('ProductSingle.php');
include_once('ProductOption.php');
include_once('Supplement.php');

class Product
{
	public $id = null;
	public $merchantProductId = null;
//	public $ecMallProductId = null;
	public $name = null;
	public $basePrice = null;
	public $taxType = null;
	public $infoUrl = null;
	public $imageUrl = null;
	public $giftName = null;
	public $optionSupport = null;
	public $option = null;
	public $shippingPolicy = null;
	public function __construct($id, $merchantProductId, $ecMallProductId,  $name, $basePrice, $taxType, $infoUrl, $imageUrl, $giftName, $stockQuantity, $status, $optionSupport, $option, $shippingPolicy, $supplements = array() )
	{
		$this->id = $id;
		$this->merchantProductId = $merchantProductId;
		$this->ecMallProductId = $ecMallProductId;
		$this->name = $name;
		$this->basePrice = $basePrice;
		$this->taxType = $taxType;
		$this->infoUrl = $infoUrl;
		$this->imageUrl = $imageUrl;

		if( $giftName ) {
			$this->giftName = $giftName;
		}else{
			unset( $this->giftName );
		}

		if( null == $stockQuantity ){
			unset( $this->stockQuantity);
		}else{
			$this->stockQuantity = $stockQuantity;
		}

		$this->status = $status;
		$this->optionSupport = $optionSupport;
		if( !empty( $option) ){
			$this->option = $option;
		}else{
			unset( $this->option );
		}
		$this->shippingPolicy = $shippingPolicy;
		if( ! empty( $supplements ) ) {
			$this->supplement = $supplements;
		}
	}
}
