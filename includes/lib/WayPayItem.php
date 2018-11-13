<?php 

class WayPayItem extends WayPayEntityAbstract{

	public $id;
	public $description;
	public $unitValue = 0;
	public $quantity = 1;

	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	public function getid()
	{
		return $this->id;
	}

	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setUnitValue($value)
	{
		$this->unitValue = $value;
		return $this;
	}

	public function getUnitValue()
	{
		return $this->unitValue;
	}

	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

}