<?php
/**
 * Render the content of the metabox in the admin post editing form.
 */
$post = $GLOBALS['post'];
$post_id = $post->ID;
$parent_id = get_post_meta($post_id, '_persistfork-parent', true);
$families = wp_get_object_terms($post_id, 'family');
$family = reset($families);
if ($parent_id): ?>
    Parent:
    <a href="<?= get_permalink($parent_id) ?>">
        <?= get_post($parent_id)->post_title ?>
    </a>
    <br />
    <?php if ($family): ?>
        Family:
        <a href="<?= home_url() . '/' . 'index.php/family/' . $family->slug . '/' ?>">
            <?= $family->name ?>
        </a>
    <?php else: ?>
        You started a new fork.
        <?php if (current_user_can('delete_posts')): ?>
            (<a href="<?= get_delete_post_link($post_id) ?>">undo</a>)
        <?php endif ?>
    <?php endif ?>
<?php else: ?>
    No parent
    <?php if ($family): ?>
        (root of family)
    <?php else: ?>
        (not a fork)
    <?php endif ?>
<?php endif ?>
