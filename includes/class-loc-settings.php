<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LOCP_Settings {

    public function __construct() {
       
    }

    public function run(){
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    public function get_options_with_defaults() {
        $default_options = array(
            'locp_enable_posts' => 1,
            'locp_enable_pages' => 1,
            'locp_loc_design' => 'design1',
        );
        
        $options = get_option('locp_options', array());
        return wp_parse_args($options, $default_options);
    }

    public function add_admin_menu() {
        add_options_page(
            __('List of Contents Settings', 'list-of-contents'),
            __('List of Contents', 'list-of-contents'),
            'manage_options',
            'list_of_contents',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('locp_settings', 'locp_options');

        add_settings_section(
            'locp_settings_section',
            __('Settings', 'list-of-contents'),
            null,
            'locp_settings'
        );

        add_settings_field(
            'locp_enable_posts',
            __('Enable for Posts', 'list-of-contents'),
            array($this, 'enable_posts_render'),
            'locp_settings',
            'locp_settings_section'
        );

        add_settings_field(
            'locp_enable_pages',
            __('Enable for Pages', 'list-of-contents'),
            array($this, 'enable_pages_render'),
            'locp_settings',
            'locp_settings_section'
        );

        add_settings_field(
            'locp_loc_design',
            __('LOC Designs', 'list-of-contents'),
            array($this, 'toc_design_render'),
            'locp_settings',
            'locp_settings_section'
        );
        // Add more settings fields as needed.
    }

    public function enable_posts_render() {
        $options = $this->get_options_with_defaults();
        ?>
        <label class="locp-switch">
            <input type="checkbox" name='locp_options[locp_enable_posts]' <?php checked(@$options['locp_enable_posts'], 1); ?>>
            <span class="locp-slider locp-round"></span>
        </label>
        <?php
    }

    public function enable_pages_render() {
        $options = $this->get_options_with_defaults();
        ?>
        <label class="locp-switch">
            <input type="checkbox" name='locp_options[locp_enable_posts]' <?php checked(@$options['locp_enable_pages'], 1); ?>>
            <span class="locp-slider locp-round"></span>
        </label>
        <!-- <input type='checkbox' name='locp_options[locp_enable_pages]' <?php checked( $options['locp_enable_pages'], 1); ?> value='1'> -->
        <?php
    }
    
    public function toc_design_render() {
        $options = $this->get_options_with_defaults();
        ?>
        <select name='locp_options[locp_loc_design]'>
            <option value='design1' <?php isset($options['locp_loc_design'])? selected($options['locp_loc_design'], 'Design 1') : ''; ?>><?php esc_html_e('Design 1', 'list-of-contents'); ?></option>
            <option value='design2' <?php isset($options['locp_loc_design'])? selected($options['locp_loc_design'], 'design2') : ''; ?>><?php esc_html_e('Design 2', 'list-of-contents'); ?></option>
            <option value='design3' <?php isset($options['locp_loc_design'])? selected($options['locp_loc_design'], 'design3') : ''; ?>><?php esc_html_e('Design 3', 'list-of-contents'); ?></option>
            <option value='design4' <?php isset($options['locp_loc_design'])? selected($options['locp_loc_design'], 'design4'): ''; ?>><?php esc_html_e('Design 4 (Two Columns)', 'list-of-contents'); ?></option>
            <option value='design5' <?php isset($options['locp_loc_design'])? selected($options['locp_loc_design'], 'design5'): ''; ?>><?php esc_html_e('Design 5 (Two Columns with order)', 'list-of-contents'); ?></option>
        </select>
        <?php
    }
    
    public function enqueue_admin_styles($hook) {
        if ($hook != 'settings_page_list_of_contents') {
            return;
        }
        wp_enqueue_style('locp_admin_css', LOCP_PLUGIN_URL . 'assets/css/admin-style.css');
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2><?php esc_html_e('List of Contents Settings', 'list-of-contents'); ?></h2>
            <?php
            settings_fields('locp_settings');
            do_settings_sections('locp_settings');
            submit_button();
            ?>
        </form>
        <?php
    }
}

// Initialize the settings.
if (is_admin()) {
    $locp_settings = new LOCP_Settings();
    $locp_settings->run();
}
