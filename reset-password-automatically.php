<?php 
/* 
Plugin Name: Reset Password Automatically - Security
Plugin URI: http://xlab.co.in
Description: Plugin changes password of admin accounts automatically on defined time intervals by user. This help to keep you website secure from hack attacks and password theft. Multiple option are available for generating random strong password, it also support pronounceable passwords. V 1.0 
Version: 1.0
Author: Deepak Sihag
Author URI: http://xlab.biz/reset-password-automatically-wordpress-plugin
License: GPLv2 
*/

    global $wpdb;
    ob_start();
    global $password_generator_version;
    $password_generator_version = "1.0";
    
    define( 'PLUGINNAME_URL', plugin_dir_url(__FILE__) ); 

    add_action('init','add_password_javascript');
    function add_password_javascript() {
        wp_enqueue_script( 'script.js', PLUGINNAME_URL.'/js/script.js');
    }

    function password_generator_install () {
          ob_start();
          global $password_generator_version;
          global $wpdb;
           $table_name = $wpdb->prefix . "pwd_security";
           $sql = "CREATE TABLE $table_name (
                    ID bigint(20) NOT NULL AUTO_INCREMENT,
                    created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    pass_length VARCHAR(255) DEFAULT NULL,
                    char_type VARCHAR(255) DEFAULT NULL,
                    char_symbol VARCHAR(255) DEFAULT NULL,
                    mini_digit VARCHAR(255) DEFAULT NULL,
                    schedule VARCHAR(255) DEFAULT NULL,
                    UNIQUE KEY id (id)
                      );";

         require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
         dbDelta( $sql );
         $defult_setting= $wpdb->insert( $table_name, array( 'created' => current_time('mysql'), 'pass_length' => '6', 'char_type' => 'Allow All Character Types', 'char_symbol' => 'a:4:{i:0;s:3:"A-Z";i:1;s:3:"a-z";i:2;s:3:"0-9";i:3;s:5:"!$%@#";}', 'mini_digit' => '1', 'schedule' => 'daily' ) );       
    }
  /* Call plugin installation function */ 
  register_activation_hook(__FILE__,'password_generator_install');
  
  /* Call cron setup function */ 
  register_activation_hook( __FILE__, 'password_generator_cron_schedule' );
  
  /* Call plugin deactivation function */ 
  register_deactivation_hook( __FILE__, 'password_generator_deactivate' );
  
/* On deactivation, remove all functions from the scheduled action hook. */
  register_deactivation_hook( __FILE__, 'password_generator_cron_deactivation' );
  
    /* cron setup function */ 
   function password_generator_cron_schedule(){
  
    $timestamp = wp_next_scheduled( 'pwd_cron' );
    
    $setting_details = get_setting_details();

    if( $timestamp == false ){
      wp_schedule_event( time(), $setting_details -> schedule, 'pwd_cron' );
    }
  }
  
  
  function password_cron_setup(){
  
      $setting_details = get_setting_details();
      $char_symbols = unserialize($setting_details->char_symbol);

      if($setting_details){

              $newpassword = generate_pdw();
              $user_query = get_users('role=administrator');

              foreach ( $user_query as $user ) {

                  wp_set_password($newpassword, $user->ID);

                  $to  = $user->user_email;

                  $subject = 'New Password';
                  $message = "<p>Hello <strong>".$user->display_name."</strong>,<br/><br/>Your password is changed.<br/>
                              Your new password is <strong>".$newpassword."</strong></p>
                              </br><p>Cheers!!<br><br>WP Passwrod Reset Security</p>";

                  $headers  = 'MIME-Version: 1.0' . "\r\n";
                  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";   
                            
                  wp_mail( $to, $subject, $message, $headers );              
              }
      }

  }

  add_action( 'pwd_cron', 'password_cron_setup' );
  add_filter( 'cron_schedules', 'pwd_generator_schedule' ); 
  
  
  /* Add Plugin Menu Start */
  function password_generator_menu() 
    {
      add_options_page('Password Generator Options', 'Password Generator', 'manage_options', 'password-generator-identifier','password_generator_main_page');
    }
    add_action('admin_menu', 'password_generator_menu');
  /* Add Plugin Menu Ends */
  
  
  function get_setting_details(){
      global $wpdb;
      $table_name = $wpdb->prefix . "pwd_security";
      $get_row = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE ID = 1");
      return $get_row;
  }


  function password_generator_main_page()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "pwd_security";

      if($_POST){
            $character_symbols = serialize($_POST['char_symbol']);
            $setting_details = get_setting_details();
              if($setting_details){
              
                    $wpdb->update( $table_name, 
                                    array( 
                                      'created' => current_time('mysql'), 'pass_length' => $_POST['pass_length'],	'char_type' => $_POST['character_type'], 'char_symbol' => $character_symbols,	'mini_digit' => $_POST['mini_digit'],	'schedule' => $_POST['schedule'],
                                      ), 
                                    array( 'ID' => 1 ), 
                                    array( '%s','%d','%s','%s','%d','%s'), 
                                    array( '%d' ) 
                                  );
                   
                              }
              else{
                  $add_setting= $wpdb->insert( $table_name, array( 'created' => current_time('mysql'), 'pass_length' => $_POST['pass_length'], 'char_type' => $_POST['character_type'], 'char_symbol' => $_POST['char_symbol'], 'mini_digit' => $_POST['mini_digit'], 'schedule' => $_POST['schedule'] ) );
              }
              
            /* remove all functions from the scheduled action hook. */
            wp_clear_scheduled_hook( 'pwd_cron' );
            password_generator_cron_schedule();
            /* Re-setup schedule cron  function */ 

              /* Redirect after submit form */ 
              $self_url = $_SERVER['PHP_SELF']."?page=password-generator-identifier";
              header("Location: $self_url");
              
      }
      
      $setting_details = get_setting_details();
      $char_symbols = unserialize($setting_details->char_symbol);
      
