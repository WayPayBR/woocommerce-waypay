<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_WayPay_CC_API extends WC_WayPay_API
{

    private $payment_methods = array(
        WayPayPayment::VISA=>'Visa',
        WayPayPayment::MASTER=>'MasterCard',
        WayPayPayment::AMEX=>'American Express',
        WayPayPayment::AURA=>'Aura',
        WayPayPayment::DINERS=>'Diners',
        WayPayPayment::ELO=>'Elo'
    );

    public function get_installments_html($order_total = 0)
    {
        $html = '';
        $installments = $this->gateway->installments;
        $html .= '<select id="waypay-card-installments" name="waypay_card_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">';
        $installment_values = $this->get_installments($order_total);
        for ($i = 1; $i <= $installments; $i++) {
            $total = $order_total / $i;
            $credit_interest = '';
            $min_per_installments = (WC_WayPay_CC_Gateway::MIN_PER_INSTALLMENT <= $this->gateway->min_per_installments)
                ? $this->gateway->min_per_installments : WC_WayPay_CC_Gateway::MIN_PER_INSTALLMENT;
            if ($i >= $this->gateway->max_without_interest && 0 != $this->gateway->max_without_interest) {
                if (!isset($installment_values[$i - 1])) continue;
                $interest_total = $installment_values[$i - 1]['installment_value'];
                $interest_order_total = $installment_values[$i - 1]['total'];
                if ($total < $interest_total) {
                    $total = $interest_total;
                    $credit_interest = sprintf(__('(%s%% per month - Total: %s)', 'woocommerce-waypay'),
                        $this->clean_values($this->gateway->interest_rate), sanitize_text_field(wc_price($interest_order_total)));
                }
            }
            if (1 != $i && $total < $min_per_installments) {
                continue;
            }
            $html .= '<option value="' . $i . '">' . esc_html(sprintf(__('%sx of %s %s', 'woocommerce-waypay'), $i,
                    sanitize_text_field(wc_price($total)), $credit_interest)) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public function get_payment_id_selected($post)
    {
        $payment_id = 1;
        if (isset($post['post_data'])) {
            parse_str($post['post_data'], $output);
            $payment_id = $output['waypay_payment_id'];
        }
        return $payment_id;
    }

    public function get_cards_data()
    {
        $items = array();
        foreach($this->payment_methods as $key => $value) {
            $img = WC_WayPay::get_plugin_url() . '/assets/images/'.$key.'.png';
            $items[] = array(
                'text' => $value,
                'value' => $key,
                'selected' => false,
                'imageSrc' => $img,
                'description' => '&nbsp;'
            );
        }
        return json_encode($items);
    }


    private function validate_installments($posted, $order_total)
    {
        try {
            if (!isset($posted['waypay_card_installments']) && 1 == $this->gateway->installments) {
                return true;
            }
            if (!isset($posted['waypay_card_installments']) || !$posted['waypay_card_installments']) {
                throw new Exception(__('Please select a number of installments.', 'woocommerce-waypay'));
            }
            $installments = intval($posted['waypay_installments']);
            $installment_total = $order_total / $installments;
            $installments_config = $this->gateway->installments;
            if ($installments >= $this->gateway->max_without_interest && 0 != $this->gateway->max_without_interest) {
                $interest_rate = $this->clean_values($this->gateway->interest_rate);
                $interest_total = $this->get_total_by_installments($order_total, $installments, $interest_rate);
                $installment_total = ($installment_total < $interest_total) ? $interest_total : $installment_total;
            }
            $min_per_installments = (WC_WayPay_CC_Gateway::MIN_PER_INSTALLMENT <= $this->gateway->min_per_installments)
                ? $this->gateway->min_per_installments : WC_WayPay_CC_Gateway::MIN_PER_INSTALLMENT;
            if ($installments > $installments_config || 1 != $installments && $installment_total < $min_per_installments) {
                throw new Exception(__('Invalid number of installments.', 'woocommerce-waypay'));
            }
        } catch (Exception $e) {
            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . esc_html($e->getMessage()), 'error');
            return false;
        }
        return true;
    }

    private function get_installment_price($price, $installments, $interest_rate, $type = WC_WayPay_CC_Gateway::INTEREST_RATE_TYPE_SIMPLE)
    {
        $price = (float)$price;
        $value = 0;
        if ($interest_rate) {
            $interest_rate = (float)(str_replace(',', '.', $interest_rate)) / 100;
            switch ($type) {
                case WC_WayPay_CC_Gateway::INTEREST_RATE_TYPE_PRICE:
                    $value = round($price * (($interest_rate * pow((1 + $interest_rate), $installments)) /
                            (pow((1 + $interest_rate), $installments) - 1)), 2);
                    break;
                case WC_WayPay_CC_Gateway::INTEREST_RATE_TYPE_COMPOUND:
                    $value = ($price * pow(1 + $interest_rate, $installments)) / $installments;
                    break;
                case WC_WayPay_CC_Gateway::INTEREST_RATE_TYPE_SIMPLE:
                    $value = ($price * (1 + ($installments * $interest_rate))) / $installments;
            }
        } else {
            if ($installments)
                $value = $price / $installments;
        }
        return $value;
    }

    private function get_total_by_installments($price, $installments, $interest_rate)
    {
        return $this->get_installment_price($price, $installments, $interest_rate,
                $this->gateway->interest_rate_caculate_method) * $installments;
    }

    private function get_installments($price = null)
    {
        $price = (float)$price;
        $max_installments = $this->gateway->installments;
        $installments_without_interest = $this->gateway->max_without_interest;
        $min_per_installment = $this->gateway->min_per_installments;
        $interest_rate = $this->clean_values($this->gateway->interest_rate);
        if ($min_per_installment > 0) {
            while ($max_installments > ($price / $min_per_installment)) $max_installments--;
        }
        $installments = array();
        if ($price > 0) {
            $max_installments = ($max_installments == 0) ? 1 : $max_installments;
            for ($i = 1; $i <= $max_installments; $i++) {
                $interest_rate_installment = ($i <= $installments_without_interest) ? '' : $interest_rate;
                $value = ($i <= $installments_without_interest) ? ($price / $i) :
                    $this->get_installment_price($price, $i, $interest_rate, $this->gateway->interest_rate_caculate_method);
                $total = $value * $i;
                $installments[] = array(
                    'total' => $total,
                    'installments' => $i,
                    'installment_value' => $value,
                    'interest_rate' => $interest_rate_installment
                );
            }
        }
        return $installments;
    }

    private function validate_fields($formData)
    {
        try {

            if (!isset($formData['waypay_card_number']) || '' === $formData['waypay_card_number']) {
                throw new Exception(__('Please type the card number.', 'woocommerce-waypay'));
            }
            if (!isset($formData['waypay_holder_name']) || '' === $formData['waypay_holder_name']) {
                throw new Exception(__('Please type the name of the card holder.', 'woocommerce-waypay'));
            }
            if (!isset($formData['waypay_card_expiry']) || '' === $formData['waypay_card_expiry']) {
                throw new Exception(__('Please type the card expiry date.', 'woocommerce-waypay'));
            }
            if (!isset($formData['waypay_card_cvc']) || '' === $formData['waypay_card_cvc']) {
                throw new Exception(__('Please type the cvc code for the card', 'woocommerce-waypay'));
            }

        } catch (Exception $e) {
            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . esc_html($e->getMessage()), 'error');
            return false;
        }
        return true;
    }

    public function order_is_new_payment(WC_Order $order)
    {
        return $this->get_waypay_order_id($order) == null;
    }

    public function get_waypay_order_id(WC_Order $order)
    {
        return get_post_meta($order->get_id(), 'orderID', 'single');
    }

    public function sale_order(WC_Order $order, $post)
    {
        $result = array(
            'result' => 'fail',
            'redirect' => ''
        );

        $this->instantiate_logger();

        $is_valid = $this->validate_fields($post);

        if ($is_valid) {
            $is_valid = $this->validate_installments($post, (float)$order->get_total());
        }

        if ($is_valid) {

            $waypay = new WayPay();
            $waypay->setNotificationURL(get_site_url() . '/?wc-api/WC_WayPay_CC_Gateway');
            $waypay->setReference($this->gateway->invoice_prefix.$order->get_id());
            $buyer = $this->get_buyer_data($order);
            $this->set_order_items_data($order,$waypay);
            $address = $this->get_address_data($order);
            $shipping = $this->get_shipping_data($order,$address);
            $payment = $this->get_payment_data($order,$post,true);

            $waypay->setBuyer($buyer);
            $waypay->setShipping($shipping);
            $waypay->setPayment($payment);

            $waypay->setAuthorization(base64_encode(
                $this->gateway->api_key.':'.$this->gateway->api_token
            ));

            $wayPayService = new WayPayService($this->log);

            $request_data = $waypay->getRequestData();

            update_post_meta($order->get_id(), '_waypay_request_data', $request_data);

            $response = $wayPayService->pay($request_data);

            if (!empty($response)) {
                update_post_meta($order->get_id(), '_waypay_result_data', $result);
                if (!$response['status']) {
                    $message = __('An error has occurred while processing your payment, please try again.', 'woocommerce-waypay');
                    update_post_meta($order->get_id(), 'waypay_error', $message);
                    wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean($message), 'error');
                } elseif($response['status'] && $response['status'] != 200) {
                    $body = json_decode($response['body'], true);
                    $has_errors = false;
                    if(is_array($body['checkout']['errors'])){
                        $has_errors = true;
                        foreach ($body['checkout']['errors'] as $error) {
                            wc_add_notice($error['message'], 'error');
                        }
                    }
                    if(!$has_errors) {
                        if($response['body']){
                            wc_add_notice($response['body'], 'error');
                        } else {
                            $message = __('An error has occurred while processing your payment, please try again.', 'woocommerce-waypay');
                            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean($message), 'error');
                        }
                    }
                } else {

                    $body = json_decode($response['body'], true);

                    $this->set_order_status(
                        $waypay->getReference(),
                        $body['checkout']['status'],
                        $this->gateway->invoice_prefix
                    );

                    $result = array(
                        'result' => 'success',
                        'redirect' => $order->get_checkout_order_received_url()
                    );

                    if ($body['checkout']['auth_reference']) {
                        $this->update_post_meta($order->get_id(), $body['checkout']);
                    }

                    WC()->cart->empty_cart();

                }
            } else {
                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' .
                    esc_html(__('An error has occurred while processing your payment, please try again.', 'woocommerce-waypay')), 'error');
            }
        }
        return $result;
    }

}