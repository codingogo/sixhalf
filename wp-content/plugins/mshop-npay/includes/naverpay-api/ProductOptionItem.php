<?php

include_once( 'ProductOptionItemValue.php' );

class ProductOptionItem
{
	const TYPE_SELECT = 'SELECT';
	const TYPE_INPUT = 'INPUT';
	public $type = TYPE_SELECT;
	public $name = '';
	public $value = null;
	public function __construct( $type, $name, $value )
	{
		$this->type = $type;
		$this->name = $name;
		$this->value = $value;
	}

}
