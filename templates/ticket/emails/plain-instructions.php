<?php
if (!defined('ABSPATH')) {
    exit;
}
_e('Payment', 'woocommerce-waypay');
echo "\n\n";
_e('Please use the link below to view your Ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-waypay');
echo "\n";
echo esc_html($url);
echo "\n";
_e('After we receive the payment confirmation, your order will be processed.', 'woocommerce-waypay');
echo "\n\n";