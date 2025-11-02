<?php
/**
 * Row Actions Examples
 *
 * Practical examples of using the WP Register Row Actions library.
 * Note: No need to wrap in admin_init - the library handles hook timing automatically!
 *
 * @package ArrayPress\WP\RegisterRowActions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// POST - AJAX action with dynamic label using helpers
register_post_row_actions( 'post', [
	'toggle_featured' => [
		'label_callback' => function ( $post_id ) {
			return get_toggle_label( 'post', $post_id, '_featured', 'Unfeatured', 'Mark Featured' );
		},
		'position'       => 'after:edit',
		'ajax'           => true,
		'callback'       => function ( $post_id ) {
			// Example: Error handling
			if ( get_post_status( $post_id ) !== 'publish' ) {
				return [
					'success' => false,
					'message' => 'Only published posts can be featured'
				];
			}

			// Toggle using helper
			$new_status = toggle_meta( 'post', $post_id, '_featured' );

			return [
				'message' => $new_status ? 'Marked as featured' : 'Unmarked as featured'
			];
		}
	]
] );

// PAGE - URL callback
register_post_row_actions( 'page', [
	'duplicate' => [
		'label'        => 'Duplicate',
		'position'     => 'after:edit',
		'url_callback' => function ( $post_id ) {
			return wp_nonce_url(
				admin_url( "admin.php?action=dup&id=$post_id" ),
				"dup_$post_id"
			);
		}
	]
] );

add_action( 'admin_action_dup', function () {
	$id = absint( $_GET['id'] ?? 0 );
	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', "dup_$id" ) ) {
		wp_die( 'Nonce failed' );
	}
	$post = get_post( $id );
	wp_insert_post( [
		'post_title'   => $post->post_title . ' (Copy)',
		'post_content' => $post->post_content,
		'post_status'  => 'draft',
		'post_type'    => 'page'
	] );
	wp_redirect( admin_url( 'edit.php?post_type=page&duplicated=1' ) );
	exit;
} );

// USER - AJAX
register_user_row_actions( [
	'send_email' => [
		'label'    => 'Send Welcome Email',
		'position' => 'after:edit',
		'ajax'     => true,
		'callback' => function ( $user_id ) {
			$user = get_userdata( $user_id );
			wp_mail( $user->user_email, 'Welcome!', 'Welcome message' );

			// No reload needed - nothing visible changed
			return [
				'message' => 'Email sent to ' . $user->user_email,
				'reload'  => false
			];
		}
	]
] );

// CATEGORY - Static URL
register_taxonomy_row_actions( 'category', [
	'view' => [
		'label'        => 'View',
		'position'     => 'after:edit',
		'url_callback' => function ( $term_id ) {
			return get_term_link( $term_id );
		},
		'target'       => '_blank'
	]
] );

// TAG - URL callback (export) using helper
register_taxonomy_row_actions( 'post_tag', [
	'export' => [
		'label'        => 'Export',
		'position'     => 'after:edit',
		'url_callback' => function ( $term_id ) {
			return get_ajax_url( 'export_tag', $term_id );
		},
		'target'       => '_blank'
	]
] );

add_action( 'wp_ajax_export_tag', function () {
	$id = absint( $_GET['id'] ?? 0 );
	if ( ! wp_verify_nonce( $_GET['nonce'] ?? '', "export_$id" ) ) {
		wp_die( 'Nonce failed' );
	}
	$term = get_term( $id );
	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename="tag.csv"' );
	echo "Name,Slug,Count\n";
	echo "{$term->name},{$term->slug},{$term->count}\n";
	exit;
} );

// COMMENT - AJAX with dynamic label using helpers
register_comment_row_actions( [
	'helpful' => [
		'label_callback' => function ( $comment_id ) {
			return get_toggle_label( 'comment', $comment_id, '_helpful', '👎 Unhelpful', '👍 Helpful' );
		},
		'position'       => 'after:approve',
		'ajax'           => true,
		'callback'       => function ( $comment_id ) {
			$new_status = toggle_meta( 'comment', $comment_id, '_helpful' );

			return [
				'message' => $new_status ? 'Marked as helpful' : 'Unmarked as helpful'
			];
		}
	]
] );

// MEDIA - AJAX
register_media_row_actions( [
	'regenerate' => [
		'label'               => '🔄 Regen',
		'position'            => 'after:edit',
		'ajax'                => true,
		'callback'            => function ( $id ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$file = get_attached_file( $id );
			$meta = wp_generate_attachment_metadata( $id, $file );
			wp_update_attachment_metadata( $id, $meta );

			return [ 'message' => 'Thumbnails regenerated' ];
		},
		'permission_callback' => function ( $id ) {
			return wp_attachment_is_image( $id );
		}
	]
] );