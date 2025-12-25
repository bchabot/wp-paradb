<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load database class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-paradb-database.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-paradb-roles.php';

// Delete plugin options.
delete_option( 'wp_paradb_options' );
delete_option( 'wp_paradb_db_version' );

// Remove custom user roles.
WP_ParaDB_Roles::remove_roles();

// Drop database tables.
WP_ParaDB_Database::drop_tables();

// Delete uploaded evidence files.
$upload_dir = wp_upload_dir();
$paradb_dir = $upload_dir['basedir'] . '/paradb-evidence';

if ( file_exists( $paradb_dir ) ) {
	// Delete all files recursively.
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $paradb_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $fileinfo ) {
		$func = ( $fileinfo->isDir() ? 'rmdir' : 'unlink' );
		$func( $fileinfo->getRealPath() );
	}

	rmdir( $paradb_dir );
}

// For multisite installations.
if ( is_multisite() ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for multisite uninstall.
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( (int) $blog_id );

		// Delete options for this site.
		delete_option( 'wp_paradb_options' );
		delete_option( 'wp_paradb_db_version' );

		// Drop tables for this site.
		WP_ParaDB_Database::drop_tables();

		restore_current_blog();
	}
}

// Clear any cached data.
wp_cache_flush();