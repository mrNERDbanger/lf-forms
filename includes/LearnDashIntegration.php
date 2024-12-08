<?php
namespace GFC;

class LearnDashIntegration {
    public function __construct() {
        // Add form as LearnDash content type
        add_filter('learndash_post_types', [$this, 'add_form_post_type']);
        
        // Add form to course builder
        add_filter('learndash_settings_fields', [$this, 'add_form_to_course_builder']);
        
        // Handle form completion
        add_action('gfc_form_submitted', [$this, 'mark_content_complete'], 10, 2);
        
        // Add form to LearnDash content types
        add_filter('learndash_content_types', [$this, 'register_form_content_type']);
    }

    public function add_form_post_type($post_types) {
        $post_types['gfc_form'] = [
            'name' => 'Form',
            'slug' => 'form',
            'taxonomies' => ['course', 'lesson', 'topic']
        ];
        return $post_types;
    }

    public function mark_content_complete($submission_id, $user_id) {
        $form_id = get_post_meta($submission_id, 'form_id', true);
        $course_id = learndash_get_course_id($form_id);
        
        if ($course_id) {
            learndash_process_mark_complete($user_id, $form_id, false, $course_id);
        }
    }

    public function register_form_content_type($content_types) {
        $content_types['gfc_form'] = [
            'label' => 'Form',
            'icon' => 'dashicons-clipboard',
            'supports' => ['completion']
        ];
        return $content_types;
    }
} 