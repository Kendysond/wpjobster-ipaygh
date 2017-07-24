<?php ob_start();
/**
 * Plugin Name: WPJobster iPayGH Gateway
 * Plugin URI: http://github.com/kendysond
 * Description: This plugin extends Jobster Theme to accept payments with iPayGH.
 * Author: kendysond
 * Author URI: http://github.com/kendysond
 * Version: 1.0
 *
 * Copyright (c) 2017
 *
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Required minimums
 */
define( 'WPJOBSTER_SAMPLE_MIN_PHP_VER_IPAYGH_IPAYGH', '5.4.0' );


class WPJobster_Ipaygh_Loader {

	/**
	 * @var Singleton The reference the *Singleton* instance of this class
	 */
	private static $instance;
	public $priority, $unique_slug;


	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Notices (array)
	 * @var array
	 */
	public $notices = array();


	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		$this->priority = 100;         
		$this->unique_slug = 'ipaygh';  

		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		add_action( 'plugins_loaded', array( $this, 'init_gateways' ), 0 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

		add_action( 'wpjobster_taketo_ipaygh_gateway', array( $this, 'taketogateway_function' ), 10 );
		add_action( 'wpjobster_processafter_ipaygh_gateway', array( $this, 'processgateway_function' ), 10 );

		if ( isset( $_POST[ 'wpjobster_save_' . $this->unique_slug ] ) ) {
			add_action( 'wpjobster_payment_methods_action', array( $this, 'save_gateway' ), 11 );
		}
	}


