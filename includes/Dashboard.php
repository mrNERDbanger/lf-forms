<?php
namespace GFC;

class Dashboard {
    public function __construct() {
        add_shortcode('gfc_dashboard', [$this, 'render_dashboard']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_styles']);
    }

    public function enqueue_dashboard_styles() {
        wp_enqueue_style(
            'gfc-dashboard-style',
            GFC_PLUGIN_URL . 'assets/css/dashboard.css',
            [],
            GFC_VERSION
        );
    }

    private function get_student_tier($hours) {
        $tiers = [
            "Master Certified Fearless Living Coach" => "MCFLC",
            "Certified Fearless Trainer" => "CFT",
            "Advanced Certified Fearless Living Coach" => "ACFLC",
            "Certified Fearless Living Coach" => "CFLC"
        ];

        if ($hours >= 500) {
            $full = "Master Certified Fearless Living Coach";
        } elseif ($hours >= 250) {
            $full = "Certified Fearless Trainer";
        } elseif ($hours >= 150) {
            $full = "Advanced Certified Fearless Living Coach";
        } else {
            $full = "Certified Fearless Living Coach";
        }

        return ['full' => $full, 'abbr' => $tiers[$full]];
    }

    private function get_student_data($user_id) {
        $user = get_userdata($user_id);
        $hours = get_user_meta($user_id, 'coaching_hours', true) ?: 0;
        $mentor_id = get_user_meta($user_id, 'mentor_id', true);
        $big_bird_id = get_user_meta($user_id, 'big_bird_id', true);
        
        // Get enrolled courses
        $courses = learndash_user_get_enrolled_courses($user_id, []);
        $course_progress = [];
        
        foreach ($courses as $course_id) {
            $progress = learndash_course_progress([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'array' => true
            ]);
            
            $course_progress[] = [
                'name' => get_the_title($course_id),
                'progress' => $progress['percentage']
            ];
        }

        // Get current unit and last lesson
        $current_unit = get_user_meta($user_id, 'current_unit', true) ?: 'Not Started';
        $last_lesson = get_user_meta($user_id, 'last_lesson', true) ?: 'None';

        return [
            'name' => $user->display_name,
            'hours' => $hours,
            'unit' => $current_unit,
            'lastLesson' => $last_lesson,
            'mentor' => get_userdata($mentor_id)->display_name ?? 'Not Assigned',
            'bigBird' => get_userdata($big_bird_id)->display_name ?? 'Not Assigned',
            'courses' => $course_progress
        ];
    }

    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return 'Please log in to view the dashboard.';
        }

        $user = wp_get_current_user();
        $role = $this->get_highest_role($user);

        ob_start();
        ?>
        <div class="gfc-dashboard">
            <h2>Student Progress Dashboard</h2>
            
            <div class="dashboard-container">
                <?php
                $students = [];
                
                switch ($role) {
                    case 'administrator':
                        $students = get_users(['role' => 'subscriber']);
                        break;
                    case 'mentor':
                        $students = get_users([
                            'meta_key' => 'mentor_id',
                            'meta_value' => get_current_user_id()
                        ]);
                        break;
                    case 'big_bird':
                        $students = get_users([
                            'meta_key' => 'big_bird_id',
                            'meta_value' => get_current_user_id()
                        ]);
                        break;
                }

                foreach ($students as $student) {
                    $student_data = $this->get_student_data($student->ID);
                    $tier = $this->get_student_tier($student_data['hours']);
                    ?>
                    <div class="student-card">
                        <div class="student-header">
                            <div class="student-avatar">
                                <?php echo strtoupper($student_data['name'][0]); ?>
                            </div>
                            <div>
                                <div class="student-name"><?php echo esc_html($student_data['name']); ?></div>
                                <div class="student-status">
                                    <?php echo esc_html($tier['abbr']); ?>
                                    <span class="tooltip"><?php echo esc_html($tier['full']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="student-info">
                            <strong>Current Unit:</strong> <?php echo esc_html($student_data['unit']); ?><br>
                            <strong>Last Lesson:</strong> <?php echo esc_html($student_data['lastLesson']); ?><br>
                            <strong>Mentor:</strong> <?php echo esc_html($student_data['mentor']); ?><br>
                            <strong>Big Bird:</strong> <?php echo esc_html($student_data['bigBird']); ?><br>
                            <strong>Courses:</strong>
                            <ul>
                                <?php foreach ($student_data['courses'] as $course): ?>
                                    <li>
                                        <?php echo esc_html($course['name']); ?>: <?php echo $course['progress']; ?>%
                                        <div class="progress-bar">
                                            <span style="width: <?php echo $course['progress']; ?>%"></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="student-actions">
                            <a href="<?php echo admin_url('admin.php?page=gfc-submissions&student=' . $student->ID); ?>" 
                               class="button">View Submissions</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_highest_role($user) {
        if (in_array('administrator', $user->roles)) return 'administrator';
        if (in_array('mentor', $user->roles)) return 'mentor';
        if (in_array('big_bird', $user->roles)) return 'big_bird';
        return 'subscriber';
    }
} 