<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */

    'storage' => [
        'disk' => env('TINYFINDER_DISK', 'public'),
        'path' => env('TINYFINDER_PATH', 'tinyfinder'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'max_file_size' => env('TINYFINDER_MAX_FILE_SIZE', 128 * 1024 * 1024), // 128MB
        'max_image_size' => env('TINYFINDER_MAX_IMAGE_SIZE', 10 * 1024 * 1024), // 10MB

        'allowed_file_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', 'gz', '7z',
            'txt', 'csv', 'json', 'xml',
            'mp3', 'mp4', 'avi', 'mov',
        ],

        'allowed_image_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    */

    'images' => [
        'driver' => env('TINYFINDER_IMAGE_DRIVER', 'gd'), // gd or imagick

        'quality' => env('TINYFINDER_IMAGE_QUALITY', 90),

        'thumbnails' => [
            'enabled' => true,
            'sizes' => [
                'small' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 300, 'height' => 300],
                'large' => ['width' => 600, 'height' => 600],
            ],
            'fit' => 'contain', // contain, cover, fill
        ],

        'resize_types' => [
            1 => 'Standard Resizing',
            2 => 'Keep Aspect Ratio',
            3 => 'Crop from Center',
            4 => 'Fill with Background',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'upload' => 'tinyfinder.upload',
        'download' => 'tinyfinder.download',
        'delete' => 'tinyfinder.delete',
        'crop' => 'tinyfinder.crop',
        'resize' => 'tinyfinder.resize',
        'manage' => 'tinyfinder.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */

    'ui' => [
        'items_per_page' => 50,
        'default_view' => 'grid', // grid or list
        'show_file_preview' => true,
        'enable_drag_drop' => true,
        'enable_bulk_actions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy Settings
    |--------------------------------------------------------------------------
    */

    'privacy' => [
        'enable_private_uploads' => true,
        'private_uploads_visible_to_owner_only' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'delete_orphaned_files' => env('TINYFINDER_DELETE_ORPHANED', false),
        'orphaned_files_days' => env('TINYFINDER_ORPHANED_DAYS', 30),
    ],

];