?>

  <div class='wrap'>
  <h2>Password Generator</h2>
    <div id="main-container" class="postbox-container metabox-holder" style="width:75%;">
      <div style="margin-right:16px;">
        <div class="postbox">
          <h3 style="cursor:default;"><span>Password Generator Settings</span></h3>
          <div class="inside">
        <p></p>
      <form id="password_generator_form" method="post" action="">
        <div>
          <label>Password Length:<label>
           <select name="pass_length" id="pass_length">   
                <?php 
                $length_count=5;

                while($length_count<=15) { ?>
                  <option value="<?php echo $length_count; ?>" <?php if($setting_details -> pass_length == $length_count){echo "selected";}?>><?php echo $length_count; ?></option>
                  <?php  $length_count++;
                } ?>    
           </select>
         </div>
         
         <div>
             <input type="radio" name="character_type" value="Allow All Character Types" <?php if($setting_details -> char_type == "Allow All Character Types" or !$setting_details){echo "checked";}?>>Allow All Character Types<br>
             <input type="radio" name="character_type" value="pronounceable"<?php if($setting_details -> char_type == "pronounceable"){echo "checked";}?>>Make Pronounceable
         </div>
         
         <div id="character_symbol">
            <input type="checkbox" name="char_symbol[]" id="Capital_alphabet" value="A-Z" <?php if($char_symbols){if(in_array('A-Z',$char_symbols)) {echo "checked";}}?>>A-Z
            <input type="checkbox" name="char_symbol[]" id="small_alphabet" value="a-z" <?php if($char_symbols){if(in_array('a-z',$char_symbols)) {echo "checked";}}?>>a-z
            <input type="checkbox" name="char_symbol[]" id="numeric" value="0-9" <?php if($char_symbols){if(in_array('0-9',$char_symbols)) {echo "checked";}}?>>0-9
            <input type="checkbox" name="char_symbol[]" id="spacial_char" value="!$%@#" <?php if($char_symbols){if(in_array('!$%@#',$char_symbols)) {echo "checked";}}?>>!$%@#
         </div>

        <div id="min_digit">
            Minimum Digit Count: <input type="text" name="mini_digit" id="mini_digit" value="<?php echo $setting_details -> mini_digit; ?>"/>
        </div>
        
        <div><label>Schedule:</label>
            <select name="schedule" id="schedule">
              <option value="daily" <?php if($setting_details -> schedule == "daily"){echo "selected";}?>>Once Daily</option>
              <option value="weekly" <?php if($setting_details -> schedule == "weekly"){echo "selected";}?>>Once Weekly</option>
              <option value="fortnightly" <?php if($setting_details -> schedule == "fortnightly"){echo "selected";}?>>Once Fortnightly</option>
              <option value="monthly" <?php if($setting_details -> schedule == "monthly"){echo "selected";}?>>Once Monthly</option>
            </select>
        </div>

          <input type="hidden" id="hide"  value="password generator"/>
        <div>
          <input type="reset" value="Cancel" class="button-secondary"/>
          <input type="submit" value="Save Setting" class="button-primary"/>
        </div>

      </form>
        </div> <!-- .inside -->
      </div> <!-- .postbox -->
    </div>
  </div>


  <div style="width:25%;" class="postbox-container metabox-holder" id="side-container">
      <div class="postbox">
        <h3 style="cursor:default;"><span>Do you like this Plugin?</span></h3>
        <div class="inside">
          <p>Please consider a donation.</p>
          <div style="text-align:center">
                      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                      <input name="cmd" value="_donations" type="hidden">
                      <input name="business" value="deepak@xlab.co.in" type="hidden">
                      <input name="lc" value="US" type="hidden">
                      <input name="item_name" value="Hide File download path plugin" type="hidden">
                      <input name="no_note" value="0" type="hidden">
                      <input name="currency_code" value="USD" type="hidden">
                      <input name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest" type="hidden">
                      <input src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" border="0" type="image">
                      <img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" height="1" border="0" width="1">
                      </form>
          </div>
          <p>If you wish to help then contact <a href="https://twitter.com/deepaksihag">@deepaksihag</a> on Twitter or use that <a href="http://xlab.co.in/get-in-touch/">contact form</a>.</p>
        </div> <!-- .inside -->
      </div> <!-- .postbox -->
  </div>
    
  </div>
  
