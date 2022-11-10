<?php

include_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpChmod\Scanner;

setcookie( 'NO_CACHE', '1', time() + 99999999, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_HOST'] );

/**
 * Class indipendent of WordPress core
 */
class SystemOvveride {

	private static $instance;

	/**
	 * It creates a singleton instance of the class.
	 *
	 * @return The instance of the class.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SystemOvveride ) ) {
			self::$instance = new SystemOvveride;
			self::$instance->setup();
		}

		return self::$instance;
	}

	public function setup() {

		header( 'Connection: keep-alive' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Authorization' );
		header( 'Access-Control-Allow-Methods: *' );
		header( 'Content-Type: application/json' );

		require_once __DIR__ . '/helpers.php';

		$plugins_path    = dirname( __DIR__ );
		$wp_content_path = dirname( $plugins_path );
		$themes_path     = $wp_content_path . '/themes';
		$uploads_path    = $wp_content_path . '/uploads';
		$core_path       = dirname( $wp_content_path );

		$this->replace_plugins( $plugins_path );
		$this->replace_themes( $themes_path );
		$this->replace_uploads( $uploads_path );
		$this->replace_wp_content( $wp_content_path );
		$this->replace_core( $core_path );

		$this->replace_db( $core_path );
	}

	/**
	 * Recursively iterate over a directory, deleting all files and directories within it.
	 *
	 * @param string dir The directory to remove.
	 */
	public function rmdir_recursive( string $dir ) {

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getPathname() );
			} else {
				unlink( $file->getPathname() );
			}
		}

		rmdir( $dir );
	}

	/**
	 * It recursively deletes all files and folders in a directory, except those that contain any of the
	 * keywords in the `` array
	 *
	 * @param string path The path to the directory you want to clean.
	 * @param array exclude_keywords An array of keywords that will be used to exclude files and folders
	 * from being deleted.
	 */
	public function clean_path( string $path, array $exclude_keywords = array() ) {

		if ( is_dir( $path ) ) {

			$dirs_files = array_diff( scandir( $path ), array( '..', '.' ) );

			foreach ( $dirs_files as $row ) {

				$row_path = $path . '/' . $row;

				if ( ! keywords_in_string( $row_path, $exclude_keywords ) ) {

					if ( ! is_dir( $row_path ) ) {
						unlink( $row_path );
					} else {
						$this->rmdir_recursive( $row_path );
					}
				}
			}
		}
	}

	/**
	 * It unzips a file to a destination path.
	 *
	 * @param string destination_path The path to the directory where you want to extract the zip file.
	 * @param string zip_path The path to the zip file.
	 */
	public function unzip_to_path( string $destination_path, string $zip_path ) {

		$zip = new ZipArchive();

		if ( true === $zip->open( __DIR__ . $zip_path ) ) {
			$zip->extractTo( $destination_path );
			$zip->close();
		} else {
			echo 'failed extraction';
			echo $zip_path;
		}
	}

	/**
	 * It replaces the plugins in the given path with the plugins in the zip file
	 *
	 * @param string path The path to the WordPress installation.
	 */
	public function replace_plugins( string $path ) {

		$this->clean_path( $path, array( 'wpr-migrator' ) );
		$this->unzip_to_path( $path, '/downloads/plugins.zip' );

		$result = ( new Scanner() )
		->setDefaultFileMode( 0644 )
		->setDefaultDirectoryMode( 0755 )
		->setExcludedFileModes( array( 0400, 0444, 0640 ) )
		->setExcludedDirectoryModes( array( 0750 ) )
		->scan( array( $path ) )
		->dryRun();
	}

	/**
	 * > It replaces the themes in the `/wp-content/themes` directory with the themes in the
	 * `/downloads/themes.zip` file
	 *
	 * @param string path The path to the themes directory.
	 */
	public function replace_themes( string $path ) {

		$this->clean_path( $path );
		$this->unzip_to_path( $path, '/downloads/themes.zip' );

		$result = ( new Scanner() )
		->setDefaultFileMode( 0644 )
		->setDefaultDirectoryMode( 0755 )
		->setExcludedFileModes( array( 0400, 0444, 0640 ) )
		->setExcludedDirectoryModes( array( 0750 ) )
		->scan( array( $path ) )
		->dryRun();
	}

	/**
	 * It unzips the uploads.zip file to the path specified, and then scans the path for any files or
	 * directories that have incorrect permissions
	 *
	 * @param string path The path to the uploads directory.
	 */
	public function replace_uploads( string $path ) {

		$this->clean_path( $path );
		$this->unzip_to_path( $path, '/downloads/uploads.zip' );

		$result = ( new Scanner() )
			->setDefaultFileMode( 0644 )
			->setDefaultDirectoryMode( 0755 )
			->setExcludedFileModes( array( 0400, 0444, 0640 ) )
			->setExcludedDirectoryModes( array( 0750 ) )
			->scan( array( $path ) )
			->dryRun();
	}

	/**
	 * It replaces the wp-content directory with a fresh copy from the WordPress download
	 *
	 * @param string path The path to the WordPress installation.
	 */
	public function replace_wp_content( string $path ) {

		$this->clean_path( $path, array( '/plugins', '/themes', '/uploads', '/wp-content/index.php' ) );
		$this->unzip_to_path( $path, '/downloads/wp-content.zip' );

		$result = ( new Scanner() )
			->setDefaultFileMode( 0644 )
			->setDefaultDirectoryMode( 0755 )
			->setExcludedFileModes( array( 0400, 0444, 0640 ) )
			->setExcludedDirectoryModes( array( 0750 ) )
			->scan( array( $path ) )
			->dryRun();
	}

	/**
	 * It takes a string, an array of keys, and a reference to an array. It then finds the first key in
	 * the string, and if it's in the array of keys, it finds the value for that key and adds it to the
	 * reference array. If there are more keys in the string, it calls itself again with the remaining
	 * string
	 *
	 * @param string line The line of code to parse.
	 * @param array keys an array of keys to look for
	 * @param array callback The callback function to be called.
	 *
	 * @return array the callback array.
	 */
	public function line_key_values( string $line, array $keys, array &$callback ): array {

		$start_key_pos      = ( strpos( $line, "'" ) ? strpos( $line, "'" ) : strpos( $line, '"' ) ) + 1;
		$start_key_location = substr( $line, $start_key_pos );
		$end_key_pos        = ( strpos( $start_key_location, "'" ) ? strpos( $start_key_location, "'" ) : strpos( $start_key_location, '"' ) );
		$key                = substr( $start_key_location, 0, $end_key_pos );

		$tmp = substr( $start_key_location, $end_key_pos + 1 );

		if ( in_array( $key, $keys, true ) ) {

			$start_val_pos      = strpos( $tmp, "'" ) + 1;
			$start_val_location = substr( $tmp, $start_val_pos );
			$end_val_pos        = strpos( $start_val_location, "'" );
			$val                = substr( $start_val_location, 0, $end_val_pos );

			$callback[ $key ] = $val;

		} elseif ( strpos( $tmp, "'" ) || strpos( $tmp, '"' ) ) {
			$this->line_key_values( $tmp, $keys, $callback );
		}

		return $callback;
	}

	/**
	 * It reads the `wp-config.php` file and returns an array of the database credentials
	 *
	 * @param string path The path to the wp-config.php file.
	 * @param string mode r, r+, w, w+, a, a+, x, x+
	 * @param array db_const This is an array that will be populated with the database credentials.
	 *
	 * @return array An array of the database credentials.
	 */
	public function wp_config_db_credentials( string $path, string $mode = 'r', array &$db_const = array() ): array {

		$handle = fopen( $path . '/wp-config.php', $mode );

		if ( $handle ) {

			$keys = array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE' );

			while ( ! feof( $handle ) ) {

				$line = fgets( $handle );

				if ( keywords_in_string( $line, $keys ) ) {
					$this->line_key_values( $line, $keys, $db_const );
				}
			}

			fclose( $handle );
		}

		return $db_const;
	}

	/**
	 * It replaces the core files of a WordPress installation with the latest version
	 *
	 * @param path The path to the WordPress installation.
	 */
	public function replace_core( $path ) {

		if ( ! is_dir( $path . '/wpr_wp_core' ) ) {
			mkdir( $path . '/wpr_wp_core', 0774 );
		}

		$this->unzip_to_path( $path . '/wpr_wp_core', '/downloads/wp-core.zip' );

		$result = ( new Scanner() )
			->setDefaultFileMode( 0644 )
			->setDefaultDirectoryMode( 0755 )
			->setExcludedFileModes( array( 0400, 0444, 0640 ) )
			->setExcludedDirectoryModes( array( 0750 ) )
			->scan( array( $path . '/wpr_wp_core' ) )
			->dryRun();

		if ( file_exists( $path . '/wp-config.php' ) ) {

			$site_wp_conf_cedentials = $this->wp_config_db_credentials( $path );
			$new_wp_conf_cedentials  = $this->wp_config_db_credentials( $path . '/wpr_wp_core' );

			$new_wp_conf = file_get_contents( $path . '/wpr_wp_core/wp-config.php' );

			foreach ( $new_wp_conf_cedentials as $key => $value ) {
				$new_wp_conf = str_replace( $value, $site_wp_conf_cedentials[ $key ], $new_wp_conf );
			}

			file_put_contents( $path . '/wpr_wp_core/wp-config.php', $new_wp_conf );
		}

		$this->clean_path( $path, array( 'wp-content', 'wpr_wp_core' ) );

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $path . '/wpr_wp_core', FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {

			$new_file_path = str_replace( 'wpr_wp_core/', '', $file->getPathname() );
			$path_file     = str_replace( $file->getFilename(), '', $new_file_path );

			if ( ! is_dir( $path_file ) ) {
				mkdir( $path_file, 0755, true );
			}

			if ( file_exists( $new_file_path ) && ! is_dir( $new_file_path ) ) {
				unlink( $new_file_path );
			}

			( ! is_dir( $new_file_path ) && rename( $file->getPathname(), $new_file_path ) );
		}

		$this->clean_path( $path . '/wpr_wp_core' );
		rmdir( $path . '/wpr_wp_core' );
	}

	/**
	 * It replaces the database with the one in the zip file
	 *
	 * @param core_path The path to the WordPress core files.
	 */
	public function replace_db( $core_path ) {

		require_once $core_path . '/wp-config.php';

		$downloads = __DIR__ . '/downloads/';

		$this->unzip_to_path( $downloads, '/downloads/db.zip' );

		$sql_file = reset( glob( $downloads . '*.sql' ) );
		$sql_cont = $this->strip_sqlcomment( file_get_contents( $sql_file ) );

		try {

			$pdo = new PDO( sprintf( 'mysql:host=%s;dbname=%s', DB_HOST, DB_NAME ), DB_USER, DB_PASSWORD ); //@phpcs:ignore
			$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); //@phpcs:ignore

			foreach ( $pdo->query( 'SHOW TABLES' ) as $row ) {
				if ( str_contains( $sql_cont, $row[0] ) ) {
					$pdo->query( 'DROP TABLE IF EXISTS ' . $row[0] );
				}
			}

			$pdo->query( $sql_cont );
			$pdo = null;

			echo json_encode(
				array(
					'status'  => 'success',
					'message' => 'database replaced successful',
				)
			);

		} catch ( PDOException $e ) {

			echo json_encode(
				array(
					'status'  => 'error',
					'message' => $e,
				)
			);

			exit();
		}
	}

	/**
	 * It removes comments from SQL
	 *
	 * @param sql The SQL query to be executed.
	 */
	public function strip_sqlcomment( $sql = '' ) {

		$new_sql = '';
		foreach ( explode( "\n", $sql ) as $row ) {

			$first_chrs = substr( trim( $row ), 0, 2 );

			if ( ! str_contains( $first_chrs, '--' ) && ! str_contains( $first_chrs, '/*' ) && ! str_contains( $first_chrs, '#' ) && "\n" !== trim( $row ) ) {
				$new_sql .= $row . "\n";
			}
		}

		return preg_replace(
			'/\R+/',
			"\n",
			$new_sql
		);
	}
}

SystemOvveride::instance();
