<?php
/**
 * Empty-state content (no posts found).
 *
 * @package Mathilde
 */
?>
<div class="no-results" style="text-align:center;padding:var(--space-9) 0;">
	<span class="m-thumb-placeholder" style="width:72px;height:72px;border-radius:50%;margin:0 auto var(--space-5);background:var(--c-blush);color:var(--c-accent);">
		<?php mathilde_the_icon( 'search', 30 ); ?>
	</span>
	<h2 class="serif"><?php esc_html_e( 'Nothing found', 'mathilde' ); ?></h2>
	<p class="text-soft" style="max-width:40ch;margin:var(--space-3) auto var(--space-5);">
		<?php esc_html_e( 'We couldn’t find anything matching your request. Try a different search or explore our latest articles.', 'mathilde' ); ?>
	</p>
	<div style="max-width:420px;margin:0 auto;"><?php get_search_form(); ?></div>
</div>
