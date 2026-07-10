<?php
/**
 * Ebooks — sell digital guides/PDFs with PayPal + secure downloads.
 *
 * This file: the `mathilde_ebook` product post type, the `mathilde_order`
 * record post type, a protected upload folder for the files, the product
 * meta box (price, file, subtitle, pages), helpers, admin columns and the
 * shop shortcodes. PayPal + downloads live in ebooks-paypal.php.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 *  Post types
 * ========================================================================= */

/**
 * Register the Ebook product + Order record post types.
 */
function mathilde_ebooks_register_cpt() {

	register_post_type(
		'mathilde_ebook',
		array(
			'labels'       => array(
				'name'               => __( 'Ebooks', 'mathilde' ),
				'singular_name'      => __( 'Ebook', 'mathilde' ),
				'add_new'            => __( 'Add Ebook', 'mathilde' ),
				'add_new_item'       => __( 'Add New Ebook', 'mathilde' ),
				'edit_item'          => __( 'Edit Ebook', 'mathilde' ),
				'new_item'           => __( 'New Ebook', 'mathilde' ),
				'view_item'          => __( 'View Ebook', 'mathilde' ),
				'search_items'       => __( 'Search Ebooks', 'mathilde' ),
				'not_found'          => __( 'No ebooks found', 'mathilde' ),
				'all_items'          => __( 'All Ebooks', 'mathilde' ),
				'menu_name'          => __( 'Ebooks', 'mathilde' ),
			),
			'public'       => true,
			'has_archive'  => 'ebooks',
			'rewrite'      => array( 'slug' => 'ebooks', 'with_front' => false ),
			'menu_icon'    => 'dashicons-book-alt',
			'menu_position'=> 26,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'show_in_rest' => false, // Classic editor so our file-upload meta box works.
		)
	);

	register_post_type(
		'mathilde_order',
		array(
			'labels'       => array(
				'name'          => __( 'Ebook Orders', 'mathilde' ),
				'singular_name' => __( 'Order', 'mathilde' ),
				'menu_name'     => __( 'Orders', 'mathilde' ),
				'all_items'     => __( 'Orders', 'mathilde' ),
				'edit_item'     => __( 'Order', 'mathilde' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'edit.php?post_type=mathilde_ebook',
			'capability_type' => 'post',
			'capabilities' => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap' => true,
			'supports'     => array( 'title' ),
			'show_in_rest' => false,
		)
	);
}
add_action( 'init', 'mathilde_ebooks_register_cpt' );

/**
 * Use the classic editor for ebooks (needed for the file-upload meta box).
 *
 * @param bool   $use       Whether to use the block editor.
 * @param string $post_type Post type.
 * @return bool
 */
function mathilde_ebooks_classic_editor( $use, $post_type ) {
	return ( 'mathilde_ebook' === $post_type ) ? false : $use;
}
add_filter( 'use_block_editor_for_post_type', 'mathilde_ebooks_classic_editor', 10, 2 );

/* =========================================================================
 *  Protected file storage
 * ========================================================================= */

/**
 * Absolute path to the protected ebook files directory (created on demand).
 *
 * @return string Trailing-slashed path.
 */
function mathilde_ebooks_dir() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'mathilde-ebooks/';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		// Block direct web access to the raw files.
		@file_put_contents( $dir . '.htaccess', "Require all denied\nDeny from all\n" );
		@file_put_contents( $dir . 'index.php', "<?php // Silence is golden.\n" );
	}
	return $dir;
}

/* =========================================================================
 *  Meta box
 * ========================================================================= */

/**
 * Ensure the edit form can upload files.
 */
function mathilde_ebooks_form_enctype() {
	global $post;
	if ( $post && 'mathilde_ebook' === $post->post_type ) {
		echo ' enctype="multipart/form-data"';
	}
}
add_action( 'post_edit_form_tag', 'mathilde_ebooks_form_enctype' );

/**
 * Add the ebook details meta box.
 */
