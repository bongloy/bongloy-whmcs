<?php
/*

Copyright (c) 2015, Hosting Playground Inc
All rights reserved.
*/
require_once(dirname(__FILE__) . "/stripe-php/init.php");

function stripe_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"Stripe"),
     "secretKey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "40", ),
     "publishableKey" => array("FriendlyName" => "Publishable Key", "Type" => "text", "Size" => "40", ),
     "stripeCurrency" =>  array("FriendlyName" => "Currency", "Type" => "text", "Size" => 40, "Default" => "usd"),
     "multiCurrency" => array("FriendlyName" => "Enable Multiple Currency Support", "Type" => "yesno","Description" => "When this option is enabled the users default currency from WHMCS will be passed to Stripe instead of the currency inputed above."), 
     "countryCodeCheck" => array("FriendlyName" => "Country Code Check", "Type" => "yesno","Description" => "Mark payments as failed if the country code of the card doesn't match the entered country code."), 
    );
	return $configarray;
}

function stripe_update_customer($params) {
	
   $stripe_token = $_SESSION['stripeToken'];
   unset($_SESSION['stripeToken']);
   
   $whmcs_client_id = $params['clientdetails']['userid'];
   $result = select_query("tblclients","gatewayid,email", array("id" => $whmcs_client_id));
   $customer_data = mysql_fetch_array($result);
   $gateway_id = $customer_data['gatewayid'];
   global $cc_encryption_hash;
   $cchash = md5($cc_encryption_hash.$whmcs_client_id);
   $email = $customer_data['email'];
   global $CONFIG;
   $systemurl = ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'].'/' : $CONFIG['SystemURL'].'/';

   $result = select_query("tblpaymentgateways","value", array("gateway" => "stripe","setting" => "secretKey"));
   $gateway_data = mysql_fetch_array($result);
   
   $country_code_check = get_query_val("tblpaymentgateways","value", array("gateway" => "stripe", "setting" => "countryCodeCheck"));

   
   try {
	   \Stripe\Stripe::setApiKey($gateway_data['value']);
	   $create_new_customer = true;
	   
	   if ($gateway_id != null && $gateway_id != '' && $gateway_id != 'stripejs') {
	     try {
	       $stripe_customer = \Stripe\Customer::retrieve($gateway_id);
	       $create_new_customer = false;
	       if ($stripe_customer->deleted) {
		     $create_new_customer = true;
	       }
	     }
	     catch (Exception $e) {
		   $create_new_customer = true;
	     }
	     
	   }
	   if ($create_new_customer) {
	     $stripe_customer =  \Stripe\Customer::create(array(
	                           "email" => $email,
	                           "card" => $stripe_token));
	     $card = $stripe_customer->sources->retrieve($stripe_customer->default_source);                      
         $client_record_query = select_query("tblclients","billingcid,country", array("id" => $_SESSION['uid']));
         $client_record = mysql_fetch_array($client_record_query);
         $client_country = "";
  
         
         if ($client_record["billingcid"] != 0 ) {
           $billing_contact_query = select_query("tblcontacts","country", array("id" => $client_record["billingcid"]));
           $billing_contact = mysql_fetch_array($billing_contact_query);
           $client_country = $billing_contact["country"];  
         }
         else {
           $client_country = $client_record["country"];
         }
         
         if ($country_code_check != "on" || $client_country == $card->country ) {
           $exp_date = $_SESSION['stripe_ccexpirymonth'].substr($_SESSION['stripe_ccexpiryyear'],-2);
	       full_query("UPDATE tblclients set expdate = AES_ENCRYPT('".$exp_date."','". $cchash. "') WHERE id = ". $whmcs_client_id);
	       update_query("tblclients", array("cardtype" => $card->brand, "gatewayid" =>$stripe_customer->id,"cardlastfour" => $card->last4), array("id" => $whmcs_client_id));
  	      }	                       
  	      else {
	  	    return array("result" => false, "error" => "Country Code does not match billing address country, Customer ID: " . $_SESSION['uid']);  
  	      }    
	       
	   }
	   else {
		 // update existing customer  
		 $stripe_customer = \Stripe\Customer::retrieve($gateway_id);
	     $stripe_customer->card = $stripe_token;
	     $stripe_customer->save();
	     $card = $stripe_customer->sources->retrieve($stripe_customer->default_source);
	     $client_record_query = select_query("tblclients","billingcid,country", array("id" => $_SESSION['uid']));
         $client_record = mysql_fetch_array($client_record_query);
         $client_country = "";
  
         
         if ($client_record["billingcid"] != 0 ) {
           $billing_contact_query = select_query("tblcontacts","country", array("id" => $client_record["billingcid"]));
           $billing_contact = mysql_fetch_array($billing_contact_query);
           $client_country = $billing_contact["country"];  
         }
         else {
           $client_country = $client_record["country"];
         }
         
         if ($country_code_check != "on" || $client_country == $card->country ) {
           $exp_date = $_SESSION['stripe_ccexpirymonth'].substr($_SESSION['stripe_ccexpiryyear'],-2);
	       full_query("UPDATE tblclients set expdate = AES_ENCRYPT('".$exp_date."','". $cchash. "') WHERE id = ". $whmcs_client_id);
	       update_query("tblclients", array("cardtype" => $card->brand, "gatewayid" =>$stripe_customer->id,"cardlastfour" => $card->last4), array("id" => $whmcs_client_id));  	      }	                       
  	      else {
	  	    return array("result" => false, "error" => "Country Code does not match billing address country, Customer ID: " . $_SESSION['uid']);  
  	      }  

	   
	   }
	   unset($_SESSION['stripe_ccexpirymonth']);
	   unset($_SESSION['stripe_ccexpiryyear']);
	   return array("result" => true, "gatewayid" =>$stripe_customer->id);
   }
   catch(Exception $e) {
	   unset($_SESSION['stripe_ccexpirymonth']);
	   unset($_SESSION['stripe_ccexpiryyear']);
       $body = $e->getJsonBody();
       $error_message = $body["error"]["message"];
  	   return array("result" => false, "error" => $error_message);
   }
	
}


