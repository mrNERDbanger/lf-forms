<?php
/*
Plugin Name: Gravity Forms Converter
Description: Converts Gravity Forms JSON exports to WordPress custom post types with advanced forwarding
Version: 1.0
Author: Your Name
*/

class GravityFormsConverter {
    public function __construct() {
        add_action('init', [$this, 'register_form_post_type']);
        add_action('add_meta_boxes', [$this, 'add_forwarding_meta_box']);
        add_action('save_post', [$this, 'save_forwarding_settings']);
        add_action('manage_form_posts_columns', [$this, 'add_forwarding_column']);
        add_action('manage_form_posts_custom_column', [$this, 'display_forwarding_column'], 10, 2);
        add_action('wp_ajax_upload_gravity_forms_json', [$this, 'handle_json_upload']);
    }

    public function register_form_post_type() {
        register_post_type('form', [
            'labels' => [
                'name' => 'Forms',
                'singular_name' => 'Form',
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-forms'
        ]);
    }

    public function add_forwarding_meta_box() {
        add_meta_box(
            'form_forwarding_settings',
            'Form Forwarding',
            [$this, 'render_forwarding_meta_box'],
            'form',
            'side',
            'default'
        );
    }

    public function render_forwarding_meta_box($post) {
        $forwarding_enabled = get_post_meta($post->ID, 'form_forwarding_enabled', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="form_forwarding_enabled" 
                   value="1" 
                   <?php checked(1, $forwarding_enabled, true); ?> />
            Enable Form Forwarding
        </label>
        <?php
    }

    public function save_forwarding_settings($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        
        $forwarding_enabled = isset($_POST['form_forwarding_enabled']) ? 1 : 0;
        update_post_meta($post_id, 'form_forwarding_enabled', $forwarding_enabled);
    }

    public function add_forwarding_column($columns) {
        $columns['form_forwarding'] = 'Forwarding';
        return $columns;
    }

    public function display_forwarding_column($column, $post_id) {
        if ($column === 'form_forwarding') {
            $forwarding_enabled = get_post_meta($post_id, 'form_forwarding_enabled', true);
            echo $forwarding_enabled 
                ? '<div style="background-color: green; color: white; border-radius: 15px; padding: 5px; text-align: center;">FORWARDING ON</div>' 
                : '';
        }
    }

    public function convert_gravity_forms_json($json_data) {
        $form_data = json_decode($json_data, true);
        
        foreach ($form_data as $form_id => $form_details) {
            $form_html = $this->generate_form_html($form_details['fields']);
            
            $new_form_post = [
                'post_title' => $form_details['title'] ?? 'Untitled Form',
                'post_type' => 'form',
                'post_status' => 'publish',
                'meta_input' => [
                    'original_form_id' => $form_id,
                    'form_html' => $form_html
                ]
            ];

            wp_insert_post($new_form_post);
        }
    }

    public function generate_form_html($fields) {
        $form_html = "<form method='post' action='" . esc_url(admin_url('admin-ajax.php')) . "'>\n";
        $form_html .= "<input type='hidden' name='action' value='submit_converted_form'>\n";
        
        foreach ($fields as $field) {
            $field_id = $field['id'];
            $label = $field['label'] ?? '';
            $required = $field['required'] ? 'required' : '';
            
            $form_html .= "<div class='form-field'>\n";
            
            switch ($field['type']) {
                case 'email':
                    $form_html .= $this->generate_email_input($field_id, $label, $required);
                    break;
                    
                case 'phone':
                    $form_html .= $this->generate_phone_input($field_id, $label, $required);
                    break;
                    
                case 'date':
                    $form_html .= $this->generate_date_input($field_id, $label, $required);
                    break;
                    
                case 'time':
                    $form_html .= $this->generate_time_input($field_id, $label, $required);
                    break;
                    
                case 'url':
                    $form_html .= $this->generate_url_input($field_id, $label, $required);
                    break;
                    
                case 'name':
                    $form_html .= $this->generate_name_input($field_id, $label, $required);
                    break;
                    
                case 'address':
                    $form_html .= $this->generate_address_input($field_id, $label, $required);
                    break;
                    
                case 'list':
                    $columns = $field['columns'] ?? array();
                    $form_html .= $this->generate_list_input($field_id, $label, $columns, $required);
                    break;
                    
                case 'multiselect':
                    $choices = $field['choices'] ?? array();
                    $form_html .= $this->generate_multiselect($field_id, $label, $choices, $required);
                    break;
                    
                case 'number':
                    $form_html .= $this->generate_number_input($field_id, $label, $required);
                    break;
                    
                case 'captcha':
                    $form_html .= $this->generate_captcha();
                    break;
                    
                case 'html':
                    $form_html .= $this->generate_html_field($field['content'] ?? '');
                    break;
                    
                case 'section':
                    $form_html .= $this->generate_section_break($label);
                    break;
                    
                case 'pagebreak':
                    $form_html .= $this->generate_page_break();
                    break;
                    
                case 'radio':
                    $choices = $field['choices'] ?? array();
                    $form_html .= $this->generate_radio($field_id, $label, $choices, $required);
                    break;
                    
                case 'checkbox':
                    $choices = $field['choices'] ?? array();
                    $form_html .= $this->generate_checkboxes($field_id, $label, $choices, $required);
                    break;
                    
                case 'hidden':
                    $default_value = $field['default_value'] ?? '';
                    $form_html .= $this->generate_hidden_input($field_id, $default_value);
                    break;
                    
                // Fallback for basic text input
                default:
                    $form_html .= sprintf(
                        "  <label for='field-%s'>%s</label>\n" .
                        "  <input type='text' id='field-%s' name='field-%s' %s>\n",
                        $field_id, esc_html($label),
                        $field_id, $field_id, $required
                    );
                    break;
            }
            
            $form_html .= "</div>\n";
        }

        $form_html .= "  <button type='submit'>Submit</button>\n</form>";
        return $form_html;
    }

    public function handle_json_upload() {
        // Handle JSON file upload via AJAX
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permission denied');
        }

        $uploaded_file = $_FILES['gravity_forms_json'];
        
        if ($uploaded_file['type'] !== 'application/json') {
            wp_send_json_error('Invalid file type');
        }

        $json_content = file_get_contents($uploaded_file['tmp_name']);
        $this->convert_gravity_forms_json($json_content);

        wp_send_json_success('Forms imported successfully');
    }

    public function handle_form_submission() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'submit_converted_form') {
            return;
        }

