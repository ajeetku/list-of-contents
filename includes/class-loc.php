<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LOCP_Plugin {
    private $settings;
    public function __construct() {
        $this->settings = new LOCP_Settings();

        // Add initialization actions and filters here.
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_loc'));
    }

    public function run() {
        // Code to run the plugin.
    }

    public function load_textdomain() {
        load_plugin_textdomain('list-of-contents', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        $options = $this->settings->get_options_with_defaults();
        $design_class = isset($options['locp_loc_design']) ? $options['locp_loc_design'] : 'design1';
        if($design_class=='design1'){
        wp_enqueue_style('locp-style', LOCP_PLUGIN_URL . 'assets/css/style.css', array(), LOCP_PLUGIN_VESION);
        }else{
            wp_enqueue_style('locp-style', LOCP_PLUGIN_URL . 'assets/css/format/'.$design_class.'.css', array(), LOCP_PLUGIN_VESION);
        }
        wp_enqueue_script('locp-script', LOCP_PLUGIN_URL . 'assets/js/script.js', array(), LOCP_PLUGIN_VESION, true);
    }

    public function insert_loc($content) {
        if (is_singular() && in_the_loop() && is_main_query()) {
            $options = $this->settings->get_options_with_defaults();
            if ((is_single() && $options['locp_enable_posts']) || (is_page() && $options['locp_enable_pages'])) {
                // Logic to generate and insert TOC goes here.
                $toc = $this->generate_locp($content);
                
                // Insert the TOC after the first paragraph
                $content = $this->insert_loc_after_first_paragraph($content, $toc);
            }
        }
        return $content;
    }

    private function generate_locp($content) {
        $options = $this->settings->get_options_with_defaults();
        // $options = get_option('locp_options');
        $design_class = isset($options['locp_loc_design']) ? $options['locp_loc_design'] : 'design1';
    
        $toc = '<div class="loc-toc ' . esc_attr($design_class) . '"><h3>'.esc_html(__('List of content','list-of-contents')).'</h3><ol>';
        $pattern = '/<h([1-6])[^>]*>(.*?)<\/h\1>/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
    
        if (!empty($matches)) {
            foreach ($matches as $heading) {
                $id = sanitize_title($heading[2]);
                $toc .= '<li><a href="#' . esc_attr($id) . '">' . esc_html(wp_kses($heading[2], array())) . '</a></li>';
                // Add ID to the original heading in content
                $content = str_replace($heading[0], '<h' . esc_attr($heading[1]) . ' id="' . esc_attr($id) . '">' . esc_html(wp_kses($heading[2], array())) . '</h' . esc_attr($heading[1]) . '>', $content);
            }
        }
    
        $toc .= '</ol></div>';
        return $toc . $content;
    }    
    

    private function insert_loc_after_first_paragraph($content, $toc) {
        $pattern = '/(<p[^>]*>.*?<\/p>)/i';
        $split_content = preg_split($pattern, $content, 2, PREG_SPLIT_DELIM_CAPTURE);

        if (count($split_content) >= 2) {
            $split_content[0] .= $split_content[1] . $toc;
            $content = implode('', array_slice($split_content, 0, 1)) . implode('', array_slice($split_content, 2));
        }

        return $content;
    }
}
