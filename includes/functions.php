<?php

include plugin_dir_path( __FILE__ ).'cashback-cocote.php';

add_action( 'woocommerce_order_status_completed', 'mysite_woocommerce_order_status_completed', 10, 1 );

function mysite_woocommerce_order_status_completed( $order_id ) {
    $fp = fopen(plugin_dir_path( __DIR__ ). 'log' . DIRECTORY_SEPARATOR . 'log_' . date('Ymd') . '.log', 'a+');
    $observer = '[LOG ' . date('Y-m-d H:i:s') . '] function mysite_woocommerce_order_status_completed()';
    fwrite($fp, $observer . "\n");
    $observer = '[LOG ' . date('Y-m-d H:i:s') . '] '."Order complete for order $order_id";
    fwrite($fp, $observer . "\n");

    $resultat = check_cocote_export();
    $resultat_order = check_order( $order_id );

    if($resultat!=0 && isset($order_id) && isset($resultat->shop_id) && isset($resultat->private_key) && isset($resultat_order['orderPrice']) && isset($resultat_order['email'])) {

        exec('php '.plugin_dir_path( __DIR__ ) . DIRECTORY_SEPARATOR . 'cashback-cocote-2.php'.
            ' '.$resultat->shop_id.
            ' '.$resultat->private_key.
            ' '.$resultat_order['email'].
            ' '.$order_id.
            ' '.$resultat_order['orderPrice'] );
    }
    fclose($fp);
}

function check_cocote_export()
{
    global $wpdb;

    $row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}cocote_export WHERE 1");
    if (!is_null($row)) {
        return $row;
    }
    else{
        return 0;
    }
}

function check_order( $order_id ){
    $order = wc_get_order( $order_id );

    $data_order = array();
    $data_order['orderPrice'] = $order->get_total();
    $data_order['email'] = $order->get_billing_email();

   return $data_order;
}