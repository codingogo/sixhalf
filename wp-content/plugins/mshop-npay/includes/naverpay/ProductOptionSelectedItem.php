<?php

include_once('ProductOptionSelectedItemValue.php');

class ProductOptionSelectedItem
{
	const TYPE_SELECT = 'SELECT';
	const TYPE_INPUT = 'INPUT';
	public $type = '';
	public $name = '';
	public $value = '';
	public function __construct( $type, $name, $id, $text )
	{
		$this->type = $type;
		$this->name = $name;
		$this->value = new ProductOptionSelectedItemValue( $id, $text );
	}

}
