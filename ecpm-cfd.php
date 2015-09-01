<?php
/*
Plugin Name: Custom Field Data Icons
Plugin URI: http://www.easycpmods.com
Description: Custom Field Data Icons is a lightweight plugin that will display custom field data with icons on front page. It requires Classipress theme to be installed.
Author: Easy CP Mods
Version: 1.2.2
Author URI: http://www.easycpmods.com
*/

define('ECPM_CFD', 'ecpm-cfd');
define('CFD_NAME', '/custom-field-data-icons');
define('CFD_VERSION', '1.2.2');

register_activation_hook( __FILE__, 'ecpm_cfd_activate');
//register_deactivation_hook( __FILE__, 'ecpm_cfd_deactivate');
register_uninstall_hook( __FILE__, 'ecpm_cfd_uninstall');

add_action('plugins_loaded', 'ecpm_cfd_plugins_loaded');
add_action('admin_init', 'ecpm_cfd_requires_version');
  
add_action('admin_menu', 'ecpm_cfd_create_menu_set');
add_action('wp_enqueue_scripts', 'ecpm_cfd_enqueuestyles');
add_action('admin_enqueue_scripts', 'ecpm_cfd_enqueuescripts');

add_action('appthemes_after_post_content', 'ecpm_get_loop_ad_details' ); 


function ecpm_cfd_requires_version() {
  $allowed_apps = array('classipress');
  
  if ( defined(APP_TD) && !in_array(APP_TD, $allowed_apps ) ) { 
	  $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );
		
    if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "<strong>".$plugin_data['Name']."</strong> requires a AppThemes Classipress theme to be installed. Your Wordpress installation does not appear to have that installed. The plugin has been deactivated!<br />If this is a mistake, please contact plugin developer!<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
		}
	}
}

function ecpm_cfd_activate() {
  $ecpm_cfd_settings = get_option('ecpm_cfd_settings');
  if ( empty($ecpm_cfd_settings) ) {
    $ecpm_cfd_settings = array(
      'installed_version' => CFD_VERSION,
      'h_position' => 'left',
      'show_icons' => '5',
      'max_fields' => '10',
      'sort_fields' => 'nosort',
      'enable_flds' => array(),
      'sel_fields' => array(),
      'sel_images' => array()
    );
    update_option( 'ecpm_cfd_settings', $ecpm_cfd_settings );
  }
}

function ecpm_cfd_uninstall() {                                   
  delete_option( 'ecpm_cfd_settings' );
}

function ecpm_cfd_plugins_loaded() {
  $ecpm_cfd_installed = get_option( 'ecpm_cfd_installed');
  if ($ecpm_cfd_installed == 'yes') {
     require_once( WP_PLUGIN_DIR . CFD_NAME . '/ecpm_cfd_update_db.php' );
     ecpm_cfd_update_db();
  }

  $dir = dirname(plugin_basename(__FILE__)).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR;
	load_plugin_textdomain(ECPM_CFD, false, $dir);
}


function ecpm_cfd_enqueuestyles()	{
  wp_enqueue_style('ecpm_cfd_style', plugins_url('ecpm-cfd.css', __FILE__));
}

function ecpm_cfd_enqueuescripts()	{
  //wp_enqueue_script('ecpm-cfd-jscript', plugins_url('ecpm-cfd.js', __FILE__), array(), '', true);
  wp_enqueue_style('ecpm_cfd_style', plugins_url('ecpm-cfd.css', __FILE__));
}

function ecpm_cfd_get_settings($ret_value){
  $cfd_settings = get_option('ecpm_cfd_settings');
  return $cfd_settings[$ret_value];
}

function ecpm_cfd_getFieldNames(){
  global $wpdb;
  
  $sql = "SELECT field_name FROM $wpdb->cp_ad_fields WHERE field_type IN ('drop-down', 'text box', 'radio')";
  $results = $wpdb->get_results( $sql );

  return $results;
}

function ecpm_cfd_getFieldLabel($field_name){
  global $wpdb;
 
  $sql = "SELECT field_label FROM $wpdb->cp_ad_fields WHERE field_name = '".$field_name."'";
  $result = $wpdb->get_var( $sql );
  
  if (!isset($result))
    return $field_name;
  else  
    return $result;
}

