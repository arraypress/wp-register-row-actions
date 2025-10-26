# WordPress Register Row Actions

A lightweight library for registering custom row actions in WordPress admin tables. This library provides a clean, simple API for adding URL-based and AJAX-powered actions to posts, users, taxonomies, comments, and media without complex configuration.

## Features

- **Simple API**: Register custom row actions with minimal code
- **Action Positioning**: Position actions before or after existing actions
- **Three Action Types**:
    - Static URL actions
    - Dynamic URL via callbacks
    - AJAX actions with automatic handling
- **Permission Control**: Control action visibility based on user capabilities
- **Automatic AJAX Handling**: Built-in AJAX processing with security
- **Multiple Post Types**: Register same actions across multiple post types or taxonomies
- **Remove Default Actions**: Clean up unwanted default actions
- **Lightweight**: Minimal JavaScript, clean architecture

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher

## Installation

Install via Composer:

```bash
composer require arraypress/wp-register-row-actions
```

## Basic Usage

### Static URL Actions

Simple actions that link to a URL:

```php
register_post_row_actions( 'post', [
    'preview_external' => [
        'label'    => __( 'Preview External', 'textdomain' ),
        'url'      => 'https://example.com/preview/',
        'position' => 'after:view',
        'target'   => '_blank',
        'icon'     => 'external',
    ]
] );
```

### Dynamic URL Actions

Actions with URLs generated via callback:

```php
register_post_row_actions( 'post', [
    'duplicate' => [
        'label'        => __( 'Duplicate', 'textdomain' ),
        'url_callback' => function( $post_id ) {
            return wp_nonce_url(
                admin_url( "admin.php?action=duplicate_post&post=$post_id" ),
                "duplicate_post_$post_id"
            );
        },
        'position'     => 'after:edit',
        'capability'   => 'edit_posts',
    ]
] );
```

### AJAX Actions

Actions that execute via AJAX with automatic handling:

```php
register_post_row_actions( 'post', [
    'mark_featured' => [
        'label'    => __( 'Mark Featured', 'textdomain' ),
        'position' => 'after:edit',
        'ajax'     => true,
        'callback' => function( $post_id, $options = [] ) {
            // Perform the action
            update_post_meta( $post_id, 'featured', true );
            
            // Return response
            return [
                'success' => true,
                'message' => __( 'Post marked as featured', 'textdomain' ),
                'reload'  => true, // Optional: reload the page
            ];
        },
        'confirm'  => __( 'Mark this post as featured?', 'textdomain' ),
    ]
] );
```

## Action Configuration Options

| Option                | Type     | Description                                                                      |
|-----------------------|----------|----------------------------------------------------------------------------------|
| `label`               | string   | Action label text                                                                |
| `url`                 | string   | Static URL for the action                                                        |
| `url_callback`        | callable | Function to generate URL dynamically                                             |
| `ajax`                | bool     | Whether this is an AJAX action                                                   |
| `callback`            | callable | Function to execute for AJAX actions                                             |
| `position`            | string   | Action position (e.g., 'after:edit', 'before:trash')                            |
| `permission_callback` | callable | Function to check if user can see action                                         |
| `capability`          | string   | Required capability (default: 'manage_options')                                  |
| `confirm`             | string   | Confirmation message for AJAX actions                                            |
| `class`               | string   | Additional CSS classes                                                           |
| `target`              | string   | Link target (e.g., '_blank')                                                     |
| `icon`                | string   | Dashicon name (without 'dashicons-' prefix)                                      |

## Table-Specific Examples

### Post Actions

```php
register_post_row_actions( 'post', [
    'send_notification' => [
        'label'    => __( 'Send Notification', 'textdomain' ),
        'position' => 'after:edit',
        'ajax'     => true,
        'callback' => function( $post_id ) {
            // Send notification logic
            $result = send_post_notification( $post_id );
            
            return [
                'message' => $result ? 
                    __( 'Notification sent!', 'textdomain' ) : 
                    __( 'Failed to send notification', 'textdomain' ),
                'reload'  => false,
            ];
        },
        'icon'     => 'email',
    ]
] );
```

### User Actions

