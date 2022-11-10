<?php
/**
 * Migrator Dashboard
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 */

defined( 'ABSPATH' ) || exit;

$stored_option = get_option( 'save_info_migrator' );

?>

<div class="admin-wpr-migrator wrap">
	<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST" class="migrator-dashboard">

		<input type="hidden" name="action" value="save_info_migrator">

		<h2><?php echo __( 'WPR Migrator dashboard', 'wpr-migrator' ); ?></h2>

		<div>
			<strong><label for="save_info_migrator_new_domine">Destination domine</label>: </strong>
			<input type="text" id="save_info_migrator_new_domine" name="save_info_migrator[new_domine]" value="<?php echo ( ! empty( $stored_option['new_domine'] ) ? $stored_option['new_domine'] : '' ); ?>" />
			<div class="note"><?php echo __( 'ex: https://test.com/', 'wpr-migrator' ); ?></div>
		</div>

		<div>
			<strong><label for="save_info_migrator_key_authorization">Key local domine authorization</label>: </strong>
			<input type="text" id="save_info_migrator_key_authorization" name="save_info_migrator[key_domine]" value="<?php echo ( ! empty( $stored_option['key_domine'] ) ? $stored_option['key_domine'] : '' ); ?>" />
			<div class="note"><?php echo __( 'Use this key on your destination site', 'wpr-migrator' ); ?></div>
		</div>

		<div>
			<strong><label for="save_info_migrator_dest_key_authorization">Key destination domine authorization</label>: </strong>
			<input type="text" id="save_info_migrator_dest_key_authorization" name="save_info_migrator[dest_key_domine]" value="<?php echo ( ! empty( $stored_option['dest_key_domine'] ) ? $stored_option['dest_key_domine'] : '' ); ?>" />
			<div class="note"><?php echo __( 'Use this space for paste your source domine key', 'wpr-migrator' ); ?></div>
		</div>

		<button type="submit" class="button button-primary"><?php echo __( 'Save', 'wpr-migrator' ); ?></button>

	</form>

	<?php if ( ! empty( $stored_option['new_domine'] ) && ! empty( $stored_option['dest_key_domine'] ) ) : ?>

		<div class="terminal-container">
			<div class="terminal-content"></div>
			<button type="button" class="btn button-primary btn-start-sync"><?php echo __( 'Start migration', 'wpr-migrator' ); ?></button>
		</div>

	<?php endif; ?>
</div>
