Sage Pay Now Credit Card Payment Module for OpenCart v3
=======================================================

Introduction
------------
Sage Pay South Africa's Pay Now third party gateway integration for OpenCart.

Installation Instructions
-------------------------
Download the files from GitHub:

* https://github.com/SagePay/PayNow-OpenCart-v3/archive/master.zip

Copy all the files to your OpenCart /admin and /catalog folders.

Configuration
-------------

Prerequisites:

You will need:

* Sage Pay Now login credentials
* Sage Pay Now Service key
* OpenCart admin login credentials

Sage Pay Now Gateway Server Configuration Steps:

1. Log into your Sage Pay Now Gateway Server configuration page:
	https://merchant.sagepay.co.za/SiteLogin.aspx
2. Go to Account / Profile
3. Click Sage Connect
4. Click Pay Now
5. Make a note of your Service key

Sage Pay Now Callback

6. Choose both the following URLs for your Accept and Decline URLs:
	http://opencart_installation/index.php

7. Choose both the following URLs for your Redirect and Notify URLs:
	http://opencart_installation/paynow_callback.php

OpenCart Steps:

1. Log into OpenCart as admin
2. Click on Extensions / Payments
3. Scroll to Sage Pay Now and click Install
4. Click 'Edit' next to Sage Pay Now
5. Type in your Sage Pay Now Service Key
6. Match payment statuses, i.e. choose Pending, Complete, Failed, and Cancelled
7. Click 'Save'

Issues & Feature Requests
-------------------------

We welcome your feedback.

Please contact Sage Pay South Africa with any questions or issues.
