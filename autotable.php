<?php 
    /*
    Plugin Name: WP AUTOTABLE Example
    Plugin URI: https://github.com/nubuntu/wp_autotable.git
    Description: Plugin for CRUD Table
    Author: Noercholis (nubuntu)
    Version: 1.0
    Author URI: http://nubuntu.github.io
    */
	
	define( 'AUTOTABLE__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'AUTOTABLE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

	require_once( AUTOTABLE__PLUGIN_DIR . 'nubuntu.autotable.php' );

	$autotable = new Autotable;
	add_action( 'init', [$autotable,'init'] );	
?>