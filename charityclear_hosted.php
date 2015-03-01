<?php
/*
Plugin Name: Charity Clear Hosted Form Gateway 
Description: Interfaces with Charity Clear Hosted Gateway
Version: 1.0
Author URI: http://www.princesssparkle.co.uk
*/

add_action('plugins_loaded', 'init_charity_clear_hosted', 0);

function init_charity_clear_hosted() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

	class WC_charity_clear_hosted extends WC_Payment_Gateway  {

		public function __construct() {
			
			$this->id = 'charityclearhosted';
			$this->method_title	= __('CharityClear', 'woothemes');
			$this->icon = apply_filters('woocommerce_charityclear_icon', plugins_url('logo.png',__FILE__));
			$this->has_fields = false;
			
			// Load the form fields.
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
			// Define user set variables
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->pay_description = $this->settings['pay_description'];
			$this->merchant_id = $this->settings['merchant_id'];
            $this->currency_id = $this->settings['currency_id'];
            $this->country_id = $this->settings['country_id'];
			$this->signature_key = $this->settings['signature_key'];
			$this->gateway_url = $this->settings['gateway_url'];
			$this->success_url = $this->settings['success_url'];
			$this->transation_prefix = $this->settings['transation_prefix'];
			
			// Actions
			add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_receipt_charityclearhosted', array(&$this, 'receipt_page'));

		}
		
		function init_form_fields() {
			
			$this->form_fields = array(
                                          'enabled' => array(
                                                'title' => __( 'Enable/Disable', 'woothemes' ),
                                                'type' => 'checkbox',
                                                'label' => __( 'Enable Charity Clear Payment', 'woothemes' ),
                                                'default' => 'yes'
                                            ),

                                          'title' => array(
                                                'title' => __( 'Title', 'woothemes' ),
                                                'type' => 'text',
                                                'description' => __( 'This controls the title the user sees during checkout.', 'woothemes' ),
                                                'default' => __( 'Credit Card via Charity Clear', 'woothemes' )
                                            ),

                                         'description' => array(
                                                'title' => __( 'Customer Message', 'woothemes' ),
                                                'type' => 'textarea',
                                                'description' => __( 'Accepts credit and debit card payments.', 'woothemes' ),
                                                'default' => 'Please proceed to Charity Clear to complete this transaction.'
                                            ),
										   'merchant_id' => array(
										  'title' => __( 'Your Merchant ID', 'woocommerce' ),
										  'type' => 'textarea',
										  'description' => __( 'Your Merchant ID Supplied by Charity Clear.', 'woocommerce' ),
										  'default' => __("100003", 'woocommerce')
										   ),
										   
										   'currency_id' => array(
										  'title' => __( 'Your Currency ID', 'woocommerce' ),
										  'type' => 'textarea',
										  'description' => __( 'Your Currency ID Supplied by Charity Clear.', 'woocommerce' ),
										  'default' => __("826", 'woocommerce')
										   ),   
										  
										'country_id' => array(
										  'title' => __( 'Your Country ID', 'woocommerce' ),
										  'type' => 'textarea',
										  'description' => __( 'Your Country ID Supplied by Charity Clear.', 'woocommerce' ),
										  'default' => __("826", 'woocommerce')
										   ),  
										
										'signature_key' => array(
										  'title' => __( 'Your Secret Key', 'woocommerce' ),
										  'type' => 'textarea',
										  'description' => __( 'This is the signature key you set in your merchant centre.', 'woocommerce' ),
										  'default' => __("yoursecretk3y", 'woocommerce')
										   ), 
										   
										'gateway_url' => array(
										  'title' => __( 'Payment Gateway URL', 'woocommerce' ),
										  'type' => 'textarea',
										  'description' => __( 'This is the url for the charity clear payment gateway - you might need to change this if you need to brand your form', 'woocommerce' ),
										  'default' => __("https://gateway.charityclear.com/paymentform/", 'woocommerce')
										   ),
									
										'success_url' => array(
											'title' => __( 'Success URL', 'woothemes' ),
											'type' => 'text',
											'description' => __( 'For example, http://yourdomain.com/thank-you. If a page doesn\'t exist, create one.', 'woothemes' ),
											'default' => __( '' , 'woothemes' )
										),
										
										'transation_prefix' => array(
											'title' => __( 'Transation Prefix', 'woothemes' ),
											'type' => 'text',
											'description' => __( 'Enter a prefix for you transactions. This will help you identify them in the Charity Clear MM', 'woothemes' ),
											'default' => __( 'MyStore' , 'woothemes' )
										),
			);
			
		}
		
		public function admin_options() {
			?>
			<h3><?php _e('Charity Clear Payment', 'woothemes'); ?></h3>
			<table class='form-table'>
			<?php
			  // Generate the HTML For the settings form.
			  $this->generate_settings_html();
			?>
			</table>
			<?php
		}

		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}
		
		function process_payment( $order_id ) {
			
			$order = &new WC_Order( $order_id );
			
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
			
		}

		function receipt_page( $order ) {
			echo '<p>'.__($this->pay_description, 'woocommerce').'</p>';
			echo $this->generate_charity_clear_form( $order );
		}

		function generate_charity_clear_form($order_id) {
			
			global $woocommerce;
			
			$order = new WC_Order( $order_id );
			$amount = $order->get_total();
            $amount = number_format((float)$amount, 2, '.', '');
			
            //$redirect_url = WC()->api_request_url( 'WC_charity_clear_hosted' );
            $redirect_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_charity_clear_hosted', home_url( '/' ) ) );
			$preshared_key = $this->signature_key;
			
			$putArray['merchantID'] = $this->merchant_id;
            $putArray['countryCode'] = $this->country_id;
            $putArray['currencyCode'] = $this->currency_id;
			$putArray['type'] = 1;
			$putArray['amount'] = $amount;
			$putArray['transactionUnique'] = $order_id;
			$putArray['redirectURL'] = $redirect_url;
			$putArray['customerName'] = $order->billing_first_name." ".$order->billing_last_name;
			$putArray['customerEmail'] = $order->billing_email;
			$putArray['customerAddress'] = $this->charity_clear_buildaddress($order);
			$putArray['customerPostcode'] = $order->billing_postcode;
			
            ksort($putArray);
                        
			$signature = hash("SHA512", http_build_query($putArray) . $preshared_key);
     			 
                        
			$gateway_url = $this->gateway_url;

			$output.= '
			<div class="charityclear-form">
			 <form action='. htmlentities($gateway_url) .' method="post"> ';
			 
			$output.= $this->charity_clear_addfields($putArray);
			 
			$output.= '
                         <input type="hidden" name ="signature" value="'.$signature.'"/>
     			 <input type="submit" value="PROCEED TO PAYMENT">
			</div>';
			
			return $output;
		}
		
	function charity_clear_buildaddress($order) {
   
		$address_out=implode("\n",array(
					$order->shipping_address_1,
					$order->shipping_address_2,
					$order->billing_state,
					$order->billing_country,
					
					));
		  return $address_out;
	}
	
	function charity_clear_addfields($fields) {
	   foreach ($fields as $name => $value) {
        	  $string .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
                }
  	   return $string;
	}
        

}


