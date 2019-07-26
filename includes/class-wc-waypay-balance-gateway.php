<?php
if (!defined('ABSPATH')) {
    exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class WC_WayPay_Balance_Gateway extends WC_Payment_Gateway_CC
{

    const ID = 'waypay-balance';

    /** @var WC_WayPay_Balance_API */
    public $api;

    public $supports = array('products');

    public $api_key;
    public $api_token;
    public $invoice_prefix;
    public $save_log;
    public $test_mode;
    public $request_account_password = true;
    public $dokan_enable_split;
    public $dokan_commission_calc_with_freight;

    private $balance = 0;

    public function __construct()
    {

        $this->id = self::ID;
        $this->icon = apply_filters( 'woocommerce_waypay_icon', plugins_url( 'assets/images/waypay.png', plugin_dir_path( __FILE__ ) ) );
        $this->method_title = __('WayPay - Virtual Balance', 'woocommerce-waypay');

        $this->method_description =
            '<img src="' .
            plugins_url(
                'assets/images/waypay.png',
                plugin_dir_path( __FILE__ )
            ) . '"><br/>' . '<strong>' .
            __('Accept Payments by Virtual Balance using the WayPay.', 'woocommerce-waypay') . '</strong>';

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


        $this->api = new WC_WayPay_Balance_API($this);

        $this->init_form_fields();
        $this->init_settings();

        // Front actions
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'set_thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'set_email_instructions'), 10, 3);
        add_action('woocommerce_api_wc_waypay_balance_gateway', array( $this->api, 'ipn_handler' ) );

        // Admin actions
        if (is_admin()) {
            add_action('admin_notices', array($this, 'do_ssl_check'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        if( is_plugin_active( 'dokan-lite/dokan.php' ) || is_plugin_active( 'dokan-pro/dokan-pro.php' ) ) {
            add_action('wc_waypay_balance_request_data', array($this->api,'set_split_data'), 11, 2);
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
        $this->balance = 0;
        $current_user = wp_get_current_user();
        if($current_user instanceof WP_User) {
            $log = null;
            if ('yes' == $this->save_log && class_exists('WC_Logger')) {
                $log = new WC_Logger();
            }
            $document = $current_user->get('billing_cnpj')
                ? $current_user->get('billing_cnpj')
                : $current_user->get('billing_cpf');
            $document = preg_replace('/\D/', '', $document);
            $wayPayService = new WayPayService($log,$this->test_mode);
            $request_data = array(
                'authorization' => base64_encode($this->api_key.':'.$this->api_token),
                'balance'=>array('cpfcnpj'=>$document)
            );
            if(($response = $wayPayService->balance($request_data)) && $response['status'] == 200) {
                $body = json_decode($response['body'],true);
                $this->balance = str_replace('R$','',$body['balance']['balance']);
                $this->balance = str_replace('.','',$this->balance);
                $this->balance = str_replace(',','.',$this->balance);
            }
        }
        return $this->balance >= WC()->cart->total && parent::is_available() && !empty($this->api_key) && !empty($this->api_token) && $this->using_supported_currency();
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
                'label' => __('Enable WayPay Virtual Balance', 'woocommerce-waypay'),
                'default' => 'no'
            ),

            'title' => array(
                'title' => __('Title', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('Displayed at checkout.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => __('Virtual Balance', 'woocommerce-waypay')
            ),

            'description' => array(
                'title' => __('Description', 'woocommerce-waypay'),
                'type' => 'textarea',
                'description' => __('Displayed at checkout.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => __('Pay your order with Virtual Balance!', 'woocommerce-waypay')
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
            ),

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
            'balance/payment-form.php',
            array(
                'balance'=>$this->balance,
                'request_account_password'=>$this->request_account_password
            ),
            'woocommerce/waypay/',
            WC_WayPay::get_templates_path()
        );
    }

    public function process_payment($order_id)
    {
        return $this->api->sale_order(wc_get_order($order_id), $_POST);
    }

    public function set_thankyou_page($order_id) {
        $order = new WC_Order($order_id);
        $result_data = get_post_meta($order_id, '_waypay_result_data', true);
        if (isset($result_data['auth_reference'])
            && in_array($order->get_status(), array('processing', 'on-hold'))
        ) {
            wc_get_template(
                'balance/payment-instructions.php',
                array(
                    'auth_reference' => $result_data['auth_reference'],
                ),
                'woocommerce/waypay/',
                WC_WayPay::get_templates_path()
            );
        }
    }

    public function set_email_instructions(WC_Order $order, $sent_to_admin, $plain_text = false) {
        if ($sent_to_admin || !in_array($order->get_status(), array('processing', 'on-hold')) || $this->id !== $order->get_payment_method()) {
            return;
        }
        $result_data = get_post_meta($order->get_id(), '_waypay_result_data', true);
        if (isset($result_data['auth_reference'])) {
            if ($plain_text) {
                wc_get_template(
                    'balance/emails/plain-instructions.php',
                    array(
                        'auth_reference' => $result_data['auth_reference'],
                    ),
                    'woocommerce/waypay/',
                    WC_WayPay::get_templates_path()
                );
            } else {
                wc_get_template(
                    'balance/emails/html-instructions.php',
                    array(
                        'auth_reference' => $result_data['auth_reference'],
                    ),
                    'woocommerce/waypay/',
                    WC_WayPay::get_templates_path()
                );
            }
        }
    }

    public function do_ssl_check() {
        if ($this->enabled == "yes") {
            $section = isset($_GET['section']) ? $_GET['section'] : '';
            if (strpos($section, 'waypay') !== false) {
                if (get_option('woocommerce_force_ssl_checkout') == "no") {
                    echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>", 'woocommerce-waypay'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
                }
            }
        }
    }

}