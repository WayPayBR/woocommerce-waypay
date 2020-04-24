<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="waypay-balance-instructions">
    <p>&nbsp;</p>
    <?php $has_balance = $balance >= $cart_total ?>
    <?php $formatted_balance = wc_price($balance) ?>
    <p><?php _e("Virtual Balance:", 'woocommerce-waypay'); ?> <strong><?php echo $formatted_balance ?><?php echo !$has_balance ? '<span class="required">*</span></strong></p>' : ''?>
</div>
<?php if($request_account_password): ?>

    <fieldset id="waypay-balance-form" class='wc-balance-form wc-payment-form'>
        <div class="clear"></div>
        <?php if($has_balance) : ?>
            <p class="form-row form-row-wide">
                <label for="waypay-account-password"><?php _e('Account password','woocommerce-waypay')?> <span class="required">*</span></label>
                <input type="password" class="input-text" name="waypay_account_password" id="waypay-account-password" value=""/>
                <input type="hidden" name="waypay_insufficient_balance" value="0"/>
            </p>
        <?php else: ?>
            <label for="waypay-account-password"><span class="required">*</span><?php _e('Insufficient balance for payment.','woocommerce-waypay')?></label>
            <input type="hidden" name="waypay_insufficient_balance" value="1"/>
        <?php endif; ?>
    </fieldset>

<?php endif; ?>