function mathilde_ebooks_meta_box() {
	add_meta_box( 'mathilde_ebook_details', __( 'Ebook Details', 'mathilde' ), 'mathilde_ebooks_render_meta_box', 'mathilde_ebook', 'side', 'high' );
	add_meta_box( 'mathilde_order_details', __( 'Order Details', 'mathilde' ), 'mathilde_order_render_meta_box', 'mathilde_order', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'mathilde_ebooks_meta_box' );

/**
 * Render the ebook details fields.
 *
 * @param WP_Post $post Post.
 */
function mathilde_ebooks_render_meta_box( $post ) {
	wp_nonce_field( 'mathilde_ebook_meta', 'mathilde_ebook_nonce' );
	$price    = get_post_meta( $post->ID, '_mathilde_ebook_price', true );
	$subtitle = get_post_meta( $post->ID, '_mathilde_ebook_subtitle', true );
	$pages    = get_post_meta( $post->ID, '_mathilde_ebook_pages', true );
	$file     = get_post_meta( $post->ID, '_mathilde_ebook_file', true );
	$s        = mathilde_membership_settings();
	?>
	<p><label><strong><?php esc_html_e( 'Price', 'mathilde' ); ?></strong> (<?php echo esc_html( $s['currency'] ); ?>)<br>
		<input type="text" name="mathilde_ebook_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%" placeholder="19.00"></label></p>
	<p><label><strong><?php esc_html_e( 'Subtitle', 'mathilde' ); ?></strong><br>
		<input type="text" name="mathilde_ebook_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" style="width:100%"></label></p>
	<p><label><strong><?php esc_html_e( 'Pages', 'mathilde' ); ?></strong><br>
		<input type="number" name="mathilde_ebook_pages" value="<?php echo esc_attr( $pages ); ?>" style="width:100%"></label></p>
	<p><strong><?php esc_html_e( 'Downloadable file (PDF/EPUB/ZIP)', 'mathilde' ); ?></strong><br>
		<?php if ( $file ) : ?>
			<span class="description"><?php mathilde_the_icon( 'check', 14 ); ?> <?php echo esc_html( $file ); ?></span><br>
		<?php endif; ?>
		<input type="file" name="mathilde_ebook_file" accept=".pdf,.epub,.zip"></p>
	<p class="description"><?php esc_html_e( 'Stored in a protected folder; delivered only via secure tokenised links after purchase.', 'mathilde' ); ?></p>
	<p class="description"><?php esc_html_e( 'Set the cover via the Featured Image.', 'mathilde' ); ?></p>
	<?php
}

/**
 * Render order details (read-only) for admins.
 *
 * @param WP_Post $post Order post.
 */
function mathilde_order_render_meta_box( $post ) {
	$fields = array(
		'ebook'     => get_the_title( (int) get_post_meta( $post->ID, '_ebook_id', true ) ),
		'email'     => get_post_meta( $post->ID, '_buyer_email', true ),
		'name'      => get_post_meta( $post->ID, '_buyer_name', true ),
		'amount'    => get_post_meta( $post->ID, '_amount', true ),
		'txn'       => get_post_meta( $post->ID, '_txn_id', true ),
		'downloads' => (int) get_post_meta( $post->ID, '_downloads', true ) . ' / ' . (int) get_post_meta( $post->ID, '_max_downloads', true ),
		'expires'   => ( $e = (int) get_post_meta( $post->ID, '_expires', true ) ) ? date_i18n( get_option( 'date_format' ), $e ) : '—',
	);
	echo '<table class="form-table">';
	foreach ( $fields as $k => $v ) {
		echo '<tr><th>' . esc_html( ucfirst( $k ) ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
	}
	echo '</table>';
}

/**
 * Save ebook meta + handle the uploaded file.
 *
 * @param int $post_id Post ID.
 */
function mathilde_ebooks_save( $post_id ) {
	if ( ! isset( $_POST['mathilde_ebook_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mathilde_ebook_nonce'] ) ), 'mathilde_ebook_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['mathilde_ebook_price'] ) ) {
		update_post_meta( $post_id, '_mathilde_ebook_price', number_format( (float) $_POST['mathilde_ebook_price'], 2, '.', '' ) );
	}
	update_post_meta( $post_id, '_mathilde_ebook_subtitle', sanitize_text_field( wp_unslash( $_POST['mathilde_ebook_subtitle'] ?? '' ) ) );
	update_post_meta( $post_id, '_mathilde_ebook_pages', (int) ( $_POST['mathilde_ebook_pages'] ?? 0 ) );

	// Handle file upload into the protected folder.
	if ( ! empty( $_FILES['mathilde_ebook_file']['name'] ) && empty( $_FILES['mathilde_ebook_file']['error'] ) ) {
		$allowed = array( 'pdf' => 'application/pdf', 'epub' => 'application/epub+zip', 'zip' => 'application/zip' );
		$check   = wp_check_filetype( sanitize_file_name( $_FILES['mathilde_ebook_file']['name'] ), $allowed );
		if ( $check['ext'] ) {
			$dir      = mathilde_ebooks_dir();
			$filename = wp_unique_filename( $dir, sanitize_file_name( $_FILES['mathilde_ebook_file']['name'] ) );
			$dest     = $dir . $filename;
			if ( move_uploaded_file( $_FILES['mathilde_ebook_file']['tmp_name'], $dest ) ) {
				// Remove a previously stored file.
				$old = get_post_meta( $post_id, '_mathilde_ebook_file', true );
				if ( $old && file_exists( $dir . $old ) && $old !== $filename ) {
					@unlink( $dir . $old );
				}
				update_post_meta( $post_id, '_mathilde_ebook_file', $filename );
			}
		}
	}
}
add_action( 'save_post_mathilde_ebook', 'mathilde_ebooks_save' );

/* =========================================================================
 *  Helpers
 * ========================================================================= */

/**
 * Ebook price as a normalised string, or '' if unset.
 *
 * @param int $ebook_id Ebook ID.
 * @return string
 */
function mathilde_ebook_price( $ebook_id ) {
	$p = get_post_meta( $ebook_id, '_mathilde_ebook_price', true );
	return ( '' !== $p && (float) $p > 0 ) ? number_format( (float) $p, 2, '.', '' ) : '';
}

/**
 * Whether the current user has purchased an ebook.
 *
 * @param int      $ebook_id Ebook ID.
 * @param int|null $user_id  User ID (defaults to current).
 * @return int|false Order ID or false.
 */
function mathilde_ebook_user_owns( $ebook_id, $user_id = null ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	$purchases = get_user_meta( $user_id, 'mathilde_ebook_purchases', true );
	$purchases = is_array( $purchases ) ? $purchases : array();
	return isset( $purchases[ $ebook_id ] ) ? (int) $purchases[ $ebook_id ] : false;
}

/**
 * Pick the ebook to recommend on a given post.
 *
 * Order of preference: per-post override → an ebook whose title matches the
 * post's primary category → newest published ebook.
 *
 * @param int|null $post_id Post ID (defaults to current).
 * @return int Ebook ID or 0.
 */
function mathilde_recommended_ebook( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	// 1) Explicit per-post choice.
	$override = (int) get_post_meta( $post_id, '_mathilde_recommended_ebook', true );
	if ( $override && 'mathilde_ebook' === get_post_type( $override ) && 'publish' === get_post_status( $override ) ) {
		return $override;
	}

	// 2) Try to match the post's primary category to an ebook title.
	$term = mathilde_primary_category( $post_id );
	if ( $term ) {
		$match = get_posts(
			array(
				'post_type'      => 'mathilde_ebook',
				'posts_per_page' => 1,
				's'              => $term->name,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( $match ) {
			return (int) $match[0];
		}
	}

	// 3) Newest published ebook.
	$latest = get_posts(
		array(
			'post_type'      => 'mathilde_ebook',
			'posts_per_page' => 1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	return $latest ? (int) $latest[0] : 0;
}

/* =========================================================================
 *  Admin columns
 * ========================================================================= */

/**
 * Add Price + Sales columns to the ebooks list.
 *
 * @param array $cols Columns.
 * @return array
 */
function mathilde_ebooks_columns( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['ebook_price'] = __( 'Price', 'mathilde' );
			$new['ebook_sales'] = __( 'Sales', 'mathilde' );
			$new['ebook_file']  = __( 'File', 'mathilde' );
		}
	}
	return $new;
}
add_filter( 'manage_mathilde_ebook_posts_columns', 'mathilde_ebooks_columns' );

/**
 * Populate the custom columns.
 *
 * @param string $col Column key.
 * @param int    $post_id Post ID.
 */
function mathilde_ebooks_column_content( $col, $post_id ) {
	$s = mathilde_membership_settings();
	if ( 'ebook_price' === $col ) {
		$p = mathilde_ebook_price( $post_id );
		echo $p ? esc_html( mathilde_membership_format_price( $p, $s['currency'] ) ) : '—';
	} elseif ( 'ebook_sales' === $col ) {
		$count = (int) ( new WP_Query( array( 'post_type' => 'mathilde_order', 'post_status' => 'any', 'meta_key' => '_ebook_id', 'meta_value' => $post_id, 'fields' => 'ids', 'no_found_rows' => false ) ) )->found_posts;
		echo esc_html( $count );
	} elseif ( 'ebook_file' === $col ) {
		echo get_post_meta( $post_id, '_mathilde_ebook_file', true ) ? '<span style="color:#2e7d52">' . esc_html__( 'Yes', 'mathilde' ) . '</span>' : '<span style="color:#b3261e">' . esc_html__( 'Missing', 'mathilde' ) . '</span>';
	}
}
add_action( 'manage_mathilde_ebook_posts_custom_column', 'mathilde_ebooks_column_content', 10, 2 );

/* =========================================================================
 *  Front-end: shortcodes + assets
 * ========================================================================= */

/**
 * `[mathilde_ebooks]` — a grid of ebooks for sale.
 *
 * @param array $atts Attributes: number, columns.
 * @return string
 */
function mathilde_ebooks_grid_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'number' => 12, 'columns' => 3 ), $atts, 'mathilde_ebooks' );
	$q    = new WP_Query(
		array(
			'post_type'      => 'mathilde_ebook',
			'posts_per_page' => (int) $atts['number'],
			'no_found_rows'  => true,
		)
	);
	if ( ! $q->have_posts() ) {
		return '<p class="text-soft">' . esc_html__( 'No ebooks available yet.', 'mathilde' ) . '</p>';
	}
	ob_start();
	echo '<div class="grid grid--' . (int) $atts['columns'] . ' ebook-grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/ebooks/card' );
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'mathilde_ebooks', 'mathilde_ebooks_grid_shortcode' );

