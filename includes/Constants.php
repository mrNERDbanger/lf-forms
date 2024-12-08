<?php
namespace GFC;

class Constants {
    // Plugin version
    const VERSION = '1.0.0';
    
    // Database version for migrations
    const DB_VERSION = '1.0.0';
    
    // Capability definitions
    const CAPABILITIES = [
        'view_forms' => 'view_forms',
        'view_courses' => 'view_courses',
        'edit_custom_forms' => 'edit_custom_forms',
        'view_submissions' => 'view_submissions',
        'view_quiz_results' => 'view_quiz_results',
        'view_course_progress' => 'view_course_progress'
    ];
    
    // Form field types
    const FIELD_TYPES = [
        'text', 'textarea', 'select', 'radio', 'checkbox', 
        'email', 'date', 'time', 'phone', 'url', 'fileupload',
        'hidden', 'captcha', 'name', 'number', 'list', 'html',
        'password', 'section'
    ];
    
    // PDF settings
    const PDF_OPTIONS = [
        'page_size' => 'A4',
        'orientation' => 'P',
        'margins' => [
            'top' => 15,
            'right' => 15,
            'bottom' => 15,
            'left' => 15
        ]
    ];
    
    // Email settings
    const EMAIL_TEMPLATES = [
        'submission' => 'submission-notification',
        'quiz_completion' => 'quiz-completion',
        'course_completion' => 'course-completion'
    ];
    
    // Upload directory
    const UPLOAD_DIR = 'form-submissions';
    
    // Allowed file types
    const ALLOWED_FILE_TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
} 