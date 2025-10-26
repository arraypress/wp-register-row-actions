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

/**
 * Example 1: Simple URL Action
 *
 * Add a link to preview posts on the frontend.
 */
register_post_row_actions( 'post', [
	'frontend_preview' => [
		'label'    => __( 'Frontend Preview', 'textdomain' ),
		'url'      => home_url( '?p=' ),
		'position' => 'after:view',
		'target'   => '_blank',
		'icon'     => 'visibility',
	]
] );

/**
 * Example 2: Dynamic URL with Callback
 *
 * Duplicate a post with proper nonce security.
 */
register_post_row_actions( 'post', [
	'duplicate' => [
		'label'        => __( 'Duplicate', 'textdomain' ),
		'url_callback' => function ( $post_id ) {
			return wp_nonce_url(
				add_query_arg( [
					'action'  => 'duplicate_post',
					'post_id' => $post_id,
				], admin_url( 'admin.php' ) ),
				"duplicate_post_$post_id"
			);
		},
		'position'     => 'after:edit',
		'capability'   => 'edit_posts',
		'icon'         => 'admin-page',
	]
] );

// Handle the duplicate action
add_action( 'admin_action_duplicate_post', function () {
	$post_id = absint( $_GET['post_id'] ?? 0 );

	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', "duplicate_post_$post_id" ) ) {
		wp_die( __( 'Security check failed', 'textdomain' ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( __( 'Permission denied', 'textdomain' ) );
	}

	// Duplicate the post
	$post     = get_post( $post_id );
	$new_post = [
		'post_title'   => $post->post_title . ' (Copy)',
		'post_content' => $post->post_content,
		'post_status'  => 'draft',
		'post_type'    => $post->post_type,
		'post_author'  => get_current_user_id(),
	];

	$new_post_id = wp_insert_post( $new_post );

	// Redirect back with success message
	wp_redirect( add_query_arg( [
		'duplicated' => 1,
		'post'       => $new_post_id,
	], admin_url( 'edit.php' ) ) );
	exit;
} );

/**
 * Example 3: AJAX Action - Mark as Featured
 *
 * Toggle featured status with AJAX.
 */
register_post_row_actions( 'post', [
	'toggle_featured' => [
		'label'    => __( 'Toggle Featured', 'textdomain' ),
		'position' => 'after:edit',
		'ajax'     => true,
		'callback' => function ( $post_id ) {
			$is_featured = get_post_meta( $post_id, 'featured', true );
			$new_status  = ! $is_featured;

			update_post_meta( $post_id, 'featured', $new_status );

			return [
				'message' => $new_status
					? __( 'Post marked as featured', 'textdomain' )
					: __( 'Post unmarked as featured', 'textdomain' ),
				'reload'  => true,
			];
		},
		'icon'     => 'star-filled',
	]
] );

/**
 * Example 4: AJAX Action with Confirmation
 *
 * Send notification to subscribers with confirmation.
 */
register_post_row_actions( 'post', [
	'send_notification' => [
		'label'    => __( 'Notify Subscribers', 'textdomain' ),
		'position' => 'after:edit',
		'ajax'     => true,
		'callback' => function ( $post_id ) {
			// Get post
			$post = get_post( $post_id );

			// Get subscribers (example)
			$subscribers = get_users( [
				'meta_key'   => 'subscribed_to_notifications',
				'meta_value' => '1',
			] );

			$sent = 0;
			foreach ( $subscribers as $subscriber ) {
				$result = wp_mail(
					$subscriber->user_email,
					sprintf( __( 'New post: %s', 'textdomain' ), $post->post_title ),
					sprintf( __( 'Check out our new post: %s', 'textdomain' ), get_permalink( $post_id ) )
				);

				if ( $result ) {
					$sent ++;
				}
			}

			return [
				'message' => sprintf(
					__( 'Notification sent to %d subscribers', 'textdomain' ),
					$sent
				),
			];
		},
		'confirm'  => __( 'Send notification to all subscribers?', 'textdomain' ),
		'icon'     => 'email',
	]
] );

/**
 * Example 5: Conditional Action Display
 *
 * Only show action for draft posts.
 */
register_post_row_actions( 'post', [
	'quick_publish' => [
		'label'               => __( 'Publish Now', 'textdomain' ),
		'position'            => 'after:edit',
		'ajax'                => true,
		'callback'            => function ( $post_id ) {
			wp_update_post( [
				'ID'          => $post_id,
				'post_status' => 'publish',
			] );

			return [
				'message' => __( 'Post published successfully', 'textdomain' ),
				'reload'  => true,
			];
		},
		'permission_callback' => function ( $post_id ) {
			$post = get_post( $post_id );

			return $post->post_status === 'draft' && current_user_can( 'publish_post', $post_id );
		},
		'icon'                => 'yes',
	]
] );

/**
 * Example 6: User Actions
 *
 * Send welcome email to users.
 */
register_user_row_actions( [
	'send_welcome' => [
		'label'               => __( 'Send Welcome Email', 'textdomain' ),
		'position'            => 'after:edit',
		'ajax'                => true,
		'callback'            => function ( $user_id ) {
			$user = get_userdata( $user_id );

			$result = wp_mail(
				$user->user_email,
				__( 'Welcome!', 'textdomain' ),
				__( 'Welcome to our site! We\'re glad to have you.', 'textdomain' )
			);

			return [
				'message' => $result
					? __( 'Welcome email sent successfully', 'textdomain' )
					: __( 'Failed to send welcome email', 'textdomain' ),
			];
		},
		'permission_callback' => function ( $user_id ) {
			return current_user_can( 'edit_user', $user_id );
		},
		'icon'                => 'email',
	]
] );

/**
 * Example 7: Taxonomy Actions
 *
 * Export taxonomy terms.
 */
register_taxonomy_row_actions( [ 'category', 'post_tag' ], [
	'export_term' => [
		'label'        => __( 'Export', 'textdomain' ),
		'url_callback' => function ( $term_id ) {
			return add_query_arg( [
				'action'   => 'export_term',
				'term_id'  => $term_id,
				'_wpnonce' => wp_create_nonce( 'export_term_' . $term_id ),
			], admin_url( 'admin-ajax.php' ) );
		},
		'position'     => 'after:edit',
		'icon'         => 'download',
		'target'       => '_blank',
	]
] );

/**
 * Example 8: Media Actions
 *
 * Regenerate image thumbnails.
 */
register_media_row_actions( [
	'regenerate_thumbnails' => [
		'label'               => __( 'Regenerate Thumbnails', 'textdomain' ),
		'position'            => 'after:edit',
		'ajax'                => true,
		'callback'            => function ( $attachment_id ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$file = get_attached_file( $attachment_id );

			if ( ! file_exists( $file ) ) {
				throw new Exception( __( 'File not found', 'textdomain' ) );
			}

			$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
			wp_update_attachment_metadata( $attachment_id, $metadata );

			return [
				'message' => __( 'Thumbnails regenerated successfully', 'textdomain' ),
			];
		},
		'permission_callback' => function ( $attachment_id ) {
			return current_user_can( 'edit_post', $attachment_id ) &&
			       wp_attachment_is_image( $attachment_id );
		},
		'icon'                => 'image-rotate',
	]
] );

/**
 * Example 9: Comment Actions
 *
 * Mark comments as helpful.
 */
register_comment_row_actions( [
	'mark_helpful' => [
		'label'    => __( 'Mark Helpful', 'textdomain' ),
		'position' => 'after:approve',
		'ajax'     => true,
		'callback' => function ( $comment_id ) {
			$is_helpful = get_comment_meta( $comment_id, 'helpful', true );
			$new_status = ! $is_helpful;

			update_comment_meta( $comment_id, 'helpful', $new_status );

			return [
				'message' => $new_status
					? __( 'Comment marked as helpful', 'textdomain' )
					: __( 'Comment unmarked as helpful', 'textdomain' ),
				'reload'  => true,
			];
		},
		'icon'     => 'thumbs-up',
	]
] );

/**
 * Example 10: Remove Default Actions
 *
 * Clean up the actions list.
 */
register_post_row_actions( 'post', [
	'custom_edit' => [
		'label'    => __( 'Custom Edit', 'textdomain' ),
		'url'      => admin_url( 'post.php?action=edit' ),
		'position' => 'before:view',
	]
], [ 'inline hide-if-no-js', 'trash' ] ); // Remove Quick Edit and Trash

/**
 * Example 11: Multiple Post Types
 *
 * Add the same action to multiple post types.
 */
register_post_row_actions( [ 'post', 'page', 'product' ], [
	'view_analytics' => [
		'label'        => __( 'Analytics', 'textdomain' ),
		'url_callback' => function ( $post_id ) {
			return add_query_arg( [
				'page'    => 'analytics',
				'post_id' => $post_id,
			], admin_url( 'admin.php' ) );
		},
		'position'     => 'after:view',
		'icon'         => 'chart-bar',
	]
] );

/**
 * Example 12: Advanced Error Handling
 *
 * Handle errors gracefully in AJAX callbacks.
 */
register_post_row_actions( 'post', [
	'sync_external' => [
		'label'    => __( 'Sync with API', 'textdomain' ),
		'position' => 'after:edit',
		'ajax'     => true,
		'callback' => function ( $post_id ) {
			try {
				// Attempt API sync
				$api_response = wp_remote_post( 'https://api.example.com/sync', [
					'body' => [
						'post_id' => $post_id,
						'title'   => get_the_title( $post_id ),
					],
				] );

				if ( is_wp_error( $api_response ) ) {
					throw new Exception( $api_response->get_error_message() );
				}

				$response_code = wp_remote_retrieve_response_code( $api_response );
				if ( $response_code !== 200 ) {
					throw new Exception( sprintf(
						__( 'API returned error code: %d', 'textdomain' ),
						$response_code
					) );
				}

				return [
					'message' => __( 'Successfully synced with external API', 'textdomain' ),
				];

			} catch ( Exception $e ) {
				return [
					'success' => false,
					'message' => sprintf(
						__( 'Sync failed: %s', 'textdomain' ),
						$e->getMessage()
					),
				];
			}
		},
		'icon'     => 'update',
		'confirm'  => __( 'Sync this post with the external API?', 'textdomain' ),
	]
] );