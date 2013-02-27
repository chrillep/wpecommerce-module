<?php

// Setup
$nzshpcrt_gateways[$num]['name'] = 'SVEA Delbetalning';
$nzshpcrt_gateways[$num]['internalname'] = 'svea_delbet_gateway';
$nzshpcrt_gateways[$num]['function'] = 'gateway_svea_delbet_gateway';
$nzshpcrt_gateways[$num]['form'] = "form_svea_delbet_gateway";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_svea_delbet_gateway";


//Validate the part payment amount, If amount is not equal to or more than 1000 SEK, partpay will not be an option for payment
function partPayValid(){
	global $wpsc_cart;
	
	$totalPrice = 0;
	
	foreach($wpsc_cart->cart_items as $d) {
			$totalPrice = $totalPrice + (($d->unit_price + (("0.".$d->cart->tax_percentage)*$d->unit_price))*$d->quantity);
	}
	
    if ($wpsc_cart->selected_shipping_method != ''){
        if (isset($wpsc_cart->shipping_quotes['Local Shipping'])){
            $totalPrice = $totalPrice +  $wpsc_cart->shipping_quotes['Local Shipping'];
        }
        
    }
    
	if ($totalPrice >= 1000){
		return 1;
	}else{ 
		return 0; 
		}

}

//Form to be shown when "SVEA faktura" option is chosen in checkoutpage
$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] .= '
<tr class="wpsc_checkout_field" id="svea_delbet_tr02">
	<td class="wpsc_checkout_field">
		<label for="svea_pnr">Personnr/Org.nr: </label>
	</td>
	<td class="wpsc_checkout_field">
		<input type="text" class="text" id="svea_delbet_pnr" name="svea_delbet_pnr" maxlength="10" />
		<input type="hidden" name="partPayValid" id="partPayValid" value="'.partPayValid().'" />
	</td>
</tr>
<tr class="wpsc_checkout_field" id="persnr_error_tr_delbet">
	<td></td>
	<td><span id="pers_nr_error_delbet"></span></td>
</tr>

<tr id="svea_delbet_tr03">
	<td><img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_delbetala.png" title="SVEA faktura" /></td>
	<td><input type="button" id="uppd_adress_delbet" name="uppd_adress_delbet" value="Hämta adress" /><span id="svea_loading_delbet"></span></td>
</tr>

<tr id="svea_adress_info_text">
	<td colspan="3">
		<h4>Leveransadress:</h4>
	</td>
</tr>

<tr id="svea_payment_info">
	<td>
		Välj betalningsplan:
	</td>
    <td>
        <select name="svea_payment_options" id="svea_payment_options"></select>
    </td>
</tr>

<tr id="svea_dd_delbet">
	<td>
		Faktureringsadress:
	</td>
    <td>
        <select name="svea_adresser_delbet" id="svea_adresser_delbet"></select>
    </td>
</tr>

';


function form_svea_delbet_gateway(){
	
	if (get_option('svea_testmode') == "1"){ $test_check = "checked='checked'";}else{ $test_check = '';}
	
	//Show form for User credentials in admin payment options
	$output ='<tr>';

	$output.='<td><label for="svea_gateway_delbet_username">Användarnamn</label></td>';
	$output.='<td><input name="svea_gateway_delbet_username" type="text" value="'.get_option('svea_gateway_username').'" /></td>';
	$output.= '</tr><tr>';
	
	$output.='<td><label for="svea_password">Lösenord:</label></td>';
	$output.='<td><input name="svea_gateway_delbet_password" type="password" value="'.get_option('svea_gateway_password').'"/></td>';
	
	$output.= '</tr><tr>';
	
	$output.='<td><label for="svea_client_nr">Clientnr:</label></td>';
	$output.='<td><input name="svea_delbet_client_nr" type="text" maxlength="5" value="'.get_option('svea_delbet_client_no').'" /></td>';
	$output .='</tr>';

	$output .= '<tr>
				<td><label for="svea_testmode">Testläge:</label></td>
				<td><input name="svea_testmode" type="checkbox" '. $test_check .' value="1" /></td>
				</tr>';
	
	$output .='<tr>';
	$output.='<td><img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_delbetala.png" title="SVEA Delbetala" /></td>';
	$output.='<td></td>';
	$output.= '</tr><tr>';
	 
	return $output; 
}


