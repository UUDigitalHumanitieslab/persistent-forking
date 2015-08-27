<?php
/**
 * Render the content of the Parent metabox in the admin post editing form.
 */
$post_id = $GLOBALS['post']->ID;
$parent_id = get_post_meta($post_id, '_persistfork-parent', true);
if ($parent_id): ?>
    Parent:
    <a href="<?= get_permalink($parent_id) ?>">
        <?= get_post($parent_id)->post_title ?>
    </a>
<?php endif ?>