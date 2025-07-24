<?php
/*
Plugin Name: Book Manager
Description: A plugin to manage books.
Version: 1.0
Author: Nischal Dahal
*/

function bm_register_book_cpt() {
    register_post_type('book', [
        'labels' => [
            'name' => 'Books',
            'singular_name' => 'Book',
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'bm_register_book_cpt');


// Add Meta Box
function bm_add_meta_boxes() {
    add_meta_box(
        'bm_book_details',       // Meta box ID
        'Book Details',          // Title of the meta box
        'bm_render_meta_box',    // Callback function to display fields
        'book',                  // Post type where it appears
        'normal',                // Context (normal, side, etc.)
        'high'                   // Priority
    );
}
add_action('add_meta_boxes', 'bm_add_meta_boxes');


// Display fields inside meta box
function bm_render_meta_box($post) {
    // Add a nonce field for security
    wp_nonce_field('bm_save_meta_box_data', 'bm_meta_box_nonce');

    // Retrieve existing values from database
    $author_name = get_post_meta($post->ID, '_bm_author_name', true);
    $published_year = get_post_meta($post->ID, '_bm_published_year', true);

    ?>
    <p>
        <label for="bm_author_name">Author Name:</label><br />
        <input type="text" id="bm_author_name" name="bm_author_name" value="<?php echo esc_attr($author_name); ?>" size="30" />
    </p>
    <p>
        <label for="bm_published_year">Published Year:</label><br />
        <input type="number" id="bm_published_year" name="bm_published_year" value="<?php echo esc_attr($published_year); ?>" size="4" />
    </p>
    <?php
}


// Save meta box data securely
function bm_save_meta_box_data($post_id) {
    // Check if nonce is set
    if (!isset($_POST['bm_meta_box_nonce'])) {
        return;
    }

    // Verify nonce is valid
    if (!wp_verify_nonce($_POST['bm_meta_box_nonce'], 'bm_save_meta_box_data')) {
        return;
    }

    // Prevent autosave from overwriting data
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permission
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save Author Name
    if (isset($_POST['bm_author_name'])) {
        update_post_meta($post_id, '_bm_author_name', sanitize_text_field($_POST['bm_author_name']));
    }

    // Save Published Year
    if (isset($_POST['bm_published_year'])) {
        update_post_meta($post_id, '_bm_published_year', intval($_POST['bm_published_year']));
    }
}
add_action('save_post', 'bm_save_meta_box_data');


// Add new columns to the Books admin list
function bm_add_custom_columns($columns) {
    // Add Author and Published Year columns after the title column
    $new_columns = array();

    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;

        if ($key === 'title') {
            $new_columns['author_name'] = 'Author';
            $new_columns['published_year'] = 'Year';
        }
    }
    return $new_columns;
}
add_filter('manage_book_posts_columns', 'bm_add_custom_columns');


// Show data in the custom columns
function bm_custom_columns_content($column, $post_id) {
    if ($column == 'author_name') {
        $author = get_post_meta($post_id, '_bm_author_name', true);
        echo esc_html($author);
    }
    if ($column == 'published_year') {
        $year = get_post_meta($post_id, '_bm_published_year', true);
        echo esc_html($year);
    }
}
add_action('manage_book_posts_custom_column', 'bm_custom_columns_content', 10, 2);
