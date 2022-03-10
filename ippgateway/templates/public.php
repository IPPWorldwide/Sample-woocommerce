<?php

$action = admin_url( 'admin-post.php' );
wp_enqueue_script( 'payment-gateway-file', add_query_arg(array("cryptogram" => $_GET['cryptogram'], "checkoutId" => $_GET['checkout_id']), 'https://pay.ippeurope.com/pay.js'),array(),null, true);
//wp_enqueue_script( 'payment-gateway-file', add_query_arg(array("cryptogram" => $cryptogram), plugin_dir_url( __FILE__ ) . '../assets/pay.js'),array(),null, true);

echo get_header();
echo '
    <form role="form" action="'.admin_url( 'admin-post.php' ).'" class="paymentWidgets" data-brands="VISA MASTER" data-theme="divs" method="post">
        <input type="hidden" name="catalog_nonce" value="'.wp_create_nonce( 'catalog_nonce' ).'"/>
        <input type="hidden" name="action" value="ippgateway_proceess_payment" />
    </form>
';
echo get_footer();