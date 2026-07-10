<?php
/**
 * Author box — shown beneath single posts.
 *
 * @package Mathilde
 */

$author_id   = get_the_author_meta( 'ID' );
$author_name = get_the_author();
$author_bio  = get_the_author_meta( 'description' );
$author_url  = get_author_posts_url( $author_id );
?>
<div class="author-box reveal">
	<div class="author-box__avatar"><?php echo get_avatar( $author_id, 168 ); ?></div>
	<div class="author-box__body">
		<span class="author-box__eyebrow"><?php esc_html_e( 'Written by', 'mathilde' ); ?></span>
		<h3 class="author-box__name"><a href="<?php echo esc_url( $author_url ); ?>"><?php echo esc_html( $author_name ); ?></a></h3>
		<?php if ( $author_bio ) : ?>
			<p class="author-box__bio"><?php echo esc_html( $author_bio ); ?></p>
		<?php endif; ?>
		<?php
		// Author social links from profile contact fields.
		$socials = array(
			'instagram' => get_the_author_meta( 'instagram' ),
			'pinterest' => get_the_author_meta( 'pinterest' ),
			'twitter'   => get_the_author_meta( 'twitter' ),
		);
		$socials = array_filter( $socials );
		if ( $socials ) {
			echo '<div class="m-social">';
			foreach ( $socials as $icon => $url ) {
				printf(
					'<a href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>',
					esc_url( $url ),
					esc_attr( ucfirst( $icon ) ),
					mathilde_icon( $icon, 18 )
				);
			}
			printf( '<a href="mailto:%s" aria-label="%s">%s</a>', esc_attr( get_the_author_meta( 'user_email' ) ), esc_attr__( 'Email', 'mathilde' ), mathilde_icon( 'mail', 18 ) );
			echo '</div>';
		}
		?>
	</div>
</div>
