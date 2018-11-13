<?php

class WayPayCard extends WayPayEntityAbstract {

	public $name;
	public $number;
	public $expMonth;
	public $expYear;
	public $cid;
	public $installments = 1;
	public $save = 0;

    /**
     * Gets the value of name.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the value of name.
     *
     * @param mixed $name the name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the value of number.
     *
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Sets the value of number.
     *
     * @param mixed $number the number
     *
     * @return self
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Gets the value of expMonth.
     *
     * @return mixed
     */
    public function getExpMonth()
    {
        return $this->expMonth;
    }

    /**
     * Sets the value of expMonth.
     *
     * @param mixed $expMonth the exp month
     *
     * @return self
     */
    public function setExpMonth($expMonth)
    {
        $this->expMonth = $expMonth;

        return $this;
    }

    /**
     * Gets the value of expYear.
     *
     * @return mixed
     */
    public function getExpYear()
    {
        return $this->expYear;
    }

    /**
     * Sets the value of expYear.
     *
     * @param mixed $expYear the exp year
     *
     * @return self
     */
    public function setExpYear($expYear)
    {
        $this->expYear = $expYear;

        return $this;
    }

    /**
     * Gets the value of cid.
     *
     * @return mixed
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Sets the value of cid.
     *
     * @param mixed $cid the cid
     *
     * @return self
     */
    public function setCid($cid)
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * Gets the value of installments.
     *
     * @return mixed
     */
    public function getInstallments()
    {
        return $this->installments;
    }

    /**
     * Sets the value of installments.
     *
     * @param mixed $installments the installments
     *
     * @return self
     */
    public function setInstallments($installments = 1)
    {
        $this->installments = $installments;

        return $this;
    }

    /**
     * Gets the value of save.
     *
     * @return mixed
     */
    public function getSave()
    {
        return $this->save;
    }

    /**
     * Sets the value of save.
     *
     * @param mixed $save the save
     *
     * @return self
     */
    public function setSave($save)
    {
        $this->save = $save;

        return $this;
    }
}