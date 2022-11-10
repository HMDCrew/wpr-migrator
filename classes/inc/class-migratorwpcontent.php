<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MigratorWPContent' ) ) :

	class MigratorWPContent {

		private static $instance;

		private $key_domine;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MigratorWPContent ) ) {
				self::$instance = new MigratorWPContent;
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
				'/wpr-compress-wpcontent',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_compress_wp_content_callback' ),
				)
			);

			$server->register_route(
				'rest-api-migrator',
				'/wpr-remove-bk-wpcontent',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_remove_backups_wpcontent_callback' ),
				)
			);
		}


		/**
		 * It compresses the wp-content folder into a zip file
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_compress_wp_content_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$key_site = ( ! empty( $params['key_site'] ) ? preg_replace( '/[^0-9a-zA-Z\-]/i', '', $params['key_site'] ) : '' );

			if ( $key_site === $this->key_domine ) {

				if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'wp-content.zip' ) ) {
					wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'wp-content.zip' );
				}

				$zip = new ZipArchive();

				if ( $zip->open( WPR_MIGRATOR_PLUGIN_BACKUP . 'wp-content.zip', ZipArchive::CREATE ) ) {

					$upload_dir = wp_upload_dir();

					$files_unziped = array();
					$files_ziped   = array();

					$files = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator( WP_CONTENT_DIR )
					);

					foreach ( $files as $file => $key ) {
						if ( ! in_array( $key->getFilename(), array( '.', '..' ), true ) && ! keywords_in_string(
							$file,
							array(
								WP_PLUGIN_DIR,
								get_theme_root(),
								$upload_dir['basedir'],
							)
						) ) {

							$zip_file = str_replace( array( WP_CONTENT_DIR . '/', WP_CONTENT_DIR ), '', $file );
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
								'message'  => 'partial compression wp-content',
								'url'      => WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP . 'wp-content.zip',
								'unzipped' => $files_unziped,
								'local'    => true,
							)
						);
					} elseif ( empty( $files_unziped ) && ! empty( $files_ziped ) ) {
						wp_send_json(
							array(
								'status'  => 'success',
								'message' => 'wp-content compressed',
								'url'     => WPR_MIGRATOR_PLUGIN_DIR_URL_BACKUP . 'wp-content.zip',
								'local'   => true,
							)
						);
					}
				}

				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Error wp-content compresion',
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
		 * It deletes the wp-content.zip file from the backup folder
		 */
		public function wpr_remove_backups_wpcontent_callback() {

			if ( file_exists( WPR_MIGRATOR_PLUGIN_BACKUP . 'wp-content.zip' ) ) {
				wp_delete_file( WPR_MIGRATOR_PLUGIN_BACKUP . 'wp-content.zip' );
			}
		}
	}

endif;

MigratorWPContent::instance();
