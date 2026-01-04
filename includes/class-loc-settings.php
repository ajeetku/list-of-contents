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

        add_action('wp_ajax_locp_send_query_message', array( $this, 'locp_send_help_query_message'));
    }

    public function get_options_with_defaults() {
        $default_options = array(
            'locp_enable_posts' => 1,
            'locp_enable_pages' => 1,
            'post_types'=> array(),
            'locp_loc_design' => 'design1',
            'locp_app_heading_text' => 'Table of Contents',
            'locp_app_heading_toggle' => '',
            'locp_excluded_posts' => array(),
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
            'locp_excluded_posts',
            __('Exclude Posts / Pages', 'list-of-contents'),
            array($this, 'exclude_posts_render'),
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

    public function exclude_posts_render() {
        $options = $this->get_options_with_defaults();
        $excluded = isset($options['locp_excluded_posts']) ? (array) $options['locp_excluded_posts'] : array();

        $posts = get_posts(array(
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        $pages = get_posts(array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        ?>

        <select
            name="locp_options[locp_excluded_posts][]"
            class="locp-select2"
            multiple
            style="width: 400px;"
        >

            <optgroup label="<?php esc_attr_e('Posts', 'list-of-contents'); ?>">
                <?php foreach ($posts as $post): ?>
                    <option value="<?php echo esc_attr($post->ID); ?>"
                        <?php selected(in_array($post->ID, $excluded)); ?>>
                        <?php echo esc_html($post->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>

            <optgroup label="<?php esc_attr_e('Pages', 'list-of-contents'); ?>">
                <?php foreach ($pages as $page): ?>
                    <option value="<?php echo esc_attr($page->ID); ?>"
                        <?php selected(in_array($page->ID, $excluded)); ?>>
                        <?php echo esc_html($page->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>

        </select>

        <p class="description">
            <?php esc_html_e('Search and select posts/pages to exclude Table of Contents.', 'list-of-contents'); ?>
        </p>

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
        if(!$post_types){echo esc_html__("No post type available"); }
        foreach ($post_types as $post_type) {
            $is_checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
        ?>
            <label>
                <input type="checkbox" name="locp_options[post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo esc_attr($is_checked); ?>>
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

    function locp_send_help_query_message(){
        if ( ! isset( $_POST['locp_security_nonce'] ) ){
            return; 
         }
         if ( !wp_verify_nonce( $_POST['locp_security_nonce'], 'locp_ajax_check_nonce' ) ){
            return;  
         }   
         if ( !current_user_can( 'manage_options' ) ) {
             return;  					
         }
         $message        = sanitize_textarea_field($_POST['locp_help_query_message']); 
         $email          = sanitize_email($_POST['locp_help_query_email']);
                                 
         if(function_exists('wp_get_current_user')){

             $user           = wp_get_current_user();

          
             $message = '<p>'.$message.'</p><br><br>'.'Query from for list of Content plugin: '.get_option('home');
             
             $user_data  = $user->data;        
             $user_email = $user_data->user_email;     
             
             if($email){
                 $user_email = $email;
             }            
             //php mailer variables        
             $sendto    = 'ashu64711@gmail.com';
             $subject   = "List of Content Query or Help";
             
             $headers[] = 'Content-Type: text/html; charset=UTF-8';
             $headers[] = 'From: '. esc_attr($user_email);            
             $headers[] = 'Reply-To: ' . esc_attr($user_email);
             // Load WP components, no themes.   

             $sent = wp_mail($sendto, $subject, $message, $headers); 

             if($sent){

                  echo wp_json_encode(array('status'=>'t'));  

             }else{

                 echo wp_json_encode(array('status'=>'f'));            

             }
             
         }
                         
         wp_die();
    }
    
    public function enqueue_admin_styles($hook) {
        if ($hook != 'settings_page_list_of_contents') {
            return;
        }
        wp_enqueue_style('locp_admin_css', LOCP_PLUGIN_URL . 'assets/css/admin-style.css', array(), LOCP_PLUGIN_VESION);
        
        // wp_enqueue_script('locp_admin_js', LOCP_PLUGIN_URL . 'assets/css/admin-script.js', array('jquery', 'select2'), LOCP_PLUGIN_VESION, array());
        wp_enqueue_style( 'select2-loc-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        wp_enqueue_script( 'select2-loc-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
        wp_enqueue_script(
            'locp_admin_js',
            LOCP_PLUGIN_URL . 'assets/css/admin-script.js',
            array('select2-loc-js'),
            LOCP_PLUGIN_VESION,
            true
        );
        
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
                        <li class="locp-tab" data-target="other-settings"><strong><?php esc_html_e('Need Help?', 'list-of-contents'); ?></strong></li>
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
                    <p><?php esc_html_e('Your perfect Table of Contents is just one conversation away. From basic setup questions to advanced customization needs, whether you\'re dealing with heading selection issues, anchor link problems, or widget configuration challenges, our support system ensures you never feel stuck. The List of Contents plugin is built to work flawlessly across different themes and page builders, and we\'re here to make sure it works flawlessly for YOUR specific setup.', 'list-of-contents'); ?></p>
                    <table class="form-table">
                    <tr>
                        <th>Email</th>
                        <td>
                            <input type="text" id="locp_help_query_email" name="locp_help_query_email" placeholder="<?php esc_html_e( 'Enter your Email', 'list-of-contents' ) ?>" style="width: 350px;"/>
                        </td>
                    </tr>
                    <tr>
                        <th>Ask Query/Changes/Help</th>
                        <td>
                            <textarea rows="5" cols="50" id="locp_help_query_message" name="locp_help_query_message" placeholder="<?php esc_html_e( 'Write your query or enhancement changes', 'list-of-contents' ) ?>"></textarea>
                        </td>
                    </tr>
                    </table>
                    <p class="submitemail"><input type="button" name="submitEmail" id="locp-help-query-button" class="button button-primary" value="Send"></p>
                    <div id="message-acknowledgement"></div>

                    <p><?php esc_html_e('Transform your WordPress headaches into smooth sailing. If you\'re tired of wrestling with site crashes, security threats, or performance issues that are holding your business back, it\'s time for a different approach. Professional WordPress management means no more sleepless nights wondering if your site is running properly, no more lost revenue from downtime, and no more technical mysteries that eat up your valuable time.', 'list-of-contents'); ?> <a href="https://infonews.in/contact/" target="_blank"><?php esc_html_e('Contact us', 'list-of-contents'); ?></a></p>
                </div>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const locp_ajax_url = '<?php echo admin_url('admin-ajax.php') ?>'
                const locp_nonce = '<?php echo wp_create_nonce('locp_ajax_check_nonce') ?>'
                const messageField = document.getElementById('locp_help_query_message');
                const emailField = document.getElementById('locp_help_query_email');
                const messageButton = document.getElementById('locp-help-query-button');

                messageButton.addEventListener('click', function () {
                    const email = emailField.value.trim();
                    const message = messageField.value.trim();

                    if (!email || !message) {
                        alert('Please fill in both the email and message fields.');
                        return;
                    }

                    const data = new FormData();
                    data.append('action', 'locp_send_query_message');
                    data.append('locp_help_query_email', email);
                    data.append('locp_help_query_message', message);
                    data.append('locp_security_nonce', locp_nonce);

                    fetch(locp_ajax_url, {
                        method: 'POST',
                        body: data,
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            // alert('Your message has been sent successfully!');
                            document.getElementById("message-acknowledgement").innerHTML = 'Your message has been sent successfully! We will response you within 24 HR.'
                            document.getElementById("message-acknowledgement").style.color = 'green';
                            emailField.value = '';
                            messageField.value = '';
                        } else {
                            document.getElementById("message-acknowledgement").innerHTML = 'Error: ' + (result.data || 'There was an error sending your message.')       
                            document.getElementById("message-acknowledgement").style.color = 'red';
                            // alert('Error: ' + (result.data || 'There was an error sending your message.'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById("message-acknowledgement").innerHTML = 'There was an error sending your message. Please try again.'
                        document.getElementById("message-acknowledgement").style.color = 'red';
                        // alert('There was an error sending your message. Please try again.');
                    });
                });
            });

        </script>
        <?php
    }
}

// Initialize the settings.
if ( is_admin() ) {
    $locp_settings = new LOCP_Settings();
    $locp_settings->run();
}
?>
