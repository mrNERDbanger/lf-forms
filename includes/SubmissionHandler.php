<?php
namespace GFC;

class SubmissionHandler {
    public function handle_submission() {
        // Verify nonce
        check_ajax_referer('gfc_submission', 'gfc_nonce');
        
        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        // Sanitize and validate form data
        $form_data = $this->sanitize_form_data($_POST);
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        // Handle file uploads
        $uploaded_files = $this->handle_file_uploads($_FILES);
        $form_data['uploaded_files'] = $uploaded_files;
        
        // Create submission post
        $submission_id = wp_insert_post([
            'post_type' => 'gfc_submission',
            'post_title' => sprintf(
                '%s - %s - %s',
                get_userdata($user_id)->display_name,
                get_the_title($form_id),
                current_time('mysql')
            ),
            'post_status' => 'publish',
            'post_author' => $user_id,
            'meta_input' => [
                'form_id' => $form_id,
                'form_data' => $form_data,
                'submission_date' => current_time('mysql')
            ]
        ]);
        
        if (is_wp_error($submission_id)) {
            wp_send_json_error('Failed to save submission');
        }
        
        // Generate PDF
        $pdf_path = $this->generate_pdf($submission_id);
        update_post_meta($submission_id, 'pdf_path', $pdf_path);
        
        // Mark LearnDash content complete
        do_action('gfc_form_submitted', $submission_id, $user_id);
        
        // Send notifications
        $this->send_notifications($submission_id);
        
        // Return success response
        wp_send_json_success([
            'message' => 'Form submitted successfully',
            'submission_id' => $submission_id,
            'pdf_url' => wp_get_attachment_url($pdf_path)
        ]);
    }
    
    private function sanitize_form_data($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        return $sanitized;
    }
    
    private function handle_file_uploads($files) {
        $uploaded = [];
        
        if (empty($files)) {
            return $uploaded;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        foreach ($files as $field_name => $file) {
            $attachment_id = media_handle_upload($field_name, 0);
            if (!is_wp_error($attachment_id)) {
                $uploaded[$field_name] = $attachment_id;
            }
        }
        
        return $uploaded;
    }
    
    private function generate_pdf($submission_id) {
        require_once(GFC_PLUGIN_DIR . 'vendor/autoload.php');
        
        $submission = get_post($submission_id);
        $form_data = get_post_meta($submission_id, 'form_data', true);
        
        // Create PDF using TCPDF
        $pdf = new \TCPDF();
        $pdf->SetCreator(get_bloginfo('name'));
        $pdf->SetAuthor(get_userdata($submission->post_author)->display_name);
        $pdf->SetTitle($submission->post_title);
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        // Add content to PDF
        $pdf->Cell(0, 10, $submission->post_title, 0, 1, 'C');
        foreach ($form_data as $field => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $pdf->Cell(60, 10, ucfirst(str_replace('_', ' ', $field)) . ':', 0);
            $pdf->Cell(0, 10, $value, 0, 1);
        }
        
        // Save PDF
        $upload_dir = wp_upload_dir();
        $pdf_path = sprintf(
            '%s/form-submissions/%s.pdf',
            $upload_dir['basedir'],
            $submission_id
        );
        
        wp_mkdir_p(dirname($pdf_path));
        $pdf->Output($pdf_path, 'F');
        
        // Create attachment
        return wp_insert_attachment([
            'post_mime_type' => 'application/pdf',
            'post_title' => $submission->post_title,
            'post_content' => '',
            'post_status' => 'inherit'
        ], $pdf_path);
    }
    
    private function send_notifications($submission_id) {
        $submission = get_post($submission_id);
        $user_id = $submission->post_author;
        
        // Get mentor and big bird
        $mentor_id = get_user_meta($user_id, 'mentor_id', true);
        $big_bird_id = get_user_meta($user_id, 'big_bird_id', true);
        
        $pdf_path = get_post_meta($submission_id, 'pdf_path', true);
        $pdf_url = wp_get_attachment_url($pdf_path);
        
        $subject = sprintf('New Form Submission: %s', $submission->post_title);
        $message = $this->get_notification_message($submission_id, $pdf_url);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // Send to mentor
        if ($mentor_id) {
            $mentor = get_userdata($mentor_id);
            wp_mail($mentor->user_email, $subject, $message, $headers);
        }
        
        // Send to big bird
        if ($big_bird_id) {
            $big_bird = get_userdata($big_bird_id);
            wp_mail($big_bird->user_email, $subject, $message, $headers);
        }
    }
    
    private function get_notification_message($submission_id, $pdf_url) {
        $submission = get_post($submission_id);
        $user = get_userdata($submission->post_author);
        
        ob_start();
        ?>
        <h2>New Form Submission</h2>
        <p>A new form has been submitted by <?php echo esc_html($user->display_name); ?>.</p>
        
        <p><strong>Form:</strong> <?php echo esc_html(get_the_title(get_post_meta($submission_id, 'form_id', true))); ?></p>
        <p><strong>Date:</strong> <?php echo get_the_date('F j, Y g:i a', $submission_id); ?></p>
        
        <p>
            <a href="<?php echo esc_url(admin_url('post.php?post=' . $submission_id . '&action=edit')); ?>">
                View Submission
            </a>
            |
            <a href="<?php echo esc_url($pdf_url); ?>">
                Download PDF
            </a>
        </p>
        <?php
        return ob_get_clean();
    }
}