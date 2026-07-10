<?php
/**
 * Post meta boxes — editorial extras (key takeaways, TOC toggle, review).
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the "Article Extras" meta box on posts.
 */
function mathilde_add_meta_boxes() {
	add_meta_box(
		'mathilde_article_extras',
		__( 'Mathilde — Article Extras', 'mathilde' ),
		'mathilde_render_meta_box',
		'post',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'mathilde_add_meta_boxes' );

/**
 * Render the meta box fields.
 *
 * @param WP_Post $post Current post.
 */
function mathilde_render_meta_box( $post ) {
	wp_nonce_field( 'mathilde_meta', 'mathilde_meta_nonce' );

	$takeaways = get_post_meta( $post->ID, '_mathilde_takeaways', true );
	$hide_toc  = get_post_meta( $post->ID, '_mathilde_hide_toc', true );
	$score     = get_post_meta( $post->ID, '_mathilde_review_score', true );
	$verdict   = get_post_meta( $post->ID, '_mathilde_review_verdict', true );
	$pros      = get_post_meta( $post->ID, '_mathilde_review_pros', true );
	$cons      = get_post_meta( $post->ID, '_mathilde_review_cons', true );
	$rec_ebook = (int) get_post_meta( $post->ID, '_mathilde_recommended_ebook', true );
	?>
	<style>
		.mathilde-mb p { margin: 0 0 6px; font-weight: 600; }
		.mathilde-mb textarea, .mathilde-mb input[type="text"], .mathilde-mb input[type="number"] { width: 100%; }
		.mathilde-mb .field { margin-bottom: 18px; }
		.mathilde-mb .desc { font-weight: 400; color: #777; font-size: 12px; }
	</style>
	<div class="mathilde-mb">
		<div class="field">
			<p><label for="mathilde_takeaways"><?php esc_html_e( 'Key Takeaways', 'mathilde' ); ?></label></p>
			<span class="desc"><?php esc_html_e( 'One per line. Shown in the pink "Key Takeaways" box near the top of the article. Leave blank to hide.', 'mathilde' ); ?></span>
			<textarea id="mathilde_takeaways" name="mathilde_takeaways" rows="4"><?php echo esc_textarea( $takeaways ); ?></textarea>
		</div>

		<div class="field">
			<label><input type="checkbox" name="mathilde_hide_toc" value="1" <?php checked( $hide_toc, '1' ); ?>> <?php esc_html_e( 'Hide the Table of Contents on this article', 'mathilde' ); ?></label>
		</div>

		<hr>
		<p><strong><?php esc_html_e( 'Review (optional)', 'mathilde' ); ?></strong> <span class="desc"><?php esc_html_e( 'Fill in to display a review box + Review schema.', 'mathilde' ); ?></span></p>
		<div class="field">
			<p><label for="mathilde_review_score"><?php esc_html_e( 'Rating (0–5)', 'mathilde' ); ?></label></p>
			<input type="number" id="mathilde_review_score" name="mathilde_review_score" min="0" max="5" step="0.1" value="<?php echo esc_attr( $score ); ?>">
		</div>
		<div class="field">
			<p><label for="mathilde_review_verdict"><?php esc_html_e( 'Verdict / summary', 'mathilde' ); ?></label></p>
			<input type="text" id="mathilde_review_verdict" name="mathilde_review_verdict" value="<?php echo esc_attr( $verdict ); ?>">
		</div>
		<div class="field">
			<p><label for="mathilde_review_pros"><?php esc_html_e( 'Pros', 'mathilde' ); ?></label></p>
			<span class="desc"><?php esc_html_e( 'One per line. Shown as a “What we love” list in the review box.', 'mathilde' ); ?></span>
			<textarea id="mathilde_review_pros" name="mathilde_review_pros" rows="3"><?php echo esc_textarea( $pros ); ?></textarea>
		</div>
		<div class="field">
			<p><label for="mathilde_review_cons"><?php esc_html_e( 'Cons', 'mathilde' ); ?></label></p>
			<span class="desc"><?php esc_html_e( 'One per line. Shown as a “Worth noting” list in the review box.', 'mathilde' ); ?></span>
			<textarea id="mathilde_review_cons" name="mathilde_review_cons" rows="3"><?php echo esc_textarea( $cons ); ?></textarea>
		</div>

		<?php if ( post_type_exists( 'mathilde_ebook' ) ) : ?>
			<hr>
			<div class="field">
				<p><label for="mathilde_recommended_ebook"><?php esc_html_e( 'Recommended guide (shown after the content)', 'mathilde' ); ?></label></p>
				<span class="desc"><?php esc_html_e( 'Leave on “Auto” to match by category, or pick a specific ebook.', 'mathilde' ); ?></span><br>
				<select id="mathilde_recommended_ebook" name="mathilde_recommended_ebook">
					<option value="0"><?php esc_html_e( '— Auto-select —', 'mathilde' ); ?></option>
					<?php
					$ebooks = get_posts( array( 'post_type' => 'mathilde_ebook', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
					foreach ( $ebooks as $ebook ) {
						printf(
							'<option value="%d" %s>%s</option>',
							(int) $ebook->ID,
							selected( $rec_ebook, $ebook->ID, false ),
							esc_html( $ebook->post_title )
						);
					}
					?>
				</select>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save the meta box fields.
 *
 * @param int $post_id Post ID.
 */
function mathilde_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['mathilde_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mathilde_meta_nonce'] ) ), 'mathilde_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_mathilde_takeaways', sanitize_textarea_field( wp_unslash( $_POST['mathilde_takeaways'] ?? '' ) ) );
	update_post_meta( $post_id, '_mathilde_hide_toc', isset( $_POST['mathilde_hide_toc'] ) ? '1' : '' );
	update_post_meta( $post_id, '_mathilde_review_score', sanitize_text_field( wp_unslash( $_POST['mathilde_review_score'] ?? '' ) ) );
	update_post_meta( $post_id, '_mathilde_review_verdict', sanitize_text_field( wp_unslash( $_POST['mathilde_review_verdict'] ?? '' ) ) );
	update_post_meta( $post_id, '_mathilde_review_pros', sanitize_textarea_field( wp_unslash( $_POST['mathilde_review_pros'] ?? '' ) ) );
	update_post_meta( $post_id, '_mathilde_review_cons', sanitize_textarea_field( wp_unslash( $_POST['mathilde_review_cons'] ?? '' ) ) );
	update_post_meta( $post_id, '_mathilde_recommended_ebook', (int) ( $_POST['mathilde_recommended_ebook'] ?? 0 ) );
}
add_action( 'save_post', 'mathilde_save_meta_box' );

/**
 * Add author profile contact fields used by the author box.
 *
 * @param array $fields Existing fields.
 * @return array
 */
function mathilde_author_contact_fields( $fields ) {
	$fields['instagram'] = __( 'Instagram URL', 'mathilde' );
	$fields['pinterest'] = __( 'Pinterest URL', 'mathilde' );
	$fields['twitter']   = __( 'Twitter / X URL', 'mathilde' );
	return $fields;
}
add_filter( 'user_contactmethods', 'mathilde_author_contact_fields' );
