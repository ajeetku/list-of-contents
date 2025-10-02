<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LOCP_Plugin {
    private $settings;
    public function __construct() {
        $this->settings = new LOCP_Settings();

        // Add initialization actions and filters here.
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_filter('the_content', array($this, 'insert_loc'));
        add_action('init', array($this, 'register_block_loc'));
        add_action('init', array($this, 'handle_content_updates'));
    }

    public function run() {
        // Code to run the plugin.
    }

    public function load_textdomain() {
        load_plugin_textdomain('list-of-contents', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_block_loc() {
        // if ( function_exists( 'register_block_type' ) ) {
        //     register_block_type( 'locp/table-of-contents', array(
        //         'editor_script' => 'locp-block-editor',
        //         'editor_style'  => 'locp-block-editor',
        //         'render_callback' => array($this, 'render_table_of_contents_block'),
        //         'attributes' => array(
        //             'design' => array(
        //                 'type' => 'string',
        //                 'default' => 'design1'
        //             ),
        //             'headingText' => array(
        //                 'type' => 'string',
        //                 'default' => 'Table of Contents'
        //             ),
        //             'enableToggle' => array(
        //                 'type' => 'boolean',
        //                 'default' => false
        //             ),
        //             'position' => array(
        //                 'type' => 'string',
        //                 'default' => 'after_first_paragraph'
        //             )
        //         )
        //     ) );
        // }
        register_block_type( LOCP_PLUGIN_PATH.'/build/toc/');
    }

    public function enqueue_scripts() {
        $options = $this->settings->get_options_with_defaults();
        $design_class = isset($options['locp_loc_design']) ? $options['locp_loc_design'] : 'design1';
        if($design_class=='design1'){
        wp_enqueue_style('locp-style', LOCP_PLUGIN_URL . 'assets/css/style.css', array(), LOCP_PLUGIN_VESION);
        }else{
            wp_enqueue_style('locp-style', LOCP_PLUGIN_URL . 'assets/css/format/'.$design_class.'.css', array(), LOCP_PLUGIN_VESION);
        }
        wp_enqueue_script('locp-script', LOCP_PLUGIN_URL . 'assets/js/loc-script.js', array(), LOCP_PLUGIN_VESION, true);
    }

    public function enqueue_block_editor_assets() {
        $options = $this->settings->get_options_with_defaults();
    $   design  = isset( $options['locp_loc_design'] ) ? $options['locp_loc_design'] : 'design1';
        wp_enqueue_script(
            'locp-block-editor',
            LOCP_PLUGIN_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-i18n'),
            LOCP_PLUGIN_VESION
        );

        wp_enqueue_style(
            'locp-block-editor',
            LOCP_PLUGIN_URL . 'assets/css/editor.css',
            array('wp-edit-blocks'),
            LOCP_PLUGIN_VESION
        );
        
        // Enqueue design CSS files for editor preview
        if($design=='design1'){
         wp_enqueue_style('locp-design1', LOCP_PLUGIN_URL . 'assets/css/style.css', array(), LOCP_PLUGIN_VESION);
        }else{
            wp_enqueue_style('locp-design1', LOCP_PLUGIN_URL . 'assets/css/format/' . $design . '.css', array(), LOCP_PLUGIN_VESION);
        }
    }

    private function has_toc_block( $content ) {
        return strpos( $content, '<!-- wp:locp/table-of-contents' ) !== false;
    }

    /**
     * Render the table of contents block
     */
    public function render_table_of_contents_block($attributes, $content) {
        global $post;
        
        if (!$post) {
            return '';
        }
        if ( $this->has_toc_block( $post->post_content ) ) {
            return $content;
        }

        $design = isset($attributes['design']) ? $attributes['design'] : 'design1';
        $headingText = isset($attributes['headingText']) ? $attributes['headingText'] : 'Table of Contents';
        $enableToggle = isset($attributes['enableToggle']) ? $attributes['enableToggle'] : false;
        
        // Get the post content
        $post_content = $post->post_content;
        
        // Generate TOC from the content
        $toc_data = $this->generate_locp($post_content);
        
        if (!isset($toc_data[1]) || count($toc_data[1]) == 0) {
            return '<div class="locp-toc-block ' . esc_attr($design) . '"><p>' . esc_html__('No headings found in this post.', 'list-of-contents') . '</p></div>';
        }
        
        // Build the TOC HTML
        $toc_html = '<div class="locp-toc-block ' . esc_attr($design) . '">';
        $toc_html .= '<p style="cursor:pointer" id="list-table-of-contents">' . esc_html($headingText) . '</p>';
        $toc_html .= '<nav' . ($enableToggle ? ' style="display: none;"' : '') . '><ol>';
        
        // Add TOC items
        foreach ($toc_data[1] as $heading) {
            $id = sanitize_title($heading[2]);
            if ($id) {
                $toc_html .= '<li><a href="#' . esc_attr($id) . '">' . esc_html(wp_kses($heading[2], array())) . '</a></li>';
            }
        }
        
        $toc_html .= '</ol></nav></div>';
        
        // Add JavaScript for toggle functionality if enabled
        if ($enableToggle) {
            $toc_html .= '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const tocTitle = document.getElementById("list-table-of-contents");
                    const tocNav = tocTitle && tocTitle.nextElementSibling ? tocTitle.nextElementSibling : null;
                    
                    if (tocTitle && tocNav) {
                        tocTitle.addEventListener("click", function () {
                            tocNav.style.display = tocNav.style.display === "none" ? "block" : "none";
                        });
                    }
                });
            </script>';
        }
        
        return $toc_html;
    }

    /**
     * Handle dynamic updates when post content changes
     */
    public function handle_content_updates() {
        // Add filter to ensure TOC is updated when content changes
        add_filter('the_content', array($this, 'update_toc_on_content_change'), 20);
        
        // Add action to handle AJAX updates
        add_action('wp_ajax_update_toc_content', array($this, 'ajax_update_toc_content'));
        add_action('wp_ajax_nopriv_update_toc_content', array($this, 'ajax_update_toc_content'));
    }

    /**
     * Update TOC when content changes
     */
    public function update_toc_on_content_change($content) {
        // Check if this is a TOC block update
        if (isset($_POST['action']) && $_POST['action'] === 'update_toc_content') {
            return $content;
        }
        
        // Process content for TOC blocks
        $content = $this->process_toc_blocks($content);
        
        return $content;
    }

    /**
     * Process TOC blocks in content
     */
    private function process_toc_blocks($content) {
        // Find TOC blocks and update them
        if (strpos($content, '<!-- wp:locp/table-of-contents') !== false) {
            $content = preg_replace_callback(
                '/<!-- wp:locp\/table-of-contents(.*?)-->/s',
                array($this, 'update_toc_block'),
                $content
            );
        }
        
        return $content;
    }

    /**
     * Update individual TOC block
     */
    private function update_toc_block($matches) {
        // Parse block attributes
        $block_content = $matches[0];
        $attributes = array();
        
        // Extract attributes from block comment
        if (preg_match('/"design":"([^"]+)"/', $block_content, $design_match)) {
            $attributes['design'] = $design_match[1];
        }
        if (preg_match('/"headingText":"([^"]+)"/', $block_content, $heading_match)) {
            $attributes['headingText'] = $heading_match[1];
        }
        if (preg_match('/"enableToggle":(true|false)/', $block_content, $toggle_match)) {
            $attributes['enableToggle'] = $toggle_match[1] === 'true';
        }
        
        // Render the updated block
        return $this->render_table_of_contents_block($attributes, '');
    }

    /**
     * AJAX handler for updating TOC content
     */
    public function ajax_update_toc_content() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'toc_update_nonce') || !current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_die('Post not found');
        }
        
        // Get updated content
        $content = wp_kses_post($_POST['content']);
        
        // Process TOC blocks
        $updated_content = $this->process_toc_blocks($content);
        
        // Return updated content
        wp_send_json_success(array(
            'content' => $updated_content,
            'toc_blocks' => $this->extract_toc_blocks($updated_content)
        ));
    }

    /**
     * Extract TOC blocks from content
     */
    private function extract_toc_blocks($content) {
        $toc_blocks = array();
        
        if (preg_match_all('/<!-- wp:locp\/table-of-contents(.*?)-->/s', $content, $matches)) {
            foreach ($matches[0] as $index => $block) {
                $attributes = array();
                
                // Extract attributes
                if (preg_match('/"design":"([^"]+)"/', $block, $design_match)) {
                    $attributes['design'] = $design_match[1];
                }
                if (preg_match('/"headingText":"([^"]+)"/', $block, $heading_match)) {
                    $attributes['headingText'] = $heading_match[1];
                }
                if (preg_match('/"enableToggle":(true|false)/', $block, $toggle_match)) {
                    $attributes['enableToggle'] = $toggle_match[1] === 'true';
                }
                
                $toc_blocks[] = array(
                    'block' => $block,
                    'attributes' => $attributes
                );
            }
        }
        
        return $toc_blocks;
    }

    public function insert_loc($content) {
        if (is_singular() && in_the_loop() && is_main_query()) {
            if ( $this->has_toc_block( $content ) ) {
                return $content; 
            }

            $options = $this->settings->get_options_with_defaults();
            if ((is_single() && $options['locp_enable_posts']) || (is_page() && $options['locp_enable_pages'])) {
                // Logic to generate and insert TOC goes here.
                $toc = $this->generate_locp($content);
                
                if(isset($toc[1]) && count($toc[1])>0){
                    // Insert the TOC after the first paragraph
                    $content = $this->insert_loc_after_first_paragraph($content, $toc);
                }
            }
        }
        return $content;
    }

    private function generate_locp($content) {
        $options = $this->settings->get_options_with_defaults();
        // $options = get_option('locp_options');
        $design_class = isset($options['locp_loc_design']) ? $options['locp_loc_design'] : 'design1';
    
        $toc = '<div class="loc-toc ' . esc_attr($design_class) . '"><p style="cursor:pointer" id="list-table-of-contents">'.esc_html(__($options['locp_app_heading_text'],'list-of-contents')).'</p><nav><ol>';
        
        global $post;

        if (strpos($post->post_content, '<!--nextpage-->') !== false) {
            $pages = explode('<!--nextpage-->', $post->post_content);
            $pattern = '/<h([1-6])[^>]*>(.*?)<\/h\1>/i';
            foreach ($pages as $page_num => $page_content) {
                preg_match_all($pattern, $page_content, $page_matches, PREG_SET_ORDER);
                foreach ($page_matches as $heading) {
                    $id = sanitize_title($heading[2]);
                    if($id){
                        $link = '#'.esc_attr($id);
                        if($this->getCurrentPage()!=($page_num + 1)){
                            $link = trailingslashit(get_permalink($post->ID)) . ($page_num + 1) . '/#' . esc_attr($id);
                        }
                        $toc .= '<li><a href="' . esc_attr($link) . '">' . esc_html(wp_kses($heading[2], array())) . '</a></li>';
                        $contentReplacer[] = $heading;
                    }
                }
            }
        }else{
            $pattern = '/<h([1-6])[^>]*>(.*?)<\/h\1>/i';
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            $contentReplacer = array();
            if (!empty($matches)) {
                foreach ($matches as $heading) {
                    $id = sanitize_title($heading[2]);
                    if($id){
                        $toc .= '<li><a href="#' . esc_attr($id) . '">' . esc_html(wp_kses($heading[2], array())) . '</a></li>';

                        $contentReplacer[] = $heading;
                    }
                    // Add ID to the original heading in content
                    // $content = str_replace($heading[0], '<h' . esc_attr($heading[1]) . ' id="' . esc_attr($id) . '">' . esc_html(wp_kses($heading[2], array())) . '</h' . esc_attr($heading[1]) . '>', $content);
                }
            }
        }
        $toc .= '</ol></nav></div>';
        $designBasedJs = '';
            if($design_class=='design6'){
                $designBasedJs = 'if (classnames.includes("design6")) {
                    tocTitle.addEventListener("click", function () {
                        if(tocTitle.parentNode.className.includes("loc-closed")){
                            tocTitle.parentNode.classList.remove("loc-closed");
                        }else{
                            tocTitle.parentNode.classList.add("loc-closed");
                        }
                        
                    })
                }';
            }
            $toggleTitle = '';
        if(isset($options['locp_app_heading_toggle']) && $options['locp_app_heading_toggle']==1){
            $toggleTitle = 'const tocNav = tocTitle && tocTitle.nextElementSibling ? tocTitle.nextElementSibling : null; // Assuming the TOC <nav> follows the title. .querySelector("ol")

                if (tocTitle && tocNav) {
                    tocNav.style.display = "block"; // Initially hide the TOC content.

                    tocTitle.addEventListener("click", function () {
                        tocNav.style.display = tocNav.style.display === "none" ? "block" : "none";
                    });
                }';
        
        }
        $toc .= '<script id="loc-javascript">
            document.addEventListener("DOMContentLoaded", function () {
                const tocTitle = document.getElementById("list-table-of-contents");
                let classnames = tocTitle.parentNode.className
                '.$designBasedJs.'
                
                '.$toggleTitle.'
            });
        </script>';
        return array($toc , $contentReplacer);
    }    
    

    private function insert_loc_after_first_paragraph($content, $toc) {
        foreach ($toc[1] as $heading) {
            $id = sanitize_title($heading[2]);
            // Add ID to the original heading in content
            $content = str_replace($heading[0], '<h' . esc_attr($heading[1]) . ' id="' . esc_attr($id) . '">' . esc_html(wp_kses($heading[2], array())) . '</h' . esc_attr($heading[1]) . '>', $content);
        }
        $pattern = '/(<p[^>]*>.*?<\/p>)/i';
        $split_content = preg_split($pattern, $content, 2, PREG_SPLIT_DELIM_CAPTURE);

        if (count($split_content) >= 2) {
            $split_content[0] .= $split_content[1] . $toc[0];
            $content = implode('', array_slice($split_content, 0, 1)) . implode('', array_slice($split_content, 2));
            // $position = 2;
            // array_splice($split_content, $position, 0, $toc);
            // $content = implode('', $split_content);
        }

        return $content;
    }

    /**
	 * if page brake is added must get current age
	 * which break the WordPress global $wp_query var by unsetting it
	 * or overwriting it which breaks the method call
	 * that `get_query_var()` uses to return the query variable.
	 *
	 * @access protected
	 * @since  1.0.2
	 *
	 * @return int
	 */
	protected function getCurrentPage() {

		global $wp_query;

		// Check to see if the global `$wp_query` var is an instance of WP_Query and that the get() method is callable.
		// If it is then when can simply use the get_query_var() function.
		if ( $wp_query instanceof WP_Query && is_callable( array( $wp_query, 'get' ) ) ) {

			$page =  get_query_var( 'page', 1 );

			return 1 > $page ? 1 : $page;

			// If a theme or plugin broke the global `$wp_query` var, check to see if the $var was parsed and saved in $GLOBALS['wp_query']->query_vars.
		} elseif ( isset( $GLOBALS['wp_query']->query_vars[ 'page' ] ) ) {

			return $GLOBALS['wp_query']->query_vars[ 'page' ];

			// We should not reach this, but if we do, lets check the original parsed query vars in $GLOBALS['wp_the_query']->query_vars.
		} elseif ( isset( $GLOBALS['wp_the_query']->query_vars[ 'page' ] ) ) {

			return $GLOBALS['wp_the_query']->query_vars[ 'page' ];

			// Ok, if all else fails, check the $_REQUEST super global.
		} elseif ( isset( $_REQUEST[ 'page' ] ) ) {

			return $_REQUEST[ 'page' ];
		}

		// Finally, return the $default if it was supplied.
		return 1;
	}
}
