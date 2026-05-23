<?php
/**
 * Lestra Vida - Media Optimization
 */

if (!defined('ABSPATH')) exit;

add_filter(
    'big_image_size_threshold',
    function ($threshold, $imagesize, $file, $attachment_id) {
        return 1024;
    },
    10,
    4
);