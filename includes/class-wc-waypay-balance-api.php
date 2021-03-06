<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_WayPay_Balance_API extends WC_WayPay_API {

    public function sale_order(WC_Order $order, $post) {

        $result = array(
            'result' => 'fail',
            'redirect' => ''
        );

        $this->instantiate_logger();

        $is_valid = $this->validate_fields($post, $order);

        if ($is_valid) {
            $waypay = new WayPay();
            $waypay->setNotificationURL(get_site_url() . '/?wc-api=WC_WayPay_Balance_Gateway');
            $waypay->setReference($this->gateway->invoice_prefix . $order->get_id());
            $waypay->setAccountPassword($post['waypay_account_password']);
            $buyer = $this->get_buyer_data($order);
            $this->set_order_items_data($order, $waypay);
            $address = $this->get_address_data($order);
            $shipping = $this->get_shipping_data($order, $address);
            $payment = $this->get_payment_data($order, $post, WayPayPayment::SALDO);

            $waypay->setBuyer($buyer);
            $waypay->setShipping($shipping);
            $waypay->setPayment($payment);
            $waypay->setAuthorization(base64_encode(
                $this->gateway->api_key . ':' . $this->gateway->api_token
            ));

            $wayPayService = new WayPayService($this->log, $this->gateway->test_mode);

            $request_data = $waypay->getRequestData();

            $request_data = apply_filters('wc_waypay_balance_request_data', $request_data, $order);
            update_post_meta($order->get_id(), '_waypay_request_data', $request_data);

            $response = $wayPayService->pay($request_data);

            if (!empty($response)) {

                $body = json_decode($response['body'], true);

                update_post_meta($order->get_id(), '_waypay_result_data', $body);
                if (!$response['status']) {
                    $message = __('An error has occurred while processing your payment, please try again.', 'woocommerce-waypay');
                    update_post_meta($order->get_id(), 'waypay_error', $message);
                    wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean($message), 'error');
                } elseif ($response['status'] && $response['status'] != 200) {
                    $body = json_decode($response['body'], true);
                    $has_errors = false;
                    if (is_array($body['checkout']['errors'])) {
                        $has_errors = true;
                        foreach ($body['checkout']['errors'] as $error) {
                            wc_add_notice($error['message'], 'error');
                        }
                    }
                    if (!$has_errors) {
                        if ($response['body']) {
                            $message = $response['body'];
                            $body = json_decode($response['body'], true);
                            if (isset($body['error'])) {
                                $message = $body['error']['message'];
                            }
                            wc_add_notice($message, 'error');
                        } else {
                            $message = __('An error has occurred while processing your payment, please try again.', 'woocommerce-waypay');
                            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean($message), 'error');
                        }
                    }
                } else {

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

    private function validate_fields($formData, WC_Order $order) {

        if (!$this->gateway->request_account_password) {
            return true;
        }

        try {

            if (!isset($formData['waypay_account_password']) || '' === $formData['waypay_account_password']) {
                if (isset($formData['waypay_insufficient_balance']) && $formData['waypay_insufficient_balance'] == '1') {
                    throw new Exception(__('Insufficient balance for payment.', 'woocommerce-waypay'));
                }
                throw new Exception(__('Please type the account password.', 'woocommerce-waypay'));
            } else {
                $password = $formData['waypay_account_password'];
                if ($order->get_meta('_billing_cpf')) {
                    $document = $this->clean_number($order->get_meta('_billing_cpf'));
                }
                if ($order->get_meta('_billing_cnpj')) {
                    $document = $this->clean_number($order->get_meta('_billing_cnpj'));
                }
                $wayPayService = new WayPayService($this->log, $this->gateway->test_mode);
                if (!$wayPayService->login($document, $password)) {
                    throw new Exception(__('Invalid username or password for WayPay account.<br/>Forgot password? <a target="_blank" href="https://suaconta.waypay.com.br/Auth/forgotPassword">Click here to recovery password</a>.', 'woocommerce-waypay'));
                }
            }

        } catch (Exception $e) {
            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . $e->getMessage(), 'error');
            return false;
        }
        return true;
    }

}