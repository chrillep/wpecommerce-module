jQuery(document).ready(function(){
				
				//Clear if auto-checked
				jQuery("input:radio[name=custom_gateway]:checked").removeAttr('checked');
				
				jQuery("input:radio[name=custom_gateway]").click( function() {
					var checked_payment = jQuery("input:radio[name=custom_gateway]:checked").val();

					if (checked_payment == 'svea_gateway'){
						setTimeout('jQuery("#uppd_adress_fakt").attr("disabled", "true")', 500);
						setTimeout('jQuery(".make_purchase").attr("disabled", "true")', 500);
						if (jQuery('#svea_total_price').val() != '1'){ 
							updatePrice();
						}
					}else if (checked_payment == 'svea_delbet_gateway'){
						setTimeout('jQuery("#uppd_adress_delbet").attr("disabled", "true")', 500);
						setTimeout('jQuery(".make_purchase").attr("disabled", "true")', 500);
						if (jQuery("#partPayValid").val() == "1"){
                            jQuery("#svea_delbet_tr02").show();
                            jQuery("#svea_delbet_tr03").show();
						}else{
							disablePartPay();
						}
							
                    }else{
						jQuery(".make_purchase").removeAttr("disabled");
					}
					
					if (checked_payment != 'svea_gateway' && jQuery("#svea_total_price").val() == "1"){
						jQuery('tr.total_price:last').remove();
						
					}
					
				});

				function updatePrice(){

					jQuery.ajax({
						  type: "GET",
						  url: "wp-content/plugins/wp-e-commerce/wpsc-merchants/library/svea_ajax-red.php",
						  data: "uc=1",
						  success: function(msg){
							eval(msg);
							}
						});
				}
				
				function disablePartPay(){
							jQuery("#uppd_adress_delbet").attr("display", "none")
							jQuery("#pers_nr_error_delbet").html("För Delbetalning måste beloppet överstiga 1000 kr");
							jQuery("#persnr_error_tr_delbet").show();
				}
				
				//Hide fields Delbet
				jQuery("#svea_payment_info").hide();
				jQuery("#persnr_error_tr_delbet").hide();
				jQuery("#svea_adress_info_text").hide();
				jQuery("#svea_dd_delbet").hide();
                jQuery("#svea_delbet_tr02").hide();
                jQuery("#svea_delbet_tr03").hide();
				
				//Hide fields fakt
				jQuery("#pers_nr_error_tr_fakt").hide();
				jQuery("#svea_adress_info_fakt").hide();
				jQuery("#svea_dd_fakt").hide();
				
				
				
				function getPaymentPlan(){
                    jQuery('#svea_payment_options').empty();
					jQuery.ajax({
						  type: "POST",
						  url: "wp-content/plugins/wp-e-commerce/wpsc-merchants/library/svea_get_payments.php",
						  data: "getPaymentPlan=1",
						  success: function(msg){
							eval(msg);
							}
						});
				}

				/*** Faktura js ***/
				function fakt_validation(){
					var persnr = jQuery("#svea_fakt_pnr").val();
						
						if (persnr.length < 10 ){
							jQuery("#uppd_adress_fakt").attr("disabled", "true");
							jQuery(".make_purchase").attr("disabled", "true")
							jQuery("#pers_nr_error_fakt").html("ditt personnr måste vara 10 siffor");
							jQuery("#persnr_error_tr_fakt").show();	

						}else{
							jQuery("#persnr_error_tr_fakt").hide();
							jQuery("#uppd_adress_fakt").trigger('click');
							if (jQuery('#agree').attr("checked")) {
						        jQuery(".make_purchase").removeAttr("disabled");
						    } else {
						    	jQuery(".make_purchase").attr("disabled", "true");
							}
						}
					}
				
				
				//Validation
				jQuery("#svea_fakt_pnr, #agree").keyup(function(){
					fakt_validation();						
				}).change(function(){
					fakt_validation();						
				});	

				
				//Get addresses function
				jQuery("#uppd_adress_fakt").click(function(){	
					
						var is_company = jQuery("#is_company_fakt").val();
					
						var persnr = jQuery("#svea_fakt_pnr").val();
						jQuery("#svea_loading_fakt").html("<img src='wp-content/plugins/wp-e-commerce/wpsc-merchants/indicator.gif' title='loading' />");
						jQuery("#svea_adresser_fakt").empty();
						
						jQuery.ajax({
						  type: "POST",
						  url: "wp-content/plugins/wp-e-commerce/wpsc-merchants/library/svea_addresses.php",
						  data: "pnr="+persnr+"&is_company="+is_company,
						  success: function(msg){
							eval(msg);
							
							jQuery("#svea_loading_fakt").empty();;
							jQuery("#svea_adress_info_fakt").show();
							jQuery("#svea_dd_fakt").show();
						  }
						});
					
				});
				
				
				/*** Delbetalning js ***/
				
				//Validation
				function delbet_validation(){
					var persnr = jQuery("#svea_delbet_pnr").val();

						var errMsg = null;
						
						if (persnr.length < 10){
							errMsg = "Personnr måste vara 10 siffor";
						}else if(jQuery("#partPayValid").val() != "1"){
							errMsg = "För Delbetalning måste beloppet överstiga 1000 kr";
						}
						
						if (errMsg == null){
							jQuery("#persnr_error_tr_delbet").hide();
							jQuery("#uppd_adress_delbet").removeAttr("disabled");
							
						}else{
							jQuery("#uppd_adress_delbet").attr("disabled", "true")
							jQuery(".make_purchase").attr("disabled", "true")
							jQuery("#pers_nr_error_delbet").html(errMsg);
							jQuery("#persnr_error_tr_delbet").show();
						}							
				}
				
				jQuery("#svea_delbet_pnr").blur(function(){
					delbet_validation();
				}).keyup(function(){
					delbet_validation();						
				});
				
				jQuery("input:radio[name=is_company_delbet]").click(function(){		
					delbet_validation();
				});
				
				
				//The get adress function
				jQuery("#uppd_adress_delbet").click(function(){	
					
						var is_company = jQuery("input:radio[name=is_company_delbet]:checked").val();
						
						var persnr = jQuery("#svea_delbet_pnr").val();
						jQuery("#svea_loading_delbet").html("<img src='wp-content/plugins/wp-e-commerce/wpsc-merchants/indicator.gif' title='loading' />");
						jQuery("#svea_adresser_delbet").empty();
						
						jQuery.ajax({
						  type: "POST",
						  url: "wp-content/plugins/wp-e-commerce/wpsc-merchants/library/svea_get_payments.php",
						  data: "pnr="+persnr+"&is_company="+is_company,
						  success: function(msg){
							eval(msg);
							jQuery("#svea_loading_delbet").empty();
							
							jQuery("#svea_payment_info").show();
                            getPaymentPlan();
						  }
						});
					
				});
                
				
			});