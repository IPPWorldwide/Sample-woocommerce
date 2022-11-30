<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: IPPGateway Services
 * Plugin URI: https://www.ippeurope.com
 * Description: IPPGateway
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 5.6
 * Author: Mathias Gajhede
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: ippgateway
 * Domain Path: /languages
 *
 * WC requires at least: 3.3
 * WC tested up to: 5.9.0
 */

defined( 'ABSPATH' ) or exit;
define("IPPGATEWAY_DIR",plugin_dir_url( __FILE__ ));


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

register_activation_hook(__FILE__, 'ippgateway_activation');
register_deactivation_hook(__FILE__, 'ippgateway_deactivation');

 function ippgateway_activation() {
     wp_schedule_event(time(), 'hourly', 'ipp_hourly_event');
 }
 function ippgateway_deactivation() {
    wp_clear_scheduled_hook('ipp_hourly_event');
}
add_action('ipp_hourly_event', 'ipp_hourly_transfer_orders');

function ipp_hourly_transfer_orders() {
    $posts = wc_get_orders( array(
        'limit'        => 3000,
        'orderby'      => 'date',
        'order'        => 'DESC',
        'meta_key'     => 'syncronized', // The postmeta key field
        'meta_compare' => 'NOT EXISTS', // The comparison argument
    ) );
    foreach($posts as $order) {
        $settings = get_option( 'woocommerce_ippgateway_gateway_settings' );
        $order_id = $order->get_id();
        $data = [];
        $order_data = [];
        $data["id"] = $settings["merchant_id"];
        $data["key2"] = $settings["payment_key"];
        $data["order_id"] = $order_id;

        $order_data["order_key"] = $order->get_order_key();
        $order_data["formatted_total"] = $order->get_formatted_order_total();
        $order_data["order_tax"] = $order->get_cart_tax();
        $order_data["order_currency"] = $order->get_currency();
        $order_data["order_tax_discount"] = $order->get_discount_tax();
        $order_data["order_discount_total"] = $order->get_discount_total();
        $order_data["order_fees"] = $order->get_fees();
        $order_data["order_shipping_tax"] = $order->get_shipping_tax();
        $order_data["order_shipping_total"] = $order->get_shipping_total();
        $order_data["subtotal"] = $order->get_subtotal();
        $order_data["tax_total"] = $order->get_tax_totals();
        $order_data["taxes"] = $order->get_taxes();
        $order_data["total"] = $order->get_total();
        $order_data["total_tax"] = $order->get_total_tax();
        $order_data["total_refunded"] = $order->get_total_refunded();
        $order_data["tax_refunded"] = $order->get_total_tax_refunded();
        $order_data["shipping_refunded"] = $order->get_total_shipping_refunded();
        $order_data["items_refunded"] = $order->get_item_count_refunded();
        $order_data["qty_refunded"] = $order->get_total_qty_refunded();
        $order_data["date_created"] = $order->get_date_created();
        $order_data["date_modified"] = $order->get_date_modified();
        $order_data["date_completed"] = $order->get_date_completed();
        $order_data["date_paid"] = $order->get_date_paid();
        $order_data["customer_id"] = $order->get_customer_id();
        $order_data["user_id"] = $order->get_user_id();
        $order_data["user_ip"] = $order->get_customer_ip_address();
        $order_data["user_agent"] = $order->get_customer_user_agent();
        $order_data["billing_first_name"] = $order->get_billing_first_name();
        $order_data["billing_last_name"] = $order->get_billing_last_name();
        $order_data["billing_country"] = $order->get_billing_company();
        $order_data["billing_address_1"] = $order->get_billing_address_1();
        $order_data["billing_address_2"] = $order->get_billing_address_2();
        $order_data["billing_city"] = $order->get_billing_city();
        $order_data["billing_state"] = $order->get_billing_state();
        $order_data["billing_postcode"] = $order->get_billing_postcode();
        $order_data["billing_country"] = $order->get_billing_country();
        $order_data["billing_email"] = $order->get_billing_email();
        $order_data["billing_phone"] = $order->get_billing_phone();
        $order_data["shipping_first_name"] = $order->get_shipping_first_name();
        $order_data["shipping_last_name"] = $order->get_shipping_last_name();
        $order_data["shipping_company"] = $order->get_shipping_company();
        $order_data["shipping_address_1"] = $order->get_shipping_address_1();
        $order_data["shipping_address_2"] = $order->get_shipping_address_2();
        $order_data["shipping_city"] = $order->get_shipping_city();
        $order_data["shipping_state"] = $order->get_shipping_state();
        $order_data["shipping_postcode"] = $order->get_shipping_postcode();
        $order_data["shipping_country"] = $order->get_shipping_country();
        $order_data["billing_full_name"] = $order->get_formatted_billing_full_name();
        $order_data["shipping_full_name"] = $order->get_formatted_shipping_full_name();
        $order_data["billing_full_address"] = $order->get_formatted_billing_address();
        $order_data["shipping_full_address"] = $order->get_formatted_shipping_address();

        $order_data["payment_method"] = $order->get_payment_method();
        $order_data["payment_method_title"] = $order->get_payment_method_title();
        $order_data["payment_transaction_id"] = $order->get_transaction_id();

        foreach($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product = $item->get_product();
            $product_name = $item->get_name();
            $quantity = $item->get_quantity();
            $subtotal = $item->get_subtotal();
            $total = $item->get_total();
            $tax = $item->get_subtotal_tax();
            $taxclass = $item->get_tax_class();
            $taxstat = $item->get_tax_status();
            $allmeta = $item->get_meta_data();
            $product_type = $item->get_type();
            $order_data["items"][] = [
                "product_id"    => $product_id,
                "variation_id"  => $variation_id,
                "product"       => $product,
                "product_name"  => $product_name,
                "quantity"      => $quantity,
                "subtotal"      => $subtotal,
                "total"         => $total,
                "tax"           => $tax,
                "taxclass"      => $taxclass,
                "taxstat"       => $taxstat,
                "allmeta"       => $allmeta,
                "product_type"  => $product_type
            ];
        }
        $data["order_data"] = json_encode($order_data, JSON_THROW_ON_ERROR);
        $response = wp_remote_post("https://api.ippeurope.com/company/orders/add_order.php", array(
                'method'      => 'POST',
                'timeout'     => 3,
                'redirection' => 2,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'body'        => $data,
                'cookies'     => array()
            )
        );
        if (!is_wp_error( $response ) ) {
            update_post_meta($order_id, 'syncronized', time());
        }
    }
}
function ippgateway_alternative_payment_methods() {
    $settings = get_option( 'woocommerce_ippgateway_gateway_settings' );
    if(isset($settings["merchant_id"]) && $settings["merchant_id"] !== "" && isset($settings["payment_key"]) && $settings["payment_key"] !== "") {
        include(plugin_dir_path( __FILE__ )."classes/IPPGateway.php");
        $ipp = new IPPGateway($settings["merchant_id"],$settings["payment_key"]);

        $data = $ipp->request("company/payment_methods/index", ["company_id" => $settings["merchant_id"], "key2" => $settings["payment_key"]])->content;
        foreach($data as $value) {
            if(file_exists(__DIR__ . "/extensions/".strtolower(str_replace(" ","_",$value->name)).".php"))
                include(__DIR__ . "/extensions/".strtolower(str_replace(" ","_",$value->name)).".php");
        }
    }
}
ippgateway_alternative_payment_methods();
add_action('woocommerce_order_status_completed', 'ippgateway_order_status_change');

