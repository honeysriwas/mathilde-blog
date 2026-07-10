<?php
/**
 * Comments template.
 *
 * @package Mathilde
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title m-section-title">
			<?php
			$count = get_comments_number();
			printf(
				/* translators: %s: comment count */
				esc_html( _n( '%s Comment', '%s Comments', $count, 'mathilde' ) ),
				esc_html( number_format_i18n( $count ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'avatar_size' => 56,
					'short_ping'  => true,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination(
			array(
				'prev_text' => mathilde_icon( 'arrow-left', 18 ),
				'next_text' => mathilde_icon( 'arrow-right', 18 ),
			)
		); ?>

	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="no-comments text-soft"><?php esc_html_e( 'Comments are closed.', 'mathilde' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_form'         => 'comment-form',
			'title_reply_before' => '<h3 class="comment-reply-title serif">',
			'title_reply_after'  => '</h3>',
			'title_reply'        => __( 'Leave a Comment', 'mathilde' ),
			'class_submit'       => 'btn',
		)
	);
	?>
</div>
