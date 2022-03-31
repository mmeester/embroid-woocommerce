<?php

/**
 * Plugin Name:   Embroid.dev for Woocommerce
 * Plugin URI:    https://embroid.dev
 * Description:   Automate creation of your embroidery files
 * Version:       0.1.0
 * Author:        e-mmer Interactive
 * Author URI:    https://e-mmer.nl
 */

defined('ABSPATH') || exit;

add_action('woocommerce_order_status_processing', 'wcem_order_status_processing');
function wcem_order_status_processing($order_id)
{
  $order = wc_get_order($order_id);
  $items = $order->get_items();
  $options = get_option('wcem_plugin_options');

  if (isset($options['in_excl'])) {
    $in_excl = $options['in_excl'];
  } else {
    $in_excl = null;
  }

  if (isset($options['products'])) {
    $selected_products = $options['products'];
  } else {
    $selected_products = [];
  }

  foreach ($items as $item) {
    $product_id = $item->get_product_id();

    if ($in_excl !== null && $in_excl === 'include') {
      if (in_array($product_id, $selected_products)) {
        wcem_create_embroid($item, $order_id);
      }
    } elseif ($in_excl !== null && $in_excl === 'exclude') {
      if (!in_array($product_id, $selected_products)) {
        wcem_create_embroid($item, $order_id);
      }
    }
  }
}

function wcem_create_embroid($item, $order_id)
{
  file_put_contents(dirname(__FILE__) . '/logs/embroid.log', '[' . date("F j, Y, g:i a") . '][Start]: Create embroid' . PHP_EOL, FILE_APPEND);
  // <array> WC_Meta_Data
  $meta_data = $item->get_meta_data();

  $name = '';
  $font = 'romantic';

  foreach ($meta_data as $meta) {
    $data = $meta->get_data();

    if ((strstr(strtolower($data['key']), 'naam') || strstr(strtolower($data['key']), 'tekst')) && !strstr(strtolower($data['key']), 'locatie')) {
      $name = $data['value'];
    }

    if (strstr(strtolower($data['key']), 'lettertype') && strtolower($data['value']) === 'modern') {
      $font = 'modern';
    }
  }

  if ($name !== '') {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.embroid.dev/generate?font=' . $font,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => '{
        "name": "' . $name . '",
        "reference": "' . $order_id . '"
      }',
      CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'Content-Type: application/json'
      ),
    ));

    $response = json_decode(curl_exec($curl));

    curl_close($curl);

    if ($response->png) {
      $item->update_meta_data(__('Embroid PNG Review', 'embroid-woocommmerce'), $response->png);
      $item->update_meta_data(__('Embroid PES file', 'embroid-woocommmerce'), $response->pes);
      $item->update_meta_data(__('Embroid DST file', 'embroid-woocommmerce'), $response->dst);
      $item->save();
    }


    file_put_contents(dirname(__FILE__) . '/logs/embroid.log', '[' . date("F j, Y, g:i a") . '][NOTICE]: Embroid for ' . $data['value'] . ' created. ' . PHP_EOL, FILE_APPEND);
  }
}


/**
 * Register the administration menu for this plugin into the WordPress Dashboard menu.
 *
 * @since    1.0.0
 */
function add_plugin_admin_menu()
{
  // Add woocommerce menu subitem
  add_submenu_page(
    'woocommerce',
    __('Embroidery products in Woocommerce', 'embroid-woocommmerce'),
    __('Embroid', 'embroid-woocommmerce'),
    'manage_options',
    'embroid-woocommmerce',
    'display_plugin_setup_page'
  );
}

add_action('admin_menu', 'add_plugin_admin_menu', 72);

/**
 * Render the settings page for this plugin.
 *
 * @since    1.0.0
 */
function display_plugin_setup_page()
{
  include_once('partials/settings.php');
}

add_filter('woocommerce_order_item_get_formatted_meta_data', 'wcem_unset_meta_mail', 10, 2);
function wcem_unset_meta_mail($formatted_meta, $item)
{
  // Only on emails notifications    
  if (!isset($_POST['wc_order_action']) && (is_admin() || is_wc_endpoint_url())) {
    file_put_contents(dirname(__FILE__) . '/logs/email.log', '[' . date("F j, Y, g:i a") . '][NOTICE]: no reformatating needed' . PHP_EOL, FILE_APPEND);
    return $formatted_meta;
  }

  // file_put_contents('./logs/email.log', '['.date("F j, Y, g:i a").'][NOTICE]: Reformatting the following meta:'.PHP_EOL, FILE_APPEND);
  foreach ($formatted_meta as $key => $meta) {
    if (in_array($meta->key, array(__('Embroid PNG Review', 'embroid-woocommmerce'), __('Embroid PES file', 'embroid-woocommmerce'), __('Embroid DST file', 'embroid-woocommmerce')))) {
      file_put_contents(dirname(__FILE__) . '/logs/email.log', '[' . date("F j, Y, g:i a") . '][NOTICE]: Reformat ' . $key . ':' . PHP_EOL, FILE_APPEND);
      unset($formatted_meta[$key]);
    }
  }
  return $formatted_meta;
}

require plugin_dir_path(__FILE__) . 'functions/settings.php';
