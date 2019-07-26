<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="waypay-balance-instructions">
    <p>&nbsp;</p>
    <?php $balance = wc_price($balance) ?>
    <p><?php _e("Virtual Balance:", 'woocommerce-waypay'); ?> <strong><?php echo $balance ?>.</strong></p>
</div>
<?php if($request_account_password): ?>

    <fieldset id="waypay-balance-form" class='wc-balance-form wc-payment-form'>
        <div class="clear"></div>
        <p class="form-row form-row-wide">
            <label for="waypay-account-password"><?php _e('Account password','woocommerce-waypay')?> <span class="required">*</span></label>
            <input type="password" class="input-text" name="waypay_account_password" id="waypay-account-password" value=""/>
        </p>
    </fieldset>

<?php endif; ?>