<?php
    /**
     * Render the inset with fork button and parent link for the 
     * public view of a post.
     *
     * Required parameters:
     * $post_id     the ID of the post for which the inset will be 
     *              rendered
     * $image_url   the URL of the fork icon image file
     */
    $fork_url = add_query_arg(array(
        'action' => 'persistent_fork',
        'post' => $post_id,
        'nonce' => wp_create_nonce('persistent_forking')
    ), home_url());
    $parent_id = get_post_meta($post_id, '_persistfork-parent', true);
?>
<a href="<?= $fork_url ?>" title="Fork this post">
    <img
        src="<?= $image_url ?>"
        title="Fork"
        alt="Fork"
        style="display: inline;"
    />
    Fork
</a>
<?php if ($parent_id): ?>
    | Forked from:
    <a href="<?= get_permalink($parent_id) ?>">
        <?= get_post($parent_id)->post_title ?>
    </a>
<?php endif ?>
