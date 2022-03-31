<?php

/**
 * wcem_register_settings.
 * 
 * @return void
 */
function wcem_register_settings() 
{
    register_setting('wcem_plugin_options', 'wcem_plugin_options', 'wcem_plugin_options_validate');
    
    add_settings_section('product_settings', 'Product Settings', 'wcem_plugin_section_text', 'wcem_plugin');

    add_settings_field('wcem_plugin_setting_type', 'Include or exclude products ', 'wcem_plugin_setting_type', 'wcem_plugin', 'product_settings');
    add_settings_field('wcem_plugin_setting_products', 'Products', 'wcem_plugin_setting_products', 'wcem_plugin', 'product_settings');
    // add_settings_field('wcem_plugin_setting_master_key', 'Master Key', 'wcem_plugin_setting_master_key', 'wcem_plugin', 'product_settings');
}
add_action('admin_init', 'wcem_register_settings');

/**
 * wcem_plugin_section_text.
 * 
 * @return string
 */
function wcem_plugin_section_text(): string
{
    return '';
}

/**
 * wcem_plugin_setting_hostname.
 * 
 * @return void
 */
function wcem_plugin_setting_type()
{
    $options = get_option('wcem_plugin_options');
    $include_checked = '';
    $exclude_checked = '';
    
    if(isset($options['in_excl'])) {
      if($options['in_excl'] === 'include') {
        $include_checked = 'checked';
      } else {
        $exclude_checked = 'checked';
      }
    }
    
    echo '<label><input type="radio" name="wcem_plugin_options[in_excl]" value="include" '.  $include_checked .'>Include</label>
    <label><input type="radio" name="wcem_plugin_options[in_excl]" value="exclude" '. $exclude_checked . '>Exclude</label>';
}

/**
 * wcem_plugin_setting_port.
 * 
 * @return void
 */
function wcem_plugin_setting_products()
{ 
    $products = [];
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 1000,
        'post_status'    => 'publish',
    );

    $loop = new WP_Query( $args );

    while ( $loop->have_posts() ) : $loop->the_post();
        global $product;
        $products[] = [
            'value' => $product->get_id(),
            'label' => $product->get_title(),
        ];
    endwhile;

    wp_reset_query();
    
    $options = get_option('wcem_plugin_options');
    $selected_products = [];
    
    if(isset($options['products'])) { 
      $selected_products = $options['products']; 
    }
    
    $output = '<select class="regular-text" id="wcem_plugin_setting_port" name="wcem_plugin_options[products][]" multiple size="20">';
    foreach($products as $product) {
      $output .= '<option value="'. $product['value'] .'" ';
        if(in_array($product['value'], $selected_products)) {
          $output .= 'selected'; 
        }
      $output .= '>'. $product['label'] .'</option>';
    }
    $output .= '</select>';
    
    echo $output;
}