function ecpm_cfd_getFieldType($field_name){
  global $wpdb;
 
  $sql = "SELECT field_type FROM $wpdb->cp_ad_fields WHERE field_name = '".$field_name."'";
  $result = $wpdb->get_var( $sql );
  if (!isset($result))
    return 'text box';
  else
    return $result;
}

function ecpm_cfd_getAllowedFields($cfd_fields){
  if (empty($cfd_fields))
    return false;
    
  $allowed_fields = array();
  $results = ecpm_cfd_getFieldNames();

  foreach ( $results as $field ) {
    if ( in_array( $field->field_name, $cfd_fields) ) {
      $allowed_fields[] = $field->field_name;
    }
  }
   
  return $allowed_fields;
}

// display some custom fields on the loop ad listing
function ecpm_get_loop_ad_details() {
  global $post, $wpdb;
  if ( is_single() )
    return;

  $ecpm_cfd_settings = get_option('ecpm_cfd_settings');
  
  $ecpm_cfd_sort_fields = $ecpm_cfd_settings['sort_fields'];
  $ecpm_cfd_show_icons  = $ecpm_cfd_settings['show_icons'];
  $ecpm_cfd_enable_flds = $ecpm_cfd_settings['enable_flds'];
  $ecpm_cfd_sel_fields  = $ecpm_cfd_settings['sel_fields'];
  $ecpm_cfd_sel_images  = $ecpm_cfd_settings['sel_images'];
  $ecpm_cfd_h_position  = $ecpm_cfd_settings['h_position'];  
  
  $location = 'list';

  if ( ! $post )
    return;
  
  $cp_results = ecpm_cfd_getAllowedFields($ecpm_cfd_sel_fields);
  if (!$cp_results)
    return;

  $showing_icon = 1;
  $ecpm_cfd_out_arr = array();
  
  echo '<div id="custom-stats-'.$ecpm_cfd_h_position.'">';

  foreach ( $cp_results as $cp_result ) {
    $cfd_key = array_search($cp_result, $ecpm_cfd_sel_fields);
    if ($cfd_key === false )
      continue;
      
    if ( $ecpm_cfd_enable_flds[$cfd_key] == 'on' ) {
      
      $post_meta_val = get_post_meta( $post->ID, $cp_result, true );
      if ( empty( $post_meta_val ) )
        continue;
    
      $field_label = ecpm_cfd_getFieldLabel($cp_result);
      $field_type = ecpm_cfd_getFieldType($cp_result);
      $cfd_image_filename = $ecpm_cfd_sel_images[$cfd_key];
      $image_html = '';
        
      $args = array( 'value' => $post_meta_val, 'label' => $field_label, 'id' => $cp_result, 'class' => '' );
      $args = apply_filters( 'cp_ad_details_' . $cp_result, $args, $cp_result, $post, $location );

      $image_html = '';
      if ( $cfd_image_filename )
         $image_html = '<img class="custom-stats-icon" src="'. plugins_url('images/'. $cfd_image_filename, __FILE__). '" title="'. esc_html( translate( $args['label'], APP_TD ) ).'" width="16" height="16">';
         
      if ( $args ) {
        $ecpm_cfd_out_arr[$cfd_key] = '<span class="custom-stats">'.$image_html . $args['value'] . '</span>';

        if ( in_array($ecpm_cfd_sort_fields, array('nosort', '') ) )
          echo $ecpm_cfd_out_arr[$cfd_key]; 
      }
    }
  }
  
  if ( in_array($ecpm_cfd_sort_fields, array('random', 'number') ) ) {
    if ( $ecpm_cfd_sort_fields == 'random' ) 
      shuffle($ecpm_cfd_out_arr);
    else
      ksort($ecpm_cfd_out_arr);
        
    foreach ( $ecpm_cfd_out_arr as $arr_value ) {
      if ($showing_icon <= $ecpm_cfd_show_icons ) {
        if ( $arr_value ) {
          echo $arr_value;
          $showing_icon++;
        }
      }
    }   
  }
  echo '</div>';
}

function ecpm_cfd_create_menu_set() {
    add_options_page('Custom Field Data Icons','Custom Field Data Icons','manage_options', 'ecpm_cfd_settings_page','ecpm_cfd_settings_page_callback');
}    
  
