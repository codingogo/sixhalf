<?php

class Supplement {
	public $id = null;

	public $name = null;

	public $price = null;

	public $stockQuantity = null;

	public function __construct( $id, $name, $price, $stockQuantity ) {
		$this->id            = $id;
		$this->name          = $name;
		$this->price         = $price;
		$this->stockQuantity = $stockQuantity;
	}

}

