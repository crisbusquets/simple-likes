<?php
defined( 'ABSPATH' ) || exit;

// Register and enqueue assets
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'slb-style', SLB_URL . 'css/like-button.css', [], SLB_VERSION );
    wp_enqueue_script( 'slb-script', SLB_URL . 'js/like-button.js', [ 'jquery' ], SLB_VERSION, true );
    wp_localize_script( 'slb-script', 'slb_data', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'slb_nonce' ),
    ]);
});

// Output Like button in post content (singles only if enabled)
add_filter( 'the_content', function ( $content ) {
    if ( ! is_main_query() || ! in_the_loop() ) return $content;

    $options   = get_option( 'slb_options', [] );
    $post_type = get_post_type();

    if (
        ( is_singular( 'post' ) && ! empty( $options['show_on_posts'] ) ) ||
        ( is_singular( 'page' ) && ! empty( $options['show_on_pages'] ) )
    ) {
        $content .= slb_render_like_button( get_the_ID() );
    }

    return $content;
});

// Optionally render on archive views
add_action( 'loop_end', function ( $query ) {
    if ( ! $query->is_main_query() || is_admin() ) return;

    $options = get_option( 'slb_options', [] );
    if ( empty( $options['show_on_archives'] ) ) return;

    if ( is_archive() || is_home() || is_search() ) {
        foreach ( $query->posts as $post ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is safely constructed and escaped within the function
            echo slb_render_like_button( $post->ID );
        }
    }
});

add_action( 'init', 'slb_maybe_set_anon_cookie' );

function slb_maybe_set_anon_cookie() {
	if ( is_user_logged_in() || isset( $_COOKIE['slb_anon_id'] ) ) {
		return;
	}

	$anon_id = wp_generate_uuid4();
	setcookie( 'slb_anon_id', $anon_id, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	$_COOKIE['slb_anon_id'] = $anon_id;
}

function slb_render_like_button( $post_id ) {
    $data  = get_post_meta( $post_id, '_simple_like_data', true );
    $count = isset( $data['count'] ) ? (int) $data['count'] : 0;
    $users = isset( $data['users'] ) ? (array) $data['users'] : [];

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        $cookie_val = isset( $_COOKIE['slb_anon_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['slb_anon_id'] ) ) : '';
        $user_id = 'anon_' . $cookie_val;
    }

    $disabled      = in_array( $user_id, $users, true );
    $aria_disabled = $disabled ? 'aria-disabled="true"' : '';
    $icon          = $disabled ? '💖' : '❤️';

    $options      = get_option( 'slb_options', [] );
    $label_like   = $options['label_like'] ?? __( 'Like', 'simple-like-button' );
    $label_liked  = $options['label_liked'] ?? __( 'Liked', 'simple-like-button' );
    $label        = $disabled ? $label_liked : $label_like;

    ob_start(); ?>
<div class="slb-like-wrapper">
  <div class="wp-block-button">
    <a href="#" role="button" class="slb-like-btn wp-block-button__link <?php echo $disabled ? 'liked' : ''; ?>"
      data-post-id="<?php echo esc_attr( $post_id ); ?>"
      <?php echo $aria_disabled ? esc_attr( $aria_disabled ) : ''; ?>>
      <span class="like-icon"><?php echo esc_html( $icon ); ?></span>
      <span class="like-label"><?php echo esc_html( $label ); ?></span>
      <span class="slb-like-count"><?php echo esc_html( $count ); ?></span>
    </a>
  </div>
</div>
<?php
    return ob_get_clean();
}