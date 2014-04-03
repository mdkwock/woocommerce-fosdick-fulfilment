<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Plugin Name: Woocommerce Fosdick Fulfilment
 * Plugin URI: https://github.com/mdkwock/woocommerce-fosdick-fulfilment
 * Description: Matthew Kwock's integration of Fosdick with Woocommerce
 * Version: 1.0
 * Author: Matthew Kwock
 * Author URI: http://mattkwock.com
 *
 */
add_action( 'plugins_loaded', 'init_fosdick_integration_class' );

function init_fosdick_integration_class() {
    class WC_Gateway_Fosdick extends WC_Payment_Gateway {
        /*  All the data needs to be passed
        'ClientCode'        => $clientcode,
        'Subtotal'          => $subtotal,
        'Postage'           => $postage,
        'ShippingMethod'    => $shippingmethod,
        'Tax'               => $tax,
        'Total'             => $total,
        'ExternalId'        => $externalid,
        'OrderDate'         => $orderdate,
        'AdCode'            => $adcode,
        'SourceCode'        => $sourcecode,
        'Test'              => $test,
        'MxSubtotal'        => $mxsubtotal,
        'MxTax'             => $mxtax,
        'MxTotal'           => $mxtotal,
        'CCType'            => $cctype,
        'CCNumber'          => $ccnumber,
        'CCMonth'           => $ccmonth,
        'CCYear'            => $ccyear,
        'CCV'               => $ccv,
        'ShipFirstname'     => $shipfirstname,
        'ShipLastname'      => $shiplastname,
        'ShipAddress1'      => $shipaddress1,
        'ShipAddress2'      => $shipaddress2,
        'ShipCity'          => $shipcity,
        'ShipState'         => $shipstate,
        'ShipStateOther'    => $shipstateother,
        'ShipZip'           => $shipzip,
        'ShipCountry'       => $shipcountry,
        'ShipPhone'         => $shipphone,
        'ShipFax'           => $shipfax,
        'Email'             => $email,
        'UseAsBilling'      => $useasbilling,
        'BillFirstname'     => $billfirstname,
        'BillLastname'      => $billlastname,
        'BillAddress1'      => $billaddress1,
        'BillAddress2'      => $billaddress2,
        'BillCity'          => $billcity,
        'BillState'         => $billstate,
        'BillStateOther'    => $billstateother,
        'BillZip'           => $billzip,
        'BillCountry'       => $billcountry,
        'BillPhone'         => $billphone,
        'Items'             => NUMBER_OF_ITEMS

         */

        private $clientCode;
        private $adCode;
        private $sourceCode;
        private $test;
        private $postURL;

        /**
         * make the object and set the admin defined variables (site settings).
         * Required by woocommerce
         */
        public function __construct(){
            $this->id = "fosdick_integration";// Unique ID for your gateway. e.g. ‘your_gateway’
            $this->icon  =""; //– If you want to show an image next to the gateway’s name on the frontend, enter a URL to an image.
            $this->has_fields = true; // – Bool. Can be set to true if you want payment fields to show on the checkout (if doing a direct integration).
            $this->method_title = "Fosdick Integration"; //– Title of the payment method shown on the admin page.
            $this->method_description = "Matthew Kwock's WooCommerce Integration with Fosdick Fulfilment"; // – Description for the payment method shown on the admin page.

            //load settings
            $this->init_form_fields();
            $this->init_settings();

            //get admin set variables
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->clientCode = $this->get_option('client_code');
            $this->sourceCode = $this->get_option('source_code');
            $this->adCode = $this->get_option('ad_code');
            $this->test = $this->get_option('test')=="no"?'N':'Y';
            $this->postURL = $this->get_option('postURL');

            $this->has_fields = true;

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * create the admin settings form.
         * Required by woocommerce
         * 
         */
        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title' =>'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable Fosdick Integration',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Fosdick Integration',
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => 'Customer Message',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'type' => 'textarea',
                    'default' => ''
                ),
                'description' => array(
                    'title' => 'Fosdick Endpoint URL',
                    'description' => 'The URL that fosdick gave to send payments to',
                    'type' => 'text',
                    'default' => 'https://www.unitycart.com/ipmax/cart/ipost.asp'
                ),
                'client_code' => array(
                    'title' => 'Client Code',
                    'type' => 'text',
                    'description' => 'Your Fosdick Account\'s Client Code.',
                    'default' => '',
                    'desc_tip'      => true,
                ),
                'source_code' => array(
                    'title' => 'Source Code',
                    'type' => 'text',
                    'description' => 'This store\'s Fosdick Source Code.',
                    'default' => '',
                    'desc_tip'      => true,
                ),
                'ad_code' => array(
                    'title' => 'Ad Code',
                    'type' => 'text',
                    'description' => 'The Ad Code.',
                    'default' => '',
                    'desc_tip'      => true,
                ),
                'test' => array(
                    'title' => 'Test Mode',
                    'type' => 'checkbox',
                    'label' => 'Enable Test Mode',
                    'default' => 'no'
                )
            );
        }

        /**
         * the payment form fields displayed to the customer.
         * this is the form that is displayed on the front end
         * Will make a real template later, for simplicitiy made in here
         */
        public function payment_fields(){
            echo "<label for='cc_type'>Credit Card Type </label><select id='cc_type' name='cc_type'><option value='1_Visa'>Visa</option><option value='2_MasterCard'>Mastercard</option><option value='3_American Express'>American Express</option><option value='4_Discover'>Discover</option></select><br />";
            echo "<label for='cc_num'>Card Number </label><input type='text' id='cc_num' name='cc_num' maxlength='16' /><br />";
            echo "<label>Expiration Date</label>";
            echo "<select name='exp_month' id='exp_month'><option value='1'>1</option><option value='2'>2</option><option value='3'>3</option><option value='4'>4</option><option value='5'>5</option><option value='6'>6</option><option value='7'>7</option><option value='8'>8</option><option value='9'>9</option><option value='10'>10</option><option value='11'>11</option><option value='12'>12</option></select>";
            echo "<select name='exp_year' id='exp_year'>";
            for($i=0;$i<11;$i++){
                echo "<option value='".date('Y',strtotime("+".$i." years"))."'>".date('Y',strtotime("+".$i." years"))."</option>";
            }
            echo "</select><br />";
            echo "<label for='cvv'>Card Security Code (CVV) </label><input type='text' name='cvv' id='cvv' maxlength='4' />";
        }

        /**
         * validate the payment fields, make sure everything is there.
         * returns true if all is good, false if there is an error
         * @return boolean   whether or not the form has all the required values
         */
        public function validate_fields(){
            global $woocommerce;
            if(empty($_POST['cc_num'])){
                $woocommerce->add_error(__('Payment error:', 'woothemes') . " Credit Card Number Must be Filled in.");
                return false;
            }
            if(empty($_POST['exp_month'])||empty($_POST['exp_year'])){
                $woocommerce->add_error(__('Payment error:', 'woothemes') . " Expiration Date Must be Filled in.");
                return false;
            }
            if(empty($_POST['cvv'])){
                $woocommerce->add_error(__('Payment error:', 'woothemes') . " CVV Must be Filled in.");
                return false;
            }
            return true;
        }

        /**
         * process payment call.
         * Required by woocommerce
         * @param  int  $order_id    the order id that we are processing
         * return  string[]    returns the result and the redirect if all is good
         */
        public function process_payment( $order_id ){
            global $woocommerce;
            $order = new WC_Order( $order_id );

            $query = $this->buildQuery($order,$this->getPaymentDetails($_POST));

            $postURL = $this->postURL;

            $response = $this->postRequest($this->postURL, $query);
            if(stripos($response, "false")!==false||stripos($response, "invalid")!==false){
                //if there is a false or invalid message
                $woocommerce->add_error(__('Payment error:', 'woothemes') . $response);
                return;
            }else{
                //all good
                $order->payment_complete();
            }

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }

        /**
         * takes the formatted strings and tries to make numbers out of them
         * @param  string[]  the totals
         * @return  float[]   hopefully the correct numbers
         */
        private function parseTotals($totals){
            $result = array();
            foreach($totals as $key=>$val){
                $v = $val['value'];//get the value
                $numb = array();
                $pattern = "/\\\$\d*?</";
                $start = stripos($v, '&#36;')+5;
                $end = stripos($v, "<",$start);
                $str = substr($v, $start,$end-$start);
                $result[$key] = floatval($str);
            }
            return $result;
        }

        /**
         * organizes the payment detail into a nice array
         * @param  string[]   $postData  the customer completed paymen form
         * @return string[]              the payment details that we care about for processing the payment
         */
        private function getPaymentDetails($postData){
            $results = array("cctype"=>$postData['cc_type'],"ccnum"=>$postData['cc_num'],"ccmonth"=>$postData['exp_month'],"ccyear"=>$postData['exp_year'],"cvv"=>$postData['cvv']);
            return $results;
        }

        /**
         * Get and format the order item attributes.
         * this gets things like the variation id, qty.... that we need for fosdick
         * @param   array $orderItems  the items being ordered
         * @return  array              the items, with their attributes that we care about
         */
        private function get_item_attrs($orderItems){
            $results = array();
            foreach($orderItems as $i){
                $prod = get_product($i['item_meta']['_product_id'][0]);
                $qty = $i['item_meta']['_qty'][0];
                if(empty($i['item_meta']['_variation_id'][0])){
                    //if it is a normal product
                    $sku = $prod->get_sku();
                    $price = $prod->get_price_excluding_tax();
                }else{
                    //else its a variable product
                    foreach ( $prod->get_children() as $child_id ) {
                        $v = $prod->get_child( $child_id );
                        if($v->variation_id != $i['item_meta']['_variation_id'][0]){
                            continue;
                        }
                        $sku = $v->get_sku();
                        $price = $v->get_price_excluding_tax();
                        break;
                    }
                }
                $results[] = array("sku"=>$sku,"qty"=>$qty,"price"=>$price);
            }
            return $results;
        }

        /**
         * builds the query that we will send to fosdick.
         * 
         * @param  WC_Order  $order          the order being submitted
         * @param  string[]  $paymentDetails the details of the customer payment method
         * @return string                    the encoded payment ready to be sent to fosdick
         */
        private function buildQuery(WC_Order $order,$paymentDetails){
            $clientcode = $this->clientCode;
            $adCode = $this->adCode;
            $sourceCode = $this->sourceCode;
            $test = $this->test;
            $externalid='';
            $shipstateother = '';
            $shippingmethod = '';
            $billstateother = '';
            $shipfax = '';
            $useasbilling = 'n';
            $orderdate = '';
            $totals = $this->parseTotals($order->get_order_item_totals());
            $items = $this->get_item_attrs($order->get_items());
            $querydata = array(
                'ClientCode'        => $clientcode,
                'Subtotal'          => number_format($totals['cart_subtotal'],2,'.',''),
                'Postage'           => number_format($totals['shipping'],2,'.',''),
                'ShippingMethod'    => $shippingmethod,
                'Tax'               => number_format($order->get_total_tax(),2,'.',''),
                'Total'             => number_format($totals['order_total'],2,'.',''),
                'ExternalId'        => $order->id,
                'OrderDate'         => $orderdate,
                'AdCode'            => $adCode,
                'SourceCode'        => $sourceCode,
                'Test'              => $test,
                'MxSubtotal'        => number_format($totals['cart_subtotal'],2,'.',''),
                'MxTax'             => number_format($order->get_total_tax(),2,'.',''),
                'MxTotal'           => number_format($totals['order_total'],2,'.',''),
                'CCType'            => $paymentDetails['cctype'],
                'CCNumber'          => $paymentDetails['ccnum'],
                'CCMonth'           => $paymentDetails['ccmonth'],
                'CCYear'            => $paymentDetails['ccyear'],
                'CCV'               => $paymentDetails['cvv'],
                'ShipFirstname'     => $order->shipping_first_name,
                'ShipLastname'      => $order->shipping_last_name,
                'ShipAddress1'      => $order->shipping_address_1,
                'ShipAddress2'      => $order->shipping_address_2,
                'ShipCity'          => $order->shipping_city,
                'ShipState'         => $order->shipping_state,
                'ShipStateOther'    => $shipstateother,
                'ShipZip'           => $order->shipping_postcode,
                'ShipCountry'       => $order->shipping_country=="US"?"United States":$order->shipping_country,
                'ShipPhone'         => $order->billing_phone,
                'ShipFax'           => $shipfax,
                'Email'             => $order->billing_email,
                'UseAsBilling'      => $useasbilling,
                'BillFirstname'     => $order->billing_first_name,
                'BillLastname'      => $order->billing_last_name,
                'BillAddress1'      => $order->billing_address_1,
                'BillAddress2'      => $order->billing_address_2,
                'BillCity'          => $order->billing_city,
                'BillState'         => $order->billing_state,
                'BillStateOther'    => $billstateother,
                'BillZip'           => $order->billing_postcode,
                'BillCountry'       => $order->billing_country=="US"?"United States":$order->billing_country,
                'BillPhone'         => $order->billing_phone,
                'Items'             => count($items)
            );
            //items is an associative array, so you can't use items[$i]
            for($i=1;$i<count($items)+1;$i++){
                $item = current($items);
                $querydata["Inv".$i] = $item['sku'];
                $querydata["Qty".$i] = $item['qty'];
                $querydata["PricePer".$i] = number_format($item['price'],2,'.','');
                $querydata["NumOfPayments".$i] = 1;
                //$querydata["MxPricePer".$i] = number_format($item['price'],2,'.','');
                next($items);
            }
            $encodedquery = http_build_query($querydata, '', '&');
            return $encodedquery;
        }

        /**
         * send the built request to fosdick.
         * returns true if success, false if not
         * 
         * @param  string    $url              fosdick's endpoint
         * @param  string[]  $data             the encoded query for fosdick
         * @param  string[] $optional_headers  optional headers, not sure if ever needed
         * @return boolean                     the success of the request
         */
        private function postRequest($url, $data, $optional_headers = null)
        {
            $params = array('http' => array(
                      'method' => 'POST',
                      'content' => $data,
                      'Content-Length' => strlen($data)
                    ));

            if ($optional_headers !== null) {
                $params['http']['header'] = $optional_headers;
            }

            $ctx = stream_context_create($params);
            $fp = @fopen($url, 'rb', false, $ctx);
            if (!$fp) {
                $error = "Invalid, Problem with $url, $php_errormsg";
                return $error;
            }

            $response = @stream_get_contents($fp);
            if ($response === false) {
                $error = "Invalid, Problem reading data from $url, $php_errormsg";
                return $error;
            }
            return $response;
        }
    }
}

/**
 * woocommerce method to notify that this is a payment gateway
 * @param string[]     the methods to add (this class)
 */
function add_fosdick_integration_class( $methods ) {
    $methods[] = 'WC_Gateway_Fosdick';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_fosdick_integration_class' );