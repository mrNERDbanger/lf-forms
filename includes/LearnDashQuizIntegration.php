<?php
namespace GFC;

class LearnDashQuizIntegration {
    public function __construct() {
        add_filter('learndash_show_course_progress', [$this, 'show_course_progress']);
        add_action('learndash_after_quiz_attempt', [$this, 'handle_quiz_attempt'], 10, 4);
        add_action('learndash_course_completed', [$this, 'handle_course_completion'], 10, 2);
    }

    public function show_course_progress($show) {
        if (current_user_can('view_course_progress')) {
            return true;
        }
        return $show;
    }

    public function handle_quiz_attempt($quiz_attempt, $user_id, $quiz_id, $course_id) {
        if (!current_user_can('view_quiz_results')) {
            return;
        }

        // Store quiz results in user meta
        $quiz_results = get_user_meta($user_id, 'quiz_results', true) ?: [];
        $quiz_results[$quiz_id] = [
            'attempt' => $quiz_attempt,
            'date' => current_time('mysql'),
            'course_id' => $course_id
        ];
        update_user_meta($user_id, 'quiz_results', $quiz_results);

        // Notify mentor and big bird
        $this->notify_mentors($user_id, $quiz_id, $quiz_attempt);
    }

    public function handle_course_completion($user_id, $course_id) {
        if (!current_user_can('view_course_progress')) {
            return;
        }

        // Store course completion in user meta
        $course_progress = get_user_meta($user_id, 'course_progress', true) ?: [];
        $course_progress[$course_id] = [
            'completed' => true,
            'date' => current_time('mysql')
        ];
        update_user_meta($user_id, 'course_progress', $course_progress);

        // Notify mentor and big bird
        $this->notify_mentors($user_id, $course_id, null, true);
    }

    private function notify_mentors($user_id, $content_id, $quiz_attempt = null, $is_course = false) {
        $mentor_id = get_user_meta($user_id, 'mentor_id', true);
        $big_bird_id = get_user_meta($user_id, 'big_bird_id', true);
        $user = get_userdata($user_id);

        $subject = sprintf(
            '%s has %s %s',
            $user->display_name,
            $is_course ? 'completed' : 'attempted',
            $is_course ? 'a course' : 'a quiz'
        );

        $message = $this->get_notification_message($user, $content_id, $quiz_attempt, $is_course);
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

    private function get_notification_message($user, $content_id, $quiz_attempt, $is_course) {
        ob_start();
        ?>
        <h2><?php echo $user->display_name; ?>'s Progress Update</h2>
        <?php if ($is_course): ?>
            <p>Course Completed: <?php echo get_the_title($content_id); ?></p>
            <p>Completion Date: <?php echo current_time('F j, Y g:i a'); ?></p>
        <?php else: ?>
            <p>Quiz Attempted: <?php echo get_the_title($content_id); ?></p>
            <p>Score: <?php echo $quiz_attempt['score']; ?>%</p>
            <p>Attempt Date: <?php echo current_time('F j, Y g:i a'); ?></p>
        <?php endif; ?>
        <p>
            <a href="<?php echo admin_url('admin.php?page=mentor-dashboard&user_id=' . $user->ID); ?>">
                View Full Progress
            </a>
        </p>
        <?php
        return ob_get_clean();
    }
} 