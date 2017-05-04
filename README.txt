* REQUIREMENTS *

- This module requires the mbstring, json, and curl PHP extension and WHMCS 7.x. If these libraries are missing it will cause a 500 error or a blank screen.

* INSTALLATION INSTRUCTIONS *

The Stripe gateway module requires the use of modified templates that are provided with this distribution. Please follow the instructions below to setup the Stripe gateway module. If you are upgrading from a previous version, please use the same instructions below and replace any existing files used by this module. The templates provided are based on WHMCS 7.x six and five themes

* Upload stripe.php and the stripe-php folder to your whmcs's modules/gateways folder.

* Upload the hooks/stripe.php file to your whmcs's includes/hooks folder.

* Copy creditcard.tpl, clientareacreditcard.tpl to your WHMCS's template directory from the templates/six directory. If you made any custom changes to these templates files you will need to make these changes again inside of the new template file. These templates are based off of the six templates that come with WHMCS Version 7.x.

* If you use the boxes or  modern order form:
   - Replace the viewcart.tpl file with the one provided in the orderforms folder. 

* If you are using any of the other order forms that come with WHMCS 7.x:
  - For all of the above order forms, replace the orderforms/standard_cart/checkout.tpl with the orderforms/standard_cart/checkout.tpl provided.

Please note this module only supports the carts that come with WHMCS, if you are using a custom cart this module requires modifications to the hook file and to the viewcart.tpl or checkout.tpl in order to function properly. We do a offer custom cart integration service for $50 USD. Please contact support@serverping.net for more information.  

* Copy the orderforms/ccforms directory to your WHMCS's orderforms directory.

* Enable the Stripe module in the WHMCS admin area by going to Setup->Payments->Payment Gateways and paste in your Secret Key and Publishable Key
from the Stripe control panel which can be found by going to Your Account, API Keys.

* Make sure you do not have the Disable Credit Card Storage option unchecked under Setup->General->Security. If this is checked the module will not function since this prevents the customer tokens from being stored.

* If you wish to allow your customers to pay with Apple Pay, you will first need to enable support for this in your Stripe control panel.
 -In the Account Settings area, click the Apple Pay button.
 -View and accept the apple terms of service.
 -Then click Add new domain and follow the on screen instructions.
 After you complete this step, you can then enable Apple Pay in the gateway configuration in WHMCS.


* If you wish to be able to update your client's credit card details from the WHMCS admin area, you will need to download and install the Stripe Admin Area Credit Card Update Module which will add Stripe.JS support to the admin area. Without this module you may run into PCI compliance issues. This module is available to any customer with an active support plan at the following url:
http://www.serverping.net/clients/dl.php?type=d&id=38

* Want to accept Bitcoin via WHMCS and your Stripe account? We offer a Stripe Bitcoin payment gateway for $15 which includes one year of support and updates which may be purchased at the following url (Must has a US bank account):
https://www.serverping.net/clients/cart.php?a=add&pid=35

* Want to accept ACH payments via WHMCS and your Stripe account? We offer a ACH payment gateway for $30 which includes one year of support and updates which may be purchased at the following url (Must has a US bank account and funds can only be received in USD):
https://www.serverping.net/clients/cart.php?a=add&pid=39


* If you have any questions or need installation assistance, we offer commercial support for just $15/year. Please do not contact us regarding this module unless you have purchased the support plan.
To purchase commercial support, login to our client area using the following link:
https://www.serverping.net/clients/cart.php?gid=addons

* Please consider supporting this module by making a donation towards the development costs at https://www.serverping.net/clients/index.php?m=donate