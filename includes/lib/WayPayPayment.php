<?php

class WayPayPayment extends WayPayEntityAbstract{

	public $totalAmount;
	public $paymentMethodCode;
	public $cardData;

	const VISA = 1;
	const MASTER = 2;
	const AMEX = 3;
	const AURA = 4;
	const DINERS = 5;
	const ELO = 6;
	const SALDO = 7;
	const BOLETO = 8;

    /**
     * Gets the value of totalAmount.
     *
     * @return mixed
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Gets the value of paymentMethodCode.
     *
     * @return mixed
     */
    public function getPaymentMethodCode()
    {
        return $this->paymentMethodCode;
    }

    /**
     * Gets the value of cardData.
     *
     * @return mixed
     */
    public function getCardData()
    {
        return $this->cardData;
    }

    /**
     * Sets the value of totalAmount.
     *
     * @param mixed $totalAmount the total amount
     *
     * @return self
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Sets the value of paymentMethodCode.
     *
     * @param mixed $paymentMethodCode the payment method code
     *
     * @return self
     */
    public function setPaymentMethodCode($paymentMethodCode)
    {
        $this->paymentMethodCode = $paymentMethodCode;

        return $this;
    }

    /**
     * Sets the value of cardData.
     *
     * @param mixed $cardData the card data
     *
     * @return self
     */
    public function setCardData( WayPayCard $cardData)
    {
        $this->cardData = $cardData;

        return $this;
    }
}