function submit_svea_delbet_gateway(){
	global $wpdb;
	
	//Show SVEA logo and text in payment options at the checkout and store new Client username, password and Client no in admin payment options
	$payment_gateway_names = get_option('payment_gateway_names');
	$payment_gateway_names["svea_delbet_gateway"] = 'SVEA Delbetala <img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_delbetala.png" title="SVEA Delbetala" />';
	
	update_option("payment_gateway_names", $payment_gateway_names);
 
	if($_POST['svea_gateway_delbet_username'] != null) {	 
		update_option('svea_gateway_username',
		$_POST['svea_gateway_delbet_username']);	 
	}
	 
	if($_POST['svea_gateway_delbet_password'] != null) {	 
		update_option('svea_gateway_password',
		$_POST['svea_gateway_delbet_password']);	 
	}
	
	if($_POST['svea_delbet_client_nr'] != null) {	 
		update_option('svea_delbet_client_no',
		$_POST['svea_delbet_client_nr']);	 
	}
	
	if($_POST['svea_testmode'] == '1') {	 
		update_option('svea_testmode',
		$_POST['svea_testmode']);	 
	}else{
		update_option('svea_testmode',
		'0');
	}
	 
	return true;
}


function gateway_svea_delbet_gateway($seperator, $sessionid){
 
//$wpdb is the database handle,
//$wpsc_cart is the shopping cart object
 
global $wpdb, $wpsc_cart;
 
//This grabs the purchase log id from the database
//that refers to the $sessionid
 
$purchase_log = $wpdb->get_row(
"SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS.
"` WHERE `sessionid`= ".$sessionid." LIMIT 1"
,ARRAY_A) ;
 

//Set up the main array for the request
$data = array();
 

// Ordered Products
foreach($wpsc_cart->cart_items as $i => $Item) {
    
    if ($wpsc_cart->cart_item->tax_rate > 0){
        $productPrice = round(($Item->total_price - $Item->tax)/$Item->quantity,2);
        $tax = round(($Item->tax/($Item->total_price - $Item->tax))*100,2);
    }else{
        $productPrice = $Item->unit_price;
        $tax = $Item->cart->tax_percentage;
    }

 
if (isset($clientInvoiceRows)){
$clientInvoiceRows[$i] = Array(
          "ClientOrderRowNr" => $i,
          "Description" => $Item->product_name,
          "PricePerUnit" => $productPrice,
          "NrOfUnits" => $Item->quantity,
          "Unit" => "st",
          "VatPercent" => $tax,
          "DiscountPercent" => 0
        );
}else{
$clientInvoiceRows[] = Array(
          "ClientOrderRowNr" => $i,
          "Description" => $Item->product_name,
          "PricePerUnit" => $productPrice,
          "NrOfUnits" => $Item->quantity,
          "Unit" => "st",
          "VatPercent" => $tax,
          "DiscountPercent" => 0
        );
}
}

if ($wpsc_cart->selected_shipping_method != null || $wpsc_cart->selected_shipping_method != ''){
    
    if (isset($wpsc_cart->shipping_quotes['Local Shipping'])){
        $shippingCost = $wpsc_cart->shipping_quotes['Local Shipping'];
        foreach($wpsc_cart->cart_items as $si){
           if (isset($si->shipping)){
                $shippingCost = $shippingCost + $si->shipping;
            } 
        }
    }else if (isset($wpsc_cart->base_shipping)){
        $shippingCost = $wpsc_cart->base_shipping;
        foreach($wpsc_cart->cart_items as $si){
           if (isset($si->shipping)){
                $shippingCost = $shippingCost + $si->shipping;
            } 
        }
    }
    
    if ($wpsc_cart->cart_item->tax_rate > 0){
        $shippingPrice  = $shippingCost /  1.25;
    }else{
        $shippingPrice  = $shippingCost;
    }
    
	$clientInvoiceRows[] = Array(
		  "ClientOrderRowNr" => $i+2,
          "Description" => 'Fraktavgift',
          "PricePerUnit" => $shippingPrice,
          "NrOfUnits" => 1,
          "VatPercent" => 25,
          "DiscountPercent" => 0
	);
}

//Discount Coupon
if ($wpsc_cart->coupons_amount > 0){
    
    if (isset($wpsc_cart->cart_item->tax_rate)){
        $discountAmount  = $wpsc_cart->coupons_amount / 1.25;
    }else{
        $discountAmount  = $wpsc_cart->coupons_amount;
    }
    
	$clientInvoiceRows[] = Array(
          "Description" => $wpsc_cart->coupons_name,
          "PricePerUnit" => -$discountAmount,
          "NrOfUnits" => 1,
          "VatPercent" => 25,
          "DiscountPercent" => 0
	);
}

//The payment plan Data
$request = Array(
		"Auth" => Array(
        "Username" => get_option('svea_gateway_username'),
        "Password" => get_option('svea_gateway_password'),
        "ClientNumber" => get_option('svea_delbet_client_no')
       ),
	 'Amount' => 0,
	'PayPlan' => Array(
		'SendAutomaticGiropaymentForm' => false,
        'ClientPaymentPlanNr' => $Item->cart->log_id,
		'CampainCode' => $_POST['svea_payment_options'],
		'CountryCode' => 'SE',
		'SecurityNumber' => $_POST['svea_delbet_pnr'],
		'IsCompany' => ''
	),
	'InvoiceRows' => Array('ClientInvoiceRowInfo' => $clientInvoiceRows)
    );

//Put all the data in request tag
$data['request'] = $request;


//Check if testmode is checked
$get_testmode = $wpdb->get_results("SELECT option_value FROM wp_options WHERE option_name='svea_testmode'");

if (get_option('svea_testmode') == '1'){
	$svea_server = "https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL";
}else{
	$svea_server = "https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL";
}

//Call Soap
$client = new SoapClient( $svea_server );

 //Make soap call to below method using above data
$svea_req = $client->CreatePaymentPlan( $data );

$response = $svea_req->CreatePaymentPlanResult->RejectionCode;

if($response == 'Accepted'){
 
	//redirect to  transaction page and store in DB as a order with
	//accepted payment
	 
	$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
	"` SET `processed`= '3' WHERE `sessionid`=".$sessionid;
	 
	$wpdb->query($sql);
	 
	$transact_url = get_option('transact_url');
	 
	unset($_SESSION['WpscGatewayErrorMessage']);
	 
	header("Location: ".$transact_url.$seperator."sessionid=".$sessionid);
	
	flush();
	
}else{
 
	//redirect back to checkout page with errors
	 
	$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
	"` SET `processed`= '5' WHERE `sessionid`=".$sessionid;
	 
	$wpdb->query($sql);
	 
	$checkout_url = get_option('shopping_cart_url');
	 
	$_SESSION['WpscGatewayErrorMessage'] =
	__(responseCodesDelbet($response));
    
	header("Location: ".$checkout_url);
 
}

}


