# WP E-commerce - Svea WebPay payment module installation guide

## Requirements
* WP E-commerce plugin for Wordpress
* PHP configuration: 
	* OpenSSl
	* SOAP
	
## Installation
Upload all files contained within the *src* folder to your Wordpress *root* folder.

The files should be i the folder */wp-content/plugins/wp-e-commerce/wpsc-merchants/*

Login to your Wordpress site and choose *Store->settings->Payment options*

In the box for *General Settings*, tick the modules your wish to use. (*Invoice*,*Payment plan*, *Card*, *Direct bank*)
Click *update*.

Now your can configure the modules in the pane to the right. Enter your *username*, *password* and *secret word*. You can also set *testmode* here.

**Note** that Svea WebPay must be notified before the store can be put into production mode.

### You are now ready to start using Svea WebPay payment module!

##Important info
The request made from this module to SVEAs systems is made through a redirected form. 
The response of the payment is then sent back to the module via POST or GET (selectable in our admin).

###When using GET
Have in mind that a long response string sent via GET could get cut off in some browsers and especially in some servers due to server limitations. 
Our recommendation to solve this is to check the PHP configuration of the server and set it to accept at LEAST 512 characters.


###When using POST
As our servers are using SSL certificates and when using POST to get the response from a payment the users browser propmts the user with a question whether to continue or not, if the receiving site does not have a certificate.
Would the customer then click cancel, the process does not continue.  This does not occur if your server holds a certicifate. To solve this we recommend that you purchase a SSL certificate from your provider.

We can recommend the following certificate providers:
* InfraSec:  infrasec.se
* VeriSign : verisign.com