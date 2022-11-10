<?php

use Ifsnop\Mysqldump as IMysqldump;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MigratorDatabase' ) ) :

	class MigratorDatabase {

		private static $instance;

		private $new_domine;
		private $key_domine;
		private $dest_key_domine;

		/**
		 * A singleton pattern. It is used to create a single instance of the class.
		 *
		 * @return The instance of the class.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MigratorDatabase ) ) {
				self::$instance = new MigratorDatabase;
				self::$instance->set_up_class_variable();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * It sets up the class variables
		 */
		public function set_up_class_variable() {

			$stored_option = get_option( 'save_info_migrator' );

			$this->new_domine      = ( ! empty( $stored_option['new_domine'] ) ? $stored_option['new_domine'] : '' );
			$this->key_domine      = ( ! empty( $stored_option['key_domine'] ) ? $stored_option['key_domine'] : '' );
			$this->dest_key_domine = ( ! empty( $stored_option['dest_key_domine'] ) ? $stored_option['dest_key_domine'] : '' );
		}

		/**
		 * It adds a new action to the rest_api_init hook.
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_migrator_api_routes' ), 10 );
		}

		/**
		 * It registers a route for the REST API.
		 *
		 * @param server The server object.
		 */
		public function wpr_migrator_api_routes( $server ) {
			$server->register_route(
				'rest-api-migrator',
				'/wpr-dump-database',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_dump_database_callback' ),
				)
			);

			$server->register_route(
				'rest-api-migrator',
				'/wpr-replace-domine-database',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_replace_domine_database_callback' ),
				)
			);

			$server->register_route(
				'rest-api-migrator',
				'/wpr-compress-db',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_compress_db_callback' ),
				)
			);

			$server->register_route(
				'rest-api-migrator',
				'/wpr-remove-bk-db',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_remove_backups_db_callback' ),
				)
			);
		}

		/**
		 * It creates a backup of the database.
		 */
		public function wpr_dump_database_callback() {

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' );
			}

			$dump = new IMysqldump\Mysqldump( sprintf( 'mysql:host=%s;dbname=%s', DB_HOST, DB_NAME ), DB_USER, DB_PASSWORD );
			$dump->start( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' );

			wp_send_json(
				array(
					'status'  => ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' ) ? 'success' : 'error' ),
					'message' => ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' ) ? 'dump databse completed' : 'error dump databse' ),
					'local'   => true,
				)
			);
		}

		/**
		 * It replaces the old domain with the new domain in the database dump file
		 */
		public function wpr_replace_domine_database_callback() {

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' );
			}

			$host_dest = parse_url( $this->new_domine, PHP_URL_HOST );

			if ( ! empty( $this->new_domine ) && filter_var( $this->new_domine, FILTER_VALIDATE_URL ) && ! empty( $this->key_domine ) && ! empty( $this->dest_key_domine ) ) {

				$dump_file = file_get_contents( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' );

				file_put_contents( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql', str_replace( $_SERVER['HTTP_HOST'], $host_dest, $dump_file ) );

				wp_send_json(
					array(
						'status'  => ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) ? 'success' : 'error' ),
						'message' => ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) ? 'domine replaced' : 'error domine replaced in db' ),
						'local'   => true,
					)
				);
			}

			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Please chack information saved in backend',
					'local'   => true,
				)
			);
		}

		/**
		 * It creates a zip file of the database dump.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_compress_db_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$key_site = ( ! empty( $params['key_site'] ) ? preg_replace( '/[^0-9a-zA-Z\-]/i', '', $params['key_site'] ) : '' );

			if ( $key_site === $this->key_domine ) {

				if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'db.zip' ) ) {
					wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'db.zip' );
				}

				$zip = new ZipArchive();

				if ( $zip->open( WPR_MIGRATOR_PLUGIN_BACKUP . 'db.zip', ZipArchive::CREATE ) && file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) ) {

					if ( $zip->addFile( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql', 'dump_new_domine.sql' ) ) {

						$zip->setExternalAttributesName(
							'dump_new_domine.sql',
							ZipArchive::OPSYS_UNIX,
							fileperms( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) << 16
						);

						$zip->close();

						wp_send_json(
							array(
								'status'  => 'success',
								'message' => 'DB compressed',
								'url'     => WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP . 'db.zip',
								'local'   => true,
							)
						);
					}
				} elseif ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) ) {

					wp_send_json(
						array(
							'status'  => 'success',
							'message' => 'DB uncompressed',
							'url'     => WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP . 'dump_new_domine.sql',
							'local'   => true,
						)
					);
				}

				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Error compresion',
						'local'   => true,
					)
				);

			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Please use key domine for request',
						'local'   => true,
					)
				);
			}
		}

		/**
		 * It deletes the files that are created by the plugin
		 */
		public function wpr_remove_backups_db_callback() {

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'db.zip' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'db.zip' );
			}

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump_new_domine.sql' );
			}

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'dump.sql' );
			}
		}
	}

endif;

MigratorDatabase::instance();
