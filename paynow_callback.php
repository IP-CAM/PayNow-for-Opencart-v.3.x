<?php

/**
 * Load whatever is necessary for the current system to function
 */
function pn_load_system() {

	define('BASEDIR', dirname(__FILE__));
	define('VERSION', '3.0.2.0');

	// Configuration
	if (is_file(BASEDIR.'/config.php')) {
		require_once(BASEDIR.'/config.php');
	}

	// Startup
	require_once(BASEDIR.'/system/startup.php');

	start('catalog');

	return;
}

/**
 * Load PayNow functions/files
 */
function pn_load_paynow() {

	require dirname(__FILE__) . '/catalog/controller/extension/payment/paynow.php';

	if( !defined('PN_DEBUG') )
		define ('PN_DEBUG', false);

	if( !defined('PN_SOFTWARE_NAME') )
		require dirname(__FILE__) . '/catalog/controller/extension/payment/paynow_common.inc';
}


function pn_url_origin($s, $use_forwarded_host=false) {
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}


function pn_full_url($s, $use_forwarded_host=false) {
	return pn_url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
}

/**
 * Get the URL we'll redirect users to when coming back from the gateway (for when they choose EFT/Retail)
 */
function pn_get_redirect_url() {
	$url_for_redirect = pn_full_url($_SERVER);
	$url_for_redirect = str_ireplace(basename(__FILE__), "index.php", $url_for_redirect);

	$url_for_redirect = preg_replace("/\?.+$/", "", $url_for_redirect);

	return $url_for_redirect;
}

/**
 * Check if this is a 'callback' stating the transaction is pending.
 */
function pn_is_pending() {
	return isset($_POST['TransactionAccepted'])
		&& $_POST['TransactionAccepted'] == 'false'
		&& stristr($_POST['Reason'], 'pending');
}

// Load System
pn_load_system();

// Load PayNow
// pn_load_paynow();

// Redirect URL for users using EFT/Retail payments to notify them the order's pending
$url_for_redirect = pn_get_redirect_url() . "?route=account/order";

pflog(__FILE__ . " POST: " . print_r($_REQUEST, true) );

if( isset($_POST) && !empty($_POST) && !pn_is_pending() ) {

	// pnPost( pn_get_redirect_url(), "?route=payment/paynow/callback", http_build_query($pnData) );

	// This is the notification coming in!
	// Act as an IPN request and forward request to Credit Card method.
	// Logic is exactly the same

	global $registry;
	$PayNowController = new ControllerExtensionPaymentPayNow($registry);
	$PayNowController->callback();
	die();

} else {
	// Probably calling the "redirect" URL

	pflog(__FILE__ . ' Probably calling the "redirect" URL');

	if( $url_for_redirect ) {
		header ( "Location: {$url_for_redirect}" );
	} else {
	    die( "No 'redirect' URL set." );
	}
}

die( PN_ERR_BAD_ACCESS );
