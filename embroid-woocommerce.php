<?php
/**
 * Plugin Name:   Embroid.dev for Woocommerce
 * Plugin URI:    https://embroid.dev
 * Description:   Automate creation of your embroidery files
 * Version:       0.0.1
 * Author:        e-mmer Interactive
 * Author URI:    https://e-mmer.nl
 */

defined('ABSPATH') || exit;
 
add_action( 'woocommerce_order_status_processing', 'wcem_order_status_processing');
function wcem_order_status_processing( $order_id ){
    $order = wc_get_order( $order_id );
    $items = $order->get_items();
    
    foreach ($items as $item) {
      // <array> WC_Meta_Data
      $meta_data = $item->get_meta_data();
      
      
      foreach($meta_data as $meta) {
        $data = $meta->get_data();
        
        $name_keys = array(
          'Naam - Naam voor op de beanie', 
          'Naam op dekentje', 'Naam op cape', 
          'Naam - Naam', 
          'Naam - Naam voor op de vlaggen', 
          'Naam - Naam op koord', 
          'Naam - Naam op doekje (max. 8 letters)', 
          'Naam', 
          'Naam op lintje', 
          'Personalisatie? - Naam (&euro;7,50)',
          'Tekst op beanie'
        );
        
        if (in_array($data['key'], $name_keys)) {
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.embroid.dev/generate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
            "name": "'.$data['value'].'"
          }',
            CURLOPT_HTTPHEADER => array(
              'accept: application/json',
              'Content-Type: application/json'
            ),
          )); 

          $response = json_decode(curl_exec($curl));

          curl_close($curl);
          
          if($response->png) {
            $item->update_meta_data(__('Embroid PNG Review', 'embroid-woocommmerce'), $response->png);
            $item->update_meta_data(__('Embroid PES file', 'embroid-woocommmerce'), $response->pes);
            $item->update_meta_data(__('Embroid DST file', 'embroid-woocommmerce'), $response->dst);
            $item->save();
          }
          
          file_put_contents( dirname(__FILE__). '/logs/embroid.log', '['.date("F j, Y, g:i a").'][NOTICE]: Embroid for ' . $data['value']. ' created. '.PHP_EOL, FILE_APPEND);
          
        }
        
      }
      
    }
    
}


add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'wcem_unset_meta_mail', 10, 2);
function wcem_unset_meta_mail($formatted_meta, $item){
    // Only on emails notifications    
    if ( !isset($_POST['wc_order_action']) && (is_admin() || is_wc_endpoint_url() ) ) {
        file_put_contents( dirname(__FILE__). '/logs/email.log', '['.date("F j, Y, g:i a").'][NOTICE]: no reformatating needed'.PHP_EOL, FILE_APPEND);
        return $formatted_meta;
    }
    
    // file_put_contents('./logs/email.log', '['.date("F j, Y, g:i a").'][NOTICE]: Reformatting the following meta:'.PHP_EOL, FILE_APPEND);
    foreach( $formatted_meta as $key => $meta ){
        if( in_array( $meta->key, array(__('Embroid PNG Review', 'embroid-woocommmerce'), __('Embroid PES file', 'embroid-woocommmerce'), __('Embroid DST file', 'embroid-woocommmerce')) ) ) {
          file_put_contents( dirname(__FILE__). '/logs/email.log', '['.date("F j, Y, g:i a").'][NOTICE]: Reformat ' . $key . ':'.PHP_EOL, FILE_APPEND);
            unset($formatted_meta[$key]);
        }
    }
    return $formatted_meta;
}