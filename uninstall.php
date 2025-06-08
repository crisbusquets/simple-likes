<?php
// Exit if accessed directly or called incorrectly
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete plugin settings
delete_option( 'slb_options' );

// Delete like data from all posts
$posts = get_posts([
    'post_type'      => ['post', 'page'],
    'post_status'    => 'any',
    'numberposts'    => -1,
    'fields'         => 'ids',
]);

foreach ( $posts as $post_id ) {
    delete_post_meta( $post_id, '_simple_like_data' );
}