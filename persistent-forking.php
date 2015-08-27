<?php
/*
Plugin Name: Persistent Forking
Author: Julian Gonggrijp, Digital Humanities Lab, Utrecht University
Author URI: http://digitalhumanities.wp.hum.uu.nl
License: MIT
License URI: http://opensource.org/licenses/MIT
*/

/**
* Description
*/
 
class PersistentForking {
    
    static function add_hooks( ) {
        add_filter('the_content', array('PersistentForking', 'add_fork_controls'), 15);
        add_action('init', array('PersistentForking', 'create_family_taxonomies'), 0);
        if (isset($_REQUEST['action']) && 'persistent_fork' === $_REQUEST['action']) {
            add_action('init', array('PersistentForking', 'create_forking_form'));
        }
        add_action('add_meta_boxes', array('PersistentForking', 'editor_parent_metabox'));
        add_action('save_post', array('PersistentForking', 'save_family'), 10, 2);
    }
    
    static function create_family_taxonomies( ) {
        $labels = array(
            'name'              => _x( 'Families', 'taxonomy general name' ),
            'singular_name'     => _x( 'Family', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Families' ),
            'all_items'         => __( 'All Families' ),
            'parent_item'       => __( 'Parent Family' ),
            'parent_item_colon' => __( 'Parent Family:' ),
            'edit_item'         => __( 'Edit Family' ),
            'update_item'       => __( 'Update Family' ),
            'add_new_item'      => __( 'Add New Family' ),
            'new_item_name'     => __( 'New Family Name' ),
            'menu_name'         => __( 'Family' ),
        );
 
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_ui'           => false,
            'show_in_nav_menus' => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'family' ),
            // 'meta_box_cb' => self::some_function,  // custom metabox callback
        );
 
        register_taxonomy( 'family', array( 'post' ), $args );
    }
    
    static function custom_taxonomy_visualisation_callback( ) {
        wp_enqueue_script( 'persistfork-tax-visualisation',
            plugins_url( '/js/visualisation.js', __FILE__ ),
            array('jquery')
        );
    }

    static function add_fork_controls($content) {
        if (! current_user_can('edit_posts')) {
            return $content;
        }
        $post = $GLOBALS['post'];
        $post_id = $post->ID;
        if ($post->post_type != 'post') return $content;
        $fork_url = add_query_arg(array(
            'action' => 'persistent_fork',
            'post' => $post_id,
            'nonce' => wp_create_nonce('persistent_forking')
        ), home_url());
        $img = '<img src="' . plugins_url("/images/fork_icon.png", __FILE__) . '" style="display: inline;">';
        $fork_anchor = '<a href="' . $fork_url . '" title="Fork this post">' . $img . ' Fork</a>';
        $parent_anchor = '';
        $parent_id = get_post_meta($post_id, '_persistfork-parent', true);
        if ($parent_id) {
            $parent = get_post($parent_id);
            $parent_anchor = ' | Forked from: <a href="' . get_permalink($parent_id) . '">' . $parent->post_title . '</a>';
        }
        return $fork_anchor . $parent_anchor . $content;
    }
    
    static function fork($parent_post = null, $author = null) {
        global $post;
        if ($parent_post == null) $parent_post = $post;
        if (! is_object($parent_post)) $parent_post = get_post($parent_post);
        if (! $parent_post) return false;
        $parent_id = $parent_post->ID;
        
        if ($author == null) $author = wp_get_current_user()->ID;
        if (! user_can($author, 'edit_posts')) wp_die(__(
            'You are not allowed to create forks',
            'persistent-forking'
        ));
        
        $fork = array(
            'post_author' => $author,
            'post_status' => 'draft',
            'post_title' => '[fork] ' . $parent_post->post_title,
            'post_type' => $parent_post->post_type
        );
        $fork_id = wp_insert_post($fork);
        if (! $fork_id) return false;
        add_post_meta($fork_id, '_persistfork-parent', $parent_id, true);
        
        return $fork_id;
    }

    static function save_family($post_id, $post) {
        if ($post->post_status != 'publish') return;
        $terms = wp_get_object_terms($post_id, 'family');
        $term = reset($terms);
        if ($term) return;
        $parent_id = get_post_meta($post_id, '_persistfork-parent', true);
        if ($parent_id) {
            $terms = wp_get_object_terms($parent_id, 'family');
            $term = reset($terms);
            wp_add_object_terms($post_id, $term->term_id, 'family');
        } else {
            $term = wp_insert_term($post->post_title, 'family');
            $counter = 1;
            while (is_object($term) && is_a($term, 'WP_Error')) {
                $term = wp_insert_term($post->post_title . $counter, 'family');
                ++$counter;
            }
            wp_add_object_terms($post_id, $term['term_id'], 'family');
        }
    }
    
    static function create_forking_form( ) {
        if (! current_user_can('edit_posts')) {
            return;
        }
        if (! wp_verify_nonce($_REQUEST['nonce'], 'persistent_forking')) {
            return;
        }
        $post_id = (isset($_REQUEST['post'])) ? get_post((int)$_REQUEST['post']) : false;
        if (empty($post_id)) {
            return;
        }
        $fork_id = self::fork($post_id);
        // Redirect to form
        $redirect = get_edit_post_link($fork_id, 'redirect');
        wp_safe_redirect( $redirect );
        exit;
    }
    
    static function editor_parent_metabox( ) {
        add_meta_box(
            'persistfork_parent_reference',  // unique ID
            'Parent',  // box title
            array('PersistentForking', 'display_editor_parent_metabox'),  // callback
            'post'  // post type
        );
    }
    
    static function display_editor_parent_metabox( ) {
        $post_id = $GLOBALS['post']->ID;
        $parent_id = get_post_meta($post_id, '_persistfork-parent', true);
        if (! $parent_id) {
            echo 'none';
        } else {
            $parent = get_post($parent_id);
            echo '<a href="' . get_permalink($parent_id) . '">' . $parent->post_title . '</a>';
        }
    }
}

PersistentForking::add_hooks();

?>