function stripe_capture($params) {
    if (isset($_SESSION['stripeToken'])) {
      $stripe_data = stripe_update_customer($params);
	  if ($stripe_data["result"] == true) {
	    $gatewayid = $stripe_data["gatewayid"];
	  }
	  else {
  	    return array('status'=>'error', 'rawdata'=> $stripe_data["error"]);
	  }
    }
    else {
	  $gatewayid = $params['gatewayid'];
    }
    try {
      if ($params['multiCurrency'] == "on") {
	      $currency = $params['currency'];
      }
      else {
	    $currency = $params['stripeCurrency'];
	    if (!isset($currency) || $currency == "") {
	      $currency = "usd";
        }
      }
      \Stripe\Stripe::setApiKey($params['secretKey']);
      if ($gatewayid != null && $gatewayid != '') {

      	  $charge = \Stripe\Charge::create(array(
		    "amount" => $params['amount']*100,
		    "currency" => $currency,
		    "customer" => $gatewayid,
		    "description" => "Payment of Invoice #" . $params['invoiceid'])
		  );
		   
		  if ($charge->paid == true) {
		    $balance_transaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction);
		    $fee = $balance_transaction->fee / 100;
		    $clientCurrency = getCurrency( $params['clientdetails']['id'] );
		    if ($params['multiCurrency'] == "on" && $balance_transaction->currency != strtolower($clientCurrency['code'])) {
		      $currency = get_query_val('tblcurrencies',"id", array("code" => strtoupper($balance_transaction->currency)));
		      if ($currency == null) {
			     $currency = 1;
		      }
			  $fee = convertCurrency($fee,$currency,$clientCurrency["id"]);
		    }
		    return array('status'=>'success', 'transid'=> $charge->id, 'fee' => $fee);
		  }
		  else {
			return array('status'=>'error', 'rawdata'=> $charge->failure_message);  
		  }
	  }
	  elseif($params['cardnum']) {
	  
	    // convert stored cc to stripe stored cc
	    $whmcs_client_id = $params['clientdetails']['id'];
	    $cchash = md5($cc_encryption_hash.$whmcs_client_id);
	    $firstname = $params['clientdetails']['firstname'];
	    $lastname = $params['clientdetails']['lastname'];
	    $email = $params['clientdetails']['email'];
	    $address1 = $params['clientdetails']['address1'];
	    $address2 = $params['clientdetails']['address2'];
	    $city = $params['clientdetails']['city'];
	    $state = $params['clientdetails']['state'];
	    $postcode = $params['clientdetails']['postcode'];
	    $country = $params['clientdetails']['country'];
	    $phone = $params['clientdetails']['phonenumber'];
        $card = array("number" => $params['cardnum'],
        "exp_month" => substr($params['cardexp'], 0, 2),
        "exp_year" => substr($params['cardexp'], 2, 2),
        "name" => $firstname . " " . $lastname,
        "cvc" => $params['cccvv'],
        "address_line1" => $address1,
        "address_line2" => $address2,
        "address_zip" => $postcode,
        "address_state" => $state,
        "address_city" => $city,
        "address_country" => $country);
        
	    $stripe_customer =  \Stripe\Customer::create(array("email" => $email,"card" => $card));
	    $card = $stripe_customer->sources->retrieve($stripe_customer->default_source);
	    
	    full_query("UPDATE tblclients set expdate = AES_ENCRYPT('".$params['cardexp']."','". $cchash. "') WHERE id = ". $whmcs_client_id);
        update_query("tblclients", array("cardtype" => $card->brand, "cardnum" => '', "gatewayid" =>$stripe_customer->id,"cardlastfour" => $card->last4), array("id" => $whmcs_client_id));
        
        $charge = \Stripe\Charge::create(array(
		    "amount" => $params['amount']*100,
		    "currency" => $currency,
		    "customer" => $stripe_customer->id,
		    "description" => "Payment of Invoice #" . $params['invoiceid'])
		  );
		  
		  if ($charge->paid == true) {
  		    $balance_transaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction);
		    $fee = $balance_transaction->fee / 100;
		    $clientCurrency = getCurrency( $params['clientdetails']['id'] );
		    if ($params['multiCurrency'] == "on" && $balance_transaction->currency != strtolower($clientCurrency['code'])) {
		      $currency = get_query_val('tblcurrencies',"id", array("code" => strtoupper($balance_transaction->currency)));
		      if ($currency == null) {
			     $currency = 1;
		      }
			  $fee = convertCurrency($fee,$currency,$clientCurrency["id"]);
		    }
		    return array('status'=>'success', 'transid'=> $charge->id, 'fee' => $fee, 'rawdata'=> $charge);
		  }
		  else {
			return array('status'=>'error', 'rawdata'=> $charge->failure_message);  
		  }
        
	    
	  }
	  else {
		  return array('status'=>'error');
	  }
  }
  catch (Exception $e) {
    $body = $e->getJsonBody();
    $error_message = $body["error"]["message"];
    return array('status'=>'error', 'rawdata'=> $error_message); 
  }
}

