<?php

class ProductCombinationOptions
{
	public $id = null;
	public $name = '';
	public function __construct( $id, $name )
	{
		$this->id = $id;
		$this->name = $name;
	}

}
