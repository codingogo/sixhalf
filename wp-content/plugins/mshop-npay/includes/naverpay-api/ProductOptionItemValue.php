<?php

class ProductOptionItemValue
{
	public $id = null;
	public $text = '';
	public function __construct( $id, $text )
	{
		$this->id = $id;
		$this->text = $text;
	}

}
