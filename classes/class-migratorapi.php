<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MigratorApi' ) ) :

	class MigratorApi {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MigratorApi ) ) {
				self::$instance = new MigratorApi;
				self::$instance->includes();
			}

			return self::$instance;
		}

		public function includes() {

			header( 'Connection: keep-alive' );
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Authorization' );
			header( 'Access-Control-Allow-Methods: *' );
			header( 'Content-Type: application/json' );

			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'inc/class-migratordatabase.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'inc/class-migratorplugins.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'inc/class-migratorthemes.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'inc/class-migratoruploads.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'inc/class-migratorwpcontent.php';
			require_once WPR_MIGRATOR_PLUGIN_CLASSES . 'inc/class-migratorwpcore.php';

			\MigratorDatabase::instance();
			\MigratorPlugins::instance();
			\MigratorThemes::instance();
			\MigratorUploads::instance();
			\MigratorWPContent::instance();
			\MigratorWPCore::instance();
		}
	}

endif;

MigratorApi::instance();
