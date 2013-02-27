<?php

// Setup
$nzshpcrt_gateways[$num]['name'] = 'SVEA Kortbetalning';
$nzshpcrt_gateways[$num]['internalname'] = 'svea_card_gateway';
$nzshpcrt_gateways[$num]['function'] = 'gateway_svea_card_gateway';
$nzshpcrt_gateways[$num]['form'] = "form_svea_card_gateway";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_svea_card_gateway";

//Form to be shown when "SVEA faktura" option is chosen in checkoutpage
$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] .= '';


function form_svea_card_gateway(){
	
	if (get_option('svea_testmode') == "1"){ $test_check = "checked='checked'";}else{ $test_check = '';}
	
	//Show form for User credentials in admin payment options
	$output ='<tr>';

	$output.='<td><label for="svea_sw">Hemligt ord:</label></td>';
	$output.='<td><input name="svea_sw" type="password" value="'.get_option('svea_sw').'" /></td>';
	$output.= '</tr><tr>';
	
	$output.='<td><label for="svea_bi">Butiksid:</label></td>';
	$output.='<td><input name="svea_bi" type="text" value="'.get_option('svea_bi').'" /></td>';
	
	$output.= '</tr>';
	
	$output .= '<tr>
				<td><label for="svea_testmode">Testläge:</label></td>
				<td><input name="svea_testmode" type="checkbox" '. $test_check .' value="1" /></td>
				</tr>';
	
	$output .='<tr>';
	$output.='<td><img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_kort.png" title="SVEA kort" /></td>';
	$output.='<td></td>';
	$output.= '</tr><tr>';
	 
	return $output; 
}


function submit_svea_card_gateway(){

	//Show SVEA logo and text in payment options at the checkout and store new Client username, password and Client no in admin payment options
	$payment_gateway_names = get_option('payment_gateway_names');
	$payment_gateway_names["svea_card_gateway"] = 'SVEA Kortbetalning <img src="' . get_option('siteurl') . '/wp-content/plugins/wp-e-commerce/wpsc-merchants/svea_kort.png" title="SVEA kort" />';
	update_option("payment_gateway_names", $payment_gateway_names);
 
	if($_POST['svea_sw'] != null) {	 
		update_option('svea_sw',
		$_POST['svea_sw']);	 
	}
	 
	if($_POST['svea_bi'] != null) {	 
		update_option('svea_bi',
		$_POST['svea_bi']);	 
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


function gateway_svea_card_gateway($seperator, $sessionid){
 
//$wpdb is the database handle,
//$wpsc_cart is the shopping cart object
 
global $wpdb, $wpsc_cart;
 

//Import SVEA files
include('svea/SveaConfig.php');

//Check for testmode
$testMode = get_option('svea_testmode');

//SVEA config settings
$config = SveaConfig::getConfig();
$config->merchantId = get_option('svea_bi'); //Set your merchantid here or directly in SveaConfig::__construct()
$config->secret = get_option('svea_sw'); //Set your merchantid here or directly in SveaConfig::__construct()

 
//This grabs the purchase log id from the database
//that refers to the $sessionid
 
$purchase_log = $wpdb->get_row(
"SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS.
"` WHERE `sessionid`= ".$sessionid." LIMIT 1"
,ARRAY_A) ;


$totalPrice = 0;
$totalTax = 0;

$paymentRequest = new SveaPaymentRequest();
$order = new SveaOrder();
$paymentRequest->order = $order;;

// Ordered Products
foreach($wpsc_cart->cart_items as $i => $Item) {

    if ($wpsc_cart->cart_item->tax_rate > 0){
        $price = $Item->unit_price;
        $tax   = $Item->tax / $Item->quantity;
    }else{
        $tax = ($Item->cart->tax_percentage/100) * $Item->unit_price;
        $price = ($Item->unit_price + $tax);
    }
    
    $totalPrice = $totalPrice+($price * $Item->quantity);
    $totalTax = $totalTax + ($tax * $Item->quantity);
    
    $orderRow = new SveaOrderRow();
    $orderRow->amount = number_format(round($price,2),2,'','');
    $orderRow->vat = number_format(round($tax,2),2,'','');
    $orderRow->name = $Item->product_name;
    $orderRow->quantity = $Item->quantity;
    $orderRow->sku = $Item->sku;
    $orderRow->unit = "st";

	
    //Add the order rows to your order
    $order->addOrderRow($orderRow);
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
        $shippingVAT = $shippingCost * 0.2;
        $shippingPrice  = $shippingCost;
    }else{
        $shippingVAT = $shippingCost * 0.25;
        $shippingPrice  = $shippingCost + $shippingVAT;
    }
       
    $orderRow = new SveaOrderRow();
    $orderRow->amount = number_format(round($shippingPrice,2),2,'','');
    $orderRow->vat = number_format(round($shippingVAT,2),2,'','');
    $orderRow->name = "Fraktavgift";
    $orderRow->quantity = 1;
    $orderRow->unit = "st";

	
    //Add the order rows to your order
    $order->addOrderRow($orderRow);
    
    
    $totalPrice = $totalPrice+$shippingPrice;
    $totalTax = $totalTax + $shippingVAT;
}


//Discount Coupon
if ($wpsc_cart->coupons_amount > 0){

    $discountAmount  = $wpsc_cart->coupons_amount;
    $discountVAT     = $discountAmount * 0.2;
    
    $orderRow = new SveaOrderRow();
    $orderRow->amount = -number_format(round($discountAmount,2),2,'','');
    $orderRow->vat = -number_format(round($discountVAT,2),2,'','');
    $orderRow->name = $wpsc_cart->coupons_name;
    $orderRow->quantity = 1;
    $orderRow->unit = "st";

	
    //Add the order rows to your order
    $order->addOrderRow($orderRow);
    
    
    $totalPrice = $totalPrice-$discountAmount;
    $totalTax = $totalTax - $discountVAT;
}

//Set base data for the order
$order->amount = number_format(round($totalPrice,2),2,'','');
$order->customerRefno = $Item->cart->log_id;
$order->returnUrl = get_option('siteurl')."/wp-content/plugins/wp-e-commerce/wpsc-merchants/library/svea_ajax-red.php?sessionid=".$sessionid; //Make this correct
$order->vat = number_format(round($totalTax,2),2,'','');
$order->currency = "SEK";
$order->paymentMethod = SveaOrder::CARD;

$paymentRequest->createPaymentMessage();

$request = http_build_query($paymentRequest,'','&');

echo '
    <html>
<head>
    <script type="text/javascript">
        function doPost(){
            document.forms[0].submit();
        }
    </script>
</head>
';
echo '<body onload="doPost();">';
if ($testMode == '1'){
    echo $paymentRequest->getPaymentForm(true);
}else{
    echo $paymentRequest->getPaymentForm(false);
}
echo '</body></html>';

exit();
}

//Svea class to extend WP e-commerce
class wpsc_merchant_svea_card extends wpsc_merchant {
	
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
add_action('wp_enqueue_scripts',  array('wpsc_merchant_svea_card','svea_scripts'));
add_action('wpsc_bottom_of_shopping_cart',  array('wpsc_merchant_svea_card','visa_form'));
?>