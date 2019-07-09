<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="woocommerce-message">
    <span>
        <?php printf(__('Payment successfully: # %s.', 'woocommerce-waypay'), '<strong>' . $auth_reference . '</strong>'); ?><br/>
    </span>
</div>