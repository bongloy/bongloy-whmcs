<?php
use Illuminate\Database\Capsule\Manager as Capsule;


/*
  
  Stripe WHMCS Payment Gateway 4.0.2 for WHMCS 7.x  

  Copyright (c) 2016, Hosting Playground Inc
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
      "statementDescriptor" => array("FriendlyName" => "Custom Statement Descriptor", "Type" => "text", "Size" => "40", "Description" => "Limited to 22 characters for the charge descriptor and must not use the greater than, less than, single quote or double-quote symbols (>, <, ‘, “)"), 
      "addInvoiceNumberToDescriptor" => array("FriendlyName" => "Add the Invoice # to the Custom Statement Descriptor", "Type" => "yesno"), 
      "applePay" => array("FriendlyName" => "Enable Apply Pay", "Type" => "yesno")

    );
	return $configarray;
}

function stripe_update_customer($params) {
	
   $stripe_token = $_SESSION['stripeToken'];
   unset($_SESSION['stripeToken']);
   
   $whmcs_client_id = $params['clientdetails']['userid'];
   
   $client_data = \WHMCS\User\Client::find($whmcs_client_id);
   
   $gateway_id = $client_data->gatewayid;
   global $cc_encryption_hash;
   $cchash = md5($cc_encryption_hash.$whmcs_client_id);
   $email = $client_data->email;
   global $CONFIG;
   $systemurl = ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'].'/' : $CONFIG['SystemURL'].'/';

  
   $secret_key = Capsule::table('tblpaymentgateways')->where('gateway','stripe')->where('setting','secretKey')->first()->value;

   $country_code_check  = Capsule::table('tblpaymentgateways')->where('gateway','stripe')->where('setting','countryCodeCheck')->first()->value;
   
   try {
	   \Stripe\Stripe::setApiKey($secret_key);
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
         $client_country = "";
         
         if ($client_data->billingContactId != 0 ) {
           $client_country = \WHMCS\User\Client\Contact::find($client_data->billingContactId)->country;
         }
         else {
           $client_country = $client_data->country;
         }
         
         if ($country_code_check != "on" || $client_country == $card->country ) {
           $exp_date = $_SESSION['stripe_ccexpirymonth'].substr($_SESSION['stripe_ccexpiryyear'],-2);
	       $client_data->cardtype = $card->brand;
	       $client_data->gatewayid = $stripe_customer->id;
	       $client_data->cardlastfour = $card->last4;
	       $client_data->expdate = $client_data->generateCreditCardEncryptedField($exp_date);
	       $client_data->save();
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
	     $client_country = "";
  
         
         if ($client_data->billingContactId != 0 ) {
           $client_country = \WHMCS\User\Client\Contact::find($client_data->billingContactId)->country;
         }
         else {
           $client_country = $client_data->country;
         }
         
         if ($country_code_check != "on" || $client_country == $card->country ) {
           $exp_date = $_SESSION['stripe_ccexpirymonth'].substr($_SESSION['stripe_ccexpiryyear'],-2);
	       $client_data->cardtype = $card->brand;
	       $client_data->gatewayid = $stripe_customer->id;
	       $client_data->cardlastfour = $card->last4;
	       $client_data->expdate = $client_data->generateCreditCardEncryptedField($exp_date);
	       $client_data->save();   }	                       
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

          $new_charge = array(
		    "amount" => $params['amount']*100,
		    "currency" => $currency,
		    "customer" => $gatewayid,
		    "description" => "Payment of Invoice #" . $params['invoiceid']);


          if (!empty($params['statementDescriptor'])) {
            if ($params['addInvoiceNumberToDescriptor'] == 'on') {
              $statement_descriptor = $params['statementDescriptor'] . " #" . $params['invoiceid'];
              if (strlen($statement_descriptor) > 22) {
	            $remaining_chars = 22-strlen(" #" . $params['invoiceid']);
	            $statement_descriptor = substr($statement_descriptor,0,$remaining_chars) . " #" . $params['invoiceid'];
              }
            }
            else {
	          $statement_descriptor = $params['statementDescriptor'];
            }
            $new_charge["statement_descriptor"] = $statement_descriptor;
          }
          
      	  $charge = \Stripe\Charge::create($new_charge);
		   
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