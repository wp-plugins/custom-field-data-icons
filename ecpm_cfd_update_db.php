<?php
function ecpm_cfd_update_db(){
  $ecpm_cfd_show_icons = get_option('ecpm_cfd_show_icons');
  $ecpm_cfd_position = get_option('ecpm_cfd_position');
  $ecpm_cfd_enable_flds = get_option('ecpm_cfd_enable_flds');
  $ecpm_cfd_sel_fields = get_option('ecpm_cfd_sel_fields');
  $ecpm_cfd_sel_images = get_option('ecpm_cfd_sel_images');
  $ecpm_cfd_max_fields = get_option('ecpm_cfd_max_fields');
  $ecpm_cfd_sort_fields = get_option('ecpm_cfd_sort_fields');
  
  $ecpm_cfd_settings['installed_version'] = CFD_VERSION;
  $ecpm_cfd_settings['show_icons'] = $ecpm_cfd_show_icons;
  $ecpm_cfd_settings['h_position'] = $ecpm_cfd_position; 
  $ecpm_cfd_settings['enable_flds'] = $ecpm_cfd_enable_flds;
  $ecpm_cfd_settings['sel_fields'] = $ecpm_cfd_sel_fields;
  $ecpm_cfd_settings['sel_images'] = $ecpm_cfd_sel_images;    
  $ecpm_cfd_settings['max_fields'] = $ecpm_cfd_max_fields;  
  $ecpm_cfd_settings['sort_fields'] = $ecpm_cfd_sort_fields;    

  update_option( 'ecpm_cfd_settings', $ecpm_cfd_settings );
  
  delete_option( 'ecpm_cfd_show_icons' );
  delete_option( 'ecpm_cfd_position' );
  delete_option( 'ecpm_cfd_enable_flds' );
  delete_option( 'ecpm_cfd_sel_fields' );
  delete_option( 'ecpm_cfd_sel_images' );
  delete_option( 'ecpm_cfd_max_fields' );
  delete_option( 'ecpm_cfd_sort_fields' );
  
  delete_option( 'ecpm_cfd_installed' );
  
?>
    <div id="message" class="updated">
        <p><strong><?php _e('Custom Field Data Icons Database was updated!') ?></strong></p>
    </div>
<?php   
}
?>