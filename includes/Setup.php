<?php
namespace GFC;

class Setup {
    public static function activate() {
        // Create necessary roles with expanded capabilities
        add_role('mentor', 'Mentor', [
            'read' => true,
            'view_forms' => true,
            'view_courses' => true,
            'edit_custom_forms' => true,
            'edit_posts' => true,
            'view_submissions' => true,
            'view_quiz_results' => true,
            'view_course_progress' => true
        ]);
        
        add_role('big_bird', 'Big Bird', [
            'read' => true,
            'view_forms' => true,
            'view_courses' => true,
            'edit_custom_forms' => true,
            'edit_posts' => true,
            'view_submissions' => true,
            'view_quiz_results' => true,
            'view_course_progress' => true
        ]);
        
        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('view_forms');
            $admin->add_cap('view_courses');
            $admin->add_cap('edit_custom_forms');
            $admin->add_cap('view_submissions');
            $admin->add_cap('view_quiz_results');
            $admin->add_cap('view_course_progress');
        }
        
        // Create upload directory for PDFs
        $upload_dir = wp_upload_dir();
        wp_mkdir_p($upload_dir['basedir'] . '/form-submissions');
        
        // Create database tables
        self::create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        // Remove roles and capabilities
        remove_role('mentor');
        remove_role('big_bird');
        
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('view_forms');
            $admin->remove_cap('view_courses');
            $admin->remove_cap('edit_custom_forms');
            $admin->remove_cap('view_submissions');
            $admin->remove_cap('view_quiz_results');
            $admin->remove_cap('view_course_progress');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}form_submissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            submission_data longtext NOT NULL,
            pdf_path varchar(255) DEFAULT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY form_id (form_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 