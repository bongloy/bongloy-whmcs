<?php

function serverping_save_stripe_token($vars) {
  $_SESSION['stripeToken'] = $vars['stripeToken'];
  $_SESSION['stripe_ccexpirymonth'] = $vars['ccexpirymonth'];
  $_SESSION['stripe_ccexpiryyear'] = $vars['ccexpiryyear'];	
  $_SESSION['stripe_card_country'] = $vars['card_country'];
  $_SESSION['stripe_ccexpirydate'] = $_SESSION['stripe_ccexpirymonth'].substr($_SESSION['stripe_ccexpiryyear'],-2);  
}

function serverping_stripe_cc_form($vars) {
  global $whmcs;
  
  $supported_carts_form_names["modern"] = 'cardformone.tpl';
  $supported_carts_form_names["slider"] = 'cardformone.tpl';
  $supported_carts_form_names["boxes"] = 'cardformfive.tpl';
  $supported_carts_form_names["cart"] = 'cardformone.tpl';
  $supported_carts_form_names["verticalsteps"] = 'cardformone.tpl';
  $supported_carts_form_names["web20cart"] = 'cardformtwo.tpl';
  $supported_carts_form_names["comparison"] = 'cardformthree.tpl';
  
  $supported_carts_form_names["cloud_slider"] = 'cardformstandardcart.tpl';
  $supported_carts_form_names["premium_comparison"] = 'cardformstandardcart.tpl';
  $supported_carts_form_names["pure_comparison"] = 'cardformstandardcart.tpl';
  $supported_carts_form_names["standard_cart"] = 'cardformstandardcart.tpl';
  $supported_carts_form_names["universal_slider"] = 'cardformstandardcart.tpl';
  $supported_carts_form_names["cloud_slider"] = 'cardformstandardcart.tpl';
  
  if (function_exists("stripe_custom_forms")) {
    $custom_forms = stripe_custom_forms($vars);
    $supported_carts_form_names = array_merge($supported_carts_form_names,$custom_forms["stripe_custom_cart_form_names"]);
  }
  
  $cart = $vars['carttpl'];
  
  if ($vars['filename'] == 'cart' && isset($supported_carts_form_names["$cart"])) {
    return array("cc_form_name" => $supported_carts_form_names["$cart"]);
  }
  elseif($vars['filename'] == 'cart' && $vars['carttpl'] == 'ajaxcart') {
    $result = select_query("tblpaymentgateways","value", array("gateway" => "stripe","setting" => "publishableKey"));
    $gateway_data = mysql_fetch_array($result);
    
    $script = '
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<script type="text/javascript">
            Stripe.setPublishableKey(\''. $gateway_data['value'] . '\');
            var $customer_name = \'' . addslashes($vars['clientsdetails']['firstname']). ' ' . addslashes($vars['clientsdetails']['lastname']) .'\';
            var $address_1 = \'' . addslashes($vars['clientsdetails']['address1']). '\';
            var $address_2 = \'' . addslashes($vars['clientsdetails']['address2']). '\';
            var $city = \'' . addslashes($vars['clientsdetails']['city']). '\';
            var $zip = \'' . addslashes($vars['clientsdetails']['postcode']). '\';
            var $county = \'' . addslashes($vars['clientsdetails']['country']). '\';
            var $state = \'' . addslashes($vars['clientsdetails']['state']). '\';
            
            var $continue_button = \''. $whmcs->get_lang('completeorder'). '\';
            var $wait_button = \''. $whmcs->get_lang('pleasewait'). '\';
            
            function stripeResponseHandler(status, response) {
                if (response.error) {
                    jQuery(\'.ordernow\').removeAttr("disabled");
                    jQuery(\'.ordernow\').attr("value",$continue_button);
                    jQuery("#payment-errors").html(response.error.message);
                    jQuery(".payment-errors").show();
                } else {
                    var form$ = jQuery("#checkoutfrm");
                    var token = response[\'id\'];
                    form$.append("<input type=\'hidden\' name=\'stripeToken\' value=\'" + token + "\' />");
                    form$.append("<input type=\'hidden\' name=\'ccnumber\' value=\'4242424242424242\' />");
                    form$.append("<input type=\'hidden\' name=\'cccvv\' value=\'111\' />");   
                    form$.append("<input type=\'hidden\' name=\'card_country\' value=\'" + response[\'card\'][\'country\'] +"\' />");     
                    jQuery(\'.ordernow\').removeAttr("disabled");
                    jQuery(\'.ordernow\').attr("value",$continue_button);
                    completeorder();
                }
            }

            jQuery(document).ready(function() {
                jQuery("#checkoutfrm").submit(function(event) {
                    event.preventDefault();
                    if (jQuery(\'input[name=paymentmethod]:radio:checked\').val() == "stripe" && jQuery(\'input[name=ccinfo]:checked\').val() == "new") {
                      jQuery(\'.ordernow\').attr("disabled", "disabled");
                      jQuery(\'.ordernow\').attr("value",$wait_button);

                      if ($customer_name == " ") {
                        $customer_name = jQuery(\'[name="firstname"]\').val() + " " + jQuery(\'[name="lastname"]\').val();
                        $address_1 = jQuery(\'[name="address1"]\').val();
                        $address_2 = jQuery(\'[name="address2"]\').val();
                        $city =  jQuery(\'[name="city"]\').val();
                        $state =  jQuery(\'#stateselect option:selected\').val();
                        $zip = jQuery(\'[name="postcode"]\').val();
                        $county = jQuery(\'[name="county"]\').val();
                      }
                     
                      Stripe.setPublishableKey(\''. $gateway_data['value'] . '\');
           
                      Stripe.createToken({
                        number: jQuery(\'.card-number\').val(),
                        cvc: jQuery(\'.card-cvc\').val(),
                        exp_month: jQuery(\'.card-expiry-month\').val(),
                        exp_year: jQuery(\'.card-expiry-year\').val(),
                        name: $customer_name,
                        address_line1: $address_1,
                        address_line2: $address_2,
                        address_city: $city,
                        address_state: $state,
                        address_zip:  $zip,
                        address_country: $county,
                      }, stripeResponseHandler);
                      return false; 
                    }
                    else {
                      completeorder();
                      return true;                    
                    }
               });
            });
        </script>';
    
    return array('cc_form_script' => $script, 'cc_form_name' => 'cardformfour.tpl');
  }
}


function serverping_stripe_error_saving_card($vars) {
  if ($vars["clientareaaction"] == "creditcard") { 
    if ($_SESSION['card_error']) {
	    unset($_SESSION['card_error']);
	    return array("card_error" => true);
    }
    else {
	    return array("card_error" => false);
    }
  }
}

function serverping_stripe_cart_checkout($vars) {
  global $whmcs;
  
  $supported_carts = array("modern","slider","cart","verticalsteps","boxes","web20cart","comparison");
  $standard_carts = array("cloud_slider","premium_comparison","pure_comparison","standard_cart", "cloud_slider","universal_slider"); 
 
  if (function_exists("stripe_custom_forms")) {
    $custom_forms = stripe_custom_forms($vars);
    $supported_carts = array_merge($supported_carts,$custom_forms["stripe_custom_carts"]);
  }
  
  if ($vars['filename'] == 'cart' && (in_array($vars['carttpl'], $supported_carts)) ) {
    
    if (isset($custom_forms["stripe_custom_carts_scripts"][$vars['carttpl']])) {
	  $script = $custom_forms["stripe_custom_carts_scripts"][$vars['carttpl']];
    }
    else {
      $result = select_query("tblpaymentgateways","value", array("gateway" => "stripe","setting" => "publishableKey"));
      $gateway_data = mysql_fetch_array($result);

      $script = '<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
      <script type="text/javascript">
            Stripe.setPublishableKey(\''. $gateway_data['value'] . '\');
            var $customer_name = \'' . addslashes($vars['clientsdetails']['firstname']). ' ' . addslashes($vars['clientsdetails']['lastname']) .'\';
            var $address_1 = \'' . addslashes($vars['clientsdetails']['address1']). '\';
            var $address_2 = \'' . addslashes($vars['clientsdetails']['address2']). '\';
            var $city = \'' . addslashes($vars['clientsdetails']['city']). '\';
            var $zip = \'' . addslashes($vars['clientsdetails']['postcode']). '\';
            var $county = \'' . addslashes($vars['clientsdetails']['country']). '\';
            var $state = \'' . addslashes($vars['clientsdetails']['state']). '\';
            var $continue_button = \''. $whmcs->get_lang('completeorder'). '\';
            var $wait_button = \''. $whmcs->get_lang('pleasewait'). '\';
            
            function stripeResponseHandler(status, response) {
                if (response.error) {
                    jQuery(\'.ordernow\').removeAttr("disabled");
                    jQuery(\'.ordernow\').attr("value",$continue_button);
                    jQuery(".payment-errors").html(response.error.message);
                    jQuery(".payment-errors").show();
                } else {
                    var form$ = jQuery("#mainfrm");
                    var token = response[\'id\'];
                    form$.append("<input type=\'hidden\' name=\'stripeToken\' value=\'" + token + "\' />");
                    form$.append("<input type=\'hidden\' name=\'ccnumber\' value=\'4242424242424242\' />");
                    form$.append("<input type=\'hidden\' name=\'cccvv\' value=\'111\' />");     
                    form$.append("<input type=\'hidden\' name=\'card_country\' value=\'" + response[\'card\'][\'country\'] +"\' />");     
                    form$.attr(\'action\',form$.attr(\'action\')+ "&submit=true");      
                    form$.get(0).submit();
                }
            }

            jQuery(document).ready(function() {
                var $buttonpressed;
                jQuery(\'.submitbutton\').click(function() {
                  $buttonpressed = $(this).attr(\'name\');
                })
                jQuery("#mainfrm").submit(function(event) {
                    if ($buttonpressed == null && jQuery(\'input[name=paymentmethod]:radio:checked\').val() == "stripe" && jQuery(\'input[name=ccinfo]:checked\').val() == "new") {
                      jQuery(\'.ordernow\').attr("disabled", "disabled");
                      jQuery(\'.ordernow\').attr("value",$wait_button);
                      if ($customer_name == " ") {
                        $customer_name = jQuery(\'[name="firstname"]\').val() + " " + jQuery(\'[name="lastname"]\').val();
                        $address_1 = jQuery(\'[name="address1"]\').val();
                        $address_2 = jQuery(\'[name="address2"]\').val();
                        $city =  jQuery(\'[name="city"]\').val();
                        $state =  jQuery(\'#stateselect option:selected\').val();
                        $zip = jQuery(\'[name="postcode"]\').val();
                        $county = jQuery(\'[name="county"]\').val();
                      }
                     
                     
                      Stripe.createToken({
                        number: jQuery(\'.card-number\').val(),
                        cvc: jQuery(\'.card-cvc\').val(),
                        exp_month: jQuery(\'.card-expiry-month\').val(),
                        exp_year: jQuery(\'.card-expiry-year\').val(),
                        name: $customer_name,
                        address_line1: $address_1,
                        address_line2: $address_2,
                        address_city: $city,
                        address_state: $state,
                        address_zip:  $zip,
                        address_country: $county,
                      }, stripeResponseHandler);
                      return false;
                    }
                    else {
	                  jQuery("#mainfrm").attr(\'action\',jQuery("#mainfrm").attr(\'action\')+ "&submit=true");   
                      return true;
                    }
               });
            });
        </script>';
        
        }
        return $script;
  }
  if ($vars['filename'] == 'cart' && (in_array($vars['carttpl'], $standard_carts)) ) {
    
    if (isset($custom_forms["stripe_custom_carts_scripts"][$vars['carttpl']])) {
	  $script = $custom_forms["stripe_custom_carts_scripts"][$vars['carttpl']];
    }
    else {
      $result = select_query("tblpaymentgateways","value", array("gateway" => "stripe","setting" => "publishableKey"));
      $gateway_data = mysql_fetch_array($result);

      $script = '<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
      <script type="text/javascript">
            Stripe.setPublishableKey(\''. $gateway_data['value'] . '\');
            var $customer_name = \'' . addslashes($vars['clientsdetails']['firstname']). ' ' . addslashes($vars['clientsdetails']['lastname']) .'\';
            var $address_1 = \'' . addslashes($vars['clientsdetails']['address1']). '\';
            var $address_2 = \'' . addslashes($vars['clientsdetails']['address2']). '\';
            var $city = \'' . addslashes($vars['clientsdetails']['city']). '\';
            var $zip = \'' . addslashes($vars['clientsdetails']['postcode']). '\';
            var $county = \'' . addslashes($vars['clientsdetails']['country']). '\';
            var $state = \'' . addslashes($vars['clientsdetails']['state']). '\';
            var $continue_button = \''. $whmcs->get_lang('completeorder'). '\';
            var $wait_button = \''. $whmcs->get_lang('pleasewait'). '\';
            
            function stripeResponseHandler(status, response) {
                if (response.error) {
                    jQuery(\'#btnCompleteOrder\').removeAttr("disabled");
                    jQuery(\'#btnCompleteOrder\').attr("value",$continue_button);
                    jQuery(".payment-errors").html(response.error.message);
                    jQuery(".payment-errors").show();
                } else {
                    var form$ = jQuery("#mainfrm");
                    var token = response[\'id\'];
                    form$.append("<input type=\'hidden\' name=\'stripeToken\' value=\'" + token + "\' />");
                    form$.append("<input type=\'hidden\' name=\'ccnumber\' value=\'4242424242424242\' />");
                    form$.append("<input type=\'hidden\' name=\'cccvv\' value=\'111\' />");     
                    form$.append("<input type=\'hidden\' name=\'card_country\' value=\'" + response[\'card\'][\'country\'] +"\' />");     
                    form$.attr(\'action\',form$.attr(\'action\')+ "&submit=true");      
                    form$.get(0).submit();
                }
            }

            jQuery(document).ready(function() {
                var $buttonpressed;
                jQuery(\'.submitbutton\').click(function() {
                  $buttonpressed = $(this).attr(\'name\');
                })
                jQuery("#mainfrm").submit(function(event) {
                    if ($buttonpressed == null && jQuery(\'input[name=paymentmethod]:radio:checked\').val() == "stripe" && jQuery(\'input[name=ccinfo]:checked\').val() == "new") {
                      jQuery(\'#btnCompleteOrder\').attr("disabled", "disabled");
                      jQuery(\'#btnCompleteOrder\').attr("value",$wait_button);
                      if ($customer_name == " ") {
                        $customer_name = jQuery(\'[name="firstname"]\').val() + " " + jQuery(\'[name="lastname"]\').val();
                        $address_1 = jQuery(\'[name="address1"]\').val();
                        $address_2 = jQuery(\'[name="address2"]\').val();
                        $city =  jQuery(\'[name="city"]\').val();
                        $state =  jQuery(\'#stateselect option:selected\').val();
                        $zip = jQuery(\'[name="postcode"]\').val();
                        $county = jQuery(\'[name="county"]\').val();
                      }
                      
                      
                      $exp_month = "";
                      $exp_year = "";

                      if (jQuery("#inputCardExpiry").val().split("/")[0] != null) {
	                    $exp_month = jQuery("#inputCardExpiry").val().split("/")[0].trim();    
	                  }                     
                      if (jQuery("#inputCardExpiry").val().split("/")[1] != null) {
	                    $exp_year = jQuery("#inputCardExpiry").val().split("/")[1].trim();    
	                  }                     
                     
                      jQuery("#ccexpirymonth").val($exp_month);                     
                      jQuery("#ccexpiryyear").val($exp_year);
                     
                      Stripe.createToken({
                        number: jQuery(\'.card-number\').val(),
                        cvc: jQuery(\'.card-cvc\').val(),
                        exp_month: $exp_month,
                        exp_year: $exp_year,
                        name: $customer_name,
                        address_line1: $address_1,
                        address_line2: $address_2,
                        address_city: $city,
                        address_state: $state,
                        address_zip:  $zip,
                        address_country: $county,
                      }, stripeResponseHandler);
                      return false;
                    }
                    else {
	                  jQuery("#mainfrm").attr(\'action\',jQuery("#mainfrm").attr(\'action\')+ "&submit=true");   
                      return true;
                    }
               });
            });
        </script>';
        
        }
        return $script;
  }
   elseif ($vars["filename"] == 'creditcard') {
       $result = select_query("tblpaymentgateways","value", array("gateway" => "stripe","setting" => "publishableKey"));
       $gateway_data = mysql_fetch_array($result);

       $script = '
        <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
        <script type="text/javascript">
            // this identifies your website in the createToken call below
            Stripe.setPublishableKey(\''.$gateway_data['value'] .'\');
            var $continue_button = \''. $whmcs->get_lang('ordercontinuebutton'). '\';
            var $wait_button = \''. $whmcs->get_lang('pleasewait'). '\';
            
            
              function stripeResponseHandler(status, response) {
                if (response.error) {
                    // re-enable the submit button
                    jQuery(\'.submit-button\').removeAttr("disabled");
                    // show the errors on the form
                    jQuery(".payment-errors").html(response.error.message);
                    jQuery(\'#submit-button\').val($continue_button);
                    jQuery(\'#cc_input\').show();
                    jQuery(".payment-errors").show();
                } else {
                    var form$ = jQuery("#payment-form");
                    // token contains id, last4, and card type
                    var token = response[\'id\'];
                    // insert the token into the form so it gets submitted to the server
                    form$.append("<input type=\'hidden\' name=\'stripeToken\' value=\'" + token + "\' />");
                    form$.append("<input type=\'hidden\' name=\'cccvv2\' value=\'123\'/>");
                    
                    jQuery.post("modules/gateways/stripe-php/stripesave.php", jQuery("#payment-form").serialize(), function(data) {
                      if (data == "error") {
                         jQuery(\'.submit-button\').removeAttr("disabled");
                         jQuery(".payment-errors").html("Your card could not be saved, please try again or contact support.");
                         jQuery(\'#submit-button\').val($continue_button);
                         jQuery(".payment-errors").show();
                         jQuery(\'#cc_input\').show();
                     
                      }
                      else {
	                     jQuery(\'input:radio[name=ccinfo]\').removeAttr("disabled");
                         jQuery(\'input:radio[name=ccinfo]\')[0].checked = true;   
                         jQuery(\'input:radio[name=ccinfo]\').val(\'useexisting\');
                         form$.get(0).submit();
                      }
                     
                     });
                    
                }
            }

            jQuery(document).ready(function() {
                jQuery("#payment-form").submit(function(event) {
                    jQuery(".submit-button").attr("value",$wait_button);
                    
                    if (jQuery("input[name=ccinfo]:checked").val() == "new") {
                      // disable the submit button to prevent repeated clicks
                      jQuery(".submit-button").attr("disabled", "disabled");
                      jQuery("#cc_input").hide();
                      
                      var $state = jQuery("#stateselect option:selected").val();
                      if ($state == null) {
	                    $state = jQuery("#inputState").val();
	                  }
                     
                      // createToken returns immediately - the supplied callback submits the form if there are no errors
                      Stripe.createToken({
                        number: jQuery(".card-number").val(),
                        cvc: jQuery(".card-cvc").val(),
                        exp_month: jQuery(".card-expiry-month").val(),
                        exp_year: jQuery(".card-expiry-year").val(),
                        name: jQuery("#inputFirstName").val() + " " + jQuery("#inputLastName").val(),
                        address_line1: jQuery("#inputAddress1").val(),
                        address_line2: jQuery("#inputAddress2").val(),
                        address_city: jQuery("#inputCity").val(),
                        address_state: $state,
                        address_zip:  jQuery("#inputPostcode").val(),
                        address_country: jQuery("#country").val(),
                      }, stripeResponseHandler);
                      
                      
                      return false; // submit from callback
                    }
                    else {
	                  jQuery("#payment-form").append("<input type=\'hidden\' name=\'cccvv2\' value=\'123\'/>");
                      return true;
                    }
                });
            });
        </script>';
        
        return $script;
     }
     elseif ($vars["clientareaaction"] == 'creditcard') {
       $result = select_query("tblpaymentgateways","value", array("gateway" => "stripe","setting" => "publishableKey"));
       $gateway_data = mysql_fetch_array($result);
       if ($vars["clientsdetails"]["billingcid"] != 0) {
	     $billing_contact_query = select_query("tblcontacts","firstname,lastname,email,address1,address2,city,state,postcode,phonenumber,country", array("id" => $vars["clientsdetails"]["billingcid"]));
	     $billing_contact = mysql_fetch_array($billing_contact_query);
       } 	  
       else {
         $billing_contact = $vars["clientsdetails"];
       }
       $script = '
        <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
        <script type="text/javascript">
            // this identifies your website in the createToken call below
            Stripe.setPublishableKey(\''.$gateway_data['value'] .'\');
            var $continue_button = \''. $whmcs->get_lang('clientareasavechanges'). '\';
            var $wait_button = \''. $whmcs->get_lang('pleasewait'). '\';
            
            
              function stripeResponseHandler(status, response) {
                if (response.error) {
                    // re-enable the submit button
                    jQuery(\'.submit-button\').removeAttr("disabled");
                    // show the errors on the form
                    jQuery(".payment-errors").html(response.error.message);
                    jQuery(\'#submit-button\').val($continue_button);
                    jQuery(\'#cc_input\').show();
                    jQuery(".payment-errors").show();
                } else {
                    var form$ = jQuery("#payment-form");
                    // token contains id, last4, and card type
                    var token = response[\'id\'];
                    // insert the token into the form so it gets submitted to the server
                    form$.append("<input type=\'hidden\' name=\'stripeToken\' value=\'" + token + "\' />");
                    form$.append("<input type=\'hidden\' name=\'cccvv2\' value=\'123\'/>");
                    form$.get(0).submit();                    
                }
            }

            jQuery(document).ready(function() {
                jQuery("#payment-form").submit(function(event) {
                    jQuery(".submit-button").attr("value",$wait_button);
                    
                    
                      // disable the submit button to prevent repeated clicks
                      jQuery(".submit-button").attr("disabled", "disabled");
                      jQuery("#cc_input").hide();
                     
                      // createToken returns immediately - the supplied callback submits the form if there are no errors
                      Stripe.createToken({
                        number: jQuery(".card-number2").val(),
                        cvc: jQuery(".card-cvc").val(),
                        exp_month: jQuery(".card-expiry-month").val(),
                        exp_year: jQuery(".card-expiry-year").val(),
                        name: "'. $billing_contact["firstname"] . ' ' . $billing_contact["lastname"]. '",
                        address_line1: "'. $billing_contact["address1"] . '",
                        address_line2: "'. $billing_contact["address2"] . '",
                        address_city: "'. $billing_contact["city"] . '",
                        address_state: "'. $billing_contact["state"] . '",
                        address_zip:  "'. $billing_contact["postcode"] . '",
                        address_country: "'. $billing_contact["country"] . '",
                      }, stripeResponseHandler);
                      
                      
                      return false; // submit from callback
                    
                });
            });
        </script>';
        
        return $script;
     }
   
}

function stripe_update_customer_with_no_invoice($vars) {
  if (isset($_SESSION['stripeToken'])) {
    $whmcs_client_id = (int)$_SESSION['uid'];
    $result = select_query("tblclients","*",array("id" => $whmcs_client_id));
    $client_data = mysql_fetch_array($result);
    $client_data['userid'] = $client_data['id'];
    $params['clientdetails'] = $client_data;
    $params['gatewayid'] = $client_data['gatewayid'];
    $params['cardexp'] = $_SESSION['stripe_ccexpirydate'];
    $gateway = getGatewayVariables('stripe');
    $params = $params + $gateway;
    $result = stripe_update_customer($params);
  }
}


add_hook("ShoppingCartCheckoutCompletePage",1,"stripe_update_customer_with_no_invoice");
add_hook("ClientAreaPage",1,"serverping_stripe_cc_form");
add_hook("ClientAreaPage",1,"serverping_stripe_error_saving_card");
add_hook("ClientAreaHeadOutput",1,"serverping_stripe_cart_checkout");
add_hook("ShoppingCartValidateCheckout",1,"serverping_save_stripe_token");


?>