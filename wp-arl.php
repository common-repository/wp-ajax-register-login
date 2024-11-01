<?php

/**
 * Plugin Name: WP Ajax Register Login
 * Plugin URI: minimamente.com
 * Description: This plugin add Register and Login functions to your Site
 * Version: 1.0.0
 * Author: Christian Pucci
 * Author URI: minimamente.com
 * Text Domain: wp-arl
 * Domain Path: /languages
 * License: GPL2
 */

/**
 * ADMIN
 */

function wparl_settings() {

    add_settings_section('section', __('General','wp-arl'), null, 'wparl');
    // Position
    add_settings_field('wparl-position', __('Position','wp-arl'), 'wparl_setting_position', 'wparl', 'section'); 
    register_setting('section', 'wparl-position');
    // Bootstrap
    add_settings_field('wparl-bootstrap-css', __('Disable Bootstrap CSS on your theme?','wp-arl'), 'wparl_setting_bootstrap_css', 'wparl', 'section');
    register_setting('section', 'wparl-bootstrap-css');
    
}

function wparl_setting_position() {
   ?>
        <input type="radio" name="wparl-position" value="left" <?php checked('left', get_option('wparl-position'), true); ?>><?php _e('Left','wp-arl'); ?>
        <input type="radio" name="wparl-position" value="right" <?php checked('right', get_option('wparl-position'), true); ?>><?php _e('Right','wp-arl'); ?>
   <?php
}

function wparl_setting_bootstrap_css() {
   ?>
        <input type="checkbox" name="wparl-bootstrap-css" value="1" <?php checked(1, get_option('wparl-bootstrap-css'), true); ?>>
   <?php
}

add_action('admin_init', 'wparl_settings');

function wparl_page() {
  ?>
      <div class="wrap">
         <h1><?php _e('WP ARL Settings','wp-arl'); ?></h1>
  
         <form method="post" action="options.php">
            <?php
               settings_fields('section');
  
               do_settings_sections('wparl');
                 
               submit_button(); 
            ?>
         </form>
      </div>
   <?php
}

function arl_menu_item() {
  add_submenu_page('options-general.php', 'WP ARL', 'WP ARL', 'manage_options', 'wparl', 'wparl_page'); 
}
 
add_action('admin_menu', 'arl_menu_item');


/**
 * THE WHOLE CODE
 */

/**
 * Enqueue JS
 */
add_action( 'wp_enqueue_scripts', 'arl_scripts' );

function arl_scripts() {
	
	// CSS
	$bootstrap_css = get_option('wparl-bootstrap-css', false);
	
	if ( $bootstrap_css == false ) {
		
		wp_register_style('arl-bootstrap', plugins_url('css/bootstrap.min.css',__FILE__ ));
		wp_enqueue_style('arl-bootstrap');
		
	} else {
		
	}
	wp_register_style('arl-style', plugins_url('css/arl.css', __FILE__ ) );
	wp_enqueue_style('arl-style');
	
	// JS
	wp_enqueue_script( 'arl-bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', array( 'jquery' ), '1.0', true  );
	wp_enqueue_script( 'arl-registration', plugin_dir_url( __FILE__ ) . 'js/arl-registration.js', array( 'jquery' ), '1.0', true );
	
	// Localize
	wp_localize_script( 'arl-registration', 'ajax_login_object', array( 
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'redirecturl' => home_url(),
        'loadingmessage' => ''
    ));
	
}

// Enable the user with no privileges to run ajax_login() in AJAX
add_action( 'wp_ajax_nopriv_ajaxlogin', 'ajax_login' );


/**
 * Login Validations
 */

