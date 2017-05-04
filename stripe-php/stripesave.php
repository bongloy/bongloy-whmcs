<?php
use Illuminate\Database\Capsule\Manager as Capsule;

/*
  
  Stripe WHMCS Payment Gateway 4.0.2 for WHMCS 7.x  

  Copyright (c) 2016, Hosting Playground Inc
  All rights reserved.
*/	



require("../../../init.php");
require("../../../includes/functions.php");
require("../../../includes/gatewayfunctions.php");
require("../../../includes/invoicefunctions.php");
require_once("init.php");


if (isset($_SESSION['uid'])) {
   $whmcs_client_id = $_SESSION['uid'];
   
   
   $client_data = \WHMCS\User\Client::find($whmcs_client_id);
   $gateway_id = $client_data->gatewayid;
   $cchash = md5($cc_encryption_hash.$whmcs_client_id);
   $email = $client_data->email;
   global $CONFIG;
   $systemurl = ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'].'/' : $CONFIG['SystemURL'].'/';
   
   $secret_key = Capsule::table('tblpaymentgateways')->where('gateway','stripe')->where('setting','secretKey')->first()->value;

   $country_code_check  = Capsule::table('tblpaymentgateways')->where('gateway','stripe')->where('setting','countryCodeCheck')->first()->value;
   
   try {
	   \Stripe\Stripe::setApiKey($secret_key);
	   
	   $create_new_customer = true;
	   	   
	   if ($gateway_id != null && $gateway_id != '') {
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
	                           "card" => $_REQUEST['stripeToken']));
	       $client_country = "";
           if ($client_data->billingContactId != 0 ) {
             $client_country = \WHMCS\User\Client\Contact::find($client_data->billingContactId)->country;
           }
           else {
             $client_country = $client_data->country;
           }
   
           $card = $stripe_customer->sources->retrieve($stripe_customer->default_source);
	        
           if ($country_code_check != "on" || $client_country == $card->country ) {
  	         $exp_date = $_REQUEST['ccexpirymonth'].substr($_REQUEST['ccexpiryyear'],-2);
	         $client_data->cardtype = $card->brand;
	         $client_data->gatewayid = $stripe_customer->id;
	         $client_data->cardlastfour = $card->last4;
	         $client_data->expdate = $client_data->generateCreditCardEncryptedField($exp_date);
	         $client_data->save();
  	       }
	       else {
		     if (!isset($_POST['invoiceid'])) {
    	       logTransaction($gateway_name["value"],"Country Code does not match billing address country Customer ID:" . $_SESSION['uid'],"Error"); 
               header( 'Location: '.$systemurl.'clientarea.php?action=creditcard&error=1');
             }
	         else {
		       logTransaction($gateway_name["value"],"Country Code does not match billing address country, Invoice #" . $_POST['invoiceid'] . " Customer ID:" . $_SESSION['uid'],"Error"); 
		       echo "error";
	         }
		 }	     
	       
	   }
	   else {
		 // update existing customer  
		 $stripe_customer = \Stripe\Customer::retrieve($gateway_id);
	     $stripe_customer->card = $_REQUEST['stripeToken'];
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
	       $exp_date = $_REQUEST['ccexpirymonth'].substr($_REQUEST['ccexpiryyear'],-2);
	       $client_data->cardtype = $card->brand;
	       $client_data->gatewayid = $stripe_customer->id;
	       $client_data->cardlastfour = $card->last4;
	       $client_data->expdate = $client_data->generateCreditCardEncryptedField($exp_date);
	       $client_data->save();
	     }
	     else {
		   if (!isset($_POST['invoiceid'])) {
    	     logTransaction($gateway_name["value"],"Country Code does not match billing address country Customer ID:" . $_SESSION['uid'],"Error"); 
    	     $_SESSION['card_error'] = true;
             header( 'Location: '.$systemurl.'clientarea.php?action=creditcard&error=1');
             exit();
           }
	       else {
		     logTransaction($gateway_name["value"],"Country Code does not match billing address country, Invoice #" . $_POST['invoiceid'] . " Customer ID:" . $_SESSION['uid'],"Error"); 
		     echo "error";
	       }
		 }
	   
	   }
	   if (!isset($_POST['invoiceid'])) {
         header( 'Location: '.$systemurl.'clientarea.php?action=creditcard');
       }
   }
   catch(Exception $e) {
       $body = $e->getJsonBody();
       $error_message = $body["error"]["message"];

 	   $gateway_name = Capsule::table('tblpaymentgateways')->where('gateway','stripe')->where('setting','name')->first()->value;

 	   if (!isset($_POST['invoiceid'])) {
    	 logTransaction($gateway_name,$error_message,"Error"); 
    	 $_SESSION['card_error'] = true;
         header( 'Location: '.$systemurl.'clientarea.php?action=creditcard&error=1');
       }
	   else {
		 logTransaction($gateway_name,$error_message,"Error"); 
		 echo "error";
	   }
   }
   

	
}



?>