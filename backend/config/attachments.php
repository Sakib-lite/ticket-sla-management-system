<?php

return [

    'disk' => env('ATTACHMENTS_DISK', 'local'),

    'max_files' => 5,

    'max_kilobytes' => 5120,

    'allowed_types' => ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'csv', 'docx', 'xlsx'],

];
