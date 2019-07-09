<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<h2><?php _e('Payment', 'woocommerce-waypay'); ?></h2>
<p class="order_details">
    <?php printf(__('Payment successfully: # %s.', 'woocommerce-waypay'), '<strong>' . $auth_reference . '</strong>'); ?><br/>
</p>