```php
register_user_row_actions( [
    'send_welcome_email' => [
        'label'               => __( 'Send Welcome Email', 'textdomain' ),
        'position'            => 'after:edit',
        'ajax'                => true,
        'callback'            => function( $user_id ) {
            wp_mail(
                get_userdata( $user_id )->user_email,
                'Welcome!',
                'Welcome to our site!'
            );
            
            return [
                'message' => __( 'Welcome email sent!', 'textdomain' ),
            ];
        },
        'permission_callback' => function( $user_id ) {
            return current_user_can( 'edit_user', $user_id );
        },
    ]
] );
```

### Taxonomy Actions

```php
register_taxonomy_row_actions( [ 'category', 'post_tag' ], [
    'export_terms' => [
        'label'        => __( 'Export', 'textdomain' ),
        'url_callback' => function( $term_id ) {
            return admin_url( "admin-ajax.php?action=export_term&term_id=$term_id" );
        },
        'position'     => 'after:edit',
        'icon'         => 'download',
    ]
] );
```

### Comment Actions

```php
register_comment_row_actions( [
    'mark_helpful' => [
        'label'    => __( 'Mark Helpful', 'textdomain' ),
        'position' => 'after:approve',
        'ajax'     => true,
        'callback' => function( $comment_id ) {
            update_comment_meta( $comment_id, 'helpful', true );
            
            return [
                'message' => __( 'Comment marked as helpful', 'textdomain' ),
            ];
        },
    ]
] );
```

### Media Actions

```php
register_media_row_actions( [
    'regenerate_thumbnails' => [
        'label'    => __( 'Regenerate Thumbnails', 'textdomain' ),
        'position' => 'after:edit',
        'ajax'     => true,
        'callback' => function( $attachment_id ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            
            $file = get_attached_file( $attachment_id );
            wp_generate_attachment_metadata( $attachment_id, $file );
            
            return [
                'message' => __( 'Thumbnails regenerated', 'textdomain' ),
            ];
        },
    ]
] );
```

## AJAX Callback Responses

Your AJAX callback can return various response types:

### Simple Success

```php
'callback' => function( $object_id ) {
    // Do something
    
    return [
        'message' => __( 'Action completed', 'textdomain' ),
    ];
}
```

### Reload Page

```php
'callback' => function( $object_id ) {
    // Do something
    
    return [
        'message' => __( 'Action completed', 'textdomain' ),
        'reload'  => true, // Page will reload after success
    ];
}
```

### Redirect

```php
'callback' => function( $object_id ) {
    // Do something
    
    return [
        'redirect' => admin_url( 'edit.php?success=1' ),
    ];
}
```

### Remove Row

```php
'callback' => function( $object_id ) {
    // Do something (like soft delete)
    
    return [
        'message'    => __( 'Item removed', 'textdomain' ),
        'remove_row' => true, // Row will fade out and be removed
    ];
}
```

### Update Row HTML

```php
'callback' => function( $object_id ) {
    // Do something
    
    // Generate new row HTML (advanced)
    ob_start();
    // Output new row HTML
    $row_html = ob_get_clean();
    
    return [
        'row_html' => $row_html, // Row will be replaced
    ];
}
```

## Permission Control

Control who can see actions:

```php
register_post_row_actions( 'post', [
    'publish_now' => [
        'label'               => __( 'Publish Now', 'textdomain' ),
        'ajax'                => true,
        'callback'            => function( $post_id ) {
            wp_update_post( [
                'ID'          => $post_id,
                'post_status' => 'publish',
            ] );
            
            return [
                'message' => __( 'Post published', 'textdomain' ),
                'reload'  => true,
            ];
        },
        'permission_callback' => function( $post_id ) {
            // Only show for draft posts
            $post = get_post( $post_id );
            return $post->post_status === 'draft' && current_user_can( 'publish_post', $post_id );
        },
    ]
] );
```

## Multiple Post Types

Register the same actions across multiple post types:

```php
register_post_row_actions( [ 'post', 'page', 'custom_post_type' ], [
    'custom_action' => [
        'label'    => __( 'Custom Action', 'textdomain' ),
        'position' => 'after:edit',
        // ... action config
    ]
] );
```

## Removing Default Actions

Remove unwanted default actions:

```php
register_post_row_actions( 'post', [
    'custom_action' => [
        'label' => __( 'My Action', 'textdomain' ),
        // ... config
    ]
], [ 'inline hide-if-no-js', 'trash' ] ); // Remove Quick Edit and Trash
```

## Advanced Examples

### Conditional Actions Based on Meta

