<?php
ini_set('display_errors', 0);
require_once('../../../../../wp-load.php');

if (isset($_POST['getPaymentPlan'])):

global $wpsc_cart;

foreach($wpsc_cart->cart_items as $i => $Item) {
 
    if ($Item->cart->tax_percentage > 0){
        $productPrice = $Item->unit_price;
        $tax = $Item->cart->tax_percentage;
    }else{
        $productPrice = round(($Item->total_price - $Item->tax)/$Item->quantity,2);
        $tax = round(($Item->tax/($Item->total_price - $Item->tax))*100,2);
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

//The createOrder Data
$request = Array(
	"request" => Array(
      "Auth" => Array(
        "Username" => get_option('svea_gateway_username'),
        "Password" => get_option('svea_gateway_password'),
        "ClientNumber" => get_option('svea_delbet_client_no')
       ),
      "Amount" => 0,
      "InvoiceRows" => array('ClientInvoiceRowInfo' => $clientInvoiceRows)
    )
);
	
}



if (get_option('svea_testmode') == '1'){
	$svea_server = "https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL";
}else{
	$svea_server = "https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL";
}

//Call Soap
$client = new SoapClient( $svea_server );

 //Make soap call to below method using above data
$svea_req = $client->GetPaymentPlanOptions( $request);



$response = $svea_req->GetPaymentPlanOptionsResult->PaymentPlanOptions;

foreach ($svea_req->GetPaymentPlanOptionsResult->PaymentPlanOptions->PaymentPlanOption as $key => $ss){
	
	if ($ss->ContractLengthInMonths == 3){
		$description = 'Betala om 3 månader';
	}else{
		$description = 'Delbetala på '.$ss->ContractLengthInMonths.' månader, ('.$ss->MonthlyAnnuity.' kr/mån, eff. ränta: '.round($ss->EffectiveInterestRatePercent).'%)';
	}
	echo '
		jQuery("#svea_payment_options").append("<option id=\"paymentOption'.$key.'\" value=\"'.$ss->CampainCode.'\">'.$description.'</option>");
	';
}

endif;

if (isset($_POST['pnr'])):

class Validate{

	private function luhn($ssn){
		$sum = 0;
		for ($i = 0; $i < strlen($ssn)-1; $i++){
			$tmp = substr($ssn, $i, 1) * (2 - ($i & 1)); //växla mellan 212121212
			if ($tmp > 9) $tmp -= 9;
			$sum += $tmp;
		}
	 
		//extrahera en-talet
		$sum = (10 - ($sum % 10)) % 10;
		return substr($ssn, -1, 1) == $sum;
	}
	
	private function only_numbers($ssn){
		if (is_numeric($ssn)){
			return true;
		}else{
			return false;
		}
	}
	
	private function is_company($company){
		if ($company == "false"){
			return false;
		}else{
			return true;
		}
	}
	
	public function check($ssn,$is_company){
		
		$error_msg = null;
		$company = null;
		
		if ($this->only_numbers($ssn) == false){
			$error_msg = "Persnr/orgnr får endast bestå av siffror";
		}elseif ($this->luhn($ssn) == false){
			$error_msg = "Persnr/orgnr har felaktig kontrollsiffra, vänligen ange ett giltigt nr";
		}
		
		if ($this->is_company($is_company) == true){
			$company = true;
		}else{
			$company = false;
		}
		
		$returns = array("error_msg" => $error_msg, "company" => $company);
		
		return $returns;
	}

}


$v = new validate();
$validation = $v->check($_POST['pnr'], $_POST['is_company']);

$error_msg = $validation['error_msg'];


if ($error_msg == '' || $error_msg == null){



//Call Soap and set up data

if (get_option('svea_testmode') == '1'){
	$svea_server = "https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL";
}else{
	$svea_server = "https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL";
}

$client = new SoapClient( $svea_server );


$request_adress = Array(
	"request" => Array(
      "Auth" => Array(
        "Username" => get_option('svea_gateway_username'),
        "Password" => get_option('svea_gateway_password'),
        "ClientNumber" => get_option('svea_delbet_client_no')
       ),
	  "IsCompany" => '',
	  "CountryCode" => "SE",
	  "SecurityNumber" => $_POST['pnr']
	)
  );
 
  //Handle response
  $response_adress =  $client->GetAddresses( $request_adress );
 
if (isset($response_adress->GetAddressesResult->ErrorMessage)){
	echo 'jQuery("#pers_nr_error_delbet").html("'.$response_adress->GetAddressesResult->ErrorMessage.'");
			jQuery("#persnr_error_tr_delbet").show();';
}elseif(is_array($response_adress->GetAddressesResult->Addresses->CustomerAddress)){
		foreach ($response_adress->GetAddressesResult->Addresses->CustomerAddress as $key => $info){

			$firstName = $info->FirstName;
			$lastName = $info->LastName;
			//$address = $info->AddressLine1." ".$info->AddressLine2;
            $address_1 = (!empty($info->AddressLine1)) ? $info->AddressLine1 : '';
            $address_2 = (!empty($info->AddressLine2)) ? $info->AddressLine2 : '';
            $address = $address_1." ".$address_2;
			$postCode = $info->Postcode;
			$city = $info->Postarea;
			$addressSelector = $info->AddressSelector;
			
			//Send back to user
			echo '
			jQuery("#svea_dd_delbet").show();
			jQuery("#svea_adresser_delbet").append("<option id=\"adress_'.$key.'\" value=\"'.$addressSelector.'\">'.$firstName.' '.$lastName.', '.$address.', '.$postCode.' '.$city.'</option>");
			jQuery(".make_purchase").removeAttr("disabled");
            jQuery("#persnr_error_tr_delbet").hide();
			
			';
		}
}else{
		$firstName = $response_adress->GetAddressesResult->Addresses->CustomerAddress->FirstName;
		$lastName = $response_adress->GetAddressesResult->Addresses->CustomerAddress->LastName;
        $address_1 = (!empty($response_adress->GetAddressesResult->Addresses->CustomerAddress->AddressLine1)) ? $response_adress->GetAddressesResult->Addresses->CustomerAddress->AddressLine1 : '';
        $address_2 = (!empty($response_adress->GetAddressesResult->Addresses->CustomerAddress->AddressLine2)) ? $response_adress->GetAddressesResult->Addresses->CustomerAddress->AddressLine2 : '';
        $address = $address_1." ".$address_2;
		//$address = $response_adress->GetAddressesResult->Addresses->CustomerAddress->AddressLine1." ".$response->GetAddressesResult->Addresses->CustomerAddress->AddressLine2;
		$postCode = $response_adress->GetAddressesResult->Addresses->CustomerAddress->Postcode;
		$city = $response_adress->GetAddressesResult->Addresses->CustomerAddress->Postarea;
		$addressSelector = $response_adress->GetAddressesResult->Addresses->CustomerAddress->AddressSelector;
		
		//Send back to user
		echo '
		jQuery("#svea_dd_delbet").show();
		jQuery("#svea_adresser_delbet").append("<option id=\"adress\" value=\"'.$addressSelector.'\">'.$firstName.' '.$lastName.', '.$address.', '.$postCode.' '.$city.'</option>");
		jQuery(".make_purchase").removeAttr("disabled");
        jQuery("#persnr_error_tr_delbet").hide();
		';	
 
}
  
}else{
	echo 'jQuery("#pers_nr_error_delbet").html("'.$error_msg.'");
			jQuery("#persnr_error_tr_delbet").show();
		 ';

}

endif;