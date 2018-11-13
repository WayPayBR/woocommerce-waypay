<?php
if (!defined('ABSPATH')) {
    exit;
}
_e('Payment', 'woocommerce-waypay');
echo "\n\n";
printf(__('Payment successfully: # %s.', 'woocommerce-waypay'), $auth_reference);
echo "\n\n";