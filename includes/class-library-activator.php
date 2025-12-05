<?php

class Library_Activator {
	public static function activate() {
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		Library_DB::create_table();
	}
}