function ippgateway_order_status_change($order_id) {
    $order = new WC_Order((int)$order_id);
    $transaction_id = get_post_meta($order->get_id(), 'Transaction ID', true);
    $transaction_key = get_post_meta($order->get_id(), 'Transaction Key', true);

    $order->add_order_note(__("Process Capture", 'ippgateway'));

    if(strtolower($order->status) === "completed") {
        $settings = get_option( 'woocommerce_ippgateway_gateway_settings' );

        $ipp    = new IPPGateway($settings["merchant_id"],$settings["data_key"]);
        $status = $ipp->request("company/payments/capture/index",[
            "company_id"    => $settings["merchant_id"],
            "key1"          => $settings["payment_key"],
            "transaction_id"=> $transaction_id,
            "transaction_key"=>$transaction_key,
            "amount"         => number_format($order->get_total(),2,"","")

        ]);
        $order->add_order_note(__("Capture performed", 'ippgateway'));
    }

}

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
            $this->data_key  = isset($this->settings["data_key"]) ? $this->settings["data_key"] : "";
            $this->test_mode  = isset($this->settings["test_mode"]) ? $this->settings["test_mode"] : "";

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );


            add_action( 'woocommerce_api_ippgateway', array( $this, 'webhook' ) );

            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            wp_enqueue_script( 'payment-gateway-modalit-file', plugin_dir_url( __FILE__ ) . 'assets/modalit.js',array(),null, true);

            add_action('add_meta_boxes', array(&$this, 'meta_boxes'), 10, 0);

        }
        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = new WC_Order((int)$order_id);
            $transaction_id = get_post_meta($order_id, 'Transaction ID', true);
            $transaction_key = get_post_meta($order_id, 'Transaction Key', true);

            $order->add_order_note(__("Refund performed", 'ippgateway'));

            $ipp    = new IPPGateway($this->merchant_id,$this->payment_key);
            $status = $ipp->request("company/payments/refund/index",[
                "company_id"    => $this->merchant_id,
                "key2"          => $this->data_key,
                "transaction_id"=> $transaction_id,
                "transaction_key"=>$transaction_key,
                "amount"        => $amount,
                "comment"       => $reason
            ]);
            return $status->success;
        }
        public function meta_boxes()
        {
            add_meta_box(
                'related-info',
                __('Related orders', 'wc-gateway-ippgateway'),
                array(&$this, 'meta_box_related'),
                'shop_order',
                'side',
                'high'
            );
        }
        public function meta_box_related()
        {
            global $post;
            $order = new WC_Order($post->ID);
            $transactionId = get_post_meta($order->get_id(), 'Transaction ID', true);
            $transactionKey = get_post_meta($order->get_id(), 'Transaction Key', true);
            if(isset($this->data_key) && $this->data_key !== "") {
                include(plugin_dir_path( __FILE__ )."classes/IPPCompany.php");
                $ipp    = new IPPcompany($this->merchant_id,$this->data_key);
                $related = $ipp->TransactionsRelated($transactionId,"AUTH");
                if((count((array)$related) > 0)) {
                    $action_id = $related[0]->action_id;
                    $transaction_data = $ipp->TransactionsData($action_id)->order_data->related;
                    if(count((array)$transaction_data) > 0) {
                        echo "Other orders from the same customer";
                        echo "<table>";
                            echo "<tr>";
                                echo "<td>";
                                    echo "Order ID";
                                echo "</td>";
                            echo "<tr>";
                        foreach($transaction_data as $value) {
                            if(get_post_type($value->readable_order_id) == "shop_order")
                            {
                                echo "<tr>";
                                    echo "<td>";
                                        echo "<a href='post.php?post=".$value->readable_order_id."&action=edit'>" . $value->readable_order_id . "</a>";
                                    echo "</td>";
                                echo "<tr>";
                            }
                        }
                        echo "</table>";
                    }
                }
            }
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

                'test_mode' => array(
                    'title'       => __( 'Test Mode', 'wc-gateway-ipp_hosted_payment' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Sets Webshop for Test Mode', 'wc-gateway-ipp_hosted_payment' ),
                    'default'     => __( '', 'wc-gateway-ipp_hosted_payment' ),
                    'desc_tip'    => true,
                ),

                'merchant_id' => array(
                    'title'       => __( 'Merchant ID', 'wc-gateway-ippgateway' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-ippgateway' ),
                    'default'     => __( '', 'wc-gateway-ippgateway' ),
                    'desc_tip'    => true,
                ),

                'data_key' => array(
                    'title'       => __( 'Key 1 (API Key)', 'wc-gateway-ippgateway' ),
                    'type'        => 'text',
                    'description' => __( 'Key 1 (Data Key) to be find in your Merchant Portal', 'wc-gateway-ippgateway' ),
                    'default'     => __( '', 'wc-gateway-ippgateway' ),
                    'desc_tip'    => true,
                ),

                'payment_key' => array(
                    'title'       => __( 'Key 2 (Payment Key)', 'wc-gateway-ippgateway' ),
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
                $order->set_transaction_id( $posted["transaction_id"] );
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
                $data["amount"] = number_format($amount,2,"","");
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
            $data["currency"] = $order->get_currency();
            $data["amount"] = number_format($order->get_total(),2,"","");
            if($this->test_mode == "yes")
                $data["test"] = "true";
            $data["order_id"] = $order_id;
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
            $nonce = wp_create_nonce( 'catalog_nonce' );

            return array(
                'result' 	=> 'success',
                'redirect'	=> "#checkout_id=".$data->checkout_id."&cryptogram=".$data->cryptogram."&nonce=".$nonce
            );
        }
        private function woocommerce_subscription_plugin_is_active()
        {
            return class_exists('WC_Subscriptions') && WC_Subscriptions::$name = 'subscription';
        }

    }
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
