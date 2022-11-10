<?php

/**
 * Plugin Name: WPR Migrator
 * Plugin URI: #
 * Description: WPR Migrator plugin for wp domine migration
 * Version: 0.0.1
 * Author: Andrei Leca
 * Author URI:
 * Text Domain: wpr-migrator
 * License: MIT
 */

namespace WPRMigrator;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPRMigrator' ) ) :

	class WPRMigrator {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPRMigrator ) ) {
				self::$instance = new WPRMigrator;
				self::$instance->constants();
				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Constants
		 */
		public function constants() {
			// Plugin version
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_VERSION' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_VERSION', '0.0.1' );
			}

			// Plugin file
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_FILE' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_FILE', __FILE__ );
			}

			// Plugin basename
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_BASENAME' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_BASENAME', plugin_basename( WPR_MIGRATOR_PLUGIN_FILE ) );
			}

			// Plugin directory path
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_DIR_PATH' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_DIR_PATH', trailingslashit( plugin_dir_path( WPR_MIGRATOR_PLUGIN_FILE ) ) );
			}

			// Plugin directory URL
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_DIR_URL' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_DIR_URL', trailingslashit( plugin_dir_url( WPR_MIGRATOR_PLUGIN_FILE ) ) );
			}

			// Plugin URL assets
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_DIR_URL_ASSETS' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_DIR_URL_ASSETS', trailingslashit( WPR_MIGRATOR_PLUGIN_DIR_URL . 'assets' ) );
			}

			// Plugin directory classes
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_CLASSES' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_CLASSES', trailingslashit( WPR_MIGRATOR_PLUGIN_DIR_PATH . 'classes' ) );
			}

			// Plugin directory templates
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_TEMPLATES' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_TEMPLATES', trailingslashit( WPR_MIGRATOR_PLUGIN_DIR_PATH . 'templates' ) );
			}

			// Plugin directory backup
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_BACKUP' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_BACKUP', trailingslashit( WPR_MIGRATOR_PLUGIN_DIR_PATH . 'bk' ) );
			}

			// Plugin URL backup
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP', trailingslashit( WPR_MIGRATOR_PLUGIN_DIR_URL . 'bk' ) );
			}

			// Plugin directory download
			if ( ! defined( 'WPR_MIGRATOR_PLUGIN_DOWNLOAD' ) ) {
				define( 'WPR_MIGRATOR_PLUGIN_DOWNLOAD', trailingslashit( WPR_MIGRATOR_PLUGIN_DIR_PATH . 'downloads' ) );
			}
		}

		/**
		 * Include/Require PHP files
		 */
		public function includes() {

			include_once WPR_MIGRATOR_PLUGIN_DIR_PATH . 'vendor/autoload.php';

			require_once WPR_MIGRATOR_PLUGIN_DIR_PATH . 'helpers.php';

			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'class-migratorapi.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'class-migratordashboard.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'class-migratordownloads.php';

			\MigratorApi::instance();
			\MigratorDashboard::instance();
			\MigratorDownloads::instance();
		}
	}

endif;

WPRMigrator::instance();