/**
 * `[mathilde_my_downloads]` — the logged-in user's purchased ebooks.
 *
 * @return string
 */
function mathilde_my_downloads_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p class="text-soft">' . esc_html__( 'Please log in to view your downloads.', 'mathilde' ) . '</p>';
	}
	$purchases = get_user_meta( get_current_user_id(), 'mathilde_ebook_purchases', true );
	$purchases = is_array( $purchases ) ? $purchases : array();
	if ( empty( $purchases ) ) {
		return '<p class="text-soft">' . esc_html__( 'You have not purchased any ebooks yet.', 'mathilde' ) . '</p>';
	}
	ob_start();
	echo '<div class="my-downloads">';
	foreach ( $purchases as $ebook_id => $order_id ) {
		$url = mathilde_ebook_download_url( (int) $order_id );
		if ( ! $url ) {
			continue;
		}
		printf(
			'<div class="my-downloads__item"><span>%s</span><a class="btn btn--sm" href="%s">%s</a></div>',
			esc_html( get_the_title( (int) $ebook_id ) ),
			esc_url( $url ),
			esc_html__( 'Download', 'mathilde' )
		);
	}
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'mathilde_my_downloads', 'mathilde_my_downloads_shortcode' );

/**
 * Enqueue the ebook purchase script + PayPal SDK on ebook views.
 */
