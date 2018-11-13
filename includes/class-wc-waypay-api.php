<?php
if (!defined('ABSPATH')) {
    exit;
}

abstract class WC_WayPay_API
{

    const PERSON_INDIVIDUAL = 1;
    const PERSON_JURIDIC = 2;
    const PAYMENT_METHOD_TICKET = 8;
    const PAYMENT_METHOD_TYPE_TICKET = 'ticket';

    /** @var WC_Logger */
    public $log = false;

    /** @var WC_WayPay_CC_Gateway|WC_WayPay_Ticket_Gateway */
    public $gateway = null;

    /**
     * WC_WayPay_API constructor.
     * @param null $gateway
     */
    public function __construct($gateway = null)
    {
        $this->gateway = $gateway;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function clean_values($value)
    {
        return str_replace(array('%', ','), array('', '.'), $value);
    }

    /**
     * @param $number
     * @return null|string|string[]
     */
    public function clean_number($number)
    {
        return preg_replace('/\D/', '', $number);
    }

    /**
     * @param $document
     * @return bool
     */
    protected function check_document($document)
    {
        $invalids = array(
            '00000000000',
            '11111111111',
            '22222222222',
            '33333333333',
            '44444444444',
            '55555555555',
            '66666666666',
            '88888888888',
            '99999999999',
        );
        if (empty($document)) {
            return false;
        }
        $document = $this->clean_number($document);
        if (strlen($document) <= 11) {
            $document = str_pad($document, 11, '0', STR_PAD_LEFT);
            if (strlen($document) != 11 || in_array($document, $invalids)) {
                return false;
            } else {
                for ($t = 9; $t < 11; $t++) {
                    for ($d = 0, $c = 0; $c < $t; $c++) {
                        $d += $document{$c} * (($t + 1) - $c);
                    }
                    $d = ((10 * $d) % 11) % 10;
                    if ($document{$c} != $d) {
                        return false;
                    }
                }
            }
            return true;
        } else if (strlen($document) > 14) {
            return false;
        }
        return true;
    }

    /**
     * @param $reference
     * @param $status
     * @param $invoice_prefix
     * @return bool
     */
    public function set_order_status($reference, $status, $invoice_prefix)
    {
        $valid = false;
        $order_id = intval(str_replace($invoice_prefix, '', $reference));
        $order = wc_get_order($order_id);
        if ($order) {
            if ($order->get_id() === $order_id) {
                $order_status = sanitize_text_field($status);
                switch ($order_status) {
                    case WayPay::STATUS_APPROVED : // Aprovada
                        $order->payment_complete();
                        $valid = true;
                        break;
                    case WayPay::STATUS_CANCELED : // Negada
                        $order->update_status('failed', __('WayPay: Payment Denied.', 'woocommerce-waypay'));
                        $valid = true;
                        break;
                    case WayPay::STATUS_REFUNDED : // Reembolsado
                        $order->update_status('refunded', __('WayPay: Payment Refunded.', 'woocommerce-waypay'));
                        $valid = true;
                        break;
                }
            }
        }
        return $valid;
    }

    /**
     * @param WC_Order $order
     * @return WayPayBuyer
     */
    public function get_buyer_data(WC_Order $order) {
        $buyer = new WayPayBuyer();
        $buyer->setName($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $buyer->setEmail($order->get_billing_email());
        if($order->get_meta('_billing_cpf')){
            $buyer->setCpfcnpj($this->clean_number($order->get_meta('_billing_cpf')));
        }
        if($order->get_meta('_billing_cnpj')){
            $buyer->setCpfcnpj($this->clean_number($order->get_meta('_billing_cnpj')));
        }
        if(!empty($order->get_billing_phone())){
            $phone = $this->clean_number($order->get_billing_phone());
            $buyer->setPhone(substr(trim($phone), 0, 2), substr($phone, 2));
        }
        if(!empty($order->get_meta('_billing_cellphone'))){
            $cellphone = $this->clean_number($order->get_meta('_billing_cellphone'));
            $buyer->setCellPhone(substr(trim($cellphone), 0, 2), substr($cellphone, 2));
        }
        if($order->get_meta('_billing_persontype') == self::PERSON_INDIVIDUAL && empty($cellphone)) {
            $buyer->setCellPhone(substr(trim($phone), 0, 2), substr($phone, 2));
        }
        return $buyer;
    }

    /**
     * @param WC_Order $order
     * @param WayPay $waypay
     * @return bool
     */
    public function set_order_items_data(WC_Order $order,WayPay $waypay) {
        $items = $order->get_items();
        foreach ($items as $key => $p) {
            $product = wc_get_product($p['product_id']);
            $item = new WayPayItem();
            $item->setId($product->get_id());
            $item->setDescription($p['name']);
            $item->setUnitValue($product->get_price_including_tax());
            $item->setQuantity($p['qty']);
            $waypay->addItem($item);
        }
        return true;
    }

    /**
     * @param WC_Order $order
     * @return WayPayAddress
     */
    public function get_address_data(WC_Order $order) {
        $address = new WayPayAddress();
        $address->setState($order->get_shipping_state());
        $address->setCity($order->get_shipping_city());
        $address->setStreet($order->get_shipping_address_1());
        $address->setNumber($order->get_meta('_shipping_number'));
        $address->setZip($this->clean_number($order->get_shipping_postcode()));
        $address->setDistrict($order->get_meta('_shipping_neighborhood'));
        $address->setCountry('BRASIL');
        return $address;
    }

    /**
     * @param WayPayAddress $address
     * @return WayPayShipping
     */
    public function get_shipping_data(WC_Order $order,WayPayAddress $address) {
        $shipping = new WayPayShipping();
        $shipping->setType(substr($order->get_shipping_method(),0,20));
        $shipping->setMethod(substr($order->get_shipping_method(),0,20));
        $shipping->setCost($order->get_shipping_total());
        $shipping->setAddress($address);
        return $shipping;
    }

    /**
     * @param WC_Order $order
     * @param $post
     * @return WayPayPayment
     */
    public function get_payment_data(WC_Order $order,$post,$isCard=false) {
        $expiry = sanitize_text_field($post['waypay_card_expiry']);
        $cc_number = sanitize_text_field($this->clean_number($post['waypay_card_number']));
        $cc_installments = sanitize_text_field($this->clean_number($post['waypay_card_installments']));
        $cc_holder_name = sanitize_text_field($post['waypay_holder_name']);
        $cc_cvc = sanitize_text_field($post['waypay_card_cvc']);
        $payment = new WayPayPayment();
        $payment->setPaymentMethodCode($post['waypay_payment_id']);
        $payment->setTotalAmount($order->get_total());
        if($isCard){
            $cardExp = explode('/', $expiry);
            $card = new WayPayCard();
            $card->setName($cc_holder_name);
            $card->setNumber($cc_number);
            $card->setExpMonth(trim($cardExp[0]));
            $card->setExpYear(substr(trim($cardExp[1]), 2));
            $card->setCid($cc_cvc);
            $card->setInstallments($cc_installments);
            $payment->setCardData($card);
        } else {
            $payment->setPaymentMethodCode(WayPayPayment::BOLETO);
        }
        return $payment;
    }

    public function update_post_meta($orderId, $result)
    {
        foreach ($result as $key => $value) {
            update_post_meta($orderId, $key, $value);
        }
    }

    public function instantiate_logger() {
        if ('yes' == $this->gateway->save_log && class_exists('WC_Logger')) {
            $this->log = new WC_Logger();
        }
    }

    public function ipn_handler() {
        @ob_clean();
        $data = file_get_contents( 'php://input' );
        $ipn = $this->process_ipn($data);
        if ($ipn) {
            status_header(200);
            exit();
        } else {
            wp_die( esc_html__( 'WayPay Request Unauthorized', 'woocommerce-waypay' ), esc_html__( 'WayPay Request Unauthorized', 'woocommerce-waypay' ), array( 'response' => 401 ) );
        }
    }

    private function process_ipn($data) {
        $this->instantiate_logger();
        if($data_decoded = json_decode($data,true)) {
            if ($this->log) {
                $this->log->add('waypay_api', '----- IPN -----');
                $this->log->add('waypay_api', $data);
            }
            if(isset($data_decoded['transactionStatus']['reference'])) {
                $order_id = $this->clean_number($data_decoded['transactionStatus']['reference']);
                $order = wc_get_order($order_id);
                if($order->get_id()) {
                    $this->set_order_status(
                        $data_decoded['transactionStatus']['reference'],
                        $data_decoded['transactionStatus']['status'],
                        $this->gateway->invoice_prefix
                    );
                    return true;
                }
            }
        }
        return false;
    }

}