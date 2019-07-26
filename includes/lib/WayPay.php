<?php

include_once 'WayPayEntityAbstract.php';
include_once 'WayPayAddress.php';
include_once 'WayPayBuyer.php';
include_once 'WayPayCard.php';
include_once 'WayPayCommission.php';
include_once 'WayPayItem.php';
include_once 'WayPayPayment.php';
include_once 'WayPayService.php';
include_once 'WayPayShipping.php';

class WayPay {

	public $authorization;
	public $reference = '';
	public $positionX;
	public $positionY;
	public $fingerPrint;
	public $notificationURL;
	public $buyer;
	public $shipping;
	public $payment;
	public $items = [];
	public $commissions = [];
	public $accountPassword = [];

    const STATUS_PENDENT = 1;
    const STATUS_SEND_BANK = 2;
    const STATUS_APPROVED = 3;
    const STATUS_CLOSED = 4;
    const STATUS_CANCELED = 5;
    const STATUS_REFUNDED = 6;
    const STATUS_CHARGE_BACK_ANALISYS = 7;
    const STATUS_CHARGE_PAYED = 8;
    const STATUS_CHARGE_DEBITED = 9;
    const STATUS_DISPUTED = 10;

    /**
     * Gets the value of authorization.
     *
     * @return mixed
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * Gets the value of reference.
     *
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Gets the value of positionX.
     *
     * @return mixed
     */
    public function getPositionX()
    {
        return $this->positionX;
    }

    /**
     * Gets the value of positionY.
     *
     * @return mixed
     */
    public function getPositionY()
    {
        return $this->positionY;
    }

    /**
     * Gets the value of fingerPrint.
     *
     * @return mixed
     */
    public function getFingerPrint()
    {
        return $this->fingerPrint;
    }

    /**
     * Gets the value of buyer.
     *
     * @return mixed
     */
    public function getBuyer()
    {
        return $this->buyer;
    }

    /**
     * Gets the value of shipping.
     *
     * @return mixed
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * Gets the value of items.
     *
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Gets the value of payment.
     *
     * @return mixed
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Gets the value of commissions.
     *
     * @return mixed
     */
    public function getCommissions()
    {
        return $this->commissions;
    }

    public function addItem(WayPayItem $item)
    {
    	array_push($this->items, $item);
    	return $this;
    }

    public function addCommission(WayPayCommission $commission)
    {
    	array_push($this->commissions, $commission);
    	return $this;
    }

    /**
     * Sets the value of authorization.
     *
     * @param mixed $authorization the authorization
     *
     * @return self
     */
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;

        return $this;
    }

    /**
     * Sets the value of reference.
     *
     * @param mixed $reference the reference
     *
     * @return self
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Sets the value of positionX.
     *
     * @param mixed $positionX the position
     *
     * @return self
     */
    public function setPositionX($positionX)
    {
        $this->positionX = $positionX;
        return $this;
    }

    /**
     * Sets the value of positionY.
     *
     * @param mixed $positionY the position
     *
     * @return self
     */
    public function setPositionY($positionY)
    {
        $this->positionY = $positionY;
        return $this;
    }

    /**
     * Sets the value of fingerPrint.
     *
     * @param mixed $fingerPrint the finger print
     *
     * @return self
     */
    public function setFingerPrint($fingerPrint)
    {
        $this->fingerPrint = $fingerPrint;
        return $this;
    }

    /**
     * Sets the value of buyer.
     *
     * @param mixed $buyer the buyer
     *
     * @return self
     */
    public function setBuyer($buyer)
    {
        $this->buyer = $buyer;
        return $this;
    }

    /**
     * Sets the value of shipping.
     *
     * @param mixed $shipping the shipping
     *
     * @return self
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
        return $this;
    }

    /**
     * Sets the value of payment.
     *
     * @param mixed $payment the payment
     *
     * @return self
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * Sets the value of items.
     *
     * @param mixed $items the items
     *
     * @return self
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Sets the value of commissions.
     *
     * @param mixed $commissions the commissions
     *
     * @return self
     */
    public function setCommissions($commissions)
    {
        $this->commissions = $commissions;
        return $this;
    }


    /**
     * Sets the value of reference.
     *
     * @param mixed $reference the reference
     *
     * @return self
     */
    public function setAccountPassword($accountPassword)
    {
        $this->accountPassword = $accountPassword;
        return $this;
    }

    public function getAccountPassword()
    {
        return $this->accountPassword;
    }


    public function setNotificationURL($url)
    {
    	$this->notificationURL = $url;
    	return $this;
    }

    public function getNotificationURL()
    {
    	return $this->notificationURL;
    }

    public function getRequestData() {
        $buyer = empty($this->getBuyer()) ? [] : $this->getBuyer();
        $shipping = empty($this->getShipping()) ? [] : $this->getShipping();
        $payment = empty($this->getPayment()) ? [] : $this->getPayment();
        $commissions = array();
        foreach ($this->getCommissions() as $commission) {
            $commissions[] = $commission->toArray();
        }
        $data = array(
            'authorization' => $this->getAuthorization(),
            'checkout' => array(
                'reference' => $this->getReference(),
                'buyer' => $buyer,
                'items' => $this->getItems(),
                'shipping' => $shipping,
                'payment'  => $payment,
                'notificationURL' => $this->getNotificationURL()
            ),
        );
        if($commissions){
            $data['checkout']['commissions'] = $commissions;
        }
        if($this->getAccountPassword()){
            $data['checkout']['buyer']['account_password'] = $this->getAccountPassword();
        }
        return $data;
    }

}