<?php } ?>


<?php
  /* Generate Password function */
  function generate_pdw()
  { 
        $setting_details = get_setting_details();
        $char_symbols = unserialize($setting_details->char_symbol);
        $password = '';
        
        $length = $setting_details -> pass_length;
        
        if($setting_details -> char_type == "Allow All Character Types" AND $char_symbols){
        
            if($setting_details -> mini_digit AND $length > $setting_details -> mini_digit){
              $length = $length - $setting_details -> mini_digit;
            }
            $sets = array();
            
                if(in_array('A-Z',$char_symbols)){
                  $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
                  }
                if(in_array('a-z',$char_symbols)){
                  $sets[] = 'abcdefghjkmnpqrstuvwxyz';
                  }
                if(in_array('0-9',$char_symbols)){
                  $sets[] = '1234567890';
                  }
                if(in_array('!$%@#',$char_symbols)){
                  $sets[] = '!@#$%&*?';
                  }

            $all = '';
            foreach($sets as $set)
            {
              $password .= $set[array_rand(str_split($set))];
              $all .= $set;
            }

            $all = str_split($all);
            for($i = 0; $i < $length - count($sets); $i++){
                $password .= $all[array_rand($all)];
              }

            $num_set = '1234567890';
            for($j = 0; $j <= $setting_details -> mini_digit - count($num_set); $j++){
                    $password .= array_rand(str_split($num_set));
              }

            $password = str_shuffle($password);
        }
        if($setting_details -> char_type == "pronounceable"){
          $conso=array('b','c','d','f','g','h','j','k','l','m','n','p','r','s','t','v','w','x','y','z');
          $vocal=array('a','e','i','o','u');
          $password='';
          srand ((double)microtime()*1000000);
          $max = $length/2;
          for($i=1; $i<=$max; $i++){
          $password.=$conso[rand(0,19)];
          $password.=$vocal[rand(0,4)];
          }
      }
          return $password;
      }
      


    function pwd_generator_schedule( $schedules ) {

      $schedules['weekly'] = array(
      'interval' => 7*24*60*60, //7 days * 24 hours * 60 minutes * 60 seconds
      'display' => __( 'Once Weekly')
    );
      
      $schedules['fortnightly'] = array(
      'interval' => 14*24*60*60, //14 days * 24 hours * 60 minutes * 60 seconds
      'display' => __( 'Once Fortnightly')
    );
      
      $schedules['monthly'] = array(
      'interval' => 30*24*60*60, //30 days * 24 hours * 60 minutes * 60 seconds
      'display' => __( 'Once Monthly')
    );

    return $schedules;
  }    


  /* On deactivation, remove all functions from the scheduled action hook. */
  function password_generator_cron_deactivation() {
    wp_clear_scheduled_hook( 'pwd_cron' );
  }

  /* Plugin deactivation function */ 
  function password_generator_deactivate(){
      global $wpdb;
      $table_name = $wpdb->prefix . "pwd_security";
      $drop_query = mysql_query( "DROP TABLE $table_name");
  }
