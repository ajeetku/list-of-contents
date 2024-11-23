<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LOCP_Settings {

    public function __construct() {
       
    }

    public function run(){
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_filter('plugin_action_links_' . LOCP_PLUGIN_BASENAME, array($this, 'add_settings_link'));

        add_action( 'wp_head', array( __CLASS__, 'ez_toc_schema_sitenav_creator' ) );
    }

    public function get_options_with_defaults() {
        $default_options = array(
            'locp_enable_posts' => 1,
            'locp_enable_pages' => 1,
            'post_types'=> array(),
            'locp_loc_design' => 'design1',
            'locp_app_heading_text' => 'Table of Contents',
            'locp_app_heading_toggle' => ''
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
        // Register 'locp_settings' option group and settings.
        register_setting('locp_settings', 'locp_options');

        // Main settings section.
        add_settings_section(
            'locp_settings_section',
            null,// __('Settings', 'list-of-contents')
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
            'locp_enable_post_types',
            __('Enable for post types', 'list-of-contents'),
            array($this, 'enable_otherPostTypes_render'),
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

        // Register 'locp_appearance' option group and settings.
        register_setting('locp_appearance', 'locp_options');

        // Appearance section.
        add_settings_section(
            'locp_appearance_section',
            null,//__('Appearance Settings', 'list-of-contents')
            null,
            'locp_appearance'
        );

        add_settings_field(
            'locp_app_heading_toggle',
            __('Toggle on Header', 'list-of-contents'),
            array($this, 'toc_toggle_header_render'),
            'locp_appearance',
            'locp_appearance_section'
        );
        add_settings_field(
            'locp_app_heading_text',
            __('Heading text', 'list-of-contents'),
            array($this, 'toc_heading_text_render'),
            'locp_appearance',
            'locp_appearance_section'
        );
    }

    public function enable_posts_render() {
        $options = $this->get_options_with_defaults();
        ?>
        <label class="locp-switch">
            <input type="checkbox" name='locp_options[locp_enable_posts]' <?php checked(@$options['locp_enable_posts'], 1); ?> value="1">
            <span class="locp-slider locp-round"></span>
        </label>
        <?php
    }

    public function enable_pages_render() {
        $options = $this->get_options_with_defaults();
        ?>
        <label class="locp-switch">
            <input type="checkbox" name='locp_options[locp_enable_pages]' <?php checked(@$options['locp_enable_pages'], 1); ?> value="1">
            <span class="locp-slider locp-round"></span>
        </label>
        <?php
    }

    public function enable_otherPostTypes_render(){
        $options = $this->get_options_with_defaults();
        $selected_post_types = isset($options['post_types']) ? $options['post_types'] : array();
        $args = array(
            'public'   => true,
            '_builtin' => false,
        );
        $post_types = get_post_types($args, 'objects');
        foreach ($post_types as $post_type) {
            $is_checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
        ?>
            <label>
                <input type="checkbox" name="locp_options[post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo $is_checked; ?>>
                <?php echo esc_html($post_type->label); ?>
            </label><br>
        <?php
        }
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
            <option value='design6' <?php isset($options['locp_loc_design'])? selected($options['locp_loc_design'], 'design6'): ''; ?>><?php esc_html_e('Design 6 (Right hand cornor)', 'list-of-contents'); ?></option>
        </select>
        <?php
    }

    public function toc_toggle_header_render(){
        $options = $this->get_options_with_defaults();
        ?>
        <label>
            <input type="checkbox" name="locp_options[locp_app_heading_toggle]" <?php checked(@$options['locp_app_heading_toggle'], 1); ?> value="1">
            <?php esc_html_e('Enable header toggle', 'list-of-contents'); ?>
        </label>
        <?php
    }

    public function toc_heading_text_render(){
        $options = $this->get_options_with_defaults();
        ?>
        <input type="text" name="locp_options[locp_app_heading_text]" value="<?php echo @$options['locp_app_heading_text']? @$options['locp_app_heading_text']: 'Table of Contents'; ?>">
        <?php
    }
    
    public function enqueue_admin_styles($hook) {
        if ($hook != 'settings_page_list_of_contents') {
            return;
        }
        wp_enqueue_style('locp_admin_css', LOCP_PLUGIN_URL . 'assets/css/admin-style.css', array(), LOCP_PLUGIN_VESION);
        wp_enqueue_script('locp_admin_css', LOCP_PLUGIN_URL . 'assets/css/admin-script.js', array(), LOCP_PLUGIN_VESION, array());
    }

    function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=list_of_contents">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function options_page() {
        ?>
        <div class="wrap lcop-wrap">
            <h2><?php esc_html_e('List of Contents Settings', 'list-of-contents'); ?></h2>
            <form action='options.php' method='post'>
                
                <!-- Custom Navigation Tabs -->
                <nav class="locp-admin-nav">
                    <ul>
                        <li class="locp-tab active" data-target="home"><?php esc_html_e('Home', 'list-of-contents'); ?></li>
                        <li class="locp-tab" data-target="styles"><?php esc_html_e('Appearance', 'list-of-contents'); ?></li>
                        <li class="locp-tab" data-target="other-settings"><?php esc_html_e('Other Settings', 'list-of-contents'); ?></li>
                    </ul>
                </nav>

                <!-- Custom Tab Content Sections -->
                <div class="locp-tab-content active" id="home">
                    <p><?php esc_html_e('Welcome to the List of Contents Settings.', 'list-of-contents'); ?></p>
                    <?php
                    settings_fields('locp_settings');
                    do_settings_sections('locp_settings');
                    submit_button();
                    ?>
                </div>

                <div class="locp-tab-content" id="styles">
                    <h3><?php esc_html_e('Styles', 'list-of-contents'); ?></h3>
                    <p><?php esc_html_e('Here you can customize the styles.', 'list-of-contents'); ?></p>
                    <?php 
                    settings_fields('locp_appearance');
                    do_settings_sections('locp_appearance');
                    submit_button();
                    ?>
                </div>

                <div class="locp-tab-content" id="other-settings">
                    <h3><?php esc_html_e('Other Settings', 'list-of-contents'); ?></h3>
                    <p><?php esc_html_e('Additional settings can go here.', 'list-of-contents'); ?></p>
                </div>
            </form>
        </div>
        <?php
    }
}

// Initialize the settings.
if ( is_admin() ) {
    $locp_settings = new LOCP_Settings();
    $locp_settings->run();
}
?>
