<?php
$class   = 'notice ' . ( 'yes' == $notice['is_urgent'] ? 'notice-error' : 'notice-info' );
$message = $notice['post_content'];
?>

<div class="<?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $notice['version'] ) || 'yes' == $notice['is_dismissable'] ) : ?>
	<form method="POST">
		<?php endif; ?>

		<?php if ( $message != strip_tags( $message ) ) : ?>
			<?php echo $message; ?>
		<?php else: ?>
			<p><?php echo $message; ?></p>
		<?php endif; ?>

		<?php if ( empty( $notice['version'] ) || 'yes' == $notice['is_dismissable'] ) : ?>
		<input type="hidden" name="notice-id" value="<?php echo $notice['seqno']; ?>">
		<input type="submit" class="button" name="dismiss-notice" value="더이상 보지 않기">
	</form>
<?php endif; ?>
</div>