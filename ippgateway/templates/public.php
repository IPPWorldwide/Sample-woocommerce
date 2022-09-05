<?php

$action = admin_url( 'admin-post.php' );
wp_enqueue_script( 'payment-gateway-file', add_query_arg(array("cryptogram" => $_GET['cryptogram'], "checkoutId" => $_GET['checkout_id']), 'https://pay.ippeurope.com/pay.js'),array(),null, true);
wp_register_style("payment-gateway-file", IPPGATEWAY_DIR . "/assets/ipppay.css", '', '1.0.0');
wp_enqueue_style('payment-gateway-file');
echo get_header();
echo '
<script>
    var payment_settings = {
        "payw_failed_payment"       :   "Payment failed. Please try again.",
        "payw_cardholder"           :   "Card holder",
        "payw_cardno"               :   "Card number",
        "payw_expmonth"             :   "Expmonth",
        "payw_expyear"              :   "Expyear",
        "payw_cvv"                  :   "CVV",
        "payw_confirmPayment"       :   "Knap",
        "payw_confirmPayment_btn"   :   "Confirm Payment",
        "waiting_icon"              :   "https://icon-library.com/images/loading-icon-animated-gif/loading-icon-animated-gif-7.jpg",
    };
</script>
    <div id="paymentForm">
        <div class="form">
            <h1>Payment Details</h1>
            <form role="form" action="#" class="paymentWidgets" data-brands="VISA MASTER" data-theme="divs" method="post">
                <input type="hidden" name="catalog_nonce" value="'.wp_create_nonce( 'catalog_nonce' ).'"/>
                <input type="hidden" name="action" value="ippgateway_proceess_payment" />
            </form>    
        </div>
    </div>
';
echo get_footer();