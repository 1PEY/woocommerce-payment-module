<?php
/*
Plugin Name: WooCommerce 1PEY Payment Gateway
Plugin URI: https://github.com/1PEY/woocommerce-payment-module
Description: Extends WooCommerce with 1PEY payment gateway.
Version: 1.3.0
Author: 1PEY development team

Text Domain: woocommerce-onepey
Domain Path: /languages/

 */

//setup definitions - may not be needed but belts and braces chaps!
define('BT_ONEPEY_VERSION', '1.3.0');

if ( !defined('WP_CONTENT_URL') )
  define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');

if ( !defined('WP_PLUGIN_URL') )
  define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');

if ( !defined('WP_CONTENT_DIR') )
  define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

if ( !defined('WP_PLUGIN_DIR') )
  define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');

define("BT_ONEPEY_PLUGINPATH", "/" . plugin_basename( dirname(__FILE__) ));

define('BT_ONEPEY_BASE_URL', WP_PLUGIN_URL . BT_ONEPEY_PLUGINPATH);

define('BT_ONEPEY_BASE_DIR', WP_PLUGIN_DIR . BT_ONEPEY_PLUGINPATH);

//go looking for woocommerce - if not found then do not allow this plugin to do anything
if(!function_exists('bt_get_plugins'))
{
  function bt_get_plugins()
  {
    if ( !is_multisite() )
      return false;

    $all_plugins = array_keys((array) get_site_option( 'active_sitewide_plugins' ));
    if (!is_array($all_plugins) )
      return false;

    return $all_plugins;
  }
}

