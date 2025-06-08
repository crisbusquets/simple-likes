<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_slb_like_post', 'slb_handle_like_request' );
add_action( 'wp_ajax_nopriv_slb_like_post', 'slb_handle_like_request' );

function slb_handle_like_request() {
    check_ajax_referer( 'slb_nonce', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
        wp_send_json_error( [ 'message' => 'Invalid post ID' ] );
    }

    $data  = get_post_meta( $post_id, '_simple_like_data', true );
    $count = isset( $data['count'] ) ? (int) $data['count'] : 0;
    $users = isset( $data['users'] ) ? (array) $data['users'] : [];

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        $anon_id = $_COOKIE['slb_anon_id'] ?? '';
        if ( ! $anon_id ) {
            wp_send_json_error( [ 'message' => 'Missing anonymous ID' ] );
        }
        $user_id = 'anon_' . sanitize_text_field( $anon_id );
    }

    if ( in_array( $user_id, $users, true ) ) {
        wp_send_json_error( [ 'message' => 'Already liked' ] );
    }

    $users[] = $user_id;
    $count++;

    update_post_meta( $post_id, '_simple_like_data', [
        'count' => $count,
        'users' => $users,
    ]);

    wp_send_json_success( [
        'count' => $count,
        'user_id' => $user_id,
    ] );
}