// Add the gateway to woo
function add_charity_clear_gateway( $methods ) {
  $methods[] = 'WC_charity_clear_hosted';
  return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_charity_clear_gateway' );

// Process the response
add_action('init', 'check_charity_clear_response');

function check_charity_clear_response() {
	
	if (isset($_REQUEST["signature"])) {
                $returned_post = $_POST;
                $returned_signature = $returned_post['signature']; // Grab the returned Signature
 
                unset($returned_post['signature']); //remove it from the Gateway return response
                
                $response_code = $returned_post['responseCode'];
                $response_message = $returned_post['responseMessage'];
		$display_amount = $returned_post['displayAmount'];
                $charity_clear = new WC_charity_clear_hosted;
                global $woocommerce;
                
                $signature_hash = signature_hash($returned_post, $returned_signature, $charity_clear->signature_key);
                echo "<br>Signature:".$returned_signature."<br>";
                echo "<br>Computed Hash:".$signature_hash."<br>";
                $compared_hash = check_hash($returned_signature, $signature_hash);
                if ($compared_hash == true) {
                    
                    $order_id = $returned_post['transactionUnique'];
                    $order = new WC_Order($order_id);
                  
                    if ($response_code == 0) {
                        
                        $msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                        $msg['class'] = 'success';
                        $order->add_order_note('Response Message: '.$response_message);
                        $order->add_order_note('Amount: '.$display_amount);
						
                        $woocommerce -> cart -> empty_cart();
                        $redirect_url = $order->get_checkout_order_received_url();
						$order->payment_complete();

                    } else {
                        $msg['class'] = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                        $order -> add_order_note('Response Message: '.$response_message." ".$message);
                        $redirect_url = get_permalink(woocommerce_get_page_id('checkout'));

                    }
                    
     
                }
               wc_add_notice( $msg['message'], $msg['class'] );
               
               wp_redirect( $redirect_url );
                
         }
         

}

        /*
        *  
        * The following function create a hash from the data sent back from Charity Clear 
        */
    function signature_hash($returned_post, $signature, $pre_shared_key) {
        
        ksort($returned_post); // sort it
        $sig_string = http_build_query($returned_post) . $pre_shared_key;
        // Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
        $sig_string = preg_replace('/%0D%0A|%0A%0D|%0A|%0D/i', '%0A', $sig_string);
        $our_hash = hash("SHA512", $sig_string);
   
        return $our_hash;
   }

       function check_hash($returned_hash,$signature_hash) {
        
         if ($returned_hash == $signature_hash) {
             return true;
         }
   
        return false;
   }

 
}
