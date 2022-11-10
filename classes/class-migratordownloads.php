<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MigratorDownloads' ) ) :

	class MigratorDownloads {

		private static $instance;

		private $key_domine;
		private $progress_download;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MigratorDownloads ) ) {
				self::$instance = new MigratorDownloads;
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
				'/wpr-migrator-urls',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_migrator_init_callback' ),
				)
			);
		}

		/**
		 * It downloads a file from a remote server.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_migrator_init_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			header( 'Access-Control-Allow-Origin: *' );

			$dest_key = ( ! empty( $params['dest_key'] ) ? preg_replace( '/[^a-zA-Z0-9]/', '', $params['dest_key'] ) : '' );
			$url      = ( ! empty( $params['url'] ) ? preg_replace( '/[^a-zA-Z0-9\-\_\:\.\/\&\?]/', '', $params['url'] ) : '' );
			$status   = ( ! empty( $params['status'] ) ? preg_replace( '/[^a-zA-Z0-9]/', '', $params['status'] ) : '' );

			if ( $dest_key === $this->key_domine ) {

				if ( 'in_progress' !== $status ) {

					set_time_limit( 0 );

					$file_path = WPR_MIGRATOR_PLUGIN_DOWNLOAD . basename( $url );
					$fp        = fopen( $file_path, 'w+' );

					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $url );
					curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, array( $this, 'progress' ) );
					curl_setopt( $ch, CURLOPT_HEADER, 0 );
					curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
					curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
					curl_setopt( $ch, CURLOPT_FILE, $fp );

					curl_exec( $ch );

					curl_close( $ch );

					wp_send_json(
						array(
							'status'     => 'complete',
							'message'    => 'status download in complete',
							'progress'   => $this->progress_download,
							'plugin_url' => WPR_MIGRATOR_PLUGIN_DIR_URL,
							'url'        => $url,
						)
					);
				} else {
					wp_send_json(
						array(
							'status'   => 'progress',
							'message'  => 'status download in progress',
							'progress' => $this->progress_download,
							'url'      => $url,
						)
					);
				}
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Please use keys domine for request',
					)
				);
			}
		}

		/**
		 * A callback function that is called by the curl_setopt function.
		 *
		 * @param resource The cURL resource.
		 * @param download_size The total size of the file being downloaded.
		 * @param downloaded The number of bytes downloaded so far.
		 * @param upload_size The total size of the upload (in bytes).
		 * @param uploaded The number of bytes uploaded.
		 */
		public function progress( $resource, $download_size, $downloaded, $upload_size, $uploaded ) {
			if ( $download_size > 0 ) {
				$this->progress_download = $downloaded / $download_size * 100;
			}
		}
	}

endif;

MigratorDownloads::instance();