function mathilde_ebooks_enqueue() {
	if ( ! is_singular( 'mathilde_ebook' ) && ! is_post_type_archive( 'mathilde_ebook' ) ) {
		return;
	}
	$s = mathilde_membership_settings();

	if ( mathilde_membership_configured() && ! wp_script_is( 'paypal-sdk', 'enqueued' ) ) {
		$sdk = add_query_arg(
			array(
				'client-id'  => rawurlencode( $s['client_id'] ),
				'currency'   => rawurlencode( $s['currency'] ),
				'intent'     => 'capture',
				'components' => 'buttons',
			),
			'https://www.paypal.com/sdk/js'
		);
		wp_enqueue_script( 'paypal-sdk', $sdk, array(), null, true );
	}

	wp_enqueue_script( 'mathilde-ebooks', MATHILDE_URI . 'assets/js/ebooks.js', array(), MATHILDE_VERSION, true );
	wp_localize_script(
		'mathilde-ebooks',
		'MathildeEbooks',
		array(
			'restUrl'    => esc_url_raw( rest_url( 'mathilde/v1/' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'configured' => mathilde_membership_configured(),
			'loggedIn'   => is_user_logged_in(),
			'i18n'       => array(
				'fillFields'   => __( 'Please enter your name and email to receive the download.', 'mathilde' ),
				'invalidEmail' => __( 'Please enter a valid email address.', 'mathilde' ),
				'processing'   => __( 'Verifying your payment…', 'mathilde' ),
				'success'      => __( 'Payment complete! Your download is ready.', 'mathilde' ),
				'error'        => __( 'Something went wrong. If you were charged, please contact us.', 'mathilde' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'mathilde_ebooks_enqueue', 20 );