if ( in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins' )  ) || in_array('woocommerce/woocommerce.php', (array) bt_get_plugins() ) )
{
  load_plugin_textdomain('woocommerce-onepey', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
  add_action('plugins_loaded', 'bt_onepey_go', 0);
  add_filter('woocommerce_payment_gateways', 'bt_onepey_add_gateway' );

}

require_once dirname(  __FILE__  ) . '/onepey-api-php/lib/OnePEY.php';

//Launch plugin
function bt_onepey_go()
{

  class BT_OnePEY extends WC_Payment_Gateway
  {
    var $notify_url;
    var $return_url;

    public $id = 'onepey';
    public $icon;//not used
    public $has_fields = true;
    public $method_title;
    public $title;
    public $settings;

    /**
     * constructor
     *
     */
    function __construct()
    {
      global $woocommerce;
      // load form fields
      $this->init_form_fields();
      // initialise settings
      $this->init_settings();
      // variables
      $this->title                    = $this->settings['title'];
      //admin title
      if ( current_user_can( 'manage_options' ) ){
        $this->title                    = $this->settings['admin_title'];
      }

      \OnePEY\Settings::$gatewayBase = 'https://' . $this->settings['domain-gateway'];
      \OnePEY\Settings::$checkoutBase = 'https://' . $this->settings['domain-checkout'];
      \OnePEY\Settings::$merchantId = $this->settings['shop-id'];
      \OnePEY\Settings::$passCode = $this->settings['secret-key'];
      \OnePEY\Settings::$pSignAlgorithm = $this->settings['psign-algorithm'];
      //callback URL - hooks into the WP/WooCommerce API and initiates the payment class for the bank server so it can access all functions
      $this->notify_url = WC()->api_request_url('BT_OnePEY');
      $this->notify_url = str_replace('carts.local','webhook.1pey.com:8443', $this->notify_url);

      $this->return_url = WC()->api_request_url('BT_OnePEY');
      $this->return_url = str_replace('carts.local','webhook.1pey.com:8443', $this->return_url);

      $this->method_title             = $this->title;
      $this->description              = $this->settings['description'];
      $this->transaction_type         = $this->settings['tx-type'];
      $this->debug                    = $this->settings['debug'];
      $this->show_transaction_table   = $this->settings['show-transaction-table'] == 'yes' ? true : false;
      // Logs
      if ( 'yes' == $this->debug ){
        $this->log = new WC_Logger();
      }

      add_action('admin_menu', array($this, 'bt_admin_hide') );
      add_action('admin_notices',array($this, 'bt_admin_error') );
      add_action('admin_notices',array($this, 'bt_admin_message') );
      add_action('woocommerce_receipt_onepey', array( $this, 'receipt_page'));
      add_action('woocommerce_api_bt_onepey', array( $this, 'check_ipn_response' ) );
      add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

      // display transaction table
      if ( is_admin() && $this->show_transaction_table )
      {
        add_action( 'add_meta_boxes', array($this, 'create_order_transactions_meta_box') );
        //$this->create_order_transactions_meta_box();
      }
    } // end __construct

    public function admin_options()
    {
      echo '<h3>' . __('OnePEY', 'woocommerce-onepey') . '</h3>';
      echo '<table class="form-table">';
      // generate the settings form.
      $this->generate_settings_html();
      echo '</table><!--/.form-table-->';
    } // end admin_options()


    public function init_form_fields()
    {
    	
      if ( "yes" == $this->debug ){
    	$this->log->add( "onepey", "Function `process_order` init"  );
      }
    	
      // transaction options
      $tx_options = array('payment' => __('Payment', 'woocommerce-onepey'), 'authorization' => __('Authorization', 'woocommerce-onepey'));
      $psign_options = array(
      		"sha1" => "SHA-1 (160 bits)",
      		"sha224" => "SHA-2 (224 bits)",
      		"sha256" => "SHA-2 (256 bits)",
      		"sha384" => "SHA-2 (384 bits)",
      		"sha512" => "SHA-2 (512 bits)",
      );

      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable/Disable', 'woocommerce-onepey' ),
          'type' => 'checkbox',
          'label' => __( 'Enable OnePEY', 'woocommerce-onepey' ),
          'default' => 'yes'
        ),
        'title' => array(
          'title' => __( 'Title', 'woocommerce-onepey' ),
          'type' => 'text',
          'description' => __( 'This is the title displayed to the user during checkout.', 'woocommerce-onepey' ),
          'default' => __( 'Credit or debit card', 'woocommerce-onepey' )
        ),
        'admin_title' => array(
          'title' => __( 'Admin Title', 'woocommerce-onepey' ),
          'type' => 'text',
          'description' => __( 'This is the title displayed to the admin user', 'woocommerce-onepey' ),
          'default' => __( 'OnePEY', 'woocommerce-onepey' )
        ),
        'description' => array(
          'title' => __( 'Description', 'woocommerce-onepey' ),
          'type' => 'textarea',
          'description' => __( 'This is the description which the user sees during checkout.', 'woocommerce-onepey' ),
          'default' => __("VISA, Mastercard", 'woocommerce-onepey')
        ),
        'shop-id' => array(
          'title' => __( 'Merchant ID', 'woocommerce-onepey' ),
          'type' => 'text',
          'description' => __( 'Please enter your Merchant Id.', 'woocommerce-onepey' ),
          'default' => ''
        ),
        'secret-key' => array(
          'title' => __( 'Pass Code (secret key)', 'woocommerce-onepey' ),
          'type' => 'text',
          'description' => __( 'Please enter your Pass Code (secret key).', 'woocommerce-onepey' ),
          'default' => ''
        ),
        'psign-algorithm' => array(
          'title' => __( 'pSign Algorithm', 'woocommerce-onepey' ),
          'type' => 'select',
          'options' => $psign_options,
          'description' => __( 'Select assigned pSign Algorithm (leave default if not instructed to change)', 'woocommerce-onepey' ),
        ),
        'domain-gateway' => array(
          'title' => __( 'Payment gateway domain', 'woocommerce-onepey' ),
          'type' => 'text',
          'description' => __( 'Please enter payment gateway domain of your payment processor.', 'woocommerce-onepey' ),
          'default' => '1pey.com'
        ),
        'domain-checkout' => array(
          'title' => __( 'Payment page domain', 'woocommerce-onepey' ),
          'type' => 'text',
          'description' => __( 'Please enter payment page domain of your payment processor.', 'woocommerce-onepey' ),
          'default' => '1pey.com'
        ),
        'tx-type'      => array(
          'title' => __('Transaction Type', 'woocommerce-onepey'),
          'type' => 'select',
          'options' => $tx_options,
          'description' => __( 'Select Payment (Authorization & Capture)  or Authorization.', 'woocommerce-onepey' )
        ),

        'show-transaction-table' => array(
          'title' => __('Enable admin capture etc.', 'woocommerce-onepey'),
          'type' => 'checkbox',
          'label' => __('Show Transaction Table', 'woocommerce-onepey'),
          'description' => __( 'Allows admin to send capture/void/refunds', 'woocommerce-onepey' ),
          'default' => 'yes'
        ),

        'debug' => array(
          'title' => __( 'Debug Log', 'woocommerce-onepey' ),
          'type' => 'checkbox',
          'label' => __( 'Enable logging', 'woocommerce-onepey' ),
          'default' => 'no',
          'description' =>  __( 'Log events', 'woocommerce-onepey' ),
        )

      );
    } // end init_form_fields()

    function generate_onepey_form( $order_id ) {
      //creates a self-submitting form to pass the user through to the 1PEY server
      global $woocommerce;
      $order = new WC_order( $order_id );
      if ( 'yes' == $this->debug ){
        $this->log->add( 'onepey', 'Generating payment form for order ' . $order->get_order_number()  );
      }
      // Order number & Cart Contents for description field - may change
      $item_loop = 0;
      //grab the langauge

      $lang = explode('-', get_bloginfo('language'));
      $lang = $lang[0];

      if(in_array($lang,\OnePEY\Language::getSupportedLanguages())) {
        $language=$lang;
      } else {
        $language='en';
      }

      $transaction = new \OnePEY\PaymentHostedPageOperation;

      if ($this->transaction_type == 'authorization') {
	    $transaction = new \OnePEY\AuthorizationHostedPageOperation;
      }

      $orderId = ltrim( $order->get_order_number(), '#' );
      $orderIdPrefix = "TXWC-{$this->settings['shop-id']}-";
      $trackingId = $orderIdPrefix.str_pad($orderId, 16, '0', STR_PAD_LEFT).'-'.time();
      
      $transaction->money->setCurrency(get_woocommerce_currency());
      $transaction->money->setAmount($order->get_total());
      $transaction->setDescription(__('Order', 'woocommerce') . ' # ' .$order->get_order_number());
      $transaction->setTrackingId($trackingId);
      $transaction->customer->setFirstName($order->get_billing_first_name());
      $transaction->customer->setLastName($order->get_billing_last_name());
      $transaction->customer->setCountry($order->get_billing_country());
      $transaction->customer->setAddress($order->get_billing_address_1() . $order->get_billing_address_2());
      $transaction->customer->setCity($order->get_billing_city());
      $transaction->customer->setZip($order->get_billing_postcode());
      $transaction->customer->setIP($_SERVER['REMOTE_ADDR']);
      $transaction->customer->setEmail($order->get_billing_email());
      $transaction->customer->setPhone($order->get_billing_phone());

      if (in_array($order->get_billing_country(), array('US','CA'))) {
        $transaction->customer->setState($order->get_billing_state());
      }
      else{
        $transaction->customer->setState($order->get_billing_state());
      }

      $transaction->setReturnUrl(esc_url_raw( $this->get_return_url($order) ) );
      $transaction->setNotificationUrl($this->notify_url);

      $transaction->setLanguage($language);

      if ( 'yes' == $this->debug ){
        $this->log->add( 'onepey', 'Requesting token for order ' . $order->get_order_number()  );
      }

      $response = $transaction->submit();

      if(!$response->isValid() || $response->getStatus() != \OnePEY\ResponseBase::REDIRECT || empty($response->getRedirectUrl())) {

        if ( 'yes' == $this->debug ){
          $this->log->add( 'onepey', 'Unable to get payment redirect on order: ' . $order_id . 'Reason: ' . $response->getMessage()  );
        }

        if(!$response->isValid()){
        	
        	$responseMessage = 'Unable to contact the payment server at this time. Please try later.';
        }
        else if($response->isError() || $response->isFailed()) {
        	
        	$responseMessage = 'Payment has failed or encountered some errors. Please try later.';
        }
        else if($response->isDeclined()) {
        	
        	$responseMessage = 'Payment has been declined by the proccessor. Please try later.';
        }
        else {
        	
        	$responseMessage = 'Payment has failed with unexpected processor response. Please try later.';
        }
        
        wc_add_notice(__($responseMessage), 'error');
        wc_add_notice($response->getMessage(), 'error');
        exit($responseMessage);
      }

      //now look to the result array for the token
      
      if ($response->isValid() && !empty($response->getRedirectUrl())) {
      
      	$customerRedirect = new \OnePEY\CustomerRedirectHostedPage($response->getRedirectUrl(), $response->getUid());
      	$customerRedirect->money = $transaction->money;
      	$customerRedirect->setTrackingId($transaction->getTrackingId());
      	$customerRedirect->setReturnUrl(esc_url_raw( $this->get_return_url($order) ) );
      	$customerRedirect->setNotificationUrl($this->notify_url);
      
      	$payment_url = $customerRedirect->getFullRedirectUrl();

        update_post_meta(  ltrim( $order->get_order_number(), '#' ), '_Token', $transaction->getTrackingId() );
        if ( 'yes' == $this->debug ){
          $this->log->add( 'onepey', 'Token received, forwarding customer to: '.$payment_url);
        }
        
        wp_redirect( $payment_url );
        exit;
      } 
      else{
        wc_add_notice(__('Payment error: ').$response->getMessage(), 'error');
        if ( 'yes' == $this->debug ){
          $this->log->add( 'onepey', 'Payment error order ' . $order_id.'  '.$error_to_show  );
        }
        exit('Sorry - there was an error contacting the bank server, please try later');
      }

      
      wc_enqueue_js('
        jQuery("body").block({
          message: "'.__('Thank you for your order. We are now redirecting you to make the payment.', 'woocommerce-onepey').'",
            overlayCSS: {
              background: "#fff",
              opacity: 0.6
            },
            css: {
              padding:        20,
              textAlign:      "center",
              color:          "#555",
              border:         "3px solid #aaa",
              backgroundColor:"#fff",
              cursor:         "wait",
              lineHeight:		"32px"
            }
        });
        jQuery("#submit_onepey_redirect").click();
      ');
      return '<form action="'.$payment_url.'" method="post" id="onepey_payment_form">
        <input type="hidden" name="token" value="' . $response->getUid() . '">
        <input type="submit" class="button-alt" id="submit_onepey_redirect" value="'.__('Make payment', 'woocommerce-onepey').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woothemes').'</a>
        </form>';
    }

    function process_payment( $order_id ) {
      global $woocommerce;
      
      if ( "yes" == $this->debug ){
      	$this->log->add( "onepey", "Function `process_payment` init"  );
      }

      $order = new WC_Order( $order_id );

      // Return payment page
      return array(
        'result'    => 'success',
        'redirect'	=> add_query_arg('key', $order->get_order_key(), $order->get_checkout_payment_url( true ))
      );
    }
    // end process_payment

    function receipt_page( $order ) {

      if ( "yes" == $this->debug ){
      	$this->log->add( "onepey", "Function `receipt_page` init"  );
      }
      echo $this->generate_onepey_form( $order );

    }

    function thankyou_page()
    {
      if ( "yes" == $this->debug ){
      	$this->log->add( "onepey", "Function `thankyou_page` init"  );
      }
      if ($this->description) echo wpautop(wptexturize($this->description));
    } // end thankyou_page


    public function create_order_transactions_meta_box()
    {
      if ( "yes" == $this->debug ){
      	$this->log->add( "onepey", "Function `create_order_transactions_meta_box` init"  );
      }
      //add a metabox
      add_meta_box( 'bt-onepey-order-transaction-content',
        $this->title,
        array(&$this, 'order_transaction_content_meta_box'),
        'shop_order', 'normal', 'default');
    }// end meta_box_order_transactions

    public function order_transaction_content_meta_box($post) {
      //wordpress strips <form> tags so you cannot send POST data instead you have to
      //make up a GET url and append the amount field using jQuery

      if ( "yes" == $this->debug ){
      	$this->log->add( "onepey", "Function `order_transaction_content_meta_box` init"  );
      }
      
      $order = new WC_Order($post->ID);
      $display="";

      //WordPress will also mutilate or just plain kill any PHP Sessions setup so, instead of passing the return URL that way we'll pop it into a post meta
      update_post_meta($post->ID,'_return_url', $this->curPageURL());

      switch ( $order->get_status()){
      case 'pending':
        $display.=__('Order currently pending no capture/refund available', 'woocommerce-onepey');
        break;
      case 'on-hold':
        //params for capture
        $arr_params = array( 'wc-api' => 'BT_OnePEY',
          'onepey' => 'capture',
          'uid' => md5(get_post_meta($post->ID, '_uid', true)),
          'oid' => $post->ID );
        $display.= $this->_getActionButton('capture', $order, $arr_params);
        //params for void
        $arr_params = array( 'wc-api' => 'BT_OnePEY',
          'onepey' => 'void',
          'uid' => md5(get_post_meta($post->ID, '_uid', true)),
          'oid' => $post->ID );
        $display.= $this->_getActionButton('void', $order, $arr_params);

        break;
      case 'processing':
        //params for refund
        $arr_params = array( 'wc-api' => 'BT_OnePEY',
          'onepey' => 'refund',
          'uid' => md5(get_post_meta($post->ID, '_uid', true)),
          'oid' => $post->ID );
        $display.= $this->_getActionButton('refund', $order, $arr_params);
        break;

      default:
        $display.='';
        break;
      }
      echo '<div class="panel-wrap woocommerce">';
      echo $display;
      echo '</div>';

    }// end order_transaction_content_meta_box


    private function plugin_url()
    {
      return $this->plugin;
    }// end plugin_url

    private function _getActionButton($action, $order, $arr_params) {
      switch($action) {
        case 'capture':
          $message = __('Please enter amount to capture','woocommerce-onepey');
          $btn_txt = __('Capture','woocommerce-onepey');
          break;
        case 'void':
          $message = __('Please enter amount to void','woocommerce-onepey');
          $btn_txt = __('Void','woocommerce-onepey');
          break;
        case 'refund':
          $message = __('Please enter amount to refund','woocommerce-onepey');
          $btn_txt = __('Refund','woocommerce-onepey');
          $refund_reason_txt = __('Refund reason','woocommerce-onepey');
          break;
        default:
          return '';
      }
      $display="<script>
        function add${action}URL(element)
        {
          jQuery(element).attr('href', function() {
            return this.href + '&amount='+ jQuery('#bt_${action}_amount').val();
          });
        }
        </script>";

      $display.='<p class="form-field"><label for="bt_' . $action . '_amount">'.$message.'</label>';
      $display.='<input type="text" id="bt_' . $action . '_amount" size="8" value="'. ($order->get_total() ).'" /></p>';
      if ($action == 'refund') {
        $display.='<p class="form-field"><label for="refund_comment">'.$refund_reason_txt.'</label>';
        $display.='<input type="text" size="30" value="" name="comment" id="refund_comment"></p>';
      }
      $display.='<a  onclick="javascript:add' . $action . 'URL(this);"  href="'.str_replace( 'https:', 'http:', add_query_arg( $arr_params, home_url( '/' ) ) ).'">';
      $display.='<button type="button" class="button">'.$btn_txt.'</button></a>';
      return $display;

    }

    /**
     *this function is called via the wp-api when the 1PEY server sends
     *callback data
    */
    function check_ipn_response() {
    	
      if ( "yes" == $this->debug ){
    	$this->log->add( "onepey", "Function `check_ipn_response` init"  );
      }
    	
      //check for refund/capture/void
      if (isset($_GET['onepey']) && isset($_GET['uid']) &&   isset($_GET['oid'])){
        $this->child_transaction($_GET['onepey'], $_GET['uid'], $_GET['oid'], $_GET['amount'],$_GET['comment']);
        exit();
      }
      //do normal callback response

      global $woocommerce;

      $webhook = new \OnePEY\Webhook;

      if ($webhook->isAuthorized() && $webhook->isValid() && !empty($webhook->getUid())) {
        //log
        if ( "yes" == $this->debug ){
          $display="\n-------------------------------------------\n";
          $display.= "Order No: ".$webhook->getTrackingId();
          $display.= "\nUID: ".$webhook->getUid();
          $display.="\n--------------------------------------------\n";
          $this->log->add( "onepey", $display  );
        }

        $this->process_order($webhook);

      } else {
        if ( "yes" == $this->debug ){
          $display="\n----------- Unable to proceed --------------\n";
          $display.= "Order No: ".$webhook->getTrackingId();
          $display.="\n--------------------------------------------\n";
          $this->log->add( "onepey", $display  );
        }
        wp_die( "OnePEY Notify Failure" );
      }
    }
    //end of check_ipn_response

    /**
     * 
     * @param \OnePEY\Webhook $webhook
     */
    function process_order($webhook) {
      global $woocommerce;
      
      if ( "yes" == $this->debug ){
      	$this->log->add( "onepey", "Function `process_order` init"  );
      }
      
      $orderIdPrefix = "TXWC-{$this->settings['shop-id']}-";
      $order_id = ltrim(substr($webhook->getTrackingId(),strlen($orderIdPrefix),16), '0');
      
      $order = new WC_Order( $order_id );
      $status = $webhook->getStatus();
      
      if ($status == \OnePEY\ResponseBase::AUTHORIZED)
	      $type = 'authorization';
      else
	      $type = 'payment';
      
      if (in_array($type, array('payment','authorization'))) {
        update_post_meta(  $order_id, '_uid', $webhook->getUid() );

        $messages = array(
          'payment' => array(
            'success' => __('Payment success.', 'woocommerce-onepey'),
            'failed' => __('Payment failed.', 'woocommerce-onepey'),
            'incomplete' => __('Payment incomplete, order status not updated.', 'woocommerce-onepey'),
            'error' => __('Payment error, order status not updated.', 'woocommerce-onepey'),
          ),
          'authorization' => array(
            'success' => __('Payment authorised. No money captured yet.', 'woocommerce-onepey'),
            'failed' => __('Authorization failed.', 'woocommerce-onepey'),
            'incomplete' => __('Authorisation incomplete, order status not updated.', 'woocommerce-onepey'),
            'error' => __('Authorisation error, order status not updated', 'woocommerce-onepey'),
          )

        );
        $messages['callback_error'] = __('Callback error, order status not updated', 'woocommerce-onepey');

        if ( 'yes' == $this->debug ){
          $this->log->add( 'onepey', 'Transaction type: ' . $type . '. Payment status '.$status.'. UID: '.$webhook->getUid());
        }

        if ($webhook->isSuccess()) {
          if ($type == 'payment' && $order->get_status() != 'processing') {
            $order->add_order_note( $messages[$type]['success'] . ' UID: ' . $webhook->getUid() . '<br>' );
            $order->payment_complete($webhook->getUid());
          } elseif ($order->get_status() != 'on-hold') {
            $order->update_status( 'on-hold', $messages[$type]['success'] . ' UID: ' . $webhook->getUid() . '<br>' );
          }
        } elseif ($webhook->isFailed() || $webhook->isDeclined()) {
            $order->update_status( 'failed', $messages[$type]['failed'] . ' UID: '. $webhook->getUid() .'<br>' );
        } elseif ($webhook->isIncomplete()) {
            $order->add_order_note( $messages[$type]['incomplete'] . ' UID: ' . $webhook->getUid() . '<br>' );
        } elseif ($webhook->isError()) {
            $order->add_order_note( $messages[$type]['error'] . ' UID: ' . $webhook->getUid() . '<br>' );
        } else {
            $order->add_order_note( $messages['callback_error'] . ' UID: ' . $webhook->getUid() . '<br>' );
        }
      }
    }//end function


    function child_transaction($type, $uid, $order_id, $amount, $reason = ''){
      global $woocommerce;
      $order = new WC_order( $order_id );
      //get the uid from order and compare to md5 in the $_GET
      $post_uid = get_post_meta($order_id,'_uid',true);
      $check_uid=md5($post_uid);
      if ($check_uid != $uid) {
        exit(__('UID is not correct','woocommerce-onepey'));
      }

      $messages = array(
        'void' => array(
          'not_possible' => __('Wrong order status. Void is not possible.', 'woocommerce-onepey'),
          'status' => __('Void status', 'woocommerce-onepey'),
          'failed' => __('Void attempt failed', 'woocommerce-onepey'),
          'success' => __('Payment voided', 'woocommerce-onepey'),
        ),
        'capture' => array(
          'not_possible' => __('Wrong order status. Capture is not possible.', 'woocommerce-onepey'),
          'status' => __('Capture status', 'woocommerce-onepey'),
          'failed' => __('Capture attempt failed', 'woocommerce-onepey'),
          'success' => __('Payment captured', 'woocommerce-onepey'),
        ),
        'refund' => array(
          'not_possible' => __('Wrong order status. Refund is not possible.', 'woocommerce-onepey'),
          'status' => __('Refund status', 'woocommerce-onepey'),
          'failed' => __('Refund attempt failed', 'woocommerce-onepey'),
          'success' => __('Payment refunded', 'woocommerce-onepey'),
        )
      );
      //check order status is on hold exit if not
      if (in_array($type,array('capture','void')) && $order->get_status() !='on-hold') {
        exit($messages[$type]['not_possible']);
      }
      if (in_array($type,array('refund')) && $order->get_status() !='processing') {
        exit($messages[$type]['not_possible']);
      }
      // now send data to the server

      $klass = '\\OnePEY\\' . ucfirst($type) . 'Operation';
      $transaction = new $klass(); /* @var $transaction \OnePEY\ChildTransaction */
      $transaction->setParentUid($post_uid);
      //$transaction->money->setCurrency(get_woocommerce_currency());
      $transaction->money->setAmount($amount);

      if ($type == 'refund') {
        if (isset($reason) && !empty($reason)) {
          $transaction->setReason($reason);
        } else {
          $transaction->setReason(__('Refunded from Woocommerce', 'woocommerce-onepey'));
        }
      }

      $response = $transaction->submit();

      //determine status if success
      if($response->isSuccess()){

        if ($type == 'capture') {
          $order->payment_complete($response->getUid());
          $order->add_order_note( $messages[$type]['success'] . '. UID: ' . $response->getUid() );
          update_post_meta($order_id,'_uid',$response->getUid());
        } elseif ($type == 'void') {
          $order->update_status( 'cancelled', $messages[$type]['success'] . '. UID: ' . $response->getUid() );
        } elseif ($type == 'refund' ) {
          $order->update_status( 'refunded', $messages[$type]['success'] . '. UID: ' . $response->getUid() );
        }
        if ( 'yes' == $this->debug ){
          $this->log->add( 'onepey', $messages[$type]['status'].': '.$response->getMessage());
        }
        update_post_meta($order_id,'_bt_admin_message',$messages[$type]['success']);
      }else{
        if ( 'yes' == $this->debug ){
          $this->log->add( 'onepey', $messages[$type]['failed']. ': ' .$response->getMessage());
        }
        update_post_meta($order_id,'_bt_admin_error',$messages[$type]['failed']. ': ' .$response->getMessage());
      }
      $location = get_post_meta($order_id, '_return_url', true);
      delete_post_meta($order_id, '_return_url', true);

      header ('Location:'.  $location);
      exit();
    }

    function bt_admin_error(){
      if(isset($_GET['post']))
      {
        if(get_post_meta($_GET['post'],'_bt_admin_error'))
        {
          $error=get_post_meta($_GET['post'],'_bt_admin_error',true);
          delete_post_meta($_GET['post'],'_bt_admin_error');
          echo '<div class="error">
            <p>'.$error.'</p>
            </div>';
        }
      }
    }

    function bt_admin_message(){
      if(isset($_GET['post']))
      {
        if(get_post_meta($_GET['post'],'_bt_admin_message'))
        {
          $message=get_post_meta($_GET['post'],'_bt_admin_message',true);
          delete_post_meta($_GET['post'],'_bt_admin_message');
          echo '<div class="updated">
            <p>'.$message.'</p>
            </div>';
        }
      }
    }

    function bt_admin_hide(){
      //  remove_meta_box('postcustom', 'page', 'normal');
    }

    // Now we set that function up to execute when the admin_notices action is called

    function curPageURL() {
      $pageURL = 'http';
      if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
      $pageURL .= "://";
      if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
      } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
      }
      $pageURL=rtrim($pageURL,'&message=1');
      return $pageURL;
    }
  }//end of class

  if(is_admin())
    new BT_OnePEY();
}

//add to gateways
function bt_onepey_add_gateway( $methods )
{
    $methods[] = 'BT_OnePEY';
    return $methods;
}
?>