```php
register_post_row_actions( 'product', [
    'sync_inventory' => [
        'label'               => __( 'Sync Inventory', 'textdomain' ),
        'position'            => 'after:edit',
        'ajax'                => true,
        'callback'            => function( $post_id ) {
            // Sync with external API
            $result = sync_product_inventory( $post_id );
            
            return [
                'message' => $result['message'],
                'reload'  => $result['success'],
            ];
        },
        'permission_callback' => function( $post_id ) {
            // Only show for products with external sync enabled
            return get_post_meta( $post_id, 'enable_external_sync', true ) === '1';
        },
        'icon'                => 'update',
        'confirm'             => __( 'Sync inventory with external system?', 'textdomain' ),
    ]
] );
```

### Bulk Action Trigger

```php
register_post_row_actions( 'post', [
    'add_to_queue' => [
        'label'    => __( 'Add to Queue', 'textdomain' ),
        'position' => 'after:edit',
        'ajax'     => true,
        'callback' => function( $post_id, $options = [] ) {
            // Add to processing queue
            add_to_processing_queue( $post_id );
            
            return [
                'message' => __( 'Added to processing queue', 'textdomain' ),
            ];
        },
        'icon'     => 'plus-alt',
    ]
] );
```

### Export Action

```php
register_post_row_actions( 'post', [
    'export_pdf' => [
        'label'        => __( 'Export PDF', 'textdomain' ),
        'url_callback' => function( $post_id ) {
            return add_query_arg( [
                'action'  => 'export_post_pdf',
                'post_id' => $post_id,
                '_wpnonce' => wp_create_nonce( 'export_pdf_' . $post_id ),
            ], admin_url( 'admin-ajax.php' ) );
        },
        'position'     => 'after:view',
        'target'       => '_blank',
        'icon'         => 'media-document',
    ]
] );
```

## JavaScript Events

The library fires custom JavaScript events you can hook into:

```javascript
jQuery(document).on('rowActionSuccess', function(e, data) {
    console.log('Action succeeded:', data);
});

jQuery(document).on('rowActionError', function(e, data) {
    console.log('Action failed:', data);
});

jQuery(document).on('rowActionComplete', function(e, data) {
    console.log('Action completed (success or error):', data);
});
```

## Best Practices

1. **Always Use Nonces**: For URL actions, generate nonces in `url_callback`
2. **Validate Permissions**: Use `permission_callback` for dynamic permission checks
3. **Provide Feedback**: Always return a `message` in AJAX callbacks
4. **Use Confirmations**: Add `confirm` messages for destructive actions
5. **Handle Errors**: Wrap callback code in try-catch and return appropriate errors
6. **Keep Callbacks Light**: For heavy operations, queue them for background processing
7. **Test Capabilities**: Ensure proper capability checks in both permission callbacks and AJAX handlers

## Error Handling

Handle errors gracefully in your callbacks:

```php
'callback' => function( $post_id ) {
    try {
        // Attempt action
        $result = do_something_that_might_fail( $post_id );
        
        if ( ! $result ) {
            throw new Exception( __( 'Operation failed', 'textdomain' ) );
        }
        
        return [
            'message' => __( 'Success!', 'textdomain' ),
        ];
        
    } catch ( Exception $e ) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}
```

## Comparison with Native WordPress

**Before (Manual Approach):**
```php
add_filter( 'post_row_actions', function( $actions, $post ) {
    $actions['custom'] = sprintf(
        '<a href="#" onclick="myCustomFunction(%d)">Custom Action</a>',
        $post->ID
    );
    return $actions;
}, 10, 2 );

add_action( 'wp_ajax_my_custom_action', function() {
    // Manual nonce checking
    // Manual capability checking
    // Manual AJAX handling
    // Manual response formatting
} );
```

**After (This Library):**
```php
register_post_row_actions( 'post', [
    'custom' => [
        'label'    => 'Custom Action',
        'ajax'     => true,
        'callback' => function( $post_id ) {
            // Just the business logic
            return [ 'message' => 'Done!' ];
        },
    ]
] );
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

GPL-2.0-or-later

## Author

David Sherlock - [ArrayPress](https://arraypress.com/)

## Support

- [Documentation](https://github.com/arraypress/wp-register-row-actions)
- [Issue Tracker](https://github.com/arraypress/wp-register-row-actions/issues)