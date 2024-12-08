<div class="wrap">
    <h1>Form Converter Settings</h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('gfc_options');
        do_settings_sections('gfc_options');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">PDF Logo</th>
                <td>
                    <input type="text" name="gfc_settings[pdf_logo]" value="<?php echo esc_attr(get_option('gfc_pdf_logo')); ?>" class="regular-text">
                    <button type="button" class="button media-upload">Choose Logo</button>
                    <p class="description">Logo to display on PDF exports</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Email Settings</th>
                <td>
                    <label>
                        <input type="checkbox" name="gfc_settings[notify_mentor]" value="1" 
                            <?php checked(get_option('gfc_notify_mentor'), 1); ?>>
                        Notify Mentor on submission
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="gfc_settings[notify_big_bird]" value="1" 
                            <?php checked(get_option('gfc_notify_big_bird'), 1); ?>>
                        Notify Big Bird on submission
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">LearnDash Integration</th>
                <td>
                    <label>
                        <input type="checkbox" name="gfc_settings[mark_complete]" value="1" 
                            <?php checked(get_option('gfc_mark_complete'), 1); ?>>
                        Mark lesson/topic complete on form submission
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="gfc_settings[notify_quiz]" value="1" 
                            <?php checked(get_option('gfc_notify_quiz'), 1); ?>>
                        Notify on quiz completion
                    </label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>