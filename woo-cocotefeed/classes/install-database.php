<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class install_database
{
    public function __construct()
    {
        // RAS
    }

    public static function install()
    {
        global $wpdb;

        $wpdb->query("CREATE TABLE IF NOT EXISTS 
                      {$wpdb->prefix}cocote_export (
                      id_export INT AUTO_INCREMENT PRIMARY KEY,
                      shop_id varchar(10) NOT NULL,
                      private_key varchar(255) NOT NULL,
                      export_status int(1) NOT NULL DEFAULT '1',
                      export_xml varchar(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                      ") ;
    }

    public static function uninstall()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cocote_export;");
    }
}