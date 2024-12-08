<?php
namespace GFC;

class GravityFormsConverter {
    private $form_generator;
    private $submission_handler;
    private $admin_interface;
    
    public function __construct() {
        // Initialize components
        $this->form_generator = new FormGenerator();
        $this->submission_handler = new SubmissionHandler();
        $this->admin_interface = new AdminInterface();
        
        // Register post types
        add_action('init', [$this, 'register_post_types']);
        
        // Add AJAX handlers
        add_action('wp_ajax_upload_gravity_forms_json', [$this, 'handle_json_upload']);
        add_action('wp_ajax_submit_converted_form', [$this->submission_handler, 'handle_submission']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    public function register_post_types() {
        // Register form post type
        register_post_type('gfc_form', [
            'labels' => [
                'name' => 'Forms',
                'singular_name' => 'Form',
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-forms'
        ]);
        
        // Register submission post type
        register_post_type('gfc_submission', [
            'labels' => [
                'name' => 'Form Submissions',
                'singular_name' => 'Form Submission',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => false,
            ],
            'map_meta_cap' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-clipboard'
        ]);
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'gfc-forms',
            GFC_PLUGIN_URL . 'assets/css/forms.css',
            [],
            GFC_VERSION
        );
        
        wp_enqueue_script(
            'gfc-forms',
            GFC_PLUGIN_URL . 'assets/js/forms.js',
            ['jquery'],
            GFC_VERSION,
            true
        );
        
        wp_localize_script('gfc-forms', 'gfcAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gfc_submission')
        ]);
    }
    
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        
        wp_enqueue_style(
            'gfc-admin',
            GFC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            GFC_VERSION
        );
        
        wp_enqueue_script(
            'gfc-admin',
            GFC_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            GFC_VERSION,
            true
        );
    }
    
    public function handle_json_upload() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permission denied');
        }
        
        // Verify nonce
        check_ajax_referer('gfc_upload', 'nonce');
        
        // Handle file upload
        $uploaded_file = $_FILES['gravity_forms_json'];
        if ($uploaded_file['type'] !== 'application/json') {
            wp_send_json_error('Invalid file type');
        }
        
        // Process JSON
        $json_content = file_get_contents($uploaded_file['tmp_name']);
        $this->form_generator->convert_gravity_forms_json($json_content);
        
        wp_send_json_success('Forms imported successfully');
    }
} 