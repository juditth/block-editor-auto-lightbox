<?php
/**
 * Plugin Name: Block Editor Auto Lightbox
 * Plugin URI:  https://github.com/juditth/block-editor-auto-lightbox/
 * Description: Automatically adds a lightbox to images in WordPress blocks with support for lazy loading and original image URLs.
 * Version:     1.0.6
 * Author:      Jitka Klingenbergová
 * Author URI:  https://vyladeny-web.cz/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: block-editor-auto-lightbox
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Stable Tag: 1.0.6
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BEAL_VERSION', '1.0.6');
define('BEAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BEAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BEAL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin Update Checker
 */
require 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$bealUpdateChecker = PucFactory::buildUpdateChecker(
    'https://vyladeny-web.cz/plugins/block-editor-auto-lightbox/info.json',
    __FILE__,
    'block-editor-auto-lightbox'
);

/**
 * Main plugin class
 */
class Block_Editor_Auto_Lightbox
{

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->load_dependencies();

        // Ensure settings are always up to date
        add_action('plugins_loaded', array($this, 'maybe_upgrade_settings'));
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Settings link on plugins page
        add_filter('plugin_action_links_' . BEAL_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies()
    {
        require_once BEAL_PLUGIN_DIR . 'includes/class-lightbox-handler.php';
    }


    /**
     * Get default settings
     */
    private function get_default_settings()
    {
        return array(
            'enabled' => true,
            'auto_wp_image' => true,
            'auto_wp_gallery' => true,
            'block_selector' => '',
            'group_page_images' => false,
            'touch_navigation' => true,
            'loop' => true,
            'autoplay_videos' => true,
            'close_button' => true,
            'close_on_outside_click' => true,
            'preload' => true,
        );
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        $this->maybe_upgrade_settings();
    }

    /**
     * Check and upgrade settings if needed
     */
    private function maybe_upgrade_settings()
    {
        $current_version = get_option('beal_version', '0.0.0');
        $defaults = $this->get_default_settings();

        // Get existing settings or empty array
        $existing_settings = get_option('beal_settings', array());

        // Merge with defaults (existing values take precedence)
        $updated_settings = array_merge($defaults, is_array($existing_settings) ? $existing_settings : array());

        // Update settings
        update_option('beal_settings', $updated_settings);

        // Update version
        update_option('beal_version', BEAL_VERSION);
    }


    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Cleanup if needed
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            esc_html__('Auto Lightbox Settings', 'block-editor-auto-lightbox'),
            esc_html__('Auto Lightbox', 'block-editor-auto-lightbox'),
            'manage_options',
            'block-editor-auto-lightbox',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings()
    {
        register_setting('beal_settings_group', 'beal_settings', array($this, 'sanitize_settings'));

        // General Settings Section
        add_settings_section(
            'beal_general_section',
            esc_html__('General Settings', 'block-editor-auto-lightbox'),
            array($this, 'general_section_callback'),
            'block-editor-auto-lightbox'
        );

        // Enable/Disable
        add_settings_field(
            'enabled',
            esc_html__('Enable Lightbox', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_general_section',
            array('field' => 'enabled', 'label' => esc_html__('Enable automatic lightbox for images', 'block-editor-auto-lightbox'))
        );

        // Standard Block Support
        add_settings_field(
            'auto_wp_image',
            esc_html__('Support Standard Images', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_general_section',
            array(
                'field' => 'auto_wp_image',
                'label' => esc_html__('Automatically target standard WordPress Image blocks (.wp-block-image)', 'block-editor-auto-lightbox')
            )
        );

        add_settings_field(
            'auto_wp_gallery',
            esc_html__('Support Standard Galleries', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_general_section',
            array(
                'field' => 'auto_wp_gallery',
                'label' => esc_html__('Automatically target standard WordPress Gallery blocks (.wp-block-gallery)', 'block-editor-auto-lightbox')
            )
        );

        // Custom Block Selector
        add_settings_field(
            'block_selector',
            esc_html__('Custom Block Selectors', 'block-editor-auto-lightbox'),
            array($this, 'textarea_field_callback'),
            'block-editor-auto-lightbox',
            'beal_general_section',
            array('field' => 'block_selector', 'description' => esc_html__('Enter CSS selectors for any other custom blocks or containers (one per line or comma-separated)', 'block-editor-auto-lightbox'))
        );

        // Grouping Options

        add_settings_field(
            'group_page_images',
            esc_html__('Global Page Gallery', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_general_section',
            array('field' => 'group_page_images', 'label' => esc_html__('Merge ALL images on the page into a single global lightbox gallery', 'block-editor-auto-lightbox'))
        );

        // Lightbox Behavior Section
        add_settings_section(
            'beal_behavior_section',
            esc_html__('Lightbox Behavior', 'block-editor-auto-lightbox'),
            array($this, 'behavior_section_callback'),
            'block-editor-auto-lightbox'
        );

        // Loop
        add_settings_field(
            'loop',
            esc_html__('Loop Gallery', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_behavior_section',
            array('field' => 'loop', 'label' => esc_html__('Enable infinite loop navigation', 'block-editor-auto-lightbox'))
        );

        // Touch Navigation
        add_settings_field(
            'touch_navigation',
            esc_html__('Touch Navigation', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_behavior_section',
            array('field' => 'touch_navigation', 'label' => esc_html__('Enable touch/swipe navigation', 'block-editor-auto-lightbox'))
        );

        // Close on Outside Click
        add_settings_field(
            'close_on_outside_click',
            esc_html__('Close on Outside Click', 'block-editor-auto-lightbox'),
            array($this, 'checkbox_field_callback'),
            'block-editor-auto-lightbox',
            'beal_behavior_section',
            array('field' => 'close_on_outside_click', 'label' => esc_html__('Close lightbox when clicking outside', 'block-editor-auto-lightbox'))
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        $sanitized['enabled'] = isset($input['enabled']) ? true : false;
        $sanitized['auto_wp_image'] = isset($input['auto_wp_image']) ? true : false;
        $sanitized['auto_wp_gallery'] = isset($input['auto_wp_gallery']) ? true : false;
        $sanitized['block_selector'] = sanitize_textarea_field($input['block_selector']);
        $sanitized['group_page_images'] = isset($input['group_page_images']) ? true : false;
        $sanitized['touch_navigation'] = isset($input['touch_navigation']) ? true : false;
        $sanitized['loop'] = isset($input['loop']) ? true : false;
        $sanitized['autoplay_videos'] = isset($input['autoplay_videos']) ? true : false;
        $sanitized['close_button'] = isset($input['close_button']) ? true : false;
        $sanitized['close_on_outside_click'] = isset($input['close_on_outside_click']) ? true : false;
        $sanitized['preload'] = isset($input['preload']) ? true : false;

        return $sanitized;
    }

    /**
     * Section callbacks
     */
    public function general_section_callback()
    {
        echo '<p>' . esc_html__('Configure the basic lightbox settings and target blocks.', 'block-editor-auto-lightbox') . '</p>';
    }

    public function behavior_section_callback()
    {
        echo '<p>' . esc_html__('Customize how the lightbox behaves when opened.', 'block-editor-auto-lightbox') . '</p>';
    }

    /**
     * Field callbacks
     */
    public function checkbox_field_callback($args)
    {
        $options = get_option('beal_settings');
        $field = $args['field'];
        $label = $args['label'];
        $value = isset($options[$field]) ? $options[$field] : 0;

        echo '<label>';
        echo '<input type="checkbox" name="beal_settings[' . esc_attr($field) . ']" value="1" ' . checked($value, 1, false) . '>';
        echo ' ' . esc_html($label);
        echo '</label>';
    }

    public function textarea_field_callback($args)
    {
        $options = get_option('beal_settings');
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $value = isset($options[$field]) ? $options[$field] : '';

        echo '<textarea name="beal_settings[' . esc_attr($field) . ']" rows="5" cols="50" class="large-text code">' . esc_textarea($value) . '</textarea>';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        //settings_errors('beal_messages');
        ?>
        <div class="wrap beal-settings-wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('beal_settings_group');
                do_settings_sections('block-editor-auto-lightbox');
                submit_button(esc_html__('Save Settings', 'block-editor-auto-lightbox'));
                ?>
            </form>

            <div class="beal-info-box">
                <h2>
                    <?php esc_html_e('How to Use', 'block-editor-auto-lightbox'); ?>
                </h2>
                <p>
                    <?php esc_html_e('This plugin automatically adds a lightbox to all images within the specified block selectors. No additional configuration needed!', 'block-editor-auto-lightbox'); ?>
                </p>

                <h3>
                    <?php esc_html_e('Features:', 'block-editor-auto-lightbox'); ?>
                </h3>
                <ul>
                    <li>
                        <?php esc_html_e('✓ Automatic detection of original full-size images', 'block-editor-auto-lightbox'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✓ Support for standard WordPress Image & Gallery blocks', 'block-editor-auto-lightbox'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✓ Accessible Lightbox implementation', 'block-editor-auto-lightbox'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✓ Support for lazy-loaded images', 'block-editor-auto-lightbox'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✓ Smart gallery grouping', 'block-editor-auto-lightbox'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✓ Touch/swipe navigation support', 'block-editor-auto-lightbox'); ?>
                    </li>
                </ul>

                <h3>
                    <?php esc_html_e('Accessibility Note:', 'block-editor-auto-lightbox'); ?>
                </h3>
                <p>
                    <?php esc_html_e('This plugin is built with accessibility in mind. The lightbox functionality uses ARIA attributes and keyboard navigation is fully supported.', 'block-editor-auto-lightbox'); ?>
                </p>

                <h3>
                    <?php esc_html_e('Default Block Selectors:', 'block-editor-auto-lightbox'); ?>
                </h3>
                <code>.wp-block-post-content, .entry-content, .wp-block-group</code>

                <p>
                    <?php esc_html_e('You can customize the selectors above to target specific blocks or areas of your site.', 'block-editor-auto-lightbox'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if ('settings_page_block-editor-auto-lightbox' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'beal-admin-style',
            BEAL_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            BEAL_VERSION
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        $defaults = $this->get_default_settings();
        $options = get_option('beal_settings', array());

        // Merge with defaults to ensure all values exist
        $options = array_merge($defaults, is_array($options) ? $options : array());

        // Check if lightbox is enabled
        if (!$options['enabled']) {
            return;
        }

        // Enqueue GLightbox CSS
        wp_enqueue_style(
            'glightbox',
            BEAL_PLUGIN_URL . 'assets/glightbox/glightbox.min.css',
            array(),
            '3.2.0'
        );

        // Enqueue custom CSS to hide descriptions
        wp_enqueue_style(
            'beal-custom-lightbox',
            BEAL_PLUGIN_URL . 'assets/css/custom-lightbox.css',
            array('glightbox'),
            BEAL_VERSION
        );

        // Enqueue GLightbox JS
        wp_enqueue_script(
            'glightbox',
            BEAL_PLUGIN_URL . 'assets/glightbox/glightbox.min.js',
            array(),
            '3.2.0',
            true
        );

        // Enqueue our custom script
        wp_enqueue_script(
            'beal-auto-lightbox',
            BEAL_PLUGIN_URL . 'assets/js/auto-lightbox.js',
            array('glightbox'),
            BEAL_VERSION,
            true
        );

        // Pass settings to JavaScript (options already merged with defaults)
        wp_localize_script('beal-auto-lightbox', 'bealSettings', array(
            'hasAutoWpImage' => $options['auto_wp_image'],
            'hasAutoWpGallery' => $options['auto_wp_gallery'],
            'blockSelector' => $options['block_selector'],
            'groupPageImages' => $options['group_page_images'],
            'touchNavigation' => $options['touch_navigation'],
            'loop' => $options['loop'],
            'autoplayVideos' => $options['autoplay_videos'],
            'closeButton' => $options['close_button'],
            'closeOnOutsideClick' => $options['close_on_outside_click'],
            'preload' => $options['preload'],
        ));
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('options-general.php?page=block-editor-auto-lightbox') . '">' . esc_html__('Settings', 'block-editor-auto-lightbox') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
function block_editor_auto_lightbox_init()
{
    return Block_Editor_Auto_Lightbox::get_instance();
}

// Start the plugin (use a unique function name to be safe again, I'll stick to basic one for V1 of this new named plugin, but user already had conflict with this. I'll use `beal_plugin_init`.)
add_action('plugins_loaded', 'block_editor_auto_lightbox_init');
