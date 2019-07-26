<?php
if (!defined('ABSPATH')) {
    exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class WC_WayPay_Ticket_Gateway extends WC_Payment_Gateway_CC
{

    const ID = 'waypay-ticket';

    /** @var WC_WayPay_Ticket_API */
    public $api;

    public $supports = array('products');

    public $api_key;
    public $api_token;
    public $invoice_prefix;
    public $save_log;
    public $test_mode;
    public $dokan_enable_split;
    public $dokan_commission_calc_with_freight;

    public function __construct()
    {

        $this->id = self::ID;
        $this->icon = apply_filters( 'woocommerce_waypay_icon', plugins_url( 'assets/images/waypay.png', plugin_dir_path( __FILE__ ) ) );
        $this->method_title = __('WayPay - Ticket', 'woocommerce-waypay');

        $this->method_description =
            '<img src="' .
            plugins_url(
                'assets/images/waypay.png',
                plugin_dir_path( __FILE__ )
            ) . '"><br/>' . '<strong>' .
            __('Accept Payments by Ticket using the WayPay.', 'woocommerce-waypay') . '</strong>';

        $this->has_fields = true;

        // Global Settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->api_key = $this->get_option('api_key');
        $this->api_token = $this->get_option('api_token');
        $this->invoice_prefix = $this->get_option('invoice_prefix', 'WC-');
        $this->save_log = $this->get_option('save_log');
        $this->test_mode = $this->get_option('test_mode');

        // Dokan Settings
        $this->dokan_enable_split = $this->get_option('dokan_enable_split', 'no');
        $this->dokan_commission_calc_with_freight = $this->get_option('dokan_commission_calc_with_freight', 'no');


        $this->api = new WC_WayPay_Ticket_API($this);

        $this->init_form_fields();
        $this->init_settings();

        // Front actions
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'set_thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'set_email_instructions'), 10, 3);
        add_action('woocommerce_api_wc_waypay_ticket_gateway', array( $this->api, 'ipn_handler' ) );

        // Admin actions
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        if( is_plugin_active( 'dokan-lite/dokan.php' ) || is_plugin_active( 'dokan-pro/dokan-pro.php' ) ) {
            add_action('wc_waypay_ticket_request_data', array($this->api,'set_split_data'), 11, 2);
        }

    }

    public function get_supported_currencies()
    {
        return apply_filters(
            'woocommerce_waypay_supported_currencies', array(
                'BRL',
            )
        );
    }

    public function using_supported_currency()
    {
        return in_array(get_woocommerce_currency(), $this->get_supported_currencies());
    }

    public function is_available()
    {
        return parent::is_available() && !empty($this->api_key) && !empty($this->api_token) && $this->using_supported_currency();
    }

    public function admin_options()
    {
        include 'admin/views/html-admin-page.php';
    }

    public function init_form_fields()
    {

        $this->form_fields = array(

            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-waypay'),
                'type' => 'checkbox',
                'label' => __('Enable WayPay Ticket', 'woocommerce-waypay'),
                'default' => 'no'
            ),

            'title' => array(
                'title' => __('Title', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('Displayed at checkout.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => __('Ticket', 'woocommerce-waypay')
            ),

            'description' => array(
                'title' => __('Description', 'woocommerce-waypay'),
                'type' => 'textarea',
                'description' => __('Displayed at checkout.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => __('Pay your order with a ticket.', 'woocommerce-waypay')
            ),

            'integration' => array(
                'title' => __('Integration Settings', 'woocommerce-waypay'),
                'type' => 'title',
                'description' => __('You can obtain your credentials by creating your account or by contacting us. For more information, please visit: <a href="https://www.waypay.com.br">https://www.waypay.com.br</a>.','woocommerce-waypay')
            ),

            'api_key' => array(
                'title' => __('API Key', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('API Key provided by WayPay.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => '',
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),

            'api_token' => array(
                'title' => __('API Token', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('API Token provided by WayPay.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => '',
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),

            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers, which is used to ensure that the order number is unique if you use this account in more than one store.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => 'WC-'
            ),

            'save_log' => array(
                'title' => __('Save Log', 'woocommerce-waypay'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'woocommerce-waypay'),
                'default' => 'no',
                'description' => sprintf(__('Save log for API requests. You can check this log in %s.', 'woocommerce-waypay'), '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' . __('System Status &gt; Logs', 'woocommerce-waypay') . '</a>')
            ),

            'test_mode' => array(
                'title' => __('Test Mode', 'woocommerce-waypay'),
                'type' => 'checkbox',
                'label' => __('Enable test mode', 'woocommerce-waypay'),
                'default' => 'no',
                'description' => __('In test mode, orders are not processed.', 'woocommerce-waypay'),
            )

        );

        if (is_plugin_active('dokan-lite/dokan.php') || is_plugin_active('dokan-pro/dokan-pro.php')) {
            $this->form_fields['dokan_integration_options'] =
                array(
                    'title' => __('Dokan Integration Options', 'woocommerce-waypay'),
                    'type' => 'title',
                    'description' => ''
                );

            $this->form_fields['dokan_enable_split'] =
                array(
                    'title' => __('Enable/Disable', 'woocommerce-waypay'),
                    'type' => 'checkbox',
                    'label' => __('Enable Split Payment', 'woocommerce-waypay'),
                    'default' => 'no',
                    'description' => __('Creates the order in WayPay for the owner of the Marketplace and sends the values to the sellers as commission, already discounting the commission percentage of the Administrator configured in Dokan. <strong>Note: For the PRO version of Dokan the freight is shipped separately, in the Lite version, the freight stays with the Administrator</strong>.','woocommerce-waypay')
                );

            $this->form_fields['dokan_commission_calc_with_freight'] =
                array(
                    'title' => __('Enable/Disable', 'woocommerce-waypay'),
                    'type' => 'checkbox',
                    'label' => __('Use freight in commission calculation', 'woocommerce-waypay'),
                    'default' => 'no',
                    'description' => __('Use the total freight amount in the commission calculation plus the products.','woocommerce-waypay')
                );

        }
    }

    public function payment_fields()
    {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        wc_get_template(
            'ticket/payment-form.php',
            array(),
            'woocommerce/waypay/',
            WC_WayPay::get_templates_path()
        );
    }

    public function process_payment($order_id)
    {
        return $this->api->sale_order(wc_get_order($order_id), $_POST);
    }

    public function set_thankyou_page($order_id)
    {
        $result_data = get_post_meta($order_id, '_waypay_result_data', true);
        if (isset($result_data['checkout']['url_bank_slip'])) {
            wc_get_template(
                'ticket/payment-instructions.php',
                array(
                    'url' => $result_data['checkout']['url_bank_slip'],
                ),
                'woocommerce/waypay/',
                WC_WayPay::get_templates_path()
            );

            add_post_meta($order_id, 'waypay_ticket_url', $result_data['checkout']['url_bank_slip']);
        }
    }

    public function set_email_instructions(WC_Order $order, $sent_to_admin, $plain_text = false)
    {
        if ($sent_to_admin || $this->id !== $order->get_payment_method()) {
            return;
        }
        $result_data = get_post_meta($order->get_id(), '_waypay_result_data', true);
        if (isset($result_data['checkout']['url_bank_slip'])) {
            if ($plain_text) {
                wc_get_template(
                    'ticket/emails/plain-instructions.php',
                    array(
                        'url' => $result_data['checkout']['url_bank_slip'],
                    ),
                    'woocommerce/waypay/',
                    WC_WayPay::get_templates_path()
                );
            } else {
                wc_get_template(
                    'ticket/emails/html-instructions.php',
                    array(
                        'url' => $result_data['checkout']['url_bank_slip'],
                    ),
                    'woocommerce/waypay/',
                    WC_WayPay::get_templates_path()
                );
            }
        }
    }

}