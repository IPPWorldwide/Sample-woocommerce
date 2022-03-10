jQuery(".payment_method_ippgateway_gateway").append("<h2>IPP Fields</h2>");

jQuery(".checkout.woocommerce-checkout").on("submit", function(e) {
    e.preventDefault();
    alert("Yes!");
});