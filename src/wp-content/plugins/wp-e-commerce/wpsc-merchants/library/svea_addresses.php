<?php

if (isset($_POST['pnr'])):

require_once('../../../../../wp-load.php');


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

$company = $validation['company'];
$error_msg = $validation['error_msg'];

if ($error_msg == '' || $error_msg == null){


if (get_option('svea_testmode') == '1'){
	$svea_server = "https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL";
}else{
	$svea_server = "https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL";
}

//Call Soap and set up data
$client = new SoapClient( $svea_server );


$request = Array(
	"request" => Array(
      "Auth" => Array(
        "Username" => get_option('svea_gateway_username'),
        "Password" => get_option('svea_gateway_password'),
        "ClientNumber" => get_option('svea_client_no')
       ),
	  "IsCompany" => $company,
	  "CountryCode" => "SE",
	  "SecurityNumber" => $_POST['pnr']
	)
  );
 
  //Handle response
  $response =  $client->GetAddresses( $request );
  
if (isset($response->GetAddressesResult->ErrorMessage)){
	echo 'jQuery("#pers_nr_error_fakt").html("'.$response->GetAddressesResult->ErrorMessage.'");
			jQuery("#persnr_error_tr_fakt").show();';
}elseif(is_array($response->GetAddressesResult->Addresses->CustomerAddress)){
		foreach ($response->GetAddressesResult->Addresses->CustomerAddress as $key => $info){

			//$firstName = $info->FirstName;
			//$lastName = $info->LastName;
            $firstName = ($info->BusinessType == 'Person') ? $info->FirstName : $info->LegalName;
			$lastName = ($info->BusinessType == 'Person') ? $info->LastName : '';
			$address = $info->AddressLine1." ".$info->AddressLine2;
			$postCode = $info->Postcode;
			$city = $info->Postarea;
			$addressSelector = $info->AddressSelector;
			
			//Send back to user
			echo '
			jQuery("#svea_adresser_fakt").append("<option id=\"adress_'.$key.'\" value=\"'.$addressSelector.'\">'.$firstName.' '.$lastName.', '.$address.', '.$postCode.' '.$city.'</option>");
            jQuery(".make_purchase").removeAttr("disabled");
            jQuery("#persnr_error_tr_fakt").hide();';
		}
}else{
		//$firstName = $response->GetAddressesResult->Addresses->CustomerAddress->FirstName;
		//$lastName = $response->GetAddressesResult->Addresses->CustomerAddress->LastName;
        $firstName = ($response->GetAddressesResult->Addresses->CustomerAddress->BusinessType == 'Person') ? $response->GetAddressesResult->Addresses->CustomerAddress->FirstName : $response->GetAddressesResult->Addresses->CustomerAddress->LegalName ;
		$lastName = ($response->GetAddressesResult->Addresses->CustomerAddress->BusinessType == 'Person') ? $response->GetAddressesResult->Addresses->CustomerAddress->LastName : '';
		$address = $response->GetAddressesResult->Addresses->CustomerAddress->AddressLine1." ".$response->GetAddressesResult->Addresses->CustomerAddress->AddressLine2;
		$postCode = $response->GetAddressesResult->Addresses->CustomerAddress->Postcode;
		$city = $response->GetAddressesResult->Addresses->CustomerAddress->Postarea;
		$addressSelector = $response->GetAddressesResult->Addresses->CustomerAddress->AddressSelector;
		
		//Send back to user
		echo '
		jQuery("#svea_adresser_fakt").append("<option id=\"adress\" value=\"'.$addressSelector.'\">'.$firstName.' '.$lastName.', '.$address.', '.$postCode.' '.$city.'</option>");
        jQuery(".make_purchase").removeAttr("disabled");
        jQuery("#persnr_error_tr_fakt").hide();';	
 
}
  
}else{
	echo 'jQuery("#pers_nr_error_fakt").html("'.$error_msg.'");
			jQuery("#persnr_error_tr_fakt").show();
		 ';

}

endif;
?>