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
        add_action('init', array('PersistentForking', 'add_fork_capability'));
        add_action('admin_init', array('PersistentForking', 'add_fork_capability'));
        add_filter('the_content', array('PersistentForking', 'add_fork_button'), 15);
        if (isset($_REQUEST['action']) && 'persistent_fork' === $_REQUEST['action']) {
            add_action('init', array('PersistentForking', 'create_forking_form'));
        }
        add_action('add_meta_boxes', array('PersistentForking', 'editor_parent_metabox'));
        add_action('save_post', array('PersistentForking', 'save_experiment'), 10, 2);
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
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_ui'           => false,
            'show_in_nav_menus' => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'experiment' ),
            // 'meta_box_cb' => self::some_function,  // custom metabox callback
        );
 
        register_taxonomy( 'experiment', array( 'post' ), $args );
    }
    
    static function add_fork_capability( ) {
        foreach (array('administrator', 'editor', 'author') as $name) {
            $role = get_role($name); // should be a settings variable
            $role->add_cap('create_persistent_forks');
        }
    }
    
    static function custom_taxonomy_visualisation_callback( ) {
        wp_enqueue_script( 'persistfork-tax-visualisation',
            plugins_url( '/js/visualisation.js', __FILE__ ),
            array('jquery')
        );
    }

    static function add_fork_button($content) {
        if (! current_user_can('create_persistent_forks')) {
            return $content;
        }
        $post = $GLOBALS['post'];
        if ($post->post_type != 'post') return $content;  // make the post type a setting
        $url = add_query_arg(array(
            'action' => 'persistent_fork',
            'post' => $post->ID,
            'nonce' => wp_create_nonce('persistent_forking')
        ), home_url());
        return $content . '<a href="' . $url . '" title="Fork this experiment">Fork</a>';
    }
    
    static function fork($parent_post = null, $author = null) {
        global $post;
        if ($parent_post == null) $parent_post = $post;
        if (! is_object($parent_post)) $parent_post = get_post($parent_post);
        if (! $parent_post) return false;
        $parent_id = $parent_post->ID;
        
        if ($author == null) $author = wp_get_current_user()->ID;
        if (! user_can($author, 'create_persistent_forks')) wp_die(__(
            'You are not allowed to create forks',
            'persistent-forking'
        ));
        
        $fork = array(
            'post_author' => $author,
            'post_status' => 'draft',
            'post_title' => $parent_post->post_title,
            'post_type' => $parent_post->post_type
        );
        $fork_id = wp_insert_post($fork);
        if (! $fork_id) return false;
        add_post_meta($fork_id, '_persistfork-parent', $parent_id, true);
        
        return $fork_id;
    }

    static function save_experiment($post_id, $post) {
        if ($post->post_status != 'publish') return;
        $terms = wp_get_object_terms($post_id, 'experiment');
        $term = reset($terms);
        if ($term) return;
        $parent_id = get_post_meta($post_id, '_persistfork-parent', true);
        if ($parent_id) {
            $terms = wp_get_object_terms($parent_id, 'experiment');
            $term = reset($terms);
            wp_add_object_terms($post_id, $term->term_id, 'experiment');
        } else {
            $term = wp_insert_term($post->post_title, 'experiment');
            $counter = 1;
            while (is_object($term) && is_a($term, 'WP_Error')) {
                $term = wp_insert_term($post->post_title . $counter, 'experiment');
                ++$counter;
            }
            wp_add_object_terms($post_id, $term['term_id'], 'experiment');
        }
    }
    
    static function create_forking_form( ) {
        if (! current_user_can('create_persistent_forks')) {
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