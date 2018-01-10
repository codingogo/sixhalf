<?php


class Supplement {
	public $id = null;

	public $name = null;

	public $price = null;

	public $quantity = null;

	public function __construct( $id, $name, $price, $quantity ) {
		$this->id       = $id;
		$this->name     = $name;
		$this->price    = $price;
		$this->quantity = $quantity;
	}

}