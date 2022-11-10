<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MigratorDashboard' ) ) :

	class MigratorDashboard {

		private static $instance;

		/**
		 * It creates a singleton instance of the class.
		 *
		 * @return The instance of the class.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MigratorDashboard ) ) {
				self::$instance = new MigratorDashboard;
				self::$instance->hook();
			}

			return self::$instance;
		}

		/**
		 * It creates a hook for the plugin settings menu page.
		 *
		 * @return false.
		 */
		public function hook() {
			if ( ! is_admin() ) {
				return false;
			}

			add_action( 'admin_menu', array( $this, 'plugin_settings_menu_page' ), 20 );
			add_action( 'admin_post_save_info_migrator', array( $this, 'save_info_migrator' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'web_migrator_assets' ) );
		}

		public function web_migrator_assets() {
			if ( 'tools_page_wpr-migrator' === get_current_screen()->base ) {

				wp_register_style( 'wpr-migrator-css', WPR_MIGRATOR_PLUGIN_DIR_URL_ASSETS . '/css/styles.css', false, '1.0.0' );
				wp_enqueue_style( 'wpr-migrator-css' );

				wp_enqueue_script( 'wpr-migrator-js', WPR_MIGRATOR_PLUGIN_DIR_URL_ASSETS . 'js/script.js', array( 'jquery' ), false );
			}
		}

		/**
		 * It adds a submenu page to the Tools menu
		 */
		public function plugin_settings_menu_page() {
			add_submenu_page(
				'tools.php',
				__( 'WPR Migrator', 'wpr-migrator' ),
				__( 'WPR Migrator', 'wpr-migrator' ),
				'manage_options',
				'wpr-migrator',
				array( $this, 'plugin_settings_page_content' ),
				100
			);
		}

		/**
		 * It's a function that creates a settings page for the plugin
		 */
		public function plugin_settings_page_content() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'wpr-panasonic-web-services' ) );
			}

			$stored_option = get_option( 'save_info_migrator' );
			if ( ! isset( $stored_option['key_domine'] ) || empty( $stored_option['key_domine'] ) ) {

				$key_domine = $this->str_random( 15 ) . md5( $_SERVER['HTTP_HOST'] );
				update_option( 'save_info_migrator', array( 'key_domine' => $key_domine ) );
			}

			include_once WPR_MIGRATOR_PLUGIN_TEMPLATES . 'dashboard.php';
		}

		public function str_random( $length = 16 ) {
			$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

			return substr( str_shuffle( str_repeat( $pool, $length ) ), 0, $length );
		}

		/**
		 * If the user has submitted the form, save the data and redirect back to the previous page
		 *
		 * @return the value of the option 'save_info_migrator'
		 */
		public function save_info_migrator() {
			if ( ! isset( $_POST['save_info_migrator'] ) || ! is_admin() ) {
				return;
			}

			status_header( 200 );

			update_option( 'save_info_migrator', $_POST['save_info_migrator'] );

			wp_redirect( $_SERVER['HTTP_REFERER'] );
			exit();
		}
	}

endif;

MigratorDashboard::instance();
