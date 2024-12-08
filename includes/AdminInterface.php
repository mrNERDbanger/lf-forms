<?php
namespace GFC;

class AdminInterface {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_filter('manage_gfc_submission_posts_columns', [$this, 'add_submission_columns']);
        add_action('manage_gfc_submission_posts_custom_column', [$this, 'display_submission_columns'], 10, 2);
        add_action('edit_user_profile', [$this, 'add_user_mentor_settings']);
        add_action('show_user_profile', [$this, 'add_user_mentor_settings']);
        add_action('personal_options_update', [$this, 'save_user_mentor_settings']);
        add_action('edit_user_profile_update', [$this, 'save_user_mentor_settings']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Form Submissions',
            'Form Submissions',
            'edit_posts',
            'gfc-submissions',
            [$this, 'render_submissions_page'],
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'gfc-submissions',
            'Import Forms',
            'Import Forms',
            'manage_options',
            'gfc-import',
            [$this, 'render_import_page']
        );

        add_submenu_page(
            'gfc-submissions',
            'Settings',
            'Settings',
            'manage_options',
            'gfc-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_submissions_page() {
        ?>
        <div class="wrap">
            <h1>Form Submissions</h1>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="form_filter">
                        <option value="">All Forms</option>
                        <?php
                        $forms = get_posts(['post_type' => 'gfc_form', 'numberposts' => -1]);
                        foreach ($forms as $form) {
                            echo sprintf(
                                '<option value="%d">%s</option>',
                                $form->ID,
                                esc_html($form->post_title)
                            );
                        }
                        ?>
                    </select>
                    <button class="button">Filter</button>
                </div>
            </div>
            <?php
            // Display submissions table
            $submissions_table = new SubmissionsTable();
            $submissions_table->prepare_items();
            $submissions_table->display();
            ?>
        </div>
        <?php
    }

    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1>Import Gravity Forms</h1>
            <div class="gfc-import-form">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('gfc_import', 'gfc_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gravity_forms_json">JSON File</label>
                            </th>
                            <td>
                                <input type="file" 
                                       name="gravity_forms_json" 
                                       id="gravity_forms_json" 
                                       accept=".json"
                                       required>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">Import Forms</button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (isset($_POST['gfc_settings_nonce']) && 
            wp_verify_nonce($_POST['gfc_settings_nonce'], 'gfc_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option('gfc_settings', []);
        ?>
        <div class="wrap">
            <h1>Form Converter Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('gfc_settings', 'gfc_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pdf_logo">PDF Logo</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gfc_settings[pdf_logo]" 
                                   id="pdf_logo"
                                   value="<?php echo esc_attr($settings['pdf_logo'] ?? ''); ?>"
                                   class="regular-text">
                            <button type="button" class="button media-upload">Select Image</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_template">Email Template</label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                $settings['email_template'] ?? '',
                                'email_template',
                                [
                                    'textarea_name' => 'gfc_settings[email_template]',
                                    'textarea_rows' => 10
                                ]
                            );
                            ?>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }

    public function add_submission_columns($columns) {
        $new_columns = [
            'cb' => $columns['cb'],
            'title' => 'Submission',
            'form' => 'Form',
            'user' => 'User',
            'submitted' => 'Submitted',
            'pdf' => 'PDF',
            'mentor' => 'Mentor',
            'big_bird' => 'Big Bird'
        ];
        return $new_columns;
    }

    public function display_submission_columns($column, $post_id) {
        $submission = get_post($post_id);
        $user_id = $submission->post_author;
        $form_id = get_post_meta($post_id, 'form_id', true);
        
        switch ($column) {
            case 'form':
                echo get_the_title($form_id);
                break;
                
            case 'user':
                $user = get_userdata($user_id);
                echo $user ? esc_html($user->display_name) : 'Unknown';
                break;
                
            case 'submitted':
                echo get_the_date('Y-m-d H:i:s', $post_id);
                break;
                
            case 'pdf':
                $pdf_path = get_post_meta($post_id, 'pdf_path', true);
                if ($pdf_path) {
                    printf(
                        '<a href="%s" class="button" target="_blank">Download PDF</a>',
                        wp_get_attachment_url($pdf_path)
                    );
                }
                break;
                
            case 'mentor':
                $mentor_id = get_user_meta($user_id, 'mentor_id', true);
                if ($mentor_id) {
                    $mentor = get_userdata($mentor_id);
                    echo $mentor ? esc_html($mentor->display_name) : 'None';
                }
                break;
                
            case 'big_bird':
                $big_bird_id = get_user_meta($user_id, 'big_bird_id', true);
                if ($big_bird_id) {
                    $big_bird = get_userdata($big_bird_id);
                    echo $big_bird ? esc_html($big_bird->display_name) : 'None';
                }
                break;
        }
    }

    public function add_user_mentor_settings($user) {
        if (!current_user_can('edit_users')) return;
        
        $mentors = get_users(['role' => 'mentor']);
        $big_birds = get_users(['role' => 'big_bird']);
        $current_mentor = get_user_meta($user->ID, 'mentor_id', true);
        $current_big_bird = get_user_meta($user->ID, 'big_bird_id', true);
        
        ?>
        <h3>Mentor Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="mentor_id">Mentor</label></th>
                <td>
                    <select name="mentor_id" id="mentor_id">
                        <option value="">Select Mentor</option>
                        <?php foreach ($mentors as $mentor): ?>
                            <option value="<?php echo esc_attr($mentor->ID); ?>" 
                                    <?php selected($current_mentor, $mentor->ID); ?>>
                                <?php echo esc_html($mentor->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="big_bird_id">Big Bird</label></th>
                <td>
                    <select name="big_bird_id" id="big_bird_id">
                        <option value="">Select Big Bird</option>
                        <?php foreach ($big_birds as $big_bird): ?>
                            <option value="<?php echo esc_attr($big_bird->ID); ?>"
                                    <?php selected($current_big_bird, $big_bird->ID); ?>>
                                <?php echo esc_html($big_bird->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_user_mentor_settings($user_id) {
        if (!current_user_can('edit_users')) return;
        
        if (isset($_POST['mentor_id'])) {
            update_user_meta($user_id, 'mentor_id', sanitize_text_field($_POST['mentor_id']));
        }
        
        if (isset($_POST['big_bird_id'])) {
            update_user_meta($user_id, 'big_bird_id', sanitize_text_field($_POST['big_bird_id']));
        }
    }

    private function save_settings() {
        if (!current_user_can('manage_options')) return;
        
        $settings = array_map('sanitize_text_field', $_POST['gfc_settings']);
        update_option('gfc_settings', $settings);
        add_settings_error(
            'gfc_settings',
            'settings_updated',
            'Settings saved successfully',
            'updated'
        );
    }
} 