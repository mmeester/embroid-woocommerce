<div class="wrap">
  <h1>Embroidery for Woocommerce</h1>
  
  <form action="options.php" method="post">
    
      <?php 
        settings_fields('wcem_plugin_options');
        do_settings_sections('wcem_plugin'); 
      ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
  </form>
</div>