// Login Jquery
function ajax_login(){

    // First check the nonce, if it fails the function will break
    check_ajax_referer( 'ajax-login-nonce', 'security' );

    // Nonce is checked, get the POST data and sign user on
    $info = array();
    $info['user_login'] = $_POST['username'];
    $info['user_password'] = $_POST['password'];
    $info['remember'] = true;
    
    $user_signon = wp_signon( $info, false );
    
    if ( is_wp_error($user_signon) ){    
    	$message = '';
    	if ( 'incorrect_password' == $user_signon->get_error_code() ) {
    		$message = '<div class="alert alert-danger" role="alert">'. __('Password is not correct','wp-arl') .'</div>';
    	} elseif ( 'invalid_username' == $user_signon->get_error_code() ) {
    		$message = '<div class="alert alert-danger" role="alert">'. __('Username is not correct','wp-arl') .'</div>';
    	} elseif ( 'empty_password' == $user_signon->get_error_code() ) {
    		$message = '<div class="alert alert-danger" role="alert">'. __('Insert password','wp-arl') .'</div>';
    	} else {
    		$message = '<div class="alert alert-danger" role="alert">'. __('Please compile the form','wp-arl') .'</div>';	
    	}
        echo json_encode(array(
        	'loggedin'	=> false, 
        	'message'	=> $message, /*$user_signon->get_error_message(),/*__('Errore', 'wp-arl')*/
        	'code'		=> $user_signon->get_error_code()
        ));
    } else {
        echo json_encode(array(
        	'loggedin'	=> true,
        	'message'	=> '<div class="alert alert-success">'. __('Logged-in, please wait...', 'wp-arl') .'</div>'
        ));
    }

    die();
}


// Lost password
add_action('wp_ajax_nopriv_lost_password', function () {

	$param = (isset($_POST['login']))?$_POST['login']:false;
	

	if (!$param){
		wp_send_json_error( __('Specify an email address or a valid username','wp-arl') );
	}
	
	if ( is_email($param) ){
		$user = get_user_by( 'email', $param );
	} else {
		$user = get_user_by( 'login', $param );
	}
	
	if (!$user){
		wp_send_json_error( __('Username or email does not exist','wp-arl') );
	}
	
	$newPassword = wp_generate_password(6);
	
	wp_set_password( $newPassword, $user->ID );
	
	$site = '<a href="'. site_url() .'">' . get_bloginfo('name') . '</a>';
	$mailContent = __('Dear customer, the new password to access his profile on ','wp-arl') . $site . __(' is','wp-arl') .':'.  $newPassword;
	$headers = array('Content-Type: text/html; charset=UTF-8');
	
	wp_mail($user->data->user_email, __('Reset Password','wp-arl'), $mailContent, $headers);
	
	wp_send_json_success( __('New password sent to your email address correctly','wp-arl') );
	
});

/**
 * Register Validations
 */
 
global $register_theme_options;

$register_theme_options = array(
	'password_length' => 6, 
	'username_length' => 4
);

/**
 * display_name_exists()
 * Check if given display name already exists
 *
 * @param String $display_name User display name
 * @return Int User ID
 */
function display_name_exists($display_name) {
	global $wpdb;
	return $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE display_name = %s", $display_name));
}

add_action( 'wp_ajax_nopriv_register_user', 'ajax_register_user' );

