<?php

/** @var $payment_methods array */
/** @var $payment_id int */
/** @var $this WC_WayPay_CC_Gateway */

if (!defined('ABSPATH')) {
    exit;
}
$fields = array();
$order_total = $this->get_order_total();
$select_installments = $this->api->get_installments_html($order_total);
$default_fields = array(
    'card-number-field' => '<p class="form-row form-row-wide">
    <label for="waypay-card-number">' . __('Card Number', 'woocommerce-waypay') . ' <span class="required">*</span></label>
    <input id="waypay-card-number" name="waypay_card_number" class="input-text" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" />
</p>',
    'card-holder-name-field' => '<p class="form-row form-row-wide">
    <label for="waypay-holder-name">' . __('Name Printed on the Card', 'woocommerce-waypay') . ' <span class="required">*</span></label>
    <input id="waypay-holder-name" name="waypay_holder_name" class="input-text wc-credit-card-form-card-holder-name" autocomplete="cc-holder" autocorrect="no" autocapitalize="no" spellcheck="no"/>
</p>',
    'card-expiry-field' => '<p class="form-row form-row-first">
    <label for="waypay-card-expiry">' . __('Expiry (MM/YYYY)', 'woocommerce-waypay') . ' <span class="required">*</span></label>
    <input id="waypay-card-expiry" name="waypay_card_expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__('MM / YYYY', 'woocommerce-waypay') . '" />
</p>',
    'card-cvc-field' => '<p class="form-row form-row-last">
    <label for="waypay-card-cvc">' . __('Security Code', 'woocommerce-waypay') . ' <span class="required">*</span></label>
    <input id="waypay-card-cvc" name="waypay_card_cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVV', 'woocommerce-waypay') . '" />
</p>',
    'card-installments' => '<p class="form-row form-row-wide">
        <label for="waypay-installments">' . __('Installments', 'woocommerce-waypay') . ' <span class="required">*</span></label>
        ' . $select_installments . '
</p>'
);
$fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
?>

<fieldset id="waypay-payment-form" class="wc-credit-card-form wc-payment-form">

    <input id="payment-method-type" name="payment_method_type" value="card" type="hidden"/>

    <div class="tab-content woocommerce-waypay-payment-methods">
        <div id="waypay-credit-card-form" class="waypay-method-form tab-pane active">
            <label for="waypay-payment-id"><?php _e('Card Brand','woocommerce-waypay')?> <span class="required">*</span></label>
            <input type="hidden" name="waypay_payment_id" id="waypay-payment-id" value="<?php echo $this->api->get_payment_id_selected($_POST) ?>"/>
            <div id="card-drop-down">
            </div>
            <div class="clearfix">&nbsp;</div>
            <?php
            do_action('woocommerce_credit_card_form_start', $this->id);
            foreach ($fields as $field) {
                echo $field;
            }
            do_action('woocommerce_credit_card_form_end', $this->id);
            ?>
            <div class="clearfix"></div>
        </div>
    </div>
</fieldset>
<script type="text/javascript">
    /* <![CDATA[ */
    var ddData = <?php echo $this->api->get_cards_data(); ?>;
    jQuery(document).ready(function() {
        jQuery('#card-drop-down').ddslick({
            width:300,
            imagePosition:"left",
            data: ddData,
            selectText: "<?php _e("Select your Card Brand",'woocommerce-waypay') ?>",
            onSelected: function(data) {
                jQuery('#waypay-payment-id').val(data.selectedData.value);
            }
        });
    });
    /* ]]> */
</script>