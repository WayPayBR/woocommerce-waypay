<?php
if (!defined('ABSPATH')) {
    exit;
}
_e('Payment', 'woocommerce-waypay');
echo "\n\n";
printf(__('Payment successfully: # %s.', 'woocommerce-waypay'), '<strong>' . $auth_reference . '</strong>');
echo "\n\n";