	/**
	 * Initialize the gateway. Called very early - in the context of the plugins_loaded action
	 *
	 * @since 1.0.0
	 */
	public function init_gateways() {
		load_plugin_textdomain( 'wpjobster-ipaygh', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
		add_filter( 'wpjobster_payment_gateways', array( $this, 'add_gateways' ) );
	}


	/**
	 * Add the gateways to WPJobster
	 *
	 * @since 1.0.0
	 */
	public function add_gateways( $methods ) {
		$methods[$this->priority] =
			array(
				'label'           => __( 'Ipaygh', 'wpjobster-ipaygh' ),
				'action'          => '',
				'unique_id'       => $this->unique_slug,
				'process_action'  => 'wpjobster_taketo_ipaygh_gateway',
				'response_action' => 'wpjobster_processafter_ipaygh_gateway',
			);
		add_action( 'wpjobster_show_paymentgateway_forms', array( $this, 'show_gateways' ), $this->priority, 3 );

		return $methods;
	}


	/**
	 * Save the gateway settings in admin
	 *
	 * @since 1.0.0
	 */
	public function save_gateway() {
		if ( isset( $_POST['wpjobster_save_' . $this->unique_slug] ) ) {

			// _enable and _button_caption are mandatory
			update_option( 'wpjobster_' . $this->unique_slug . '_enable',         trim( $_POST['wpjobster_' . $this->unique_slug . '_enable'] ) );
			update_option( 'wpjobster_' . $this->unique_slug . '_button_caption', trim( $_POST['wpjobster_' . $this->unique_slug . '_button_caption'] ) );

			// you can add here any other information that you need from the user
			update_option( 'wpjobster_ipaygh_enablesandbox',                      trim( $_POST['wpjobster_ipaygh_enablesandbox'] ) );
			update_option( 'wpjobster_ipaygh_lmk',                                 trim( $_POST['wpjobster_ipaygh_lmk'] ) );
			update_option( 'wpjobster_ipaygh_tmk',                                 trim( $_POST['wpjobster_ipaygh_tmk'] ) );
			// wpjobster_ipaygh_tsk
			update_option( 'wpjobster_ipaygh_success_page',                       trim( $_POST['wpjobster_ipaygh_success_page'] ) );
			update_option( 'wpjobster_ipaygh_failure_page',                       trim( $_POST['wpjobster_ipaygh_failure_page'] ) );

			echo '<div class="updated fade"><p>' . __( 'Settings saved!', 'wpjobster-ipaygh' ) . '</p></div>';
		}
	}


	/**
	 * Display the gateway settings in admin
	 *
	 * @since 1.0.0
	 */
	public function show_gateways( $wpjobster_payment_gateways, $arr, $arr_pages ) {
		$tab_id = get_tab_id( $wpjobster_payment_gateways );
		?>
		<div id="tabs<?php echo $tab_id?>">
			<form method="post" action="<?php bloginfo( 'siteurl' ); ?>/wp-admin/admin.php?page=payment-methods&active_tab=tabs<?php echo $tab_id; ?>">
			<table width="100%" class="sitemile-table">
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(); ?></td>
					<td valign="top"><?php _e( 'Ipaygh Gateway Note:', 'wpjobster-ipaygh' ); ?></td>
					<td>
					<p>Get your keys from the Ipaygh Dashboard</p>
					</td>
				</tr>

				<tr>
					<?php // _enable and _button_caption are mandatory ?>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Enable/Disable Ipaygh payment gateway', 'wpjobster-ipaygh') ); ?></td>
					<td width="200"><?php _e( 'Enable:', 'wpjobster-ipaygh' ); ?></td>
					<td><?php echo wpjobster_get_option_drop_down( $arr, 'wpjobster_ipaygh_enable', 'no' ); ?></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Enable/Disable Ipaygh test mode.', 'wpjobster-ipaygh' ) ); ?></td>
					<td width="200"><?php _e( 'Enable Test Mode:', 'wpjobster-ipaygh' ); ?></td>
					<td><?php echo wpjobster_get_option_drop_down( $arr, 'wpjobster_ipaygh_enablesandbox', 'no' ); ?></td>
				</tr>
				<tr>
					<?php // _enable and _button_caption are mandatory ?>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Put the Ipaygh button caption you want user to see on purchase page', 'wpjobster-ipaygh' ) ); ?></td>
					<td><?php _e( 'Ipaygh Button Caption:', 'wpjobster-ipaygh' ); ?></td>
					<td><input type="text" size="85" name="wpjobster_<?php echo $this->unique_slug; ?>_button_caption" value="<?php echo get_option( 'wpjobster_' . $this->unique_slug . '_button_caption' ); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Live Merchant Integration  Key', 'wpjobster-ipaygh' ) ); ?></td>
					<td ><?php _e( 'Live Merchant Integration  Key:', 'wpjobster-ipaygh' ); ?></td>
					<td><input type="text" size="85" name="wpjobster_ipaygh_lmk" value="<?php echo get_option( 'wpjobster_ipaygh_lmk' ); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Test Merchant Integration  Key', 'wpjobster-ipaygh' ) ); ?></td>
					<td ><?php _e( 'Test Merchant Integration  Key:', 'wpjobster-ipaygh' ); ?></td>
					<td><input type="text" size="85" name="wpjobster_ipaygh_tmk" value="<?php echo get_option( 'wpjobster_ipaygh_tmk' ); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Please select a page to show when Ipaygh payment successful. If empty, it redirects to the transaction page', 'wpjobster-ipaygh' ) ); ?></td>
					<td><?php _e( 'Transaction Success Redirect:', 'wpjobster-ipaygh' ); ?></td>
					<td><?php
					echo wpjobster_get_option_drop_down( $arr_pages, 'wpjobster_' . $this->unique_slug . '_success_page', '', ' class="select2" '); ?>
						</td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet( __( 'Please select a page to show when Ipaygh payment failed. If empty, it redirects to the transaction page', 'wpjobster-ipaygh' ) ); ?></td>
					<td><?php _e( 'Transaction Failure Redirect:', 'wpjobster-ipaygh' ); ?></td>
					<td><?php
					echo wpjobster_get_option_drop_down( $arr_pages, 'wpjobster_' . $this->unique_slug . '_failure_page', '', ' class="select2" '); ?></td>
				</tr>
				<tr>
					<td></td>
					<td></td>
					<td><input type="submit" name="wpjobster_save_<?php echo $this->unique_slug; ?>" value="<?php _e( 'Save Options', 'wpjobster-ipaygh' ); ?>" /></td>
				</tr>
				</table>
			</form>
		</div>
		<?php
	}


	/**
	 * This function is not required, but it helps making the code a bit cleaner.
	 *
	 * @since 1.0.0
	 */
	public function get_gateway_credentials() {

		$wpjobster_ipaygh_enablesandbox = get_option( 'wpjobster_ipaygh_enablesandbox' );

		if ( $wpjobster_ipaygh_enablesandbox == 'no' ) {
			$ipaygh_payment_url = 'https://ipaygh.url';
			$key = get_option( 'wpjobster_ipaygh_lmk' );

		} else {
			$ipaygh_payment_url = 'https://test.ipaygh.url';
			$key = get_option( 'wpjobster_ipaygh_tmk' );

		}
		return $key;
	}



	function ipaygh_generate_new_code($order_id){
		  $characters = '06EFGHI9KL'.time().'MNOPJRSUVW01YZ923234'.time().'ABCD5678QXT';
		  $charactersLength = strlen($characters);
		  $randomString = '';
		  $length = 25;
		  for ($i = 0; $i < $length; $i++) {
		      $randomString .= $characters[rand(0, $charactersLength - 1)];
		  }
		  $append = '_'.$order_id;
		  $l_append  = strlen($append);
		  $n_length = 25- $l_append;

		  $txn = substr($randomString,0,$n_length).$append;
		  
		  return $txn;
	}
	
	/**
	 * Collect all the info that we need and forward to the gateway
	 *
	 * @since 1.0.0
	 */
	public function taketogateway_function() {
		  
		$key = $this->get_gateway_credentials();

		$all_data  = array();
		// $all_data['publickey'] = $credentials['publickey'];
		// $all_data['secretkey'] = $credentials['secretkey'];
		
		$currency = wpjobster_get_currency();
		if ($currency != 'GHS') {
		   _e('You can only pay in GHS with Ipaygh, go back and select GHS','wpjobster');
			exit;
		}

		$common_details = get_common_details( $this->unique_slug, 0, $currency );

		$uid                            = $common_details['uid'];
		$order_id                            = $common_details['order_id'];
		$wpjobster_final_payable_amount = $common_details['wpjobster_final_payable_amount'];
		$currency                       = $common_details['currency'];

		////
		$all_data['amount']       = $wpjobster_final_payable_amount;
		$all_data['currency']     = $currency;
		// any other info that the gateway needs
		$all_data['firstname']    = user( $uid, 'first_name' );
		$all_data['email']        = user( $uid, 'user_email' );
		$all_data['phone']        = user( $uid, 'cell_number' );
		$all_data['lastname']     = user( $uid, 'last_name' );
		$all_data['address']      = user( $uid, 'address' );
		$all_data['city']         = user( $uid, 'city' );
		$all_data['country']      = user( $uid, 'country_name' );
		$all_data['job_title']      = $common_details['job_title'];
		$all_data['job_id']      = $common_details['pid'];
		$all_data['user_id']      = $common_details['uid'];
		$all_data['order_id']      = $order_id;
		////
		$txn_code = $this->ipaygh_generate_new_code($order_id);
		$callback_url = get_bloginfo( 'siteurl' ) . '/?payment_response=ipaygh_response&order_id='.$order_id.'&reference='.$txn_code;
		$cancel_url   = get_bloginfo("siteurl") . '/?jb_action=purchase_this&jobid=' . $order_id;
		// echo '<pre>';
		$koboamount = $wpjobster_final_payable_amount;//*100;
		


	echo '<form method=POST id="ipay" action="https://community.ipaygh.com/gateway">
		    <input type=hidden name=merchant_key value="'.$key.'" />
		    <input type=hidden name=success_url value="'.$callback_url.'" />
		    <input type=hidden name=cancelled_url value="'.$cancel_url.'" />
		    <input type=hidden name=deferred_url value="'.$cancel_url.'" />
		    <input type=hidden name=invoice_id value="'.$txn_code.'" />
		    <input type=hidden name=total value="'.$koboamount.'" />
		    
		</form><script>document.getElementById("ipay").submit();</script>';
		
		
		
		exit;
	}
	/**
	 * Process the response from the gateway and mark the order as completed or failed
	 *
	 * @since 1.0.0
	 */
	function processgateway_function() {

		$key        = $this->get_gateway_credentials();
		$reference = $_GET['reference'];
		$result = file_get_contents('https://community.ipaygh.com/v1/gateway/status_chk?invoice_id='.$reference.'&merchant_key='.$key);  
		$if = '~paid~';
		// echo $result;
		if( strpos( $result, $if ) !== false ) {
		    $status = 'success';
			$ipaygh_ref 	= $reference;
			$order_id = substr($ipaygh_ref, strpos($ipaygh_ref, "_") + 1);
			$order_details = wpjobster_get_order_details_by_orderid($order_id);
			$amt = $order_details->final_paidamount;

			$amt_arr= explode("|", $amt );
			$currency = $amt_arr['0'];
			$order_amount = $amt_arr['1'];
			$amount =$order_amount;
		}else{
			$status = "failed";
		}
		
		if ( $status == 'success' ) {

			
			if ( $amount == $order_amount) {

				$payment_status = 'completed';
				$payment_response = maybe_serialize( $_POST ); // maybe we want to debug later
				$payment_details = '';

				wpjobster_mark_job_prchase_completed( $order_id, $payment_status, $payment_response, $payment_details );
// ob_clean();

				if ( get_option( 'wpjobster_ipaygh_success_page' ) != '' ) {
					$url = get_permalink( get_option( 'wpjobster_ipaygh_success_page' ) );

				} else {
					$url = get_bloginfo( 'siteurl' ) . '/?jb_action=chat_box&oid=' . $order_id;
					wp_redirect($url);
				}

			

			} else {

				$payment_status  = 'failed';
				$payment_response = maybe_serialize( $_POST ); // maybe we want to debug later
				$payment_details = 'Final amount is different! ' . $common_details['wpjobster_final_payable_amount'] . ' expected, ' . $amount . ' paid.';

				wpjobster_mark_job_prchase_completed( $order_id, $payment_status, $payment_response, $payment_details );

				if ( get_option( 'wpjobster_ipaygh_failure_page' ) != '' ) {
					$url = get_permalink( get_option( 'wpjobster_ipaygh_failure_page' ) );
					
				} else {
				wp_redirect( get_bloginfo( 'siteurl' ) . '/?jb_action=chat_box&oid=' . $order_id );
				
				}
			}
		} else {

			$payment_status = 'failed';
			$payment_response = maybe_serialize( $_POST );
			$payment_details = 'Ipaygh gateway declined the transaction';

			wpjobster_mark_job_prchase_completed( $order_id, $payment_status, $payment_response, $payment_details );

			if ( get_option( 'wpjobster_ipaygh_failure_page' ) != '' ) {
					$url = get_permalink( get_option( 'wpjobster_ipaygh_failure_page' ) );

			} else {
					$url = get_bloginfo( 'siteurl' ) . '/?jb_action=chat_box&oid=' . $order_id;

			}
		}
			
		echo "<script>window.location='".$url."'</script>";


		exit;

	}


	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication)
	 */
	public function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class' => $class,
			'message' => $message
		);
	}


	/**
	 * The primary sanity check, automatically disable the plugin on activation if it doesn't
	 * meet minimum requirements.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 */
	public static function activation_check() {
		$environment_warning = self::get_environment_warning( true );
		if ( $environment_warning ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( $environment_warning );
		}
	}


	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation.
	 */
	public function check_environment() {
		$environment_warning = self::get_environment_warning();
		if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}


	/**
	 * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
	 * found or false if the environment has no problems.
	 */
	static function get_environment_warning( $during_activation = false ) {
		if ( version_compare( phpversion(), WPJOBSTER_SAMPLE_MIN_PHP_VER_IPAYGH, '<' ) ) {
			if ( $during_activation ) {
				$message = __( 'The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'wpjobster-ipaygh' );
			} else {
				$message = __( 'The Ipaygh Powered by wpjobster plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'wpjobster-ipaygh' );
			}
			return sprintf( $message, WPJOBSTER_SAMPLE_MIN_PHP_VER_IPAYGH, phpversion() );
		}
		return false;
	}


	/**
	 * Adds plugin action links
	 *
	 * @since 1.0.0
	 */
	public function plugin_action_links( $links ) {
		$setting_link = $this->get_setting_link();
		$plugin_links = array(
			'<a href="' . $setting_link . '">' . __( 'Settings', 'wpjobster-ipaygh' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}


	/**
	 * Get setting link.
	 *
	 * @return 
	 */
	public function get_setting_link() {
		$section_slug = $this->unique_slug;
		return admin_url( 'admin.php?page=payment-methods&active_tab=tabs' . $section_slug );
	}


	/**
	 * Display any notices we've collected thus far (e.g. for connection, disconnection)
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo "</p></div>";
		}
	}
}

$GLOBALS['WPJobster_Ipaygh_Loader'] = WPJobster_Ipaygh_Loader::get_instance();
register_activation_hook( __FILE__, array( 'WPJobster_Ipaygh_Loader', 'activation_check' ) );
