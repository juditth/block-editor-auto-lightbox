<?php
/**
 * Lightbox Handler Class
 *
 * Handles the core lightbox functionality
 *
 * @package Block_Editor_Auto_Lightbox
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BEAL_Lightbox_Handler
 */
class BEAL_Lightbox_Handler
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Additional functionality can be added here
    }

    /**
     * Get lightbox configuration
     *
     * @return array Lightbox configuration
     */
    public static function get_config()
    {
        $options = get_option('beal_settings');

        return array(
            'selector' => '.glightbox',
            'touchNavigation' => isset($options['touch_navigation']) ? $options['touch_navigation'] : true,
            'loop' => isset($options['loop']) ? $options['loop'] : true,
            'autoplayVideos' => isset($options['autoplay_videos']) ? $options['autoplay_videos'] : true,
            'closeButton' => isset($options['close_button']) ? $options['close_button'] : true,
            'closeOnOutsideClick' => isset($options['close_on_outside_click']) ? $options['close_on_outside_click'] : true,
            'preload' => isset($options['preload']) ? $options['preload'] : true,
        );
    }

    /**
     * Check if lightbox is enabled
     *
     * @return bool
     */
    public static function is_enabled()
    {
        $options = get_option('beal_settings');
        return isset($options['enabled']) && $options['enabled'];
    }
}
