<?php
/**
 * Post card component.
 *
 * $args['style']:
 *   'default' — portrait editorial card (homepage sliders)
 *   'archive' — landscape card with excerpt (category grid)
 *   'mini'    — horizontal list row (sidebar / related)
 *   'numbered'— mini card with a leading rank number ($args['number'])
 *
 * @package Mathilde
 */

$args  = wp_parse_args( $args ?? array(), array( 'style' => 'default', 'number' => 0, 'badge' => true ) );
$style = $args['style'];

if ( 'mini' === $style || 'numbered' === $style ) : ?>
	<article <?php post_class( 'mini-card' ); ?>>
		<?php if ( 'numbered' === $style && $args['number'] ) : ?>
			<span class="mini-card__num"><?php echo esc_html( sprintf( '%02d', $args['number'] ) ); ?></span>
		<?php else : ?>
			<a class="mini-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php mathilde_post_thumbnail( 'mathilde-thumb' ); ?></a>
		<?php endif; ?>
		<div class="mini-card__body">
			<h4 class="mini-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
			<?php mathilde_post_meta( array( 'author' => false, 'reading_time' => false ) ); ?>
		</div>
	</article>
<?php else : ?>
	<article <?php post_class( 'post-card reveal' . ( 'archive' === $style ? ' post-card--archive' : '' ) ); ?>>
		<a class="post-card__media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
			<?php mathilde_post_thumbnail( 'archive' === $style ? 'mathilde-card-wide' : 'mathilde-card' ); ?>
			<?php if ( $args['badge'] ) : ?>
				<span class="post-card__badge"><?php mathilde_the_icon( 'arrow-right', 16 ); ?></span>
			<?php endif; ?>
		</a>
		<div class="post-card__body">
			<?php mathilde_category_eyebrow(); ?>
			<h3 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<?php if ( 'archive' === $style ) : ?>
				<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
				<?php mathilde_post_meta( array( 'author' => false, 'reading_time' => false ) ); ?>
			<?php else : ?>
				<?php mathilde_post_meta( array( 'author' => false, 'date' => false ) ); ?>
			<?php endif; ?>
		</div>
	</article>
<?php endif; ?>
