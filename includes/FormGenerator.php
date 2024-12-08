<?php
namespace GFC;

class FormGenerator {
    public function convert_gravity_forms_json($json_data) {
        $form_data = json_decode($json_data, true);
        
        if (!$form_data) {
            throw new \Exception('Invalid JSON data');
        }

        foreach ($form_data as $form_id => $form) {
            $html = $this->generate_form_html($form);
            
            // Create new form post
            $post_data = [
                'post_title'   => $form['title'],
                'post_content' => $html,
                'post_status'  => 'publish',
                'post_type'    => 'gfc_form'
            ];
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                throw new \Exception('Failed to create form post: ' . $post_id->get_error_message());
            }
            
            // Store form metadata
            update_post_meta($post_id, 'form_description', $form['description']);
            update_post_meta($post_id, 'form_fields', $form['fields']);
            update_post_meta($post_id, 'form_button', $form['button']);
        }
    }

    private function generate_form_html($form) {
        ob_start();
        ?>
        <form class="gfc-form" method="post">
            <?php if (!empty($form['description'])): ?>
                <div class="form-description">
                    <?php echo esc_html($form['description']); ?>
                </div>
            <?php endif; ?>

            <?php
            foreach ($form['fields'] as $field) {
                $this->render_field($field);
            }
            ?>

            <div class="form-submit">
                <button type="submit" class="gfc-submit">
                    <?php echo esc_html($form['button']['text'] ?? 'Submit'); ?>
                </button>
            </div>
            
            <?php wp_nonce_field('gfc_submission', 'gfc_nonce'); ?>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_field($field) {
        $required = !empty($field['isRequired']) ? 'required' : '';
        $field_id = 'field_' . $field['id'];
        $css_class = !empty($field['cssClass']) ? $field['cssClass'] : '';
        
        echo '<div class="form-field ' . esc_attr($css_class) . '">';
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'tel':
            case 'url':
                ?>
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($required) echo '<span class="required">*</span>'; ?>
                </label>
                <input type="<?php echo esc_attr($field['type']); ?>"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($field_id); ?>"
                       placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                       <?php echo $required; ?>>
                <?php
                break;

            case 'date':
                ?>
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($required) echo '<span class="required">*</span>'; ?>
                </label>
                <input type="date"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($field_id); ?>"
                       <?php echo $required; ?>>
                <?php
                break;

            case 'textarea':
                ?>
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($required) echo '<span class="required">*</span>'; ?>
                </label>
                <textarea id="<?php echo esc_attr($field_id); ?>"
                         name="<?php echo esc_attr($field_id); ?>"
                         placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                         <?php echo $required; ?>></textarea>
                <?php
                break;

            case 'select':
                ?>
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($required) echo '<span class="required">*</span>'; ?>
                </label>
                <select id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_id); ?>"
                        <?php echo $required; ?>>
                    <?php foreach ($field['choices'] as $choice): ?>
                        <option value="<?php echo esc_attr($choice['value']); ?>">
                            <?php echo esc_html($choice['text']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                break;

            case 'radio':
            case 'checkbox':
                ?>
                <fieldset>
                    <legend>
                        <?php echo esc_html($field['label']); ?>
                        <?php if ($required) echo '<span class="required">*</span>'; ?>
                    </legend>
                    <?php foreach ($field['choices'] as $choice): ?>
                        <label class="choice-label">
                            <input type="<?php echo esc_attr($field['type']); ?>"
                                   name="<?php echo esc_attr($field_id . ($field['type'] === 'checkbox' ? '[]' : '')); ?>"
                                   value="<?php echo esc_attr($choice['value']); ?>"
                                   <?php echo $required; ?>>
                            <?php echo esc_html($choice['text']); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php
                break;

            case 'fileupload':
                ?>
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($required) echo '<span class="required">*</span>'; ?>
                </label>
                <input type="file"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($field_id); ?>"
                       accept="<?php echo esc_attr($field['allowedExtensions'] ?? ''); ?>"
                       <?php echo $required; ?>>
                <?php
                break;
        }

        if (!empty($field['description'])) {
            echo '<div class="field-description">' . esc_html($field['description']) . '</div>';
        }

        echo '</div>';
    }
}