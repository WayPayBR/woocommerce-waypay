<?php

class WayPayBuyer extends WayPayEntityAbstract {

	public $name;
	public $email;
	public $phone;
	public $cellphone;
	public $cpfcnpj;
	public $birthday;
	public $accountPassword;

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setPhone($areaCode, $number)
	{
		$this->phone = array(
			'areaCode' => $areaCode,
			'number' => $number
			);

		return $this;
	}

	public function getPhone()
	{
		return $this->phone;
	}

	public function setCellPhone($areaCode, $number)
	{
		$this->cellphone = array(
			'areaCode' => $areaCode,
			'number' => $number
		);

		return $this;
	}

	public function getCellPhone()
	{
		return $this->cellphone;
	}


	public function setCpfcnpj($cpfcnpj)
	{
		$this->cpfcnpj = $cpfcnpj;
		return $this;
	}

	public function getCpfcnpj()
	{
		return $this->cpfcnpj;
	}

	public function getBirthday()
	{
		return $this->birthday;
	}

	public function setBirthday($date)
	{
		$this->birthday = $date;
		return $this;
	}

    public function getAccountPassword()
    {
        return $this->accountPassword;
    }

    public function setAccountPassword($accountPassword)
    {
        $this->accountPassword = $accountPassword;
        return $this;
    }

}