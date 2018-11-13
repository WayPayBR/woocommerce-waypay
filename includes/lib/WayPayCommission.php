<?php 

class WayPayCommission extends WayPayEntityAbstract {

	public $reason = 'Pagamento de comissão via sistema';
	public $cpfcnpj;
	public $value = 0;

    protected $validation = array(
        'cpfcnpj' => 'notEmpty',
        'value' => 'notEmpty'
    );

    /**
     * Gets the value of reason.
     *
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Sets the value of reason.
     *
     * @param mixed $reason the reason
     *
     * @return self
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Gets the value of cpfcnpj.
     *
     * @return mixed
     */
    public function getCpfcnpj()
    {
        return $this->cpfcnpj;
    }

    /**
     * Sets the value of cpfcnpj.
     *
     * @param mixed $cpfcnpj the cpfcnpj
     *
     * @return self
     */
    public function setCpfcnpj($cpfcnpj)
    {

        $this->cpfcnpj = preg_replace( '/[^0-9]/', '', $cpfcnpj);

        return $this;
    }

    /**
     * Gets the value of value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value of value.
     *
     * @param mixed $value the value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}