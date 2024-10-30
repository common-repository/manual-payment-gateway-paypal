<?php 
/*
Plugin Name: Paypal Manual Payment Gateway
Plugin URI:  https://zitengine.com
Description: Paypal's cross-border payments platform empowers businesses, online sellers and freelancers to pay and get paid globally as easily as they do locally. Paypal is money transfer system of International by facilitating money transfer through Online. This plugin depends on woocommerce and will provide an extra payment gateway through paypal in checkout page.
Version:     1.2
Author:      Md Zahedul Hoque
Author URI:  http://facebook.com/zitengine 
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: stb
*/
defined('ABSPATH') or die('Only a foolish person try to access directly to see this white page. :-) ');
define( 'zitengine_paypal__VERSION', '1.2' );
define( 'zitengine_paypal__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
/**
 * Plugin language
 */
add_action( 'init', 'zitengine_paypal_language_setup' );
function zitengine_paypal_language_setup() {
  load_plugin_textdomain( 'stb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
/**
 * Plugin core start
 * Checked Woocommerce activation
 */
if( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
	
	/**
	 * paypal gateway register
	 */
	add_filter('woocommerce_payment_gateways', 'zitengine_paypal_payment_gateways');
	function zitengine_paypal_payment_gateways( $gateways ){
		$gateways[] = 'zitengine_paypal';
		return $gateways;
	}

	/**
	 * paypal gateway init
	 */
	add_action('plugins_loaded', 'zitengine_paypal_plugin_activation');
	function zitengine_paypal_plugin_activation(){
		
		class zitengine_paypal extends WC_Payment_Gateway {

			public $paypal_email;
			public $number_type;
			public $order_status;
			public $instructions;
			public $paypal_charge;

			public function __construct(){
				$this->id 					= 'zitengine_paypal';
				$this->title 				= $this->get_option('title', 'Paypal P2P Gateway');
				$this->description 			= $this->get_option('description', 'Paypal payment Gateway');
				$this->method_title 		= esc_html__("Paypal", "stb");
				$this->method_description 	= esc_html__("Paypal Payment Gateway Options", "stb" );
				$this->icon 				= plugins_url('images/paypal.png', __FILE__);
				$this->has_fields 			= true;

				$this->zitengine_paypal_options_fields();
				$this->init_settings();
				
				$this->paypal_email = $this->get_option('paypal_email');
				$this->number_type 	= $this->get_option('number_type');
				$this->order_status = $this->get_option('order_status');
				$this->instructions = $this->get_option('instructions');
				$this->paypal_charge = $this->get_option('paypal_charge');

				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
	            add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'zitengine_paypal_thankyou_page' ) );
	            add_action( 'woocommerce_email_before_order_table', array( $this, 'zitengine_paypal_email_instructions' ), 10, 3 );
			}


			public function zitengine_paypal_options_fields(){
				$this->form_fields = array(
					'enabled' 	=>	array(
						'title'		=> esc_html__( 'Enable/Disable', "stb" ),
						'type' 		=> 'checkbox',
						'label'		=> esc_html__( 'Paypal Payment', "stb" ),
						'default'	=> 'yes'
					),
					'title' 	=> array(
						'title' 	=> esc_html__( 'Title', "stb" ),
						'type' 		=> 'text',
						'default'	=> esc_html__( 'Paypal', "stb" )
					),
					'description' => array(
						'title'		=> esc_html__( 'Description', "stb" ),
						'type' 		=> 'textarea',
						'default'	=> esc_html__( 'Please complete your paypal payment at first, then fill up the form below.', "stb" ),
						'desc_tip'    => true
					),
	                'order_status' => array(
	                    'title'       => esc_html__( 'Order Status', "stb" ),
	                    'type'        => 'select',
	                    'class'       => 'wc-enhanced-select',
	                    'description' => esc_html__( 'Choose whether status you wish after checkout.', "stb" ),
	                    'default'     => 'wc-on-hold',
	                    'desc_tip'    => true,
	                    'options'     => wc_get_order_statuses()
	                ),				
					'paypal_email'	=> array(
						'title'			=> esc_html__( 'Paypal Email', "stb" ),
						'description' 	=> esc_html__( 'Add a paypal email ID which will be shown in checkout page', "stb" ),
						'type'			=> 'email',
						'desc_tip'      => true
					),
					'number_type'	=> array(
						'title'			=> esc_html__( 'Paypal Account Type', "stb" ),
						'type'			=> 'select',
						'class'       	=> 'wc-enhanced-select',
						'description' 	=> esc_html__( 'Select paypal account type', "stb" ),
						'options'	=> array(
							'FNF'		=> esc_html__( 'FNF', "stb" ),
							'Service'	=> esc_html__( 'Service', "stb" )
						),
						'desc_tip'      => true
					),
					'paypal_charge' 	=>	array(
						'title'			=> esc_html__( 'Enable Paypal Charge', "stb" ),
						'type' 			=> 'checkbox',
						'label'			=> esc_html__( 'Add 2.5% paypal "Payment" charge to net price', "stb" ),
						'description' 	=> esc_html__( 'If a product price is 1000 then customer have to pay ( 1000 + 25 ) = 1025$. Here 25 is paypal charge', "stb" ),
						'default'		=> 'no',
						'desc_tip'    	=> true
					),						
	                'instructions' => array(
	                    'title'       	=> esc_html__( 'Instructions', "stb" ),
	                    'type'        	=> 'textarea',
	                    'description' 	=> esc_html__( 'Instructions that will be added to the thank you page and emails.', "stb" ),
	                    'default'     	=> esc_html__( 'Thanks for purchasing through paypal. We will check and give you update as soon as possible.', "stb" ),
	                    'desc_tip'    	=> true
	                ),								
				);
			}


			public function payment_fields(){

				global $woocommerce;
				$paypal_charge = ($this->paypal_charge == 'yes') ? esc_html__(' Also note that 2.5% paypal "Payment" cost will be added with net price. Total amount you need to send us at', "stb" ). ' ' . get_woocommerce_currency_symbol() . $woocommerce->cart->total : '';
				echo wpautop( wptexturize( esc_html__( $this->description, "stb" ) ) . $paypal_charge  );
				echo wpautop( wptexturize( "paypal ".$this->number_type." Email : ".$this->paypal_email ) );

				?>
					<p>
						<label for="paypal_email"><?php esc_html_e( 'Paypal Email', "stb" );?></label>
						<input type="email" name="paypal_email" id="paypal_email" placeholder="paypal@youremail.com">
					</p>
					<p>
						<label for="paypal_transaction_id"><?php esc_html_e( 'Paypal Transaction ID', "stb" );?></label>
						<input type="text" name="paypal_transaction_id" id="paypal_transaction_id" placeholder="8N7A6D5EE7M">
					</p>
				<?php 
			}
			

			public function process_payment( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
				// Mark as on-hold (we're awaiting the paypal)
				$order->update_status( $status, esc_html__( 'Checkout with paypal payment. ', "stb" ) );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				$woocommerce->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}	


	        public function zitengine_paypal_thankyou_page() {
			    $order_id = get_query_var('order-received');
			    $order = new WC_Order( $order_id );
			    if( $order->payment_method == $this->id ){
		            $thankyou = $this->instructions;
		            return $thankyou;		        
			    } else {
			    	return esc_html__( 'Thank you. Your order has been received.', "stb" );
			    }

	        }


	        public function zitengine_paypal_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			    if( $order->payment_method != $this->id )
			        return;        	
	            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
	                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	            }
	        }

		}

	}

	/**
	 * Add settings page link in plugins
	 */
	add_filter( "plugin_action_links_". plugin_basename(__FILE__), 'zitengine_paypal_settings_link' );
	function zitengine_paypal_settings_link( $links ) {
		
		$settings_links = array();
		$settings_links[] ='<a href="https://www.facebook.com/zitengine/" target="_blank">' . esc_html__( 'Follow US', 'stb' ) . '</a>';
		$settings_links[] ='<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zitengine_paypal' ) . '">' . esc_html__( 'Settings', 'stb' ) . '</a>';
        
        // add the links to the list of links already there
		foreach($settings_links as $link) {
			array_unshift($links, $link);
		}

		return $links;
	}	

	/**
	 * If paypal charge is activated
	 */
	$paypal_charge = get_option( 'woocommerce_zitengine_paypal_settings' );
	if( $paypal_charge['paypal_charge'] == 'yes' ){

		add_action( 'wp_enqueue_scripts', 'zitengine_paypal_script' );
		function zitengine_paypal_script(){
			wp_enqueue_script( 'stb-script', plugins_url( 'js/scripts.js', __FILE__ ), array('jquery'), '1.0', true );
		}

		add_action( 'woocommerce_cart_calculate_fees', 'zitengine_paypal_charge' );
		function zitengine_paypal_charge(){

		    global $woocommerce;
		    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		    $current_gateway = '';

		    if ( !empty( $available_gateways ) ) {
		        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
		            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
		        } 
		    }
		    
		    if( $current_gateway!='' ){

		        $current_gateway_id = $current_gateway->id;

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;

				if ( $current_gateway_id =='zitengine_paypal' ) {
					$percentage = 0.025;
					$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
					$woocommerce->cart->add_fee( esc_html__('Paypal Charge', 'stb'), $surcharge, true, '' ); 
				}
		       
		    }    	
		    
		}
		
	}

	/**
	 * Empty field validation
	 */
	add_action( 'woocommerce_checkout_process', 'zitengine_paypal_payment_process' );
	function zitengine_paypal_payment_process(){

	    if($_POST['payment_method'] != 'zitengine_paypal')
	        return;

	    $paypal_email = sanitize_email( $_POST['paypal_email'] );
	    $paypal_transaction_id = sanitize_text_field( $_POST['paypal_transaction_id'] );

	    $match_number = isset($paypal_email) ? $paypal_email : '';
	    $match_id = isset($paypal_transaction_id) ? $paypal_transaction_id : '';

        $validate_number = filter_var($match_number, FILTER_VALIDATE_EMAIL);
        $validate_id = preg_match( '/[a-zA-Z0-9]+/',  $match_id );

	    if( !isset($paypal_email) || empty($paypal_email) )
	        wc_add_notice( esc_html__( 'Please add paypal Email ID', 'stb'), 'error' );

		if( !empty($paypal_email) && $validate_number == false )
	        wc_add_notice( esc_html__( 'Email ID not valid', 'stb'), 'error' );

	    if( !isset($paypal_transaction_id) || empty($paypal_transaction_id) )
	        wc_add_notice( esc_html__( 'Please add your paypal transaction ID', 'stb' ), 'error' );

		if( !empty($paypal_transaction_id) && $validate_id == false )
	        wc_add_notice( esc_html__( 'Only number or letter is acceptable', 'stb'), 'error' );

	}

	/**
	 * Update paypal field to database
	 */
	add_action( 'woocommerce_checkout_update_order_meta', 'zitengine_paypal_additional_fields_update' );
	function zitengine_paypal_additional_fields_update( $order_id ){

	    if($_POST['payment_method'] != 'zitengine_paypal' )
	        return;

	    $paypal_email = sanitize_email( $_POST['paypal_email'] );
	    $paypal_transaction_id = sanitize_text_field( $_POST['paypal_transaction_id'] );

		$number = isset($paypal_email) ? $paypal_email : '';
		$transaction = isset($paypal_transaction_id) ? $paypal_transaction_id : '';

		update_post_meta($order_id, '_paypal_email', $number);
		update_post_meta($order_id, '_paypal_transaction', $transaction);

	}

	/**
	 * Admin order page paypal data output
	 */
	add_action('woocommerce_admin_order_data_after_billing_address', 'zitengine_paypal_admin_order_data' );
	function zitengine_paypal_admin_order_data( $order ){
	    
	    if( $order->payment_method != 'zitengine_paypal' )
	        return;

		$number = (get_post_meta($order->id, '_paypal_email', true)) ? get_post_meta($order->id, '_paypal_email', true) : '';
		$transaction = (get_post_meta($order->id, '_paypal_transaction', true)) ? get_post_meta($order->id, '_paypal_transaction', true) : '';

		?>
		<div class="form-field form-field-wide">
			<img src='<?php echo plugins_url("images/paypal.png", __FILE__); ?>' alt="paypal">	
			<table class="wp-list-table widefat fixed striped posts">
				<tbody>
					<tr>
						<th><strong><?php esc_html_e('Paypal Email', 'stb') ;?></strong></th>
						<td>: <?php echo esc_attr( $number );?></td>
					</tr>
					<tr>
						<th><strong><?php esc_html_e('Transaction ID', 'stb') ;?></strong></th>
						<td>: <?php echo esc_attr( $transaction );?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php 
		
	}

	/**
	 * Order review page paypal data output
	 */
	add_action('woocommerce_order_details_after_customer_details', 'zitengine_paypal_additional_info_order_review_fields' );
	function zitengine_paypal_additional_info_order_review_fields( $order ){
	    
	    if( $order->payment_method != 'zitengine_paypal' )
	        return;

		$number = (get_post_meta($order->id, '_paypal_email', true)) ? get_post_meta($order->id, '_paypal_email', true) : '';
		$transaction = (get_post_meta($order->id, '_paypal_transaction', true)) ? get_post_meta($order->id, '_paypal_transaction', true) : '';

		?>
			<tr>
				<th><?php esc_html_e('Paypal Email:', 'stb');?></th>
				<td><?php echo esc_attr( $number );?></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Transaction ID:', 'stb');?></th>
				<td><?php echo esc_attr( $transaction );?></td>
			</tr>
		<?php 
		
	}	

	/**
	 * Register new admin column
	 */
	add_filter( 'manage_edit-shop_order_columns', 'zitengine_paypal_admin_new_column' );
	function zitengine_paypal_admin_new_column($columns){

	    $new_columns = (is_array($columns)) ? $columns : array();
	    unset( $new_columns['order_actions'] );
	    $new_columns['mobile_no'] 	= esc_html__('Send From', 'stb');
	    $new_columns['tran_id'] 	= esc_html__('Tran. ID', 'stb');

	    $new_columns['order_actions'] = $columns['order_actions'];
	    return $new_columns;

	}

	/**
	 * Load data in new column
	 */
	add_action( 'manage_shop_order_posts_custom_column', 'zitengine_paypal_admin_column_value', 2 );
	function zitengine_paypal_admin_column_value($column){

	    global $post;

	    $mobile_no = (get_post_meta($post->ID, '_paypal_email', true)) ? get_post_meta($post->ID, '_paypal_email', true) : '';
	    $tran_id = (get_post_meta($post->ID, '_paypal_transaction', true)) ? get_post_meta($post->ID, '_paypal_transaction', true) : '';

	    if ( $column == 'mobile_no' ) {    
	        echo esc_attr( $mobile_no );
	    }
	    if ( $column == 'tran_id' ) {    
	        echo esc_attr( $tran_id );
	    }
	}

} else {
	/**
	 * Admin Notice
	 */
	add_action( 'admin_notices', 'zitengine_paypal_admin_notice__error' );
	function zitengine_paypal_admin_notice__error() {
	    ?>
	    <div class="notice notice-error">
	        <p><a href="http://wordpress.org/extend/plugins/woocommerce/"><?php esc_html_e( 'Woocommerce', 'stb' ); ?></a> <?php esc_html_e( 'plugin need to active if you wanna use paypal plugin.', 'stb' ); ?></p>
	    </div>
	    <?php
	}
	
	/**
	 * Deactivate Plugin
	 */
	add_action( 'admin_init', 'zitengine_paypal_deactivate' );
	function zitengine_paypal_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset( $_GET['activate'] );
	}
}