<?php
/**
 * Neve Child Theme Functions
 */

// تحميل ملفات CSS للثيم الأب والثيم الابن
function neve_child_enqueue_styles() {
    // تحميل CSS الثيم الأب
    wp_enqueue_style( 'neve-parent-style', get_template_directory_uri() . '/style.css' );
    
    // تحميل CSS الثيم الابن
    wp_enqueue_style( 'neve-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'neve-parent-style' ),
        wp_get_theme()->get('Version')
    );
    
    // تحميل ملف التصحيحات الإضافية
    wp_enqueue_style( 'neve-child-container-fix',
        get_stylesheet_directory_uri() . '/custom-container-fix.css',
        array( 'neve-child-style' ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'neve_child_enqueue_styles', 20 );

// إزالة الفوتر المكرر
function neve_child_remove_duplicate_footer() {
    // إزالة أي فوتر إضافي
    remove_action( 'neve_after_footer_hook', 'neve_do_footer', 10 );
}
// add_action( 'init', 'neve_child_remove_duplicate_footer' );

// تصحيح عرض الكونتينر
function neve_child_fix_container_width() {
    ?>
    <style>
        /* تصحيح إضافي للكونتينر */
        .container {
            width: 100%;
            max-width: 1170px;
            margin: 0 auto;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 750px;
            }
        }
        
        @media (min-width: 992px) {
            .container {
                max-width: 970px;
            }
        }
        
        @media (min-width: 1200px) {
            .container {
                max-width: 1170px;
            }
        }
    </style>
    <?php
}
add_action( 'wp_head', 'neve_child_fix_container_width', 100 );
