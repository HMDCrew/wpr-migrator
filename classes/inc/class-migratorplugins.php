<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MigratorPlugins' ) ) :

	class MigratorPlugins {

		private static $instance;

		private $key_domine;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MigratorPlugins ) ) {
				self::$instance = new MigratorPlugins;
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

			$this->key_domine = ( ! empty( $stored_option['key_domine'] ) ? $stored_option['key_domine'] : '' );
		}

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
				'/wpr-compress-plugins',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_compress_plugins_callback' ),
				)
			);

			$server->register_route(
				'rest-api-migrator',
				'/wpr-remove-bk-plugins',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_remove_backups_plugins_callback' ),
				)
			);
		}


		/**
		 * It compresses the plugins folder into a zip file
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_compress_plugins_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$key_site = ( ! empty( $params['key_site'] ) ? preg_replace( '/[^0-9a-zA-Z\-]/i', '', $params['key_site'] ) : '' );

			if ( $key_site === $this->key_domine ) {

				if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'plugins.zip' ) ) {
					wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'plugins.zip' );
				}

				$zip = new ZipArchive();

				if ( $zip->open( WPR_MIGRATOR_PLUGIN_BACKUP . 'plugins.zip', ZipArchive::CREATE ) ) {

					$files_unziped = array();
					$files_ziped   = array();

					$files = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator( WP_PLUGIN_DIR )
					);

					foreach ( $files as $file => $key ) {

						if ( ! in_array( $key->getFilename(), array( '.', '..' ), true ) && ! keywords_in_string( $file, array( 'wpr-migrator' ) ) ) {

							$zip_file = str_replace( array( WP_PLUGIN_DIR . '/', WP_PLUGIN_DIR ), '', $file );
							if ( ! $zip->addFile( $file, $zip_file ) ) {
								$files_unziped[] = $file;
							} else {

								$files_ziped[] = $file;

								$zip->setExternalAttributesName(
									$zip_file,
									ZipArchive::OPSYS_UNIX,
									fileperms( $file ) << 16
								);
							}
						}
					}

					$zip->close();

					if ( ! empty( $files_unziped ) && ! empty( $files_ziped ) ) {
						wp_send_json(
							array(
								'status'   => 'success',
								'message'  => 'partial compression plugins',
								'url'      => WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP . 'plugins.zip',
								'unzipped' => $files_unziped,
								'local'    => true,
							)
						);
					} elseif ( empty( $files_unziped ) && ! empty( $files_ziped ) ) {
						wp_send_json(
							array(
								'status'  => 'success',
								'message' => 'plugins compressed',
								'url'     => WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP . 'plugins.zip',
								'local'   => true,
							)
						);
					}
				}

				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Error plugins compresion',
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
		 * It deletes the plugins.zip file from the plugin backup directory
		 */
		public function wpr_remove_backups_plugins_callback() {

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'plugins.zip' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'plugins.zip' );
			}
		}
	}

endif;

MigratorPlugins::instance();