function ajax_register_user () {

	global $register_theme_options;

	// Min password length
	$password_length = $register_theme_options['password_length'];

	// Min username length
	$username_length = $register_theme_options['username_length'];

	// Check that all values are set and not empty
	if ( empty($_POST['username']) || 
		 empty($_POST['email']) || 
		 empty($_POST['pwd1']) 
		) wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('All data must be filled in order to proceed!','wp-arl') .'</div>' ) );
	
	// Privacy
	if ( !isset($_POST['privacy']) || !$_POST['privacy'] )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('You must accept the privacy policy to continue.','wp-arl') .'</div>' ) );
		
	// Sanitize and get user
	$user = sanitize_user( $_POST['username'] );

	// Sanitize, validate and get
	if ( !$email = is_email( $_POST['email'] ) )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('The email entered is not valid','wp-arl') .'</div>' ) );

	// Get password
	$password = $_POST['pwd1'];

	// Check username length
	if ( strlen($user) < $username_length )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('The username must be at least','wp-arl') .' '.$username_length.' '. __('characters','wp-arl') .'.</div>' ) );

	// Check password length
	if ( strlen($password) < $password_length )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('The password must be at least','wp-arl') .' '.$password_length.' '. __('characters','wp-arl') .'.</div>' ) );

	// Double check password
	if ( $password !== $_POST['pwd2'] )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('Passwords do not match.','wp-arl') .'</div>' ) );

	// Check if user exists already
	if ( username_exists( $user ) )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('We are sorry. The chosen username is not available.','wp-arl') .'</div>' ) );

	// Check if display name already exist
	if ( display_name_exists( $user ) )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('We are sorry. The chosen username is not available.','wp-arl') .'</div>' ) );

	// Check if email exists already
	if ( email_exists($email) )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('The email indicated is already present in our database.','wp-arl') .'</div>' ) );

	// Try to create the new user
	$user_id = wp_create_user( $user, $password, $email );

	if ( is_wp_error($user_id) )
		wp_send_json_error( array('message' => '<div class="alert alert-danger" role="alert">'. __('Sorry, the recording can not be carried out on, contact your service','wp-arl') .'</div>' ) );

	// Get registered user Wp_User
	$user = get_user_by( 'id', $user_id );

	// Get login
	$login = $user->data->user_login;

	// Message sent to the user
	$message = ''. __('Username','wp-arl') .': '. $login .' '. __('Password','wp-arl') .': '. $password .'';

	// Send email
	wp_mail( $email, ''. __('Registration of new user on','wp-arl') .' '. get_bloginfo('name'), $message );

	// Log user in
	wp_signon( array( 'user_login' => $login, 'user_password' => $password ) );

	// If everything went all right send success
    wp_send_json_success( array('message' => '<div class="alert alert-success" role="alert">'. __('Welcome to','wp-arl') .' '. get_bloginfo('name') .'. '. __('A confirmation has been sent to the specified address. Soon you will be automatically logged into the system.','wp-arl') .'</div>' ) );

}	

/**
 * Isert HTML code for modals
 */
