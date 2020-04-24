<?php

class WayPayEntityAbstract {

	protected $validation = array();

	public function toArray()
	{
		$class = new StdClass();
		$validation =  $this->validation;
		unset($this->validation);
		$array = (array) $this;
		foreach ($array as $key => $value) {
			if(isset($this->validation[$key])){
				if($validation[$key] == 'notEmpty' && empty($value)){
					throw new \Exception('Campo '.$key. 'é obrigatório');
				}
			}
			if(empty($value)) {
				continue;
			}
			$class->$key = $value;
		}
		return (array) $class;
	}
}