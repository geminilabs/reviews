<?php

/**
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2016, Paul Ryley
 * @license   GPLv2 or later
 * @since     1.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews;

use GeminiLabs\SiteReviews\Container;

final class App extends Container
{
	public $defaults;
	public $file;
	public $id;
	public $name;
	public $path;
	public $prefix;
	public $url;
	public $version;

	public function __construct()
	{
		// hardcoded path to the plugin file
		$file = realpath( dirname( __DIR__ ) . '/site-reviews.php' );

		$data = [
			'id'      => 'Text Domain',
			'name'    => 'Plugin Name',
			'version' => 'Version',
		];

		$plugin = get_file_data( $file, $data, 'plugin' );

		$this->id       = $plugin['id'];
		$this->file     = $file;
		$this->name     = $plugin['name'];
		$this->path     = plugin_dir_path( $file );
		$this->prefix   = str_replace( '-', '_', $plugin['id'] );
		$this->url      = plugin_dir_url( $file );
		$this->version  = $plugin['version'];
	}

	/**
	 * This is the Application entry point
	 *
	 * @return void
	 */
	public function init()
	{
		$this->defaults = $this->defaultSettings();

		$basename = plugin_basename( $this->file );

		$controller = $this->make( 'Controllers\MainController' );
		$router     = $this->make( 'Router' );

		// Action Hooks
		add_action( 'admin_enqueue_scripts',                 [ $controller, 'enqueueAssets'] );
		add_action( 'wp_enqueue_scripts',                    [ $controller, 'enqueueAssets'] );
		add_action( 'admin_menu',                            [ $controller, 'registerMenuCount'] );
		add_action( 'add_meta_boxes_site-review',            [ $controller, 'registerMetaBox'] );
		add_action( 'admin_enqueue_scripts',                 [ $controller, 'registerPointers'], 13 );
		add_action( 'init',                                  [ $controller, 'registerPostType'] );
		add_action( 'admin_init',                            [ $controller, 'registerSettings'] );
		add_action( 'init',                                  [ $controller, 'registerShortcodes'] );
		add_action( 'admin_menu',                            [ $controller, 'registerSubMenus'] );
		add_action( 'init',                                  [ $controller, 'registerTextdomain'] );
		add_action( 'widgets_init',                          [ $controller, 'registerWidgets'] );
		add_action( 'post_submitbox_misc_actions',           [ $controller, 'renderMetaBoxPinned'] );
		add_action( "wp_ajax_{$this->prefix}_action",        [ $router, 'routeAjaxRequests'] );
		add_action( "wp_ajax_nopriv_{$this->prefix}_action", [ $router, 'routeAjaxRequests'] );
		add_action( 'init',                                  [ $router, 'routePostRequests'] );
		add_action( 'init',                                  [ $router, 'routeWebhookRequests'] );

		// Filter Hooks
		add_filter( "plugin_action_links_{$basename}", [ $controller, 'registerActionLinks'] );
		add_filter( 'dashboard_glance_items',          [ $controller, 'registerDashboardGlanceItems'] );
		add_filter( 'post_row_actions',                [ $controller, 'registerRowActions'], 10, 2 );
	}

	/**
	 * Runs on plugin activation
	 *
	 * @return void
	 */
	public function activate()
	{
		$current_version = get_option( "{$this->prefix}_version" );

		if( $current_version ) {
			update_option( "{$this->prefix}_version_upgraded_from", $current_version );
		}

		update_option( "{$this->prefix}_logging", 0 );
		update_option( "{$this->prefix}_version", $this->version );

		$this->make( 'Database' )->setSettings();

		// Schedule session purge
		if( !wp_next_scheduled( 'site-reviews/schedule/session/purge' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'site-reviews/schedule/session/purge' );
		}
	}

	/**
	 * Runs on plugin deactivation
	 *
	 * @return void
	 */
	public function deactivate()
	{
		$events = ['site-reviews/schedule/session/purge'];

		foreach( $events as $event ) {
			wp_unschedule_event( wp_next_scheduled( $event ), $event );
		}
	}

	/**
	 * Get the default settings
	 *
	 * @return array
	 */
	public function defaultSettings()
	{
		$defaults = $this->make( 'Settings' )->getSettings();

		// Allow addons to modify the default settings
		$this->defaults = apply_filters( 'site-reviews/addon/defaults', $defaults );
	}

	/**
	 * Verify permissions
	 *
	 * @return bool
	 */
	public function verify()
	{
		return current_user_can( 'customize' );
	}
}
