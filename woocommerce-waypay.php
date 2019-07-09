<?php
/**
 * Plugin Name: WooCommerce WayPay
 * Plugin URI: https://github.com/WayPayBR/woocommerce-waypay
 * Description: <strong>Oficial</strong> Plugin for WayPay Payments.
 * Author: Vinicius Tassinari
 * Author URI: http://github.com/viniciustass
 * Version: 2.0.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-waypay
 * Domain Path: /languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.7
 */

if (!defined('ABSPATH')) {
    exit;
}

include_once 'includes/lib/WayPay.php';

if (!class_exists('WC_WayPay')) :

    class WC_WayPay {

        const VERSION = '2.1.0';

        protected static $instance = null;

        public static function load_waypay_class() {
            if (null == self::$instance) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        private function __construct() {
            add_action('init', array($this, 'load_plugin_textdomain'));
            if (function_exists('curl_exec') && class_exists('WC_Payment_Gateway')) {
                $this->includes();
                add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
                add_action('admin_notices', array($this, 'show_admin_notices'));
            } else {
                add_action('admin_notices', array($this, 'notify_dependencies_missing'));
            }
        }

        public static function get_templates_path() {
            return plugin_dir_path(__FILE__) . 'templates/';
        }

        public function load_plugin_textdomain() {
            $locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-waypay');
            load_textdomain('woocommerce-waypay', trailingslashit(WP_LANG_DIR) . 'woocommerce-waypay/woocommerce-waypay-' . $locale . '.mo');
            load_plugin_textdomain('woocommerce-waypay', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        public function add_gateway($methods) {
            $methods[] = 'WC_WayPay_CC_Gateway';
            $methods[] = 'WC_WayPay_Ticket_Gateway';
            $methods[] = 'WC_WayPay_Balance_Gateway';
            return $methods;
        }

        private function includes() {
            include_once 'includes/class-wc-waypay-api.php';
            include_once 'includes/class-wc-waypay-cc-api.php';
            include_once 'includes/class-wc-waypay-cc-gateway.php';
            include_once 'includes/class-wc-waypay-ticket-api.php';
            include_once 'includes/class-wc-waypay-ticket-gateway.php';
            include_once 'includes/class-wc-waypay-balance-api.php';
            include_once 'includes/class-wc-waypay-balance-gateway.php';
            include_once 'includes/lib/WayPay.php';
        }

        public function notify_dependencies_missing() {
            if (!function_exists('curl_exec')) {
                include_once 'includes/admin/views/html-notice-missing-curl.php';
            }
            if (!class_exists('WC_Payment_Gateway')) {
                include_once 'includes/admin/views/html-notice-missing-woocommerce.php';
            }
        }

        public function plugin_action_links($links) {
            $plugin_links = array();
            $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_waypay_cc_gateway')) . '">' . __('Credit Card Settings', 'woocommerce-waypay') . '</a>';
            $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_waypay_ticket_gateway')) . '">' . __('Ticket Settings', 'woocommerce-waypay') . '</a>';
            $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_waypay_balance_gateway')) . '">' . __('Virtual Balance Settings', 'woocommerce-waypay') . '</a>';
            return array_merge($plugin_links, $links);
        }

        public function show_admin_notices() {
            if ($data = get_transient('waypay_admin_notice')) {
                ?>
                <div class="updated <?php echo $data[1] ?> is-dismissible">
                    <p><?php echo $data[0] ?></p>
                </div>
                <?php
                delete_transient('waypay_admin_notice');
            }
        }
        
        public static function get_plugin_path() {
            return plugin_dir_path( __FILE__ );
        }

        public static function get_plugin_url() {
            return untrailingslashit( plugins_url( '/', __FILE__ ) );
        }

        public static function get_main_file() {
            return __FILE__;
        }

    }

    add_action('plugins_loaded', array('WC_WayPay', 'load_waypay_class'));

endif;