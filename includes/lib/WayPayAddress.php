<?php

class WayPayAddress extends WayPayEntityAbstract {

	public $country = 'BRASIL';
	public $state;
	public $city;
	public $street;
	public $number;
	public $complement;
	public $zip;
	public $district;


    /**
     * Gets the value of country.
     *
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the value of country.
     *
     * @param mixed $country the country
     *
     * @return self
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Gets the value of state.
     *
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the value of state.
     *
     * @param mixed $state the state
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Gets the value of city.
     *
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Sets the value of city.
     *
     * @param mixed $city the city
     *
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Gets the value of street.
     *
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Sets the value of street.
     *
     * @param mixed $street the street
     *
     * @return self
     */
    public function setStreet($street)
    {
        $this->street = $street;

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
     * Gets the value of complement.
     *
     * @return mixed
     */
    public function getComplement()
    {
        return $this->complement;
    }

    /**
     * Sets the value of complement.
     *
     * @param mixed $complement the complement
     *
     * @return self
     */
    public function setComplement($complement)
    {
        $this->complement = $complement;

        return $this;
    }

    /**
     * Gets the value of zip.
     *
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Sets the value of zip.
     *
     * @param mixed $zip the zip
     *
     * @return self
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Gets the value of district.
     *
     * @return mixed
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Sets the value of district.
     *
     * @param mixed $district the district
     *
     * @return self
     */
    public function setDistrict($district)
    {
        $this->district = $district;

        return $this;
    }
}