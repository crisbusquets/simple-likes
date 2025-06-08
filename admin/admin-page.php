<?php
defined( 'ABSPATH' ) || exit;

// Add a single admin menu page
add_action( 'admin_menu', function () {
    add_options_page(
        __( 'Simple Likes', 'simple-like-button' ),
        __( 'Simple Likes', 'simple-like-button' ),
        'manage_options',
        'simple-like-button',
        'slb_render_admin_page'
    );
});

// Render tabbed admin page
function slb_render_admin_page() {
    $active_tab = $_GET['tab'] ?? 'settings';

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Simple Like Button', 'simple-like-button' ) . '</h1>';

    // Tab navigation
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=simple-like-button&tab=settings" class="nav-tab ' . ( $active_tab === 'settings' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Settings', 'simple-like-button' ) . '</a>';
    echo '<a href="?page=simple-like-button&tab=stats" class="nav-tab ' . ( $active_tab === 'stats' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Like Stats', 'simple-like-button' ) . '</a>';
    echo '</nav>';

    // Tab content
    if ( $active_tab === 'settings' ) {
        slb_render_settings_tab();
    } elseif ( $active_tab === 'stats' ) {
        slb_render_stats_tab();
    }

    echo '</div>';
}

// Register settings section and fields
add_action( 'admin_init', function () {
    register_setting( 'slb_settings', 'slb_options', [
        'sanitize_callback' => function ( $input ) {
            return [
                'show_on_posts'    => ! empty( $input['show_on_posts'] ),
                'show_on_pages'    => ! empty( $input['show_on_pages'] ),
                'show_on_archives' => ! empty( $input['show_on_archives'] ),
                'label_like'       => sanitize_text_field( $input['label_like'] ?? 'Like' ),
                'label_liked'      => sanitize_text_field( $input['label_liked'] ?? 'Liked' ),
            ];
        },
    ]);

    add_settings_section( 'slb_main', '', null, 'simple-like-button' );

    add_settings_field(
        'slb_show_on_posts',
        __( 'Show on Posts', 'simple-like-button' ),
        function () {
            $options = get_option( 'slb_options' );
            echo '<input type="checkbox" name="slb_options[show_on_posts]" value="1"' . checked( $options['show_on_posts'] ?? false, true, false ) . '>';
        },
        'simple-like-button',
        'slb_main'
    );

    add_settings_field(
        'slb_show_on_pages',
        __( 'Show on Pages', 'simple-like-button' ),
        function () {
            $options = get_option( 'slb_options' );
            echo '<input type="checkbox" name="slb_options[show_on_pages]" value="1"' . checked( $options['show_on_pages'] ?? false, true, false ) . '>';
        },
        'simple-like-button',
        'slb_main'
    );

    add_settings_field(
        'slb_show_on_archives',
        __( 'Show on Archive Views', 'simple-like-button' ),
        function () {
            $options = get_option( 'slb_options' );
            echo '<input type="checkbox" name="slb_options[show_on_archives]" value="1"' . checked( $options['show_on_archives'] ?? false, true, false ) . '>';
        },
        'simple-like-button',
        'slb_main'
    );

    add_settings_field(
        'slb_label_like',
        __( 'Like label', 'simple-like-button' ),
        function () {
            $options = get_option( 'slb_options' );
            echo '<input type="text" name="slb_options[label_like]" value="' . esc_attr( $options['label_like'] ?? 'Like' ) . '" class="regular-text">';
        },
        'simple-like-button',
        'slb_main'
    );

    add_settings_field(
        'slb_label_liked',
        __( 'Liked label', 'simple-like-button' ),
        function () {
            $options = get_option( 'slb_options' );
            echo '<input type="text" name="slb_options[label_liked]" value="' . esc_attr( $options['label_liked'] ?? 'Liked' ) . '" class="regular-text">';
        },
        'simple-like-button',
        'slb_main'
    );

});

// Settings tab content
function slb_render_settings_tab() {
    ?>
<form method="post" action="options.php">
  <?php
        settings_fields( 'slb_settings' );
        do_settings_sections( 'simple-like-button' );
        submit_button();
        ?>
</form>
<?php
}

// Stats tab content (moved from dashboard-page.php)
function slb_render_stats_tab() {
    if ( isset( $_GET['slb_reset_all'] ) ) {
        slb_reset_all_likes();
        echo '<div class="updated"><p>' . esc_html__( 'All Likes have been reset.', 'simple-like-button' ) . '</p></div>';
    }

    if ( isset( $_GET['slb_reset'] ) && isset( $_GET['post_id'] ) ) {
        $post_id = (int) $_GET['post_id'];
        delete_post_meta( $post_id, '_simple_like_data' );
        echo '<div class="updated"><p>' . sprintf( esc_html__( 'Likes reset for post ID %d.', 'simple-like-button' ), $post_id ) . '</p></div>';
    }

    $query = new WP_Query([
        'post_type' => ['post', 'page'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    if ( ! empty( $query->posts ) ) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>' . esc_html__( 'Title', 'simple-like-button' ) . '</th><th>' . esc_html__( 'Likes', 'simple-like-button' ) . '</th><th>' . esc_html__( 'Actions', 'simple-like-button' ) . '</th></tr></thead><tbody>';

        foreach ( $query->posts as $post_id ) {
            $data  = get_post_meta( $post_id, '_simple_like_data', true );
            $count = isset( $data['count'] ) ? (int) $data['count'] : 0;
            if ( $count < 1 ) continue;

            echo '<tr>';
            echo '<td><a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></td>';
            echo '<td>' . esc_html( $count ) . '</td>';
            echo '<td><a href="' . esc_url( admin_url( 'options-general.php?page=simple-like-button&tab=stats&slb_reset=1&post_id=' . $post_id ) ) . '" class="button">' . esc_html__( 'Reset', 'simple-like-button' ) . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p><a href="' . esc_url( admin_url( 'options-general.php?page=simple-like-button&tab=stats&slb_reset_all=1' ) ) . '" class="button button-secondary">' . esc_html__( 'Reset All Likes', 'simple-like-button' ) . '</a></p>';
    } else {
        echo '<p>' . esc_html__( 'No Likes to display yet.', 'simple-like-button' ) . '</p>';
    }
}

// Reset helper
function slb_reset_all_likes() {
    $posts = get_posts([
        'post_type' => ['post', 'page'],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    foreach ( $posts as $post_id ) {
        delete_post_meta( $post_id, '_simple_like_data' );
    }
}