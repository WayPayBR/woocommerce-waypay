<?php

class WayPayShipping extends WayPayEntityAbstract 
{

	public $type = 'Correios';
	public $method = 'Sedex';
	public $cost = 0;
	public $address;

	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function setCost($cost)
	{
		$this->cost = $cost;
		return $this;
	}

	public function getCost()
	{
		return $this->cost;
	}

	public function setAddress(WayPayAddress $address)
	{
		$this->address = $address;
		return $this;
	}

	public function getAddress()
	{
		return $this->address;
	}
}