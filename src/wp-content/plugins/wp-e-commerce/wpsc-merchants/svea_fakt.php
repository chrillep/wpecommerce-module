<?php

// Setup
$nzshpcrt_gateways[$num]['name'] = 'SVEA Faktura';
$nzshpcrt_gateways[$num]['internalname'] = 'svea_gateway';
$nzshpcrt_gateways[$num]['function'] = 'gateway_svea_gateway';
$nzshpcrt_gateways[$num]['form'] = "form_svea_gateway";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_svea_gateway";

//Form to be shown when "SVEA faktura" option is chosen in checkoutpage
$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] .= '
<tr class="wpsc_checkout_field">
	<td></td>
	<td class="wpsc_checkout_field">
        <select name="is_company_fakt" id="is_company_fakt">
            <option value="false">Privat</option>
            <option value="true">Företag</option>
        </select>
    </td>
</tr>
<tr class="wpsc_checkout_field">
	<td class="wpsc_checkout_field">
		<label for="svea_pnr">Personnr/Org.nr: </label>
	</td>
	<td class="wpsc_checkout_field">
		<input type="text" class="text" id="svea_fakt_pnr" name="svea_fakt_pnr" maxlength="10" />
	</td>
</tr>
<tr id="persnr_error_tr_fakt">
	<td></td>
	<td><span id="pers_nr_error_fakt"></span></td>
</tr>

<tr>
	<td></td>
	<td><input type="button" id="uppd_adress_fakt" name="uppd_adress_fakt" value="Hämta adress" /><span id="svea_loading_fakt"></span></td>
</tr>

<tr>
	<td></td>
	<td>Fakturaavgift på '.get_option('svea_fakt_avg').' kr tilkommer</td>
</tr>

<tr id="svea_dd_fakt">
	<td>
		<h4>Faktureringsadress:</h4>
	</td>
    <td>
        <select name="svea_adresser_fakt" id="svea_adresser_fakt"></select>
    </td>
</tr>
';


function form_svea_gateway(){
	
	if (get_option('svea_testmode') == "1"){ $test_check = "checked='checked'";}else{ $test_check = '';}
	
	//Show form for User credentials in admin payment options
	$output ='<tr>';

	$output.='<td><label for="svea_gateway_username">Användarnamn</label></td>';
	$output.='<td><input name="svea_gateway_username" type="text" value="'.get_option('svea_gateway_username').'" /></td>';
	$output.= '</tr><tr>';
	
	$output.='<td><label for="svea_password">Lösenord:</label></td>';
	$output.='<td><input name="svea_gateway_password" type="password" value="'.get_option('svea_gateway_password').'" /></td>';
	
	$output.= '</tr><tr>';
	
	$output.='<td><label for="svea_client_nr">Clientnr:</label></td>';
	$output.='<td><input name="svea_client_nr" type="text" maxlength="5" value="'.get_option('svea_client_no').'" /></td>';	
	$output .='</tr>';
	
	$output .= '<tr>
				<td><label for="svea_testmode">Testläge:</label></td>
				<td><input name="svea_testmode" type="checkbox" '. $test_check .' value="1" /></td>
				</tr>';
	
	$output .='<tr>';
	
	$output .= '<tr>
				<td><label for="svea_faktavgift">Fakturaavgift:</label></td>
				<td><input name="svea_fakt_avg" type="text" value="'. get_option('svea_fakt_avg') .'" />kr</td>
				</tr>';
	
	$output .='<tr>';
	
	$output.='<td><img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_faktura.png" title="SVEA faktura" /></td>';
	$output.='<td></td>';
	$output.= '</tr><tr>';
	 
	return $output; 
}


function submit_svea_gateway(){
	
	//Show SVEA logo and text in payment options at the checkout and store new Client username, password and Client no in admin payment options
	$payment_gateway_names = get_option('payment_gateway_names');
	$payment_gateway_names["svea_gateway"] = 'SVEA Faktura <img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_faktura.png" title="SVEA faktura" />';
	update_option("payment_gateway_names", $payment_gateway_names);
	//$wpdb->insert('wp_wpsc_purchase_statuses', array("id" => 534, "name" => "Order sent", "active" => 1));
 
	if($_POST['svea_gateway_username'] != null) {	 
		update_option('svea_gateway_username',
		$_POST['svea_gateway_username']);	 
	}
	 
	if($_POST['svea_gateway_password'] != null) {	 
		update_option('svea_gateway_password',
		$_POST['svea_gateway_password']);	 
	}
	
	if($_POST['svea_client_nr'] != null) {	 
		update_option('svea_client_no',
		$_POST['svea_client_nr']);	 
	}
	
	if($_POST['svea_fakt_avg'] != null) {	 
		update_option('svea_fakt_avg',
		$_POST['svea_fakt_avg']);	 
	}
	
	if($_POST['svea_testmode'] != null) {	 
		update_option('svea_testmode',
		$_POST['svea_testmode']);	 
	}else{
		update_option('svea_testmode',
		'0');
	}
	 
	return true;
}


