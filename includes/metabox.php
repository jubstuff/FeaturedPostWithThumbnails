<?php
add_action('admin_menu', 'YIW_featured_post_metabox');

function YIW_featured_post_metabox()
{
    add_meta_box('post_info',
        __('Featured', YIW_TEXT_DOMAIN),
        'YIW_post_box',
        'post',
        'side',
        'high');
}

/**
 * Shows featured form in "Write Post" section
 */
function YIW_post_box()
{
    global $post;
    $is_featured = get_post_meta($post->ID, '_yiw_featured_post', true) ? 'yes' : 'no';
    include(plugin_dir_path(__FILE__) . '/../views/metabox.php');
}


/**
 * Add/remove featured custom field
 *
 * @param integer $post_ID
 */
function YIW_add_featured($post_ID)
{
    //TODO POST validation
    $articolo = get_post($post_ID);
    if (isset($_POST['insert_featured_post'])) {
        if ($_POST['insert_featured_post'] == 'yes') {
            update_post_meta($articolo->ID, '_yiw_featured_post', 1);
        }
        elseif ($_POST['insert_featured_post'] == 'no') {
            delete_post_meta($articolo->ID, '_yiw_featured_post');
        }
    }
}

add_action('new_to_publish', 'YIW_add_featured');
add_action('save_post', 'YIW_add_featured');