<?php
/*
Plugin Name: Worklog Plugin
Description: A plugin to track worklog entries for posts and group them by authors in a custom settings page.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WorklogPlugin {
    public function __construct() {
        // Add hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('add_meta_boxes', [$this, 'add_worklog_meta_box']);
        add_action('wp_ajax_save_worklog', [$this, 'save_worklog']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        register_activation_hook(__FILE__, [$this, 'create_worklog_table']);
    }

    public function enqueue_assets($hook) {
        // Enqueue scripts and styles for admin screens
        if ($hook === 'post.php' || $hook === 'post-new.php' || $hook === 'toplevel_page_worklog-settings') {

            wp_enqueue_script('datetimepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js', ['jquery'], null, true);
            wp_enqueue_style('datetimepicker-style', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css');
            
           // Enqueue Bootstrap assets
            wp_enqueue_style('bootstrap-css', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css', [], '5.3.0');
            wp_enqueue_script('bootstrap-js', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true); 
            
            wp_enqueue_script('worklog-js', plugin_dir_url(__FILE__) . 'assets/js/worklog.js', ['jquery'], null, true);
            wp_enqueue_style('worklog-css', plugin_dir_url(__FILE__) . 'assets/css/styles.css');

            wp_localize_script('worklog-js', 'worklogAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('save_worklog_nonce'),
            ]);
        }
    }

    public function create_worklog_table() {
        // Create the database table for worklogs
        global $wpdb;
        $table_name = $wpdb->prefix . 'worklog';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            author_id BIGINT(20) UNSIGNED NOT NULL,
            time_spent VARCHAR(255) NOT NULL,
            start_date DATETIME NOT NULL,
            purpose VARCHAR(255) NOT NULL,
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_worklog_meta_box() {
        // Add meta box for worklogs
        add_meta_box(
            'worklog_meta_box',
            'Worklog',
            [$this, 'render_worklog_meta_box'],
            null,
            'side',
            'default'
        );
    }

    public function render_worklog_meta_box($post) {
        include plugin_dir_path(__FILE__) . 'views/worklog-meta-box.php';
    }

    public function save_worklog() {
        // AJAX handler to save worklog
        check_ajax_referer('save_worklog_nonce', 'security');

        global $wpdb;
        $table_name = $wpdb->prefix . 'worklog';

        $post_id = intval($_POST['post_id']);
        $author_id = get_current_user_id();
        $time_spent = sanitize_text_field($_POST['time_spent']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $purpose = sanitize_text_field($_POST['purpose']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $wpdb->insert(
            $table_name,
            [
                'post_id' => $post_id,
                'author_id' => $author_id,
                'time_spent' => $time_spent,
                'start_date' => $start_date,
                'purpose' => $purpose,
                'notes' => $notes,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        wp_send_json_success('Worklog saved successfully.');
    }

    public function add_admin_menu() {
        // Add settings page
        $icon_url = plugin_dir_url(__FILE__) . 'assets/images/logo.jpg';
        add_menu_page(
            'Worklog Settings',
            'Worklog Settings',
            'manage_options',
            'worklog-settings',
            [$this, 'render_settings_page'],
            $icon_url,
            50
        );
    }

    public function render_settings_page() {
        include plugin_dir_path(__FILE__) . 'views/settings-page.php';
    }
}

new WorklogPlugin();