function stripe_refund($vars) {

  try {
      \Stripe\Stripe::setApiKey($vars['secretKey']);
      $ch = \Stripe\Charge::retrieve($vars['transid']);      
      $refund = $ch->refund(array("amount" =>$vars['amount']*100));
      

      $last_refund = $refund->refunds->data[0];
      $balance_transaction = \Stripe\BalanceTransaction::retrieve($last_refund->balance_transaction);

      $fee_refunded = 0 + ($balance_transaction->fee)/100;
      $clientCurrency = getCurrency( $vars['clientdetails']['id'] );
      if ($vars['multiCurrency'] == "on" && $balance_transaction->currency != strtolower($clientCurrency['code'])) {
	    $currency = get_query_val('tblcurrencies',"id", array("code" => strtoupper($balance_transaction->currency)));
	    if ($currency == null) {
	      $currency = 1;
	    }
	    $fee_refunded = convertCurrency($fee_refunded,$currency,$clientCurrency["id"]);
	  }
	  if ($refund->failure_message == null) {
	    return array('status'=>'success', 'transid'=> $refund->id, 'fees' => $fee_refunded * (0 - 1), 'rawdata'=> $refund);
	  }
	  else {
		return array('status'=>'error', 'rawdata'=> $refund->failure_message);  
	  }
  }
  catch (Exception $e) {
    $body = $e->getJsonBody();
    $error_message = $body["error"]["message"];
    return array('status'=>'error', 'rawdata'=> $error_message); 
  }	
	
}

