<?php
/*
Plugin Name: Custom Field Data Icons
Plugin URI: http://www.easycpmods.com
Description: Custom Field Data Icons is a lightweight plugin that will display custom field data with icons on front page. It requires Classipress theme to be installed.
Author: Easy CP Mods
Version: 1.0.0
Author URI: http://www.easycpmods.com
*/

define('ECPM_CFD', 'ecpm-cfd');
define('CFD_NAME', '/custom-field-data-icons');
define('CFD_MAX_FIELDS', '12');

register_activation_hook( __FILE__, 'ecpm_cfd_activate');
//register_deactivation_hook( __FILE__, 'ecpm_cfd_deactivate');
register_uninstall_hook( __FILE__, 'ecpm_cfd_uninstall');

add_action('plugins_loaded', 'ecpm_cfd_plugins_loaded');
add_action('admin_init', 'ecpm_cfd_requires_version');
  
add_action('admin_menu', 'ecpm_cfd_create_menu_set');
add_action('wp_enqueue_scripts', 'ecpm_cfd_enqueuestyles');
add_action('admin_enqueue_scripts', 'ecpm_cfd_enqueuescripts');

add_action('appthemes_after_post_content', 'ecpm_get_loop_ad_details', 15 ); 


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
  $ecpm_cfd_installed = get_option('ecpm_cfd_installed');
  if ( $ecpm_cfd_installed != 'yes' ) {
    update_option( 'ecpm_cfd_installed', 'yes' );
    update_option( 'ecpm_cfd_position', 'left' );
    update_option( 'ecpm_cfd_show_icons', '5' );
    update_option( 'ecpm_cfd_max_fields', '10' );
  }
}

function ecpm_cfd_uninstall() {                                   
  delete_option( 'ecpm_cfd_installed' );
  delete_option( 'ecpm_cfd_position' );
  delete_option( 'ecpm_cfd_enable_flds' );
  delete_option( 'ecpm_cfd_sel_fields' );
  delete_option( 'ecpm_cfd_sel_images' );
  delete_option( 'ecpm_cfd_show_icons' );
  delete_option( 'ecpm_cfd_max_fields' );
}