function gateway_svea_gateway($seperator, $sessionid){

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
 
 
//If company is chosen
function is_company($is_company){
	if ($is_company == 'true'){
		return true;
	}else{
		return false;
	}
}

$company = is_company($_POST['is_company_fakt']);

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
          "Description" => $Item->product_name,
          "PricePerUnit" => $productPrice,
          "NrOfUnits" => $Item->quantity,
          "Unit" => "st",
          "VatPercent" => ceil($tax),
          "DiscountPercent" => 0
        );
}else{
$clientInvoiceRows[] = Array(
          "Description" => $Item->product_name,
          "PricePerUnit" => $productPrice,
          "NrOfUnits" => $Item->quantity,
          "Unit" => "st",
          "VatPercent" => ceil($tax),
          "DiscountPercent" => 0
        );
}

}



if (get_option('svea_fakt_avg') != null || get_option('svea_fakt_avg') != 0){
    
    if (isset($wpsc_cart->cart_item->tax_rate)){
        $invoicePrice  = get_option('svea_fakt_avg') / 1.25;
    }else{
        $invoicePrice  = get_option('svea_fakt_avg');
    }
    
	$clientInvoiceRows[] = Array(
          "Description" => 'Faktureringsavgift',
          "PricePerUnit" => $invoicePrice,
          "NrOfUnits" => 1,
          "VatPercent" => 25,
          "DiscountPercent" => 0
	);
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


//The createOrder Data
$request = Array(
      "Auth" => Array(
        "Username" => get_option('svea_gateway_username'),
        "Password" => get_option('svea_gateway_password'),
        "ClientNumber" => get_option('svea_client_no')
       ),
      "Order" => Array(
		"ClientOrderNr" => $Item->cart->log_id,
        "CountryCode" => 'SE',
        "SecurityNumber" => $_POST['svea_fakt_pnr'],
        "IsCompany" => $company,
        "OrderDate" => date(c),
		"AddressSelector" => $_POST['svea_adresser'],
        "PreApprovedCustomerId" => 0
      ),
      
      "InvoiceRows" => array('ClientInvoiceRowInfo' => $clientInvoiceRows)
    );



//Put all the data in request tag
$data['request'] = $request;

if (get_option('svea_testmode') == '1'){
	$svea_server = "https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL";
}else{
	$svea_server = "https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL";
}


//Call Soap
$client = new SoapClient( $svea_server );

 //Make soap call to below method using above data
$svea_req = $client->CreateOrder( $data );


/*****
Responsehandling
******/
 
$response = $svea_req->CreateOrderResult->RejectionCode;


if($response == 'Accepted'){
 
	//redirect to  transaction page and store in DB as a order with
	//accepted payment
	 
	$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
	"` SET `processed`= '3' WHERE `sessionid`=".$sessionid;
	 
	$wpdb->query($sql);
	if (get_option('svea_fakt_avg') != null || get_option('svea_fakt_avg') != 0){ 

       if (isset($wpsc_cart->cart_item->tax_rate)){
           $invoiceMoms = get_option('svea_fakt_avg') * 0.2;
           $invoicePris = get_option('svea_fakt_avg');
           $updateTotalQuery = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET totalprice = totalprice + ".$invoicePris." WHERE sessionid =".$sessionid;
       }else{
           $invoiceMoms = get_option('svea_fakt_avg') * 0.25;
           $invoicePris = get_option('svea_fakt_avg')+ $invoiceMoms;
           $updateTotalQuery = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET totalprice = totalprice + ".$invoicePris.", wpec_taxes_total = wpec_taxes_total + ".$invoiceMoms." WHERE sessionid =".$sessionid;
       }   
       
       
	   $sql_inv = "INSERT INTO `".WPSC_TABLE_CART_CONTENTS."` (`id`, `prodid`, `name`, `purchaseid`, `price`, `pnp`, `tax_charged`, `gst`, `quantity`, `donation`, `no_shipping`, `custom_message`, `files`, `meta`) VALUES (NULL, '0', 'Faktureringsavg', '".$wpsc_cart->log_id."', '".get_option('svea_fakt_avg')."', '0.00', '".$invoiceMoms."', '0.00', '1', '0', '0', '', '', NULL);";
	   $wpdb->query($sql_inv);
       
       
       $sql_update_total = $updateTotalQuery;
	   $wpdb->query($sql_update_total);
       
	}
	
	$transact_url = get_option('transact_url');
	 
	unset($_SESSION['WpscGatewayErrorMessage']);
	 
	header("Location: ".$transact_url.$seperator."sessionid=".$sessionid);
	
	flush();
 
}else{
 
	//redirect back to checkout page with errors
	 
	$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
	"` SET `processed`= '1' WHERE `sessionid`=".$sessionid;
	 
	$wpdb->query($sql);
	 
	$checkout_url = get_option('shopping_cart_url');
	 
	$_SESSION['WpscGatewayErrorMessage'] =
	 __(responseCodes($response));
	 
	header("Location: ".$checkout_url);
 
}

}

//Error Responses
function responseCodes($err){
        
        switch ($err){
            case "CusomterCreditRejected" :
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
            
        }
    }


//Svea class to extend WP e-commerce
class wpsc_merchant_svea_fakt extends wpsc_merchant {
	
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
add_action('wp_enqueue_scripts',  array('wpsc_merchant_svea_fakt','svea_scripts'));
add_action('wpsc_bottom_of_shopping_cart',  array('wpsc_merchant_svea_fakt','visa_form'));
?>