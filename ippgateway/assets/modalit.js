function ipp_modalit() {
    console.log("Modal it was called");
}
function loadCSS() {
    let pluginUrl = "../wp-content/plugins/ippgateway/assets/";

    var head = document.getElementsByTagName('head')[0]

    // Creating link element
    var style = document.createElement('link')
    style.href = pluginUrl + 'ipppay.css'
    style.type = 'text/css'
    style.rel = 'stylesheet'
    head.append(style);
}

window.addEventListener('popstate', function (event) {
    let searchParams = new URLSearchParams(window.location.hash.replace("#","?"));
    let checkout_id = searchParams.get("checkout_id");
    let cryptogram = searchParams.get("cryptogram");
    let nonce = searchParams.get("nonce");

    let html = "<div class='paymentBackground'></div><form role=\"form\" action=\"#\" class=\"paymentWidgets\" data-brands=\"VISA MASTER\" data-theme=\"divs\" method=\"post\">\n" +
        "                <input type=\"hidden\" name=\"catalog_nonce\" value=\"" + nonce + "\"/>\n" +
        "                <input type=\"hidden\" name=\"action\" value=\"ippgateway_proceess_payment\" />\n" +
        "            </form>";

    jQuery("#page").prepend(html);
    jQuery(".blockUI").remove();
    var script = document.createElement("script");
    script.src = "https://pay.ippeurope.com/pay.js?cryptogram=" + cryptogram + "&checkout_id=" + checkout_id;
    document.documentElement.firstChild.appendChild(script);

//    var script = document.createElement("script");
//    script.src = "../wp-content/plugins/ippgateway/assets/payment.js?cryptogram=" + cryptogram + "&checkout_id=" + checkout_id;
//    document.documentElement.firstChild.appendChild(script);

    loadCSS();
});
jQuery("body").on("click", ".paymentBackground", function() {
    console.log("Yes!");
    jQuery(".paymentWidgets").remove();
    jQuery(".paymentBackground").remove();
});