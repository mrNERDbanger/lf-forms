<?php
namespace GFC;

class PDFTemplate {
    public static function generate($submission_id) {
        $submission = get_post($submission_id);
        $form_id = get_post_meta($submission_id, 'form_id', true);
        $form_data = get_post_meta($submission_id, 'form_data', true);
        $user = get_userdata($submission->post_author);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { max-width: 200px; margin-bottom: 20px; }
                .submission-info { margin-bottom: 30px; }
                .form-data { margin-bottom: 30px; }
                .footer { text-align: center; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px; border: 1px solid #ddd; }
                th { background-color: #f5f5f5; }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="<?php echo get_option('gfc_pdf_logo'); ?>" class="logo">
                <h1><?php echo get_the_title($form_id); ?></h1>
            </div>
            
            <div class="submission-info">
                <table>
                    <tr>
                        <th>Submitted By</th>
                        <td><?php echo $user->display_name; ?></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td><?php echo get_the_date('F j, Y g:i a', $submission_id); ?></td>
                    </tr>
                    <tr>
                        <th>Mentor</th>
                        <td><?php echo get_userdata(get_user_meta($user->ID, 'mentor_id', true))->display_name ?? 'Not Assigned'; ?></td>
                    </tr>
                    <tr>
                        <th>Big Bird</th>
                        <td><?php echo get_userdata(get_user_meta($user->ID, 'big_bird_id', true))->display_name ?? 'Not Assigned'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="form-data">
                <h2>Form Responses</h2>
                <table>
                    <?php foreach ($form_data as $field_id => $value): ?>
                        <tr>
                            <th><?php echo self::get_field_label($form_id, $field_id); ?></th>
                            <td><?php echo is_array($value) ? implode(', ', $value) : $value; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="footer">
                <p>Generated on <?php echo current_time('F j, Y g:i a'); ?></p>
                <p><?php echo get_bloginfo('name'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private static function get_field_label($form_id, $field_id) {
        $form_fields = get_post_meta($form_id, 'form_fields', true);
        return $form_fields[$field_id]['label'] ?? $field_id;
    }
} 