function ecpm_cfd_plugins_loaded() {
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

function ecpm_cfd_getFields(){
  global $wpdb;
  
  $sql = "SELECT field_name, field_label FROM $wpdb->cp_ad_fields WHERE field_type IN ('drop-down', 'text box', 'radio')";
  $results = $wpdb->get_results( $sql );

  return $results;
}

function ecpm_cfd_getAllowedFields(){
  $allowed_fields = '';
  $results = ecpm_cfd_getFields();

  foreach ( $results as $field ) {
    if ( $allowed_fields ) 
      $allowed_fields .= ", ";

    $allowed_fields .= "'".$field->field_name."'";
  }
   
  return $allowed_fields;
}

function ecpm_cfd_getImageFilename($field_name){
  $ecpm_cfd_enable_flds = get_option('ecpm_cfd_enable_flds');
  $ecpm_cfd_sel_fields = get_option('ecpm_cfd_sel_fields');
  $ecpm_cfd_sel_images = get_option('ecpm_cfd_sel_images');
  
  $array_key = array_search($field_name, $ecpm_cfd_sel_fields);
  
  if ( $ecpm_cfd_enable_flds[$array_key] != 'on' || !$array_key )
    return "off";
  else
    return $ecpm_cfd_sel_images[$array_key]; 
}

// display some custom fields on the loop ad listing
function ecpm_get_loop_ad_details() {
  global $post, $wpdb;
  
  $location = 'list';

  if ( ! $post )
    return;
  
  $allowed_fields = ecpm_cfd_getAllowedFields();
  $sql = "SELECT field_label, field_name, field_type FROM $wpdb->cp_ad_fields WHERE field_name IN (".$allowed_fields.")";

  $cp_results = $wpdb->get_results( $sql );

  if ( ! $cp_results )
    return;

  $ecpm_cfd_show_icons = get_option('ecpm_cfd_show_icons');
  $showing_icon = 1;
  
  $ecpm_cfd_position = get_option('ecpm_cfd_position');
  echo '<div id="custom-stats-'.$ecpm_cfd_position.'">';

  foreach ( $cp_results as $cp_result ) {
    if ($showing_icon <= $ecpm_cfd_show_icons ) {
      
      $image_filename = ecpm_cfd_getImageFilename($cp_result->field_name);
  
      if ($image_filename != 'off') {
        $post_meta_val = get_post_meta( $post->ID, $cp_result->field_name, true );
        if ( empty( $post_meta_val ) )
          continue;
    
        $args = array( 'value' => $post_meta_val, 'label' => $cp_result->field_label, 'id' => $cp_result->field_name, 'class' => '' );
        $args = apply_filters( 'cp_ad_details_' . $cp_result->field_name, $args, $cp_result, $post, $location );

        $showing_icon++;
        $image_html = '';
        if ( $image_filename )
           $image_html = '<img class="custom-stats-icon" src="'. plugins_url('images/'. $image_filename, __FILE__). '" title="'. esc_html( translate( $args['label'], APP_TD ) ).'" width="16" height="16">';
           
        if ( $args )
          echo '<span class="custom-stats">'.$image_html . $args['value'] . '</span>';
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
    if ( !isset($_POST[ 'ecpm_cfd_position' ]) )
        $ecpm_cfd_position = '';
      else
        $ecpm_cfd_position = $_POST[ 'ecpm_cfd_position' ]; 
    
    if ( !isset($_POST[ 'ecpm_cfd_max_fields' ]) )
        $ecpm_cfd_max_fields = get_option('ecpm_cfd_max_fields');
      else
        $ecpm_cfd_max_fields = $_POST[ 'ecpm_cfd_max_fields' ];    
    
    if ( !isset($_POST[ 'ecpm_cfd_show_icons' ]) )
        $ecpm_cfd_show_icons = '';
      else
        $ecpm_cfd_show_icons = $_POST[ 'ecpm_cfd_show_icons' ];    
    
    for ($i = 0; $i < $ecpm_cfd_max_fields; $i++) {
      if ( isset($_POST[ 'ecpm_cfd_enable_fld_'.$i ]) )
        $ecpm_cfd_enable_flds[$i] = $_POST[ 'ecpm_cfd_enable_fld_'.$i ];
      else  
        $ecpm_cfd_enable_flds[$i] = "";
      
      if (isset ($_POST[ 'ecpm_cfd_field_'.$i ]) )
        $ecpm_cfd_sel_fields[$i] = $_POST[ 'ecpm_cfd_field_'.$i ];
      else  
        $ecpm_cfd_sel_fields[$i] = "";
        
      if (isset($_POST[ 'ecpm_cfd_image_'.$i ]))
        $ecpm_cfd_sel_images[$i] = $_POST[ 'ecpm_cfd_image_'.$i ];
      else  
        $ecpm_cfd_sel_images[$i] = "";
        
    }
    
    update_option( 'ecpm_cfd_show_icons', $ecpm_cfd_show_icons );
    update_option( 'ecpm_cfd_position', $ecpm_cfd_position );
    update_option( 'ecpm_cfd_enable_flds', $ecpm_cfd_enable_flds );
    update_option( 'ecpm_cfd_sel_fields', $ecpm_cfd_sel_fields ); 
    update_option( 'ecpm_cfd_sel_images', $ecpm_cfd_sel_images );
    update_option( 'ecpm_cfd_max_fields', $ecpm_cfd_max_fields );
    
    ?>
        <div id="message" class="updated">
            <p><strong><?php _e('Settings saved.') ?></strong></p>
        </div>
    <?php  
	}
  
  $ecpm_cfd_show_icons = get_option('ecpm_cfd_show_icons');
  $ecpm_cfd_position = get_option('ecpm_cfd_position');
  $ecpm_cfd_enable_flds = get_option('ecpm_cfd_enable_flds');
  $ecpm_cfd_sel_fields = get_option('ecpm_cfd_sel_fields');
  $ecpm_cfd_sel_images = get_option('ecpm_cfd_sel_images');
  $ecpm_cfd_max_fields = get_option('ecpm_cfd_max_fields');
  
  ?>
  
		<div id="cfdsetting">
			<h1><?php echo _e('Custom Field Data Icons', ECPM_DDC); ?></h1>
  			<form id='cfdsettingform' method="post" action="">
          <hr>
          <h3><?php echo _e('Position custom data', ECPM_CFD); ?></h3>
          <Input type="radio" Name="ecpm_cfd_position" value="left" <?php echo ($ecpm_cfd_position == 'left' ? 'checked':'') ;?>><?php _e('Left', ECPM_CFD);?>
          <br><Input type="radio" Name="ecpm_cfd_position" value="right" <?php echo ($ecpm_cfd_position == 'right' ? 'checked':'') ;?>><?php _e('Right', ECPM_CFD);?>

          <h3><?php echo _e('Max icons to show:', ECPM_DDC); ?></h3>
          <select name="ecpm_cfd_show_icons">
            <?php
            for ($i = 1; $i<=10; $i++)
              echo '<option value="'.$i.'"'. ($ecpm_cfd_show_icons == $i ? 'selected':'') .">".$i."</option>";
            ?>
          </select>

          <br>
          <h3><?php echo _e('Fields to show', ECPM_CFD); ?>
             <Input type='text' size='2' Name ='ecpm_cfd_max_fields' value='<?php echo $ecpm_cfd_max_fields;?>'>
          </h3>

          <table width="600px">
            <tr>
              <td width="100px" colspan="2"><?php echo _e('Enable', ECPM_CFD); ?></td>
              <td width="200px"><?php echo _e('Selected field:', ECPM_CFD); ?></td>
              <td width="300px"><?php echo _e('Selected image:', ECPM_CFD); ?></tr>
            </tr>
          <?php 
            
            $field_results = ecpm_cfd_getFields();
            $image_results = array_diff(scandir(WP_PLUGIN_DIR . CFD_NAME.'/images'), array('..', '.'));
 
            for ($i = 0; $i < $ecpm_cfd_max_fields; $i++){
              $item = 0;
              ?>
              <tr>
              <td><?php echo $i+1 .". ";?></td>
              <td><Input type='checkbox' Name='ecpm_cfd_enable_fld_<?php echo $i;?>' <?php echo ($ecpm_cfd_enable_flds[$i] == 'on' ? 'checked':'') ;?> ></td>
              <td>
              <select name="ecpm_cfd_field_<?php echo $i;?>">
                <option value="" <?php echo (!$ecpm_cfd_sel_fields[$i] ? 'selected':'') ;?>>-- No field --</option>
              <?php
            	  foreach ( $field_results as $result ) {
                  $item++;
							  ?>
									<option value="<?php echo $result->field_name; ?>" <?php echo ($ecpm_cfd_sel_fields[$i] == $result->field_name ? 'selected':'') ;?>><?php echo $result->field_label; ?></option>
							  <?php
							  } 
              ?>
              </select>
              </td>
              <td>
              <select name="ecpm_cfd_image_<?php echo $i;?>" >
                <option value="" <?php echo (!$ecpm_cfd_sel_images[$i] ? 'selected':'') ;?>>-- No image --</option>
              <?php
            	  foreach ( $image_results as $result ) {
                  if (!is_dir($result)) {
							  ?>
									<option value="<?php echo $result; ?>" <?php echo ($ecpm_cfd_sel_images[$i] == $result ? 'selected':'') ;?>><?php echo $result; ?></option>
							  <?php
                  }
							  } 
              ?>
              </select>
              <?php
              if ( $ecpm_cfd_sel_images[$i] ) { ?>
                <span class="cfd-img-admin"><img id="ecpm_image_<?php echo $i;?>" src="<?php echo plugins_url('images/'. $ecpm_cfd_sel_images[$i], __FILE__);?>"></span>
              <?php 
              }
              ?>  
              </td>
              </tr>
              <?php
					  } 
            ?>
          
          </table>
          
          <hr> 
          
  				<p class="submit">
  				<input type="submit" id="ecpm_cfd_submit" name="ecpm_cfd_submit" class="button-primary" value="<?php _e('Save settings', ECPM_CFD); ?>" />
  				</p>
  			</form>
      

		</div>
	</div>
<?php
}

?>