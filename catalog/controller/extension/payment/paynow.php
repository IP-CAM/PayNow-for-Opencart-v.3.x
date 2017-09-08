<?php
/**
 * Copyright (c) 2008 PayNow (Pty) Ltd
 * You (being anyone who is not PayNow (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayNow account. If your PayNow account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

class ControllerExtensionPaymentPayNow extends Controller
{
    var $pnHost = '';

    function __construct( $registry )
    {
        parent::__construct( $registry );
        $this->pnHost = 'https://paynow.sagepay.co.za/site/paynow.aspx';
    }

    public function index()
    {
        $this->load->language( 'extension/payment/paynow' );

        $data[ 'button_confirm' ] = $this->language->get( 'button_confirm' );

        $data[ 'action' ] = $this->pnHost;

        $this->load->model( 'checkout/order' );

        $order_info = $this->model_checkout_order->getOrder( $this->session->data[ 'order_id' ] );

        if ( $order_info )
        {
            $order_info['currency_code'] = 'ZAR';

            $data['recurring'] = false;
            foreach ( $this->cart->getProducts() as $product )
            {
                if ( $product['recurring'] )
                {
                    $data['recurring'] = true;

                    if ( $product['recurring']['frequency'] == 'month' )
                    {
                        $frequency = 3;
                    }

                    if ( $product['recurring']['frequency'] == 'year' )
                    {
                        $frequency = 6;
                    }

                    $cycles = $product['recurring']['duration'];

                    $recurring_amount = $product['recurring']['price'];

                    $custom_str3 = $product['recurring']['recurring_id'];

                    $custom_str4 = $this->session->data[ 'order_id' ];

                    $custom_str5 = $product['product_id'];

                    $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring` SET `order_id` = '" . $this->session->data[ 'order_id' ] . "', `reference` = '" . $this->session->data[ 'order_id' ] . "', `product_id` = '" . $product['product_id'] . "',
                     `product_name` = '" . $product['name'] ."', `product_quantity` = '" . $product['quantity'] . "', `recurring_id` = '" . $product['recurring']['recurring_id'] . "',
                      `recurring_name` = '" . $product['recurring']['name'] . "', `recurring_description` = '" . $product['recurring']['name'] . "',
                      `recurring_frequency` = '" . $frequency . "', `recurring_cycle` = '1', `recurring_duration` = '" . $cycles . "',
                      `recurring_price` = '" . $recurring_amount . "', `status` = '6', `date_added` = NOW()");
                }
            }

            $service_key = $this->config->get( 'payment_paynow_service_key' );

            $return_url = $this->url->link( 'checkout/success' );
            $cancel_url = $this->url->link( 'checkout/checkout', '', 'SSL' );
            $notify_url = $this->url->link( 'extension/payment/paynow/callback', '', 'SSL' );
            $name_first = html_entity_decode( $order_info[ 'payment_firstname' ], ENT_QUOTES, 'UTF-8' );
            $name_last = html_entity_decode( $order_info[ 'payment_lastname' ], ENT_QUOTES, 'UTF-8' );
            $email_address = $order_info[ 'email' ];

            $orderID = $this->session->data[ 'order_id' ];
            $amount = $this->currency->format( $order_info[ 'total' ], $order_info[ 'currency_code' ], '', false );
            $item_name = $this->config->get( 'config_name' ) . ' - #' . $this->session->data[ 'order_id' ];
            // $item_description = $this->language->get( 'text_sale_description' );
            $item_description = "Order #{$orderID} - {$customerName}";
            $reference = $this->session->data[ 'order_id' ];
            $customerID = $order_info['customer_id'];

			$sageGUID = "94cdf2e6-f2e7-4c91-ad34-da5684bfbd6f";

			$order_id_unique = $orderID . "_" . date("Ymds");

            $payArray = array (
        		'm1' => $service_key,
        		'm2' => '24ade73c-98cf-47b3-99be-cc7b867b3080',
        		// 'm3' => $sageGUID,

        		'p2' => $order_id_unique,
        		'p3' => html_entity_decode( $item_description ) . " - " . $orderID,

        		'm4' => $customerID,
        		'm5' => $name_last,
        		'm6' => $email_address,

        		'm9' => $email_address,
        		'm10' => 'route=extension/payment/paynow/callback',

        		'p4' => $amount,

        		'return_url' => $return_url,
        		'cancel_url' => $cancel_url,
        		'notify_url' => $notify_url,

        		'Budget' => 'N',

        		// 'item_description' => html_entity_decode( $item_description ),
            );


            if ( $data['recurring'] )
            {
                $payArray['custom_str2'] = date( 'Y-m-d' );
                $payArray['custom_str3'] = $custom_str3;
                $payArray['custom_str4'] = $custom_str4;
                $payArray['custom_str5'] = $custom_str5;
                $payArray['subscription_type'] = '1';
                $payArray['billing_date'] = date( 'Y-m-d' );
                $payArray['recurring_amount'] = $recurring_amount;
                $payArray['frequency'] = $frequency;
                $payArray['cycles'] = $cycles;
            }

            $secureString = '';
            foreach ( $payArray as $k => $v )
            {
                $secureString .= $k . '=' . urlencode( trim( $v ) ) . '&';
                // $data[ $k ] = $v;
            }
            $data['payvars'] = $payArray;

            // $securityHash = md5( $secureString );
            // $data[ 'signature' ] = $securityHash;
            // $data[ 'user_agent' ] = 'OpenCart 3.0';

            if ( file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/template/extension/payment/paynow' ) )
            {
                return $this->load->view( $this->config->get( 'config_template' ) . '/template/extension/payment/paynow',
                    $data );
            }
            else
            {
                return $this->load->view( 'extension/payment/paynow', $data );
            }

        }
    }

    /**
     * callback
     *
     * ITN callback handler
     *
     * @date 07/08/2017
     * @version 2.0.0
     * @access public
     *
     * @author  PayNow
     *
     */
    public function callback()
    {
        if ( $this->config->get( 'payment_paynow_debug' ) )
        {
            $debug = true;
        }
        else
        {
            $debug = false;
        }
        define( 'PN_DEBUG', $debug );
        include( 'paynow_common.inc' );
        $pnError = false;
        $pnErrMsg = '';
        $pnDone = false;
        $pnData = array();
        $pnParamString = '';
        if ( isset( $this->request->post[ 'Reference' ] ) )
        {
        	$raw = $this->request->post[ 'Reference' ];
            $exploded = explode('_', $raw);
            $order_id = array_shift($exploded);

            pnlog('-- Order ID set to : ' . $order_id);

        }
        else
        {
            $order_id = 0;
            pnlog('-- Order ID not found');
        }


        pnlog( 'PayNow ITN call received' );

        //// Notify PayNow that information has been received
        if ( !$pnError && !$pnDone )
        {
            header( 'HTTP/1.0 200 OK' );
            flush();
        }

        //// Get data sent by PayNow
        if ( !$pnError && !$pnDone )
        {
            pnlog( 'Get posted data' );

            // Posted variables from ITN
            $pnData = pnGetData();
            pnlog( 'PayNow Data: ' . print_r( $pnData, true ) );

            if ( $pnData === false )
            {
                $pnError = true;
                $pnErrMsg = PN_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        // if ( !$pnError && !$pnDone )
        // {
        //     pnlog( 'Verify security signature' );
        //     $passphrase = $this->config->get( 'payment_paynow_passphrase' );
        //     $pnPassphrase = empty( $passphrase ) ? null : $passphrase;

        //     $server = empty($this->config->get( 'payment_paynow_sandbox' )) ? 'live' : 'test';

        //     // If signature different, log for debugging
        //     if ( !pnValidSignature( $pnData, $pnParamString, $pnPassphrase, $server ) )
        //     {
        //         $pnError = true;
        //         $pnErrMsg = PN_ERR_INVALID_SIGNATURE;
        //     }
        // }

        //// Verify source IP (If not in debug mode)
        if ( !$pnError && !$pnDone && !PN_DEBUG )
        {
            pnlog( 'Verify source IP' );

            if ( !pnValidIP( $_SERVER[ 'REMOTE_ADDR' ] ) )
            {
                $pnError = true;
                $pnErrMsg = PN_ERR_BAD_SOURCE_IP;
            }
        }
        //// Get internal cart
        if ( !$pnError && !$pnDone )
        {
            // Get order data
            $this->load->model( 'checkout/order' );
            $order_info = $this->model_checkout_order->getOrder( $order_id );

            pnlog( "Purchase:\n" . print_r( $order_info, true ) );
        }

        //// Verify data received
        // if ( !$pnError )
        // {
        //     pnlog( 'Verify data received' );

        //     $pnValid = pnRequestTrace( $pnData );

        //     if ( !$pnValid )
        //     {
        //         $pnError = true;
        //         $pnErrMsg = PN_ERR_BAD_ACCESS;
        //     }
        // }

        //// Check data against internal order
        if ( !$pnError && !$pnDone )
        {
            pnlog( 'Check data against internal order' );


            $amount = $this->currency->format( $order_info['total'], 'ZAR', '', false );

            // Check order amount
            if ( !pnAmountsEqual( $pnData[ 'Amount' ], $amount ) )
            {
                $pnError = true;
                $pnErrMsg = PN_ERR_AMOUNT_MISMATCH;
            }

        }

        //// Check status and update order
        if ( !$pnError && !$pnDone )
        {
            pnlog( 'Check status and update order' );

            $transaction_id = $pnData[ 'RequestTrace' ];

                switch ($pnData['TransactionAccepted']) {
                    case 'true':
                        pnlog('- Complete');

                        // Update the purchase status
                        $order_status_id = $this->config->get('payment_paynow_completed_status_id');
						pnlog('-- Order Status Id: ' . $order_status_id);

                        break;

                    case 'false':
                        pnlog('- Failed');

                        // If payment fails, delete the purchase log
                        $order_status_id = $this->config->get('payment_paynow_failed_status_id');
                        pnlog('-- Order Status Id: ' . $order_status_id);

                        break;

                    case 'PENDING':
                        pnlog('- Pending');

                        // Need to wait for "Completed" before processing
                        break;

                    default:
                        // If unknown status, do nothing (safest course of action)
                        break;
                }

                if( $this->model_checkout_order )
                	$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);

				if($pnData ['TransactionAccepted'] == 'true') {
					$this->response->redirect($this->url->link('checkout/success'));
				} else {
					$this->session->data['error'] = "Transaction failed, reason: " . $pnData['Reason'];
					$this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
				}
                return true;

        }
        else
		{
			if( $this->model_checkout_order ) {
				$this->model_checkout_order->addOrderHistory( $order_id, $this->config->get( 'config_order_status_id' ) );
			}
			pnlog( "Errors:\n" . print_r( $pnErrMsg, true ) );
			return false;
		}

    }

}
