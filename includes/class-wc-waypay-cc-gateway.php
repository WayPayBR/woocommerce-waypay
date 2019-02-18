<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_WayPay_CC_Gateway extends WC_Payment_Gateway_CC {

    const ID = 'waypay-cc';

    const MIN_PER_INSTALLMENT = '5';

    const INTEREST_RATE_TYPE_SIMPLE = 'simple';
    const INTEREST_RATE_TYPE_COMPOUND = 'compound';
    const INTEREST_RATE_TYPE_PRICE = 'price';

    /** @var WC_WayPay_CC_API */
    public $api;

    public $api_key;
    public $api_token;
    public $invoice_prefix;
    public $save_log;
    public $show_credit_card_logos;
    public $installments;
    public $interest_rate_caculate_method;
    public $interest_rate;
    public $max_without_interest;
    public $min_per_installments;

    public $supports = array(
        'products',
    );

    public function __construct() {
        $this->id = self::ID;
        $this->icon = apply_filters('woocommerce_waypay_icon', plugins_url('assets/images/waypay.png', plugin_dir_path(__FILE__)));
        $this->method_title = __('WayPay - Credit Card', 'woocommerce-waypay');
        $this->method_description =
            '<img src="' .
            plugins_url(
                'assets/images/waypay.png',
                plugin_dir_path( __FILE__ )
            ) . '"><br/>' . '<strong>' .
            __('Accept Payments by Credit Card using the WayPay.', 'woocommerce-waypay') . '</strong>';

        $this->has_fields = true;

        // Global Settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->environment = $this->get_option('environment');
        $this->api_key = $this->get_option('api_key');
        $this->api_token = $this->get_option('api_token');
        $this->invoice_prefix = $this->get_option('invoice_prefix', 'WC-');
        $this->save_log = $this->get_option('save_log');

        // CC Settings
        $this->show_credit_card_logos = $this->get_option('show_credit_card_logos');
        $this->installments = $this->get_option('installments');
        $this->interest_rate_caculate_method = $this->get_option('interest_rate_caculate_method', self::INTEREST_RATE_TYPE_PRICE);
        $this->interest_rate = $this->get_option('interest_rate');
        $this->max_without_interest = $this->get_option('max_without_interest');
        $this->min_per_installments = $this->get_option('min_per_installments');

        $this->api = new WC_WayPay_CC_API($this);

        $this->init_form_fields();
        $this->init_settings();

        // Front actions
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'set_thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'set_email_instructions'), 10, 3);
        add_action('woocommerce_api_wc_waypay_cc_gateway', array( $this->api, 'ipn_handler' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );

        // Admin actions
        if (is_admin()) {
            add_action('admin_notices', array($this, 'do_ssl_check'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

    }

    /**
     * Checkout scripts.
     */
    public function checkout_scripts() {
        if ( is_checkout() && $this->is_available() ) {
            if ( ! get_query_var( 'order-received' ) ) {
                //$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                $suffix = '';
                wp_enqueue_style( 'waypay-checkout', plugins_url( 'assets/css/checkout' . $suffix . '.css', plugin_dir_path( __FILE__ ) ), array(), WC_WayPay::VERSION );
            }
        }
    }

    public function get_supported_currencies() {
        return apply_filters(
            'woocommerce_waypay_supported_currencies', array(
                'BRL',
                'USD',
            )
        );
    }

    public function using_supported_currency() {
        return in_array(get_woocommerce_currency(), $this->get_supported_currencies());
    }

    public function is_available() {
        return parent::is_available() && !empty($this->api_key) && !empty($this->api_token) && $this->using_supported_currency();
    }

    public function admin_options() {
        include 'admin/views/html-admin-page.php';
    }

    public function init_form_fields() {

        $this->form_fields = array(

            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-waypay'),
                'type' => 'checkbox',
                'label' => __('Enable WayPay Credit Card', 'woocommerce-waypay'),
                'default' => 'no'
            ),

            'title' => array(
                'title' => __('Title', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('Displayed at checkout.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => __('Credit Card', 'woocommerce-waypay')
            ),

            'description' => array(
                'title' => __('Description', 'woocommerce-waypay'),
                'type' => 'textarea',
                'description' => __('Displayed at checkout.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => __('Pay your order with a credit card.', 'woocommerce-waypay')
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

            'payment' => array(
                'title' => __('Payment Options', 'woocommerce-waypay'),
                'type' => 'title',
                'description' => ''
            ),

            'show_credit_card_logos' => array(
                'title' => __('Credit Card Logos', 'woocommerce-waypay'),
                'type' => 'checkbox',
                'label' => __('Show logos of Credit Cards in select', 'woocommerce-waypay'),
                'default' => 'no'
            ),

            'installments' => array(
                'title' => __('Maximum number of installments', 'woocommerce-waypay'),
                'type' => 'select',
                'description' => __('Maximum number of installments for orders in your store.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            'interest_rate' => array(
                'title' => __('Interest Rate (%)', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('Percentage of interest that will be charged to the customer in the installment where there is interest rate to be charged.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => '0'
            ),
            'interest_rate_caculate_method' => array(
                'title' => __('Interest Rate Calculate Method', 'woocommerce-waypay'),
                'type' => 'select',
                'description' => __('Choose your interest rate calculate method.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => self::INTEREST_RATE_TYPE_PRICE,
                'options' => array(
                    self::INTEREST_RATE_TYPE_SIMPLE => __('Simple', 'woocommerce-waypay'),
                    self::INTEREST_RATE_TYPE_COMPOUND => __('Compound', 'woocommerce-waypay'),
                    self::INTEREST_RATE_TYPE_PRICE => __('Price', 'woocommerce-waypay'),
                )
            ),
            'max_without_interest' => array(
                'title' => __('Number of installments without Interest Rate', 'woocommerce-waypay'),
                'type' => 'select',
                'description' => __('Indicate the number of public without Interest Rate.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => '0',
                'options' => array(
                    '0' => __('None', 'woocommerce-waypay'),
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            'min_per_installments' => array(
                'title' => __('Minimum value per installments', 'woocommerce-waypay'),
                'type' => 'text',
                'description' => __('Minimum value per installments, cannot be less than 1.', 'woocommerce-waypay'),
                'desc_tip' => true,
                'default' => self::MIN_PER_INSTALLMENT
            ),

        );
    }

    public function form() {

        //$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $suffix = '';
        wp_enqueue_script('wc-credit-card-form');
        wp_enqueue_script('wc-waypay-jquery-ddslick-lib', plugins_url('assets/js/jquery.ddslick.min.js', plugin_dir_path(__FILE__)), array('jquery'), WC_WayPay::VERSION, true);
        wp_enqueue_style( 'wc-waypay-checkoyt-style', plugins_url( 'assets/css/checkout' . $suffix . '.css', plugin_dir_path( __FILE__ ) ), array(), WC_WayPay::VERSION );
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        include_once WC_WayPay::get_plugin_path() . 'templates/cc/payment-form.php';
    }

    public function process_payment($order_id) {
        return $this->api->sale_order(wc_get_order($order_id), $_POST);
    }

    public function set_thankyou_page($order_id) {
        $order = new WC_Order($order_id);
        $result_data = get_post_meta($order_id, '_waypay_result_data', true);
        if (isset($result_data['auth_reference'])
            && in_array($order->get_status(), array('processing', 'on-hold'))
        ) {
            wc_get_template(
                'cc/payment-instructions.php',
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
                    'cc/emails/plain-instructions.php',
                    array(
                        'auth_reference' => $result_data['auth_reference'],
                    ),
                    'woocommerce/waypay/',
                    WC_WayPay::get_templates_path()
                );
            } else {
                wc_get_template(
                    'cc/emails/html-instructions.php',
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