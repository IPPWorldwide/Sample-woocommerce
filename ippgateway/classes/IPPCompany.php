<?php
defined( 'ABSPATH' ) or exit;

class IPPCompany {

    private $company_id;
    private $data_key;

    function __construct($company_id = "",$data_key = "") {
        if($company_id !== "") $this->company_id = $company_id;
        if($data_key !== "") $this->data_key = $data_key;
    }

    public function SubscriptionsList($result = "ALL") {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key, "result" => $result];
        return $this->curl("https://api.ippeurope.com/company/cards/stored/", "POST", [], $data)->content;
    }

    public function TransactionsList($list_type,$result,$payment_start,$payment_end) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key, "type" => $list_type, "result" => $result,"payment_earliest" => (strtotime($payment_start)-$_COOKIE["timezone"]),"payment_latest"=>(strtotime($payment_end)-$_COOKIE["timezone"])];
        return $this->curl("https://api.ippeurope.com/company/payments/list/", "POST", [], $data)->content;
    }
    public function TransactionsData($action_id) {
        $data = ["action_id" => $action_id,"company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/payments/", "POST", [], $data)->content;
    }
    public function TransactionsRelated($transaction_id,$method) {
        $data = ["transaction_id" => $transaction_id,"company_id" => $this->company_id, "key1" => $this->data_key, "method"=>$method];
        return $this->curl("https://api.ippeurope.com/company/payments/related/", "POST", [], $data)->content;
    }
    public function TransactionsAction($action,$transaction_id,$action_id,$amount = 0) {
        global $IPP_CONFIG;
        if((isset($IPP_CONFIG["PORTAL_LOCAL_DEACTIVATE_VOID"]) && $IPP_CONFIG["PORTAL_LOCAL_DEACTIVATE_VOID"] === "1" && $action === "void") || (isset($IPP_CONFIG["PORTAL_LOCAL_DEACTIVATE_REFUND"]) && $IPP_CONFIG["PORTAL_LOCAL_DEACTIVATE_REFUND"] === "1" && $action === "refund"))
            return false;
        $data = ["action" => $action,"transaction_id" => $transaction_id,"action_id"=>$action_id,"amount" => $amount,"company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/payments/$action/", "POST", [], $data);
    }

    public function Charts() {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/charts/", "POST", [], $data)->content;
    }


    public function MerchantData($data = []) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/data/", "POST", [], $data)->content;
    }
    public function MerchantDataUpdate($all_data = []) {
        $security_data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        $security_data["id"] = $all_data["id"];
        $security_data["field"] = "security";
        $security_data["value"] = $all_data["security"];
        $this->curl("https://api.ippeurope.com/company/data/update", "POST", [], $security_data);

        $meta_data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        $meta_data["id"] = $all_data["id"];
        $meta_data["field"] = "meta";
        $meta_data["value"] = $all_data["meta"];
        return $this->curl("https://api.ippeurope.com/company/data/update.php", "POST", [], $meta_data)->content;
    }
    public function MerchantAcquirerUpdate($acquirer_id,$settings = []) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        $data["acquirer_id"] = $acquirer_id;
        $data["settings"] = $settings;
        return $this->curl("https://api.ippeurope.com/company/acquirer/data/update.php", "POST", [], $data)->content;
    }

    public function SendPaymentLink($sender,$recipient,$expiry_time,$order_id,$amount,$currency) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        $data["url"]        = $_ENV["PORTAL_URL"];
        $data["sender"]        = $sender;
        $data["recipient"]        = $recipient;
        $data["expiry_time"]        = strtotime($expiry_time);
        $data["order_id"]        = $order_id;
        $data["amount"]        = $amount;
        $data["currency"]        = $currency;
        return $this->curl("https://api.ippeurope.com/company/payments/links/create/", "POST", [], $data);
    }

    public function InvoiceData($invoice_id) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key,"id" => $invoice_id];
        return $this->curl("https://api.ippeurope.com/company/invoice/", "POST", [], $data)->content;
    }

    public function AddUser($all_data = []) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        $data = array_merge($all_data, $data);
        return $this->curl("https://api.ippeurope.com/company/users/add/", "POST", [], $data);
    }
    public function CloseUser($update_company_id) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key, "update_company_id" => $update_company_id];
        return $this->curl("https://api.ippeurope.com/company/users/close/", "POST", [], $data);
    }
    public function ResetUserPassword($update_company_id,$password) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key, "update_company_id" => $update_company_id, "password" => $password];
        return $this->curl("https://api.ippeurope.com/company/users/password/reset/", "POST", [], $data);
    }
    public function UserData($merchant_id) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key,"company_id" => $merchant_id];
        return $this->curl("https://api.ippeurope.com/company/users/data/", "POST", [], $data)->content;
    }
    public function RequestResetUserPassword($partner_id,$email, $portal) {
        $data = ["partner_id" => $partner_id,"email" => $email,"portal" => $portal];
        return $this->curl("https://api.ippeurope.com/company/users/password/request/", "POST", [], $data);
    }
    public function ConfirmResetUserPassword($partner_id,$company_id,$initialization_time,$hash) {
        $data = ["partner_id" => $partner_id,"company_id" => $company_id,"initialization_time" => $initialization_time,"hash" => $hash];
        return $this->curl("https://api.ippeurope.com/company/users/password/request/confirm.php", "POST", [], $data);
    }

    public function DisputesData($dispute_id) {
        $data = ["dispute_id" => $dispute_id,"company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/payments/disputes/data/", "POST", [], $data)->content;
    }
    public function DisputesUpload($dispute_id,$type,$file) {
        $data = ["dispute_id" => $dispute_id,"company_id" => $this->company_id, "key1" => $this->data_key,"type" => $type];
        return $this->curl("https://api.ippeurope.com/company/payments/disputes/upload/", "POST", [], $data, [],$file)->content;
    }
    public function DisputesRelated($transaction_id) {
        $data = ["transaction_id" => $transaction_id,"company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/payments/disputes/related/", "POST", [], $data)->content;
    }

    public function Search($search_term) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key,"search" => $search_term];
        return $this->curl("https://api.ippeurope.com/company/search/", "POST", [], $data)->content;
    }

    public function InstallPlugin($company_id,$slug) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key,"plugin_slug"=>$slug];
        $install = $this->curl("https://api.ippeurope.com/company/plugins/add/", "POST", [], $data)->content;
        require_once BASEDIR . "plugins/".$slug."/init.php";
        $new_pugin = new $slug();

        $standard_configs = $new_pugin->getStandardConfigs($slug);
        $std_settings = [];
        foreach($standard_configs as $value)
            $std_settings[$value["name"]] = $value["standard"];

        $myfile = fopen(BASEDIR . "plugins/".$slug."/".$company_id."_settings.php", "w") or die("Unable to open file!");
        $txt = "<?php\n";
        $txt .= "\$settings[\"plugin_id\"] = '" . $install->plugin_id . "';\n";
        foreach($std_settings as $key=>$value) {
            $txt .= "\$settings[\"".$key."\"] = '" . $value . "';\n";
        }
        fwrite($myfile, $txt);
        fclose($myfile);
        return $install;
    }
    public function UpdatePluginSettings($plugin_id,$key,$value) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key,"plugin_id"=>$plugin_id,"key" => $key,"value"=>$value];
        return $this->curl("https://api.ippeurope.com/company/plugins/update/", "POST", [], $data);
    }
    public function RemovePlugin($company_id,$id,$slug) {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key,"plugin_id"=>$id,"plugin_slug"=>$slug];
        $remove = $this->curl("https://api.ippeurope.com/company/plugins/close/", "POST", [], $data)->content;
        
        
    }

    public function ListPayouts() {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/payouts/list/", "POST", [], $data)->content;
    }
    public function ListDisputes($state = "ALL", $status = "ALL") {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key, "state" => $state, "status" => $status];
        return $this->curl("https://api.ippeurope.com/company/payments/disputes/list/", "POST", [], $data)->content;
    }
    public function ListUsers() {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/users/list/", "POST", [], $data)->content;
    }
    public function ListInvoices() {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/invoice/list/", "POST", [], $data)->content;
    }
    public function ListPaymentLinks() {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/payments/links/list/", "POST", [], $data)->content;
    }
    public function ListVersions() {
        return $this->curl("https://api.ippeurope.com/versions.php")->content->versions;
    }
    public function ListPlugins() {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/plugins/", "POST", [], $data)->content;
    }

    public function version() {
        if(!isset($_ENV["GLOBAL_BASE_URL"]))
            $_ENV["GLOBAL_BASE_URL"] = "https://api.ippeurope.com";
        return $this->curl("https://api.ippeurope.com/version.php");
    }

    public function GetAllAccessRights()
    {
        $data = ["company_id" => $this->company_id, "key1" => $this->data_key];
        return $this->curl("https://api.ippeurope.com/company/users/access_policy/list/", "GET", $data);
    }

    public function PageLevelAccess($check_access)
    {
        $logged_in_data = $this->CheckLogin();
        $all_rules = $this->GetAllAccessRights();

        foreach($logged_in_data->content->user->acccess_rights as $idx=>$right){
            if($right === "ALL" OR $all_rules->content->all_rules->{$right}->name === $check_access){
                return true;
            }
        }
        return false;
    }

    public function request($url, $data){
        return $this->curl("https://api.ippeurope.com/".$url, "POST", [], $data);
    }
    private function curl($url, $type = 'POST', $query = [], $data = [], $headers = []){
        $data["id"] = $this->company_id;
        $data["key2"] = $this->data_key;
        $data["origin"] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url?".http_build_query($query, "", "&", PHP_QUERY_RFC3986));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if($type == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (is_array($headers) && sizeof($headers) > 0) {
            curl_setopt($ch, CURLOPT_HEADER, $headers);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        }
        $server_output = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($server_output);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }
        return $json;
    }
}
