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
$families = wp_get_object_terms($post_id, 'family');
$family = reset($families);
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
<?php if ($family):
    $family_id = $family->term_id;
    echo implode(', ', array_keys(get_object_vars($family))); ?>
    | <a href="#" onclick="visualise(data_<?= $family_id ?>);">
        show family
    </a>
    <?php
    if (! isset($persistfork_rendered)) $persistfork_rendered = array();
    if (! isset($persistfork_rendered[$family_id])):
        $persistfork_rendered[$family_id] = true;
        $nodes = get_objects_in_term($family_id, 'family');
        $edges = array();
        foreach ($nodes as $id) {
            $parent_id = get_post_meta($id, '_persistfork-parent', true);
            if ($parent_id) {
                $edges[] = array('from' => $parent_id, 'to' => $id);
            }
        }
        reset($nodes);
        $first_node = next($nodes);
        $first_edge = next($edges); ?>
        <script>
            var data_<?= $family_id ?> = {
                nodes: {
                    {
                        id: <?= $first_node ?>,
                        label: <?= get_post($first_node)->post_title ?>
                    }
                    <?php foreach ($nodes as $id): ?>
                        , {
                            id: <?= $id ?>,
                            label: <?= get_post($id)->post_title ?>
                        }
                    <?php endforeach ?>
                },
                edges: {
                    {
                        from: <?= $first_edge['from'] ?>,
                        to: <?= $first_edge['to'] ?>
                    }
                    <?php foreach ($edges as $edge): ?>
                        , {
                            from: <?= $edge['from'] ?>,
                            to: <?= $edge['to'] ?>
                        }
                    <?php endforeach ?>
                }
            };
        </script>
    <?php endif ?>
<?php endif ?>