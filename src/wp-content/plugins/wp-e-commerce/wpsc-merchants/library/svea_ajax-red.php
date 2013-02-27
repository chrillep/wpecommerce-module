<?php

require_once('../../../../../wp-load.php');
require_once('../svea/SveaConfig.php');

global $wpdb;

if ($_GET['uc']){

	echo "
    jQuery('tr.total_price:last').clone(true).insertAfter('tr.total_price:last');
	jQuery('tr.total_price:last td:last').html('Tillägg. faktura: ".number_format(get_option('svea_fakt_avg'), 2)." kr').append('<input type=\'hidden\' id=\'svea_total_price\' value=\'1\' \>');
		
		
	";
exit();
}


//Response comparison
if ($_REQUEST['response']){
    
    $sep = (preg_match('/\?/',get_option('transact_url'))) ? '&' : '?';
    
    //GETs
    $response = $_REQUEST['response'];
    $mac = $_REQUEST['mac'];
    $merchantid = $_REQUEST['merchantid'];
    $sessionid = $_REQUEST['sessionid'];
    
    $resp = new SveaPaymentResponse($response);
    
    if($resp->validateMac($mac,get_option('svea_sw')) == true && $_SESSION['wpsc_sessionid'] == $sessionid){
        
        if ($resp->statuscode == '0'){
            	$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
            	"` SET `processed`= '3' WHERE `sessionid`=".$sessionid;
            	 
    	$wpdb->query($sql);
    	
    	$redirectURL = get_option('transact_url').$sep."sessionid=".$sessionid;
            
        }else{
                $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
            	"` SET `processed`= '1' WHERE `sessionid`=".$sessionid;
        	 
    	$wpdb->query($sql);
    	
        $redirectURL = get_option('shopping_cart_url');
        }
           
    }else{
        die('nej');
    }




header("Location: ".$redirectURL);
flush();
exit();

}
?>