function stripe_storeremote($params) {
   if ($_SESSION['stripeToken']) {
      if ($params['gatewayid'] != "") {
        return array('status'=>'success', 'gatewayid'=> $params['gatewayid']);      
      }
      else {
        return array('status'=>'success', 'gatewayid'=> "stripejs");  
      }   
   }
   else {
     try {
       $gateway_id = $params['gatewayid'];
       $firstname = $params['clientdetails']['firstname'];
       $lastname = $params['clientdetails']['lastname'];
       $email = $params['clientdetails']['email'];
       $address1 = $params['clientdetails']['address1'];
       $address2 = $params['clientdetails']['address2'];
       $city = $params['clientdetails']['city'];
       $state = $params['clientdetails']['state'];
       $postcode = $params['clientdetails']['postcode'];
       $country = $params['clientdetails']['country'];
       $phone = $params['clientdetails']['phonenumber'];
       
       \Stripe\Stripe::setApiKey($params['secretKey']);
     
       if ($gateway_id == null || $gateway_id == '') {
         $card = array("number" => $params['cardnum'],
                 "exp_month" => substr($params['cardexp'], 0, 2),
                 "exp_year" => substr($params['cardexp'], 2, 2),
                 "name" => $firstname . " " . $lastname,
                 "cvc" => $params['cccvv'],
                 "address_line1" => $address1,
                 "address_line2" => $address2,
                 "address_zip" => $postcode,
                 "address_city" => $city,
                 "address_state" => $state,
                 "address_country" => $country);
     	   
     	 $stripe_customer =  \Stripe\Customer::create(array("email" => $email,"card" => $card));
         return array('status'=>'success', 'gatewayid'=>$stripe_customer->id);
            
       }
       else {
     	 
     	  if ($params['cardnum']) {
     	   $card = array("number" => $params['cardnum'],
                  "exp_month" => substr($params['cardexp'], 0, 2),
                  "exp_year" => substr($params['cardexp'], 2, 2),
                  "cvc" => $params['cccvv'],
                  "name" => $firstname . " " . $lastname,
                  "address_line1" => $address1,
                  "address_line2" => $address2,
                  "address_zip" => $postcode,
                  "address_state" => $state,
                  "address_city" => $city,
                  "address_country" => $country);
     	   $stripe_customer = \Stripe\Customer::retrieve($gateway_id);
     	   $stripe_customer->card = $card;
     	   $stripe_customer->save();
     	  
         return array('status'=>'success', 'gatewayid'=>$gateway_id);
     
        }
        else {
           if ($gateway_id != "stripejs") {
     	     $stripe_customer = \Stripe\Customer::retrieve($gateway_id);
     	     $stripe_customer->delete();
     	   }
     	   return array('status'=>'success');
       }
        
       }
     }
     catch (Exception $e) {
 	   $body = $e->getJsonBody();
       $error_message = $body["error"]["message"];
       return array('status'=>'error', 'rawdata'=> $error_message); 
     }
 }
}


?>