//Error Responses
function responseCodesDelbet($err){
        
        switch ($err){
            case "CustomerCreditRejected" :
                return 'Cannot get credit rating information';
                break;
            case "CustomerOverCreditLimit" :
                return 'Store or Sveas credit limit overused';
                break;
            case "CustomerAbuseBlock" :
                return 'This customer is blocked or has shown strange/unusual behavior';
                break;
            case "OrderExpired" :
                return 'The order is too old and can no longer be invoiced against';
                break;
            case "ClientOverCreditLimit" :
                return 'The order would cause the client to exceed Sveas credit limit';
                break;
            case "OrderOverSveaLimit" :
                return 'The order exceeds the highest order amount permitted at Svea';
                break;
            case "OrderOverClientLimit" :
                return 'The order exceeds your highest order amount permitted';
                break;
            case "CustomerSveaRejected" :
                return 'The customer has a poor credit history at Svea';
                break;
            case "CustomerCreditNoSuchEntity" :
                return 'The customer is not listed with the credit limit supplier';
                break;
            case "InvalidCampainCodeAmountCombination":
                return 'Price too low';
                break;
            default:
                return 'Connection to SVEA error';
                break;
            
        }
    }



//Svea class to extend WP e-commerce
class wpsc_merchant_svea_delbet extends wpsc_merchant {
	
	// The action to perform when Hämta adress is clicked on the checkout page
	function visa_form() {

		$outputScriptSvea .= '<style>
			#pers_nr_error_fakt{color:red;}
		</style>';
        
        echo $outputScriptSvea;
        
    }
    
    function svea_scripts(){
        //Latest jQuery
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js');
        wp_enqueue_script( 'jquery' );
        //Include svea script
        wp_enqueue_script('svea', get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/library/svea.js');
    }
	
}


//Add the above to WordPress options on checkout page etc
add_action('wp_enqueue_scripts',  array('wpsc_merchant_svea_delbet','svea_scripts'));
add_action('wpsc_bottom_of_shopping_cart',  array('wpsc_merchant_svea_delbet','visa_form'));
?>