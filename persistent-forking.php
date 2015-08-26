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
        add_action('init', array('PersistentForking', 'create_experiment_taxonomies'), 0);
        add_filter('the_content', array('PersistentForking', 'add_fork_controls'), 15);
        if (isset($_REQUEST['action']) && 'persistent_fork' === $_REQUEST['action']) {
            add_action('init', array('PersistentForking', 'create_forking_form'));
        }
        add_action('add_meta_boxes', array('PersistentForking', 'editor_parent_metabox'));
        add_action('save_post', array('PersistentForking', 'save_customfields'), 10, 2);
    }
    
    static function create_experiment_taxonomies( ) {
        $labels = array(
            'name'              => _x( 'Experiments', 'taxonomy general name' ),
            'singular_name'     => _x( 'Experiment', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Experiments' ),
            'all_items'         => __( 'All Experiments' ),
            'parent_item'       => __( 'Parent Experiment' ),
            'parent_item_colon' => __( 'Parent Experiment:' ),
            'edit_item'         => __( 'Edit Experiment' ),
            'update_item'       => __( 'Update Experiment' ),
            'add_new_item'      => __( 'Add New Experiment' ),
            'new_item_name'     => __( 'New Experiment Name' ),
            'menu_name'         => __( 'Experiment' ),
        );
 
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'experiment' ),
        );
 
        register_taxonomy( 'experiment', array( 'post' ), $args );
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
        $img = '<img src="' . plugins_url("/img/fork_icon.png", __FILE__) . '" style="display: inline;">';
        $fork_anchor = '<a href="' . $fork_url . '" title="Fork this experiment">' . $img . ' Fork</a>';
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
        self::save_customfields(
            $fork_id, $fork, $parent_id,
            get_post_meta($parent_id, '_persistfork-root')
        );
        
        return $fork_id;
    }

    static function save_customfields($post_id, $post, $parent = null, $root = null) {
        if ($parent != null) {
            add_post_meta($post_id, '_persistfork-parent', $parent, true);
        }
        if ($root == null) $root = $post_id;
        add_post_meta($post_id, '_persistfork-root', $root, true);
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
        $parent = get_post($parent_id);
        echo '<a href="' . get_permalink($parent_id) . '">' . $parent->post_title . '</a>';
    }
}

PersistentForking::add_hooks();

// function print_current_hook() {
//     echo '<p>' . current_filter() . '</p>';
// }
// add_action( 'all', 'print_current_hook' );

?>