<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_WayPay_Ticket_API extends WC_WayPay_API {

    public function sale_order(WC_Order $order, $post) {
        $result = array(
            'result' => 'fail',
            'redirect' => ''
        );

        $this->instantiate_logger();

        $waypay = new WayPay();
        $waypay->setNotificationURL(get_site_url() . '/?wc-api=WC_WayPay_Ticket_Gateway');
        $waypay->setReference($this->gateway->invoice_prefix.$order->get_id());
        $buyer = $this->get_buyer_data($order);
        $this->set_order_items_data($order, $waypay);
        $address = $this->get_address_data($order);
        $shipping = $this->get_shipping_data($order, $address);
        $payment = $this->get_payment_data($order, $post, WayPayPayment::BOLETO);

        $waypay->setBuyer($buyer);
        $waypay->setShipping($shipping);
        $waypay->setPayment($payment);
        $waypay->setAuthorization(base64_encode(
            $this->gateway->api_key.':'.$this->gateway->api_token
        ));

        $wayPayService = new WayPayService($this->log,$this->gateway->test_mode);

        $request_data = $waypay->getRequestData();
        $request_data = apply_filters('wc_waypay_ticket_request_data', $request_data, $order);

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
                        if(isset($body['error'])) {
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

        return $result;
    }

}