function ecpm_cfd_settings_page_callback() {
?>
	<div class="wrap">
	<?php
	
	if( isset( $_POST['ecpm_cfd_submit'] ) )
	{
    $ecpm_cfd_settings['enable_tabs'] = $_POST[ 'ecpm_cfd_enable_tab' ];
    $ecpm_cfd_settings['h_position'] = $_POST[ 'ecpm_cfd_h_pos' ];
    $ecpm_cfd_settings['max_fields'] = $_POST[ 'ecpm_cfd_max_fields' ];    
    $ecpm_cfd_settings['sort_fields'] = $_POST[ 'ecpm_cfd_sort_fields' ];    
    $ecpm_cfd_settings['show_icons'] = $_POST[ 'ecpm_cfd_show_icons' ];    
// loop
    for ($i = 0; $i < $ecpm_cfd_settings['max_fields']; $i++) {
      $ecpm_cfd_settings['enable_flds'][$i] = $_POST[ 'ecpm_cfd_enable_fld_'.$i ];
      $ecpm_cfd_settings['sel_fields'][$i] = $_POST[ 'ecpm_cfd_field_'.$i ];
      $ecpm_cfd_settings['sel_images'][$i] = $_POST[ 'ecpm_cfd_image_'.$i ];
    }
    
    update_option( 'ecpm_cfd_settings', $ecpm_cfd_settings );
    
    ?>
        <div id="message" class="updated">
            <p><strong><?php _e('Settings saved.') ?></strong></p>
        </div>
    <?php  
	}
  
  $ecpm_cfd_settings = get_option('ecpm_cfd_settings');
  
  $ecpm_cfd_field_results = ecpm_cfd_getFieldNames();
  $ecpm_cfd_image_results = array_diff(scandir(WP_PLUGIN_DIR . CFD_NAME.'/images'), array('..', '.'));
  ?>
  
		<div id="cfdsetting">
			<h1><?php echo _e('Custom Field Data Icons', ECPM_CFD); ?></h1>
  			<hr>
        <div id='cfd-container-left' style='float: left; margin-right: 285px;'>
        <form id='cfdsettingform' method="post" action="">
          <p>
          <strong><?php echo _e('Horizontal position:', ECPM_CFD); ?></strong>
          <br><Input type="radio" Name="ecpm_cfd_h_pos" value="left" <?php echo ($ecpm_cfd_settings['h_position'] == 'left' ? 'checked':'') ;?>><?php _e('Left', ECPM_CFD);?>
          <br><Input type="radio" Name="ecpm_cfd_h_pos" value="right" <?php echo ($ecpm_cfd_settings['h_position'] == 'right' ? 'checked':'') ;?>><?php _e('Right', ECPM_CFD);?>
          </p>

          <p>
          <strong><?php echo _e('Max icons to show:', ECPM_CFD); ?></strong>
          <select name="ecpm_cfd_show_icons">
            <?php
            for ($i = 1; $i<=10; $i++)
              echo '<option value="'.$i.'"'. ($ecpm_cfd_settings['show_icons'] == $i ? 'selected':'') .">".$i."</option>";
            ?>
          </select>
          </p>

          <p>
          <strong><?php echo _e('Sorting:', ECPM_CFD); ?></strong>
          <select name="ecpm_cfd_sort_fields">
             <option value="nosort" <?php echo ($ecpm_cfd_settings['sort_fields'] == 'nosort' ? 'selected':'') ;?>><?php echo _e('No sorting', ECPM_CFD); ?></option>
             <option value="number" <?php echo ($ecpm_cfd_settings['sort_fields'] == 'number' ? 'selected':'') ;?>><?php echo _e('By numbers', ECPM_CFD); ?></option>
             <option value="random" <?php echo ($ecpm_cfd_settings['sort_fields'] == 'random' ? 'selected':'') ;?>><?php echo _e('Random', ECPM_CFD); ?></option>
          </select>
          </p>

          <p>
          <strong><?php echo _e('Fields to show:', ECPM_CFD); ?>
             <Input type='text' size='2' Name ='ecpm_cfd_max_fields' value='<?php echo $ecpm_cfd_settings['max_fields'];?>'>
          </strong>
          </p>

          <p>
          <table width="600px" cellspacing="0" cellpadding="3" border="0">
            <tr>
              <td width="50px" colspan="2" align="center"><?php echo _e('Enable', ECPM_CFD); ?></td>
              <td align="center"><?php echo _e('Field', ECPM_CFD); ?></td>
              <td align="center" colspan="2"><?php echo _e('Icon', ECPM_CFD); ?></td>
            </tr>
          <?php 
            //  $ecpm_cfd_settings['max_fields'] = 10;
              
            for ($i = 0; $i < $ecpm_cfd_settings['max_fields']; $i++){
              $item = 0;
              ?>
              <tr>
              <td align="center"><?php echo $i+1 .". ";?></td>
              <td align="center"><Input type='checkbox' Name='ecpm_cfd_enable_fld_<?php echo $i;?>' <?php echo ( $ecpm_cfd_settings['enable_flds'][$i] == 'on' ? 'checked':'') ;?> ></td>
              <td align="center">
              <select name="ecpm_cfd_field_<?php echo $i;?>">
                <option value="" <?php echo (!$ecpm_cfd_settings['sel_fields'][$i] ? 'selected':'') ;?>><?php echo _e('-- No field --', ECPM_CFD); ?></option>
              <?php
            	  foreach ( $ecpm_cfd_field_results as $result ) {
                  $item++;
                  $field_label = ecpm_cfd_getFieldLabel($result->field_name);
							  ?>
									<option value="<?php echo $result->field_name; ?>" <?php echo ($ecpm_cfd_settings['sel_fields'][$i] == $result->field_name ? 'selected':'') ;?>><?php echo $field_label; ?></option>
							  <?php
							  } 
              ?>
              </select>
              </td>
              <td align="center">
              <select name="ecpm_cfd_image_<?php echo $i;?>" >
                <option value="" <?php echo (!$ecpm_cfd_settings['sel_images'][$i] ? 'selected':'') ;?>><?php echo _e('-- No image --', ECPM_CFD); ?></option>
              <?php
            	  foreach ( $ecpm_cfd_image_results as $result ) {
                  if (!is_dir($result)) {
							  ?>
									<option value="<?php echo $result; ?>" <?php echo ($ecpm_cfd_settings['sel_images'][$i] == $result ? 'selected':'') ;?>><?php echo $result; ?></option>
							  <?php
                  }
							  } 
              ?>
              </select>
              </td>
              <td align="center">
              <?php
              if ( $ecpm_cfd_settings['sel_images'][$i] ) { ?>
                <span class="cfd-img-admin"><img id="ecpm_image_<?php echo $i;?>" src="<?php echo plugins_url('images/'. $ecpm_cfd_settings['sel_images'][$i], __FILE__);?>"></span>
              <?php 
              }
              ?>  
              </td>
              </tr>
              <?php
					  } 
            ?>
          
          </table>
          
          </p>
          <hr> 
          
  				<p class="submit">
  				<input type="submit" id="ecpm_cfd_submit" name="ecpm_cfd_submit" class="button-primary" value="<?php _e('Save settings', ECPM_CFD); ?>" />
  				</p>
  			</form>
        </div>
        
        <div id='cfd-container-right' class='nocloud' style='border: 1px solid #e5e5e5; float: right; margin-left: -275px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04); display: inline-block; width: 234px;'>
        <h3>Custom Field Data Icons PRO</h3>
        <hr>
			  <p>Would you like to have more lines of icons or even additional text from other fields to be shown on loop ad listing?</p>
        <p>Then you should consider buying a PRO version of this plugin.</p>
        <p><strong>Additional features include:</strong>
        <ul>
        <li>- Multiple lines</li>
        <li>- Icons above or below the ad</li>
        <li>- Additional Classipress meta fields</li>
        <li>- Additional font settings</li>
        <li>- Option to dislay data with <strong>QR code</strong></li>
        </ul>
        </p>
        
			  <p>You can purchase Custom Field Data Icons PRO plugin from <a href="http://easycpmods.com/custom-field-data-icons-pro" target="_blank">here</a>.</p>
        <hr>
        <p>
        Please visit <a href="http://easycpmods.com/" target="_blank">our page</a> where you will find other usefull plugins.
        </p>
        <a href="http://easycpmods.com/" target="_blank">Easy CP Mods</a>
        </div>

		</div>
	</div>
<?php
}

?>