        // Verify nonce and other security checks here
        
        $form_data = $_POST;
        $post_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        // Check if forwarding is enabled for this form
        $forwarding_enabled = get_post_meta($post_id, 'form_forwarding_enabled', true);
        
        if ($forwarding_enabled) {
            // Generate PDF
            require_once(ABSPATH . 'wp-content/plugins/gravity-forms-converter/vendor/tecnickcom/tcpdf/tcpdf.php');
            
            $pdf = new TCPDF();
            $pdf->AddPage();
            
            // Add form data to PDF
            foreach ($form_data as $field => $value) {
                if ($field !== 'action' && $field !== 'form_id') {
                    $pdf->Cell(0, 10, "$field: $value", 0, 1);
                }
            }
            
            $pdf_file = $pdf->Output('', 'S');
            
            // Send email with PDF attachment
            $to = get_option('admin_email');
            $subject = 'New Form Submission';
            $message = 'Please find the form submission attached.';
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            wp_mail($to, $subject, $message, $headers, array(
                array(
                    'name' => 'form-submission.pdf',
                    'content' => $pdf_file,
                    'type' => 'application/pdf'
                )
            ));
        }
        
        // Redirect or show success message
        wp_send_json_success('Form submitted successfully');
    }
}

new GravityFormsConverter();