function arl_modal_registraion_login() {
    
    $position = get_option('wparl-position','right');
    
    if ( $position == 'right' ) {
    	
    	$window_position = 'style="right: 20px"';
    	
    } else {
    	
    	$window_position = 'style="left: 20px"';
    	
    }
    
    ?>
    <div class="wp-arl-window" <?php echo $window_position; ?>>
    <?php
	if ( is_user_logged_in() ) { ?>
		
		<a class="arl-logout pull-right" rel="nofollow" href="<?php echo wp_logout_url( home_url() ); ?>" title="Logout"><?php _e('Logout','themename'); ?></a></li>
		
	<?php } else { ?>
		
	<span class="arl-register pull-right" data-toggle="modal" data-target="#modal-register"><?php _e('Register','themename'); ?></span>
	<span class="arl-login pull-right" data-toggle="modal" data-target="#modal-login"><?php _e('Login','themename'); ?></span>
	
	<?php } ?>
	
	</div><!-- .wp-arl-window -->
	
    <div class="modal fade" id="modal-login" tabindex="-1" role="dialog" aria-labelledby="modal-login" aria-hidden="true">
    	<div class="modal-dialog" role="document">
    		<div class="modal-content">
    		
    			<div class="modal-header">
    				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
    					<span aria-hidden="true">&times;</span>
    					<span class="sr-only"><?php _e('Close','wp-arl'); ?></span>
    				</button>
    				<h4 class="modal-title"><?php _e('Login','wp-arl'); ?></h4>
    			</div>
    			<div class="modal-body">
    				
    				<form id="form-login" action="login" method="post">
    					
    					<div class="status_msg"></div>
    					
    					<fieldset class="form-group">
    						<label><?php _e('Username','wp-arl'); ?></label>
    						<input type="text" value="" name="username" id="user_login" class="form-control" placeholder="<?php _e('Username','wp-arl'); ?>">
    					</fieldset>
    					<fieldset class="form-group">
    						<label><?php _e('Password','wp-arl'); ?></label>
    						<input type="password" value="" name="password" id="user_pass" class="form-control" placeholder="<?php _e('Password','wp-arl'); ?>">
    					</fieldset>
    					
    					<input class="btn btn-primary submit_button" type="submit" value="<?php _e('Login','wp-arl'); ?>" name="submit">
    					<?php wp_nonce_field( 'ajax-login-nonce', 'security' ); ?>
    				</form>
    				
    			</div>
    			
    			<div class="modal-footer text-left">
    				
    				<a data-toggle="collapse" href="#lost-password" aria-expanded="false" aria-controls="collapseExample"><?php _e('Lost Password?','wp-arl'); ?></a>
    				
    				<div id="lost-password" class="collapse">
    					<form id="lost-pass" method="post" action="<?php echo esc_url( site_url('wp-login.php?action=lostpassword', 'login_post') ); ?>">
    						
    						<fieldset class="form-group">
    							<label><?php _e('Enter your username or your email','wp-arl'); ?></label>
    							<input type="text" value="" name="user_login" id="user_login_pass" class="form-control">
    						</fieldset>
    						
    						<?php do_action('login_form', 'resetpass'); ?>
    						<input type="submit" name="user-submit" value="<?php _e('Reset','wp-arl'); ?>" class="btn btn-primary user-submit">
    						<?php if ( isset( $_GET['reset']) ) {
    							$reset = $_GET['reset']; if ( $reset == true ) { echo '<span class="reset-true">'. __('Password reset. Check your email.','wp-arl') .'</span>'; }	
    						} ?>
    						<input type="hidden" name="redirect_to" value="<?php echo esc_url( site_url() ); ?>?reset=true">
    						<input type="hidden" name="user-cookie" value="1">
    					</form>
    				</div>
    				
    			</div>
    			
    		</div>
    	</div><!-- .modal-dialog -->
    </div><!-- #modal-login -->
    
    
    <div class="modal fade" id="modal-register" tabindex="-1" role="dialog" aria-labelledby="modal-register" aria-hidden="true">
    	<div class="modal-dialog" role="document">
    		<div class="modal-content">
    		
    			<div class="modal-header">
    				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
    					<span aria-hidden="true">&times;</span>
    					<span class="sr-only"><?php _e('Close','wp-arl'); ?></span>
    				</button>
    				<h4 class="modal-title"><?php _e('Register','wp-arl'); ?></h4>
    			</div>
    			<div class="modal-body">
    				
    				<form id="registration" method="post">
    					<fieldset class="form-group">
    						<label><?php _e('Username','wp-arl'); ?></label>
    						<input type="text" value="" name="username" id="username" class="form-control requiredField" placeholder="<?php _e('Insert your username...','wp-arl'); ?>">
    					</fieldset>
    					<fieldset class="form-group">
    						<label><?php _e('Email','wp-arl'); ?></label>
    						<input type="email" value="" name="email" id="email" class="form-control requiredField" placeholder="<?php _e('Insert your email...','wp-arl'); ?>">
    					</fieldset>
    					<fieldset class="form-group">
    						<label><?php _e('Password','wp-arl'); ?></label>
    						<input type="password" value="" name="pwd1" id="pwd1" class="form-control requiredField" placeholder="<?php _e('Password','wp-arl'); ?>">
    					</fieldset>
    					<fieldset class="form-group">
    						<label><?php _e('Ripeti Password','wp-arl'); ?></label>
    						<input type="password" value="" name="pwd2" id="pwd2" class="form-control requiredField" placeholder="<?php _e('Repeat password','wp-arl'); ?>">
    					</fieldset>
    					
    					<div class="checkbox">
    						<label>
    							<input type="checkbox" name="privacy" id="user_privacy">
    							<?php _e('I consent to my data','wp-arl'); ?>
    						</label>
    					</div>
    					
    					<button type="submit" name="btnregister" class="btn btn-primary"><?php _e('Register Me','wp-arl'); ?></button>
    					<input type="hidden" name="task" value="register" />
    				</form>
    				
    			</div>
    			
    		</div>
    	</div><!-- .modal-dialog -->
    </div><!-- #modal-register -->
    
    <?php
    
}
add_action( 'wp_footer', 'arl_modal_registraion_login' );