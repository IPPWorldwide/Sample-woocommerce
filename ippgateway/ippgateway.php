<?php
/**
 * Plugin Name: IPPGateway Services
 * Plugin URI: https://www.ippeurope.com
 * Description: IPPGateway
 * Author: Mathias Gajhede
 * Author URI: http://www.ippeurope.com
 * Version: 1.0.0
 * Text Domain: wc-gateway-ippgateway
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2022 IPP Europe
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-IPPGateway
 * @author    Mathias Gajhede
 * @category  Admin
 * @copyright Copyright (c) 2022, IPP Europe
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This gateway supports the IPP Gateway Services product and can be reused in any form
 */

defined( 'ABSPATH' ) or exit;

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + IPPGateway
 */
function wc_ippgateway_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Gateway_IPPGateway';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_ippgateway_add_to_gateways' );


/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_ippgateway_gateway_plugin_links( $links ) {

    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=ippgateway_gateway' ) . '">' . __( 'Configure', 'wc-gateway-ippgateway' ) . '</a>'
    );

    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_ippgateway_gateway_plugin_links' );


/**
 * IPP Gateway
 *
 * Provides an IPP Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_IPPGateway
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Mathias Gajhede
 */
add_action( 'plugins_loaded', 'wc_ippgateway_gateway_init', 11 );

function wc_ippgateway_gateway_init() {

    class WC_Gateway_IPPGateway extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            global $woocommerce;
            $supports[] = "products";
            $supports[] = 'refunds';
            $supports[] = 'subscriptions';
            $supports[] = 'subscription_cancellation';
            $supports[] = 'subscription_reactivation';
            $supports[] = 'subscription_suspension';
            $supports[] = 'subscription_amount_changes';
            $supports[] = 'subscription_date_changes';
            $supports[] = 'subscription_payment_method_change';
            $supports[] = 'multiple_subscriptions';
            $supports[] = 'add_payment_method';
            $supports[] = 'subscription_payment_method_change_customer';
            $supports[] = 'subscription_payment_method_change_admin';
            $supports[] = 'pre-orders';

            $this->id                 = 'ippgateway_gateway';
            $this->icon               = apply_filters('woocommerce_ippgateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'IPPGateway', 'wc-gateway-ippgateway' );
            $this->method_description = __( 'Allows ippgateway payments. Very handy if you use your cheque gateway for another payment method, and can help with testing. Orders are marked as "on-hold" when received.', 'wc-gateway-ippgateway' );
            $this->supports           = $supports;

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->setDescription();
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->merchant_id  = isset($this->settings["merchant_id"]) ? $this->settings["merchant_id"] : "";
            $this->payment_key  = isset($this->settings["payment_key"]) ? $this->settings["payment_key"] : "";

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            add_action( 'wp_footer', array( $this, 'add_jscript_checkout' ), 9999 );
            add_action('wp_enqueue_scripts', array( $this, 'add_jscript_checkout' ));


            add_action( 'woocommerce_api_ippgateway', array( $this, 'webhook' ) );

            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);



        }

        public function setDescription() {
            return $this->get_option( 'description' );
        }
        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {

            $this->form_fields = apply_filters( 'wc_ippgateway_form_fields', array(

                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'wc-gateway-ippgateway' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable IPPGateway Payment', 'wc-gateway-ippgateway' ),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title'       => __( 'Title', 'wc-gateway-ippgateway' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-ippgateway' ),
                    'default'     => __( 'IPPGateway Payment', 'wc-gateway-ippgateway' ),
                    'desc_tip'    => true,
                ),

                'merchant_id' => array(
                    'title'       => __( 'Merchant ID', 'wc-gateway-ippgateway' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-ippgateway' ),
                    'default'     => __( '', 'wc-gateway-ippgateway' ),
                    'desc_tip'    => true,
                ),

                'payment_key' => array(
                    'title'       => __( 'Key 2', 'wc-gateway-ippgateway' ),
                    'type'        => 'text',
                    'description' => __( 'Key 2 (Payment Key) to be find in your Merchant Portal', 'wc-gateway-ippgateway' ),
                    'default'     => __( '', 'wc-gateway-ippgateway' ),
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __( 'Description', 'wc-gateway-ippgateway' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-ippgateway' ),
                    'default'     => __( 'Please pay through VISA or MasterCard', 'wc-gateway-ippgateway' ),
                    'desc_tip'    => true,
                ),

                'instructions' => array(
                    'title'       => __( 'Instructions', 'wc-gateway-ippgateway' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-ippgateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            ) );
        }


        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions ) {
                echo wpautop( wptexturize( $this->instructions ) );
            }
        }


        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        /**
         * Handle the IPN from IPP
         */
        function webhook() {
            include(plugin_dir_path( __FILE__ )."classes/IPPGateway.php");
            $posted = stripslashes_deep($_GET);

            header( 'HTTP/1.1 200 OK' );
            $order = new WC_Order((int)$posted["wooorderid"]);

            $ipp    = new IPPGateway($this->merchant_id,$this->payment_key);
            $status = $ipp->payment_status($posted["transaction_id"],$posted["transaction_key"]);

            if($status->result == "ACK") {
                $order->add_order_note(__('Callback performed', 'ippgateway'));
                update_post_meta((int)$posted["wooorderid"], 'Transaction ID', $posted["transaction_id"]);
                update_post_meta((int)$posted["wooorderid"], 'Transaction Key', $posted["transaction_key"]);
                update_post_meta((int)$posted["wooorderid"], 'Card no', $status->card_data->pan);
                $order->update_status('processing');
                status_header(200);
            }
            die();
        }


        /**
         * Process the subscription and return the result
         *
         * @param int $amount
         * @param int $order_id
         * @return array
         */
        public function scheduled_subscription_payment($amount, $order_id)
        {
            $subscription = $this->get_subscription($order_id);
            $renewal_order_id = $this->is_woocommerce_3() ? $order_id->get_id() : $order_id->id;

            $parent_order = $subscription->order;
            $parent_order_id = $this->is_woocommerce_3() ? $parent_order->get_id() : $parent_order->id;
            $transaction_id = get_post_meta($parent_order_id, 'Transaction ID', true);
            $latest_renewal = get_post_meta($parent_order_id, 'latest_renewal', true);

            update_post_meta((int)$parent_order_id, 'latest_renewal', time());

            $order = new WC_Order((int)$parent_order_id);
            $renewal_order = wc_get_order( $renewal_order_id );
            if ($latest_renewal < time() - 1) {

                include(plugin_dir_path( __FILE__ )."classes/IPPGateway.php");
                $ipp = new IPPGateway($this->merchant_id,$this->payment_key);

                $data   = [];
                $data["currency"] = $renewal_order->get_currency();
                $data["amount"] = $amount;
                $data["order_id"] = $renewal_order_id;
                $data["transaction_type"] = "ECOM";
                $data["ipn"] = add_query_arg('wooorderid', $renewal_order_id, add_query_arg('wc-api', 'ippgateway', $this->get_return_url($renewal_order)));
                $data["accepturl"] = $this->get_return_url($renewal_order);

                $order_item = $renewal_order->get_items();
                $data['total_products'] = count($order_item);
                $i = 1;
                $data["product"] = [];
                foreach ($order_item as $product) {
                    $data["product"][$i . "_id"] = $product["product_id"];
                    $data["product"][$i . "_name"] = $product["name"];
                    $data["product"][$i . "_qty"] = $product["qty"];
                    $data["product"][$i . "_total"] = $product["line_total"];
                    $data["product"][$i . "_tax"] = $product["line_tax"];
                    $i++;
                }
                $data["billing"] = [];
                $data["billing"]["name"] = $renewal_order->get_billing_first_name() . " " . $renewal_order->get_billing_last_name();
                $data["billing"]["email"] = $renewal_order->get_billing_email();

                $data = $ipp->checkout_id($data);
                $data_url = $data->checkout_id;
                $cryptogram = $data->cryptogram;

                $order->add_order_note(__('Subscription performed', 'ippgateway'));

                $order = new WC_Order((int)$renewal_order_id);
                $order->add_order_note(__('Subscription as Child performed', 'ippgateway'));
                $order->add_order_note(__('Checkout ID: ' . $data_url, 'ippgateway'));
                $order->add_order_note(__('Cryptogram:' . $cryptogram, 'ippgateway'));

                $rebilling_request = [];
                $rebilling_request["method"]        = "card";
                $rebilling_request["checkout_id"]   = $data_url;
                $rebilling_request["reference_transaction_id"] = $transaction_id;
                $rebilling_request["cryptogram"]    = $cryptogram;
                $rebilling_request["cipher"]        = 2022;

                $data = $ipp->rebilling($rebilling_request);

                $order->add_order_note(__(json_encode($rebilling_request), 'ippgateway'));
                $order->add_order_note(__(json_encode($data), 'ippgateway'));


            } else {
                $order->add_order_note(__('Subscription tried renewed less than 180 seconds ago', 'ippgateway'));
            }

        }

        private function get_subscription($order)
        {
            if (!function_exists('wcs_get_subscriptions_for_renewal_order')) {
                return null;
            }
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order);
            return end($subscriptions);
        }
        private function is_woocommerce_3()
        {
            return version_compare(WC()->version, '3.0', 'ge');
        }


        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            /*
            // Mark as on-hold (we're awaiting the payment)

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();
            */

            include(plugin_dir_path( __FILE__ )."classes/IPPGateway.php");
            $ipp = new IPPGateway($this->merchant_id,$this->payment_key);

            $data   = [];
            $data["currency"] = "EUR";
            $data["amount"] = 800;
            $data["order_id"] = 123;
            $data["transaction_type"] = "ECOM";
            $data["ipn"] = add_query_arg('wooorderid', $order_id, add_query_arg('wc-api', 'ippgateway', $this->get_return_url($order)));
            $data["accepturl"] = $this->get_return_url($order);

            $order_item = $order->get_items();
            $data['total_products'] = count($order_item);
            $i = 1;
            $data["product"] = [];
            foreach ($order_item as $product) {
                $data["product"][$i . "_id"] = $product["product_id"];
                $data["product"][$i . "_name"] = $product["name"];
                $data["product"][$i . "_qty"] = $product["qty"];
                $data["product"][$i . "_total"] = $product["line_total"];
                $data["product"][$i . "_tax"] = $product["line_tax"];
                $i++;
            }
            $data["billing"] = [];
            $data["billing"]["name"] = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
            $data["billing"]["email"] = $order->get_billing_email();

            if ($this->woocommerce_subscription_plugin_is_active() && wcs_order_contains_subscription($order)) {
                $data['rebilling'] = 1;
            }

            $data = $ipp->checkout_id($data);
            $data_url = $data->checkout_id;
            $cryptogram = $data->cryptogram;

            return array(
                'result' 	=> 'success',
                'redirect'	=> "/ippgateway?checkout_id=".$data->checkout_id."&cryptogram=".$data->cryptogram
            );
        }
        private function woocommerce_subscription_plugin_is_active()
        {
            return class_exists('WC_Subscriptions') && WC_Subscriptions::$name = 'subscription';
        }

        function add_jscript_checkout() {
            global $wp;
            if ( is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ) {
                wp_enqueue_script( 'js-file', plugin_dir_url( __FILE__ ) . 'assets/ipppay.js');
            }
        }

    } // end \WC_Gateway_IPPGateway class

}
add_filter( 'generate_rewrite_rules', function ( $wp_rewrite ){
    $wp_rewrite->rules = array_merge(
        ['ippgateway/?$' => 'index.php?custom=1'],
        $wp_rewrite->rules
    );
} );
add_filter( 'query_vars', function( $query_vars ){
    $query_vars[] = 'custom';
    return $query_vars;
} );
add_action( 'template_redirect', function(){
    $custom = intval( get_query_var( 'custom' ) );
    if ( $custom ) {
        include plugin_dir_path( __FILE__ ) . 'templates/public.php';
        die;
    }
} );
