<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'library_books';

// Drop the custom table
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Optionally, delete plugin options
delete_option( 'library_db_version' );
