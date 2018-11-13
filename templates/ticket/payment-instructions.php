<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="woocommerce-message">
    <span><a class="button" href="<?php echo esc_url($url); ?>" target="_blank"
             style="display: block !important; visibility: visible !important;"><?php _e('Pay the Order', 'woocommerce-waypay'); ?></a><?php _e('Please click in the following button to view your Ticket.', 'woocommerce-waypay'); ?>
        <br/><?php _e('You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-waypay'); ?>
        <br/><?php _e('After we receive the payment confirmation, your order will be processed.', 'woocommerce-waypay'); ?></span>
</div>