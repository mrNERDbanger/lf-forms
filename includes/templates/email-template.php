<?php
namespace GFC;

class EmailTemplate {
    public static function get_submission_notification($submission_id, $recipient_type) {
        $submission = get_post($submission_id);
        $user = get_userdata($submission->post_author);
        $form_id = get_post_meta($submission_id, 'form_id', true);
        $pdf_url = wp_get_attachment_url(get_post_meta($submission_id, 'pdf_path', true));
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .content { margin-bottom: 30px; }
                .button { 
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                }
                .footer { text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>New Form Submission</h1>
                </div>
                
                <div class="content">
                    <p>Hello <?php echo $recipient_type; ?>,</p>
                    
                    <p><?php echo $user->display_name; ?> has submitted a new form response.</p>
                    
                    <p><strong>Form:</strong> <?php echo get_the_title($form_id); ?><br>
                    <strong>Date:</strong> <?php echo get_the_date('F j, Y g:i a', $submission_id); ?></p>
                    
                    <p>
                        <a href="<?php echo $pdf_url; ?>" class="button">Download PDF</a>
                        <a href="<?php echo admin_url('admin.php?page=gfc-submissions&submission=' . $submission_id); ?>" class="button">
                            View Submission
                        </a>
                    </p>
                </div>
                
                <div class="footer">
                    <p>This is an automated message from <?php echo get_bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
} 