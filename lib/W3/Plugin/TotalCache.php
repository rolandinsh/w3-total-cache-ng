<?php

/**
 * W3 Total Cache plugin
 */
require_once dirname(__FILE__) . '/../Plugin.php';

if (! defined('W3_PLUGIN_TOTALCACHE_LINK_URL')) {
    define('W3_PLUGIN_TOTALCACHE_LINK_URL', 'http://www.w3-edge.com/wordpress-plugins/');
}

if (! defined('W3_PLUGIN_TOTALCACHE_LINK_NAME')) {
    define('W3_PLUGIN_TOTALCACHE_LINK_NAME', 'WordPress Plugins');
}

/**
 * Class W3_Plugin_TotalCache
 */
class W3_Plugin_TotalCache extends W3_Plugin
{
    /**
     * Plugin action
     *
     * @var string
     */
    var $_action = '';
    
    /**
     * Runs plugin
     */
    function run()
    {
        register_activation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'deactivate'
        ));
        
        add_action('admin_menu', array(
            &$this, 
            'admin_menu'
        ));
        
        add_filter('favorite_actions', array(
            &$this, 
            'favorite_actions'
        ));
        
        if (is_admin()) {
            add_action('init', array(
                &$this, 
                'init'
            ));
        }
        
        if ($this->_config->get_boolean('common.support', true) && $this->_config->get_string('common.support.type', 'footer') == 'footer') {
            add_action('wp_footer', array(
                &$this, 
                'footer'
            ));
        }
        
        if (isset($_REQUEST['w3tc_action'])) {
            $action = trim($_REQUEST['w3tc_action']);
            
            if (method_exists(&$this, $action)) {
                call_user_func(array(
                    &$this, 
                    $action
                ));
                die();
            }
        }
        
        @header('X-Powered-By: ' . W3_PLUGIN_POWERED_BY);
        
        ob_start(array(
            &$this, 
            'ob_callback'
        ));
        
        /**
         * Run DbCache plugin
         */
        require_once W3_PLUGIN_DIR . '/lib/W3/Plugin/DbCache.php';
        $w3_plugin_dbcache = W3_Plugin_DbCache::instance();
        $w3_plugin_dbcache->run();
        
        /**
         * Run PgCache plugin
         */
        require_once W3_PLUGIN_DIR . '/lib/W3/Plugin/PgCache.php';
        $w3_plugin_pgcache = W3_Plugin_PgCache::instance();
        $w3_plugin_pgcache->run();
        
        /**
         * Run CDN plugin
         */
        require_once W3_PLUGIN_DIR . '/lib/W3/Plugin/Cdn.php';
        $w3_plugin_cdn = W3_Plugin_Cdn::instance();
        $w3_plugin_cdn->run();
        
        /**
         * Run Minify plugin
         */
        require_once W3_PLUGIN_DIR . '/lib/W3/Plugin/Minify.php';
        $w3_plugin_minify = W3_Plugin_Minify::instance();
        $w3_plugin_minify->run();
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_TotalCache
     */
    function &instance()
    {
        static $instance = null;
        
        if (! $instance) {
            $class = __CLASS__;
            $instance = & new $class();
        }
        
        return $instance;
    }
    
    /**
     * Activate plugin action
     */
    function activate()
    {
        @copy(W3_CONFIG_DEFAULT_PATH, W3_CONFIG_PATH);
        @chmod(W3_CONFIG_PATH, 0666);
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        @unlink(W3_CONFIG_PATH);
    }
    
    /**
     * Init Action
     */
    function init()
    {
        wp_enqueue_style('w3tc-options', WP_PLUGIN_URL . '/w3-total-cache/inc/css/options.css');
        wp_enqueue_script('w3tc-options', WP_PLUGIN_URL . '/w3-total-cache/inc/js/options.js');
    }
    
    /**
     * Admin menu
     */
    function admin_menu()
    {
        add_options_page('W3 Total Cache', 'W3 Total Cache', 'manage_options', W3_PLUGIN_FILE, array(
            &$this, 
            'options'
        ));
    }
    
    /**
     * favorite_actions filter
     */
    function favorite_actions($actions)
    {
        $actions['options-general.php?page=' . W3_PLUGIN_FILE . '&amp;flush_all'] = array(
            'Empty Caches', 
            'manage_options'
        );
        
        return $actions;
    }
    
    /**
     * Footer plugin action
     */
    function footer()
    {
        echo '<div>Performance Optimization <a href="http://www.w3-edge.com/wordpress-plugins/" rel="external">WordPress Plugins</a> by W3 EDGE</div>';
    }
    
    /**
     * Options page
     */
    function options()
    {
        require_once dirname(__FILE__) . '/../Request.php';
        
        $config = & $this->_config;
        $errors = array();
        $notes = array();
        
        $tab = W3_Request::get_string('tab');
        
        switch ($tab) {
            case 'general':
            case 'pgcache':
            case 'dbcache':
            case 'minify':
            case 'cdn':
            case 'install':
            case 'faq':
            case 'about':
                break;
            
            default:
                $tab = 'general';
        }
        
        /**
         * Save config
         */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $keys = array(
                'dbcache.enabled' => 'boolean', 
                'dbcache.debug' => 'boolean', 
                'dbcache.engine' => 'string', 
                'dbcache.memcached.engine' => 'string', 
                'dbcache.memcached.servers' => 'array', 
                'dbcache.reject.admin' => 'boolean', 
                'dbcache.reject.uri' => 'array', 
                'dbcache.reject.sql' => 'array', 
                'dbcache.lifetime.default' => 'integer', 
                'dbcache.lifetime.options' => 'integer', 
                'dbcache.lifetime.links' => 'integer', 
                'dbcache.lifetime.terms' => 'integer', 
                'dbcache.lifetime.user' => 'integer', 
                'dbcache.lifetime.post' => 'integer', 
                
                'pgcache.enabled' => 'boolean', 
                'pgcache.debug' => 'boolean', 
                'pgcache.engine' => 'string', 
                'pgcache.memcached.engine' => 'string', 
                'pgcache.memcached.servers' => 'array', 
                'pgcache.lifetime' => 'integer', 
                'pgcache.compress' => 'boolean', 
                'pgcache.cache.logged' => 'boolean', 
                'pgcache.cache.query' => 'boolean', 
                'pgcache.cache.home' => 'boolean', 
                'pgcache.cache.feed' => 'boolean', 
                'pgcache.cache.404' => 'boolean', 
                'pgcache.cache.flush' => 'boolean', 
                'pgcache.cache.headers' => 'array', 
                'pgcache.accept.files' => 'array', 
                'pgcache.reject.uri' => 'array', 
                'pgcache.reject.ua' => 'array', 
                'pgcache.mobile.check' => 'boolean', 
                'pgcache.mobile.whitelist' => 'array', 
                'pgcache.mobile.browsers' => 'array', 
                
                'minify.enabled' => 'boolean', 
                'minify.debug' => 'boolean', 
                'minify.engine' => 'string', 
                'minify.memcached.engine' => 'string', 
                'minify.memcached.servers' => 'array', 
                'minify.rewrite' => 'boolean', 
                'minify.logger' => 'boolean', 
                'minify.builder' => 'boolean', 
                'minify.cache.path' => 'string', 
                'minify.cache.locking' => 'string', 
                'minify.docroot' => 'string', 
                'minify.fixtime' => 'integer', 
                'minify.compress' => 'boolean', 
                'minify.compress.ie6' => 'boolean', 
                'minify.options' => 'array', 
                'minify.symlinks' => 'array', 
                'minify.lifetime' => 'integer', 
                'minify.html.enable' => 'boolean', 
                'minify.html.strip.comments' => 'boolean', 
                'minify.html.strip.crlf' => 'boolean', 
                'minify.html.reject.admin' => 'boolean', 
                'minify.css.enable' => 'boolean', 
                'minify.css.strip.comments' => 'boolean', 
                'minify.css.strip.crlf' => 'boolean', 
                'minify.css.clean' => 'boolean', 
                'minify.css.groups' => 'array', 
                'minify.js.enable' => 'boolean', 
                'minify.js.strip.comments' => 'boolean', 
                'minify.js.strip.crlf' => 'boolean', 
                'minify.js.clean' => 'boolean', 
                'minify.js.groups' => 'array', 
                
                'cdn.enabled' => 'boolean', 
                'cdn.engine' => 'string', 
                'cdn.domain' => 'string', 
                'cdn.process.includes' => 'boolean', 
                'cdn.process.theme' => 'boolean', 
                'cdn.process.minify' => 'boolean', 
                'cdn.process.custom' => 'boolean', 
                'cdn.files.includes' => 'string', 
                'cdn.files.theme' => 'string', 
                'cdn.files.custom' => 'array', 
                'cdn.limit.queue' => 'integer', 
                'cdn.ftp.host' => 'string', 
                'cdn.ftp.user' => 'string', 
                'cdn.ftp.pass' => 'string', 
                'cdn.ftp.path' => 'string', 
                'cdn.ftp.pasv' => 'boolean', 
                
                'common.support' => 'boolean', 
                'common.support.type' => 'string'
            );
            
            $config->read_request($keys);
            
            if ($tab == 'minify') {
                $css_files_header = W3_Request::get_array('css_files_include');
                $js_files_header = W3_Request::get_array('js_files_include');
                $js_files_header_nb = W3_Request::get_array('js_files_include-nb');
                $js_files_footer = W3_Request::get_array('js_files_include-footer');
                $js_files_footer_nb = W3_Request::get_array('js_files_include-footer-nb');
                
                $css_header_files = array();
                $js_header_files = array();
                $js_header_nb_files = array();
                $js_footer_files = array();
                $js_footer_nb_files = array();
                
                foreach ($css_files_header as $css_header_file) {
                    if (! empty($css_header_file)) {
                        $css_header_files[] = $css_header_file;
                    }
                }
                
                foreach ($js_files_header as $js_header_file) {
                    if (! empty($js_header_file)) {
                        $js_header_files[] = $js_header_file;
                    }
                }
                
                foreach ($js_files_header_nb as $js_header_nb_file) {
                    if (! empty($js_header_nb_file)) {
                        $js_header_nb_files[] = $js_header_nb_file;
                    }
                }
                
                foreach ($js_files_footer as $js_footer_file) {
                    if (! empty($js_footer_file)) {
                        $js_footer_files[] = $js_footer_file;
                    }
                }
                
                foreach ($js_files_footer_nb as $js_footer_nb_file) {
                    if (! empty($js_footer_nb_file)) {
                        $js_footer_nb_files[] = $js_footer_nb_file;
                    }
                }
                
                $config->set('minify.css.groups', array(
                    'include' => array(
                        'files' => $css_header_files
                    )
                ));
                
                $config->set('minify.js.groups', array(
                    'include' => array(
                        'blocking' => true, 
                        'files' => $js_header_files
                    ), 
                    'include-nb' => array(
                        'blocking' => false, 
                        'files' => $js_header_nb_files
                    ), 
                    'include-footer' => array(
                        'blocking' => true, 
                        'files' => $js_footer_files
                    ), 
                    'include-footer-nb' => array(
                        'blocking' => false, 
                        'files' => $js_footer_nb_files
                    )
                ));
            }
            
            $config->set('common.defaults', false);
            
            if ($config->save()) {
                $notes[] = 'Plugin configuration updated successfully.';
            } else {
                $errors[] = 'Unable to save plugin configuration.';
            }
            
            if ($config->get_boolean('common.support', true) && $config->get_string('common.support.type', 'footer') == 'blogroll') {
                $this->link_insert();
            } else {
                $this->link_delete();
            }
        }
        
        if (isset($_REQUEST['flush_all'])) {
            $this->flush_all();
            $notes[] = 'All caches emptied successfully.';
        }
        
        if (isset($_REQUEST['flush_memcached'])) {
            $this->flush_memcached();
            $notes[] = 'Memcached cache emptied successfully.';
        }
        
        if (isset($_REQUEST['flush_apc'])) {
            $this->flush_apc();
            $notes[] = 'APC cache emptied successfully.';
        }
        
        if (isset($_REQUEST['flush_file'])) {
            $this->flush_file();
            $notes[] = 'Disk cache emptied successfully.';
        }
        
        /**
         * Do some checks
         */
        $check_memcache = $this->check_memcache();
        $check_apc = $this->check_apc();
        
        if (! $check_memcache && ! $check_apc) {
            $errors[] = '<strong>Memcached</strong> nor <strong>APC</strong> appear to be installed correctly.';
        }
        
        if ($config->get_boolean('dbcache.enabled') && ! $this->check_db()) {
            $errors[] = '<strong>Database caching</strong> is not available. <strong>db.php</strong> is not installed. Either the wp-content directory is not write-able or you have another caching plugin installed.';
        }
        
        if ($config->get_boolean('pgcache.enabled')) {
            if (! $this->check_advanced_cache()) {
                $errors[] = '<strong>Page caching</strong> is not available. <strong>advanced-cache.php</strong> is not installed. Either the <strong>wp-content</strong> directory is not write-able or you have another caching plugin installed.';
            } elseif (! defined('WP_CACHE')) {
                $errors[] = '<strong>Page caching</strong> is not available. <strong>WP_CACHE</strong> constant is not defined in wp-config.php.';
            }
        }
        
        if ($config->get_boolean('common.defaults', true)) {
            $notes[] = 'The plugin is in quick setup mode, our recommended defaults are set. Simply satisfy all warnings and enable the plugin to get started or customize all of the settings you wish.';
            
            if ($tab == 'cdn') {
                $notes[] = 'It appears this is the first time you are using this feature. Unless you wish to first import attachments in your posts that are not already in the media library, please start a "manual export to <acronym title="Content Delivery Network">CDN</acronym>" and only enable this module after pending attachments have been successfully uploaded.';
            }
        }
        
        /**
         * Show page
         */
        include W3_PLUGIN_DIR . '/inc/options/common/header.phtml';
        include W3_PLUGIN_DIR . '/inc/options/' . $tab . '.phtml';
        include W3_PLUGIN_DIR . '/inc/options/common/footer.phtml';
    }
    
    /**
     * CDN queue action
     */
    function cdn_queue()
    {
        require_once dirname(__FILE__) . '/../Request.php';
        require_once dirname(__FILE__) . '/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $cdn_queue_action = W3_Request::get_string('cdn_queue_action');
        $cdn_queue_tab = W3_Request::get_string('cdn_queue_tab');
        
        $notes = array();
        
        switch ($cdn_queue_tab) {
            case 'upload':
            case 'delete':
                break;
                
            default:
                $cdn_queue_tab = 'upload';
        }
        
        switch ($cdn_queue_action) {
            case 'delete':
                $cdn_queue_id = W3_Request::get_integer('cdn_queue_id');
                if (! empty($cdn_queue_id)) {
                    $w3_plugin_cdn->queue_delete($cdn_queue_id);
                    $notes[] = 'File successfully deleted from the queue';
                }
                break;
            
            case 'empty':
                $cdn_queue_type = W3_Request::get_integer('cdn_queue_type');
                if (! empty($cdn_queue_type)) {
                    $w3_plugin_cdn->queue_empty($cdn_queue_type);
                    $notes[] = 'Queue successfully emptied';
                }
                break;
        }
        
        $queue = $w3_plugin_cdn->queue_get();
        $title = 'Unsuccessfull transfers queue';
        
        include W3_PLUGIN_DIR . '/inc/popup/common/header.phtml';
        include W3_PLUGIN_DIR . '/inc/popup/cdn_queue.phtml';
        include W3_PLUGIN_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN export library action
     */
    function cdn_export_library()
    {
        require_once dirname(__FILE__) . '/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $total = $w3_plugin_cdn->get_library_objects_count();
        $title = 'Media library export';
        
        include W3_PLUGIN_DIR . '/inc/popup/common/header.phtml';
        include W3_PLUGIN_DIR . '/inc/popup/cdn_export_table.phtml';
        include W3_PLUGIN_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN exprt library process
     */
    function cdn_export_library_process()
    {
        set_time_limit(300);
        
        require_once dirname(__FILE__) . '/../Request.php';
        require_once dirname(__FILE__) . '/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        
        $count = null;
        $total = null;
        $results = array();
        
        $w3_plugin_cdn->export_library($limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{local_path: '%s', remote_path: '%s', result: %d, error: '%s'}", addslashes($result['local_path']), addslashes($result['remote_path']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN export includes action
     */
    function cdn_export()
    {
        require_once dirname(__FILE__) . '/../Request.php';
        require_once dirname(__FILE__) . '/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $cdn_export_type = W3_Request::get_string('cdn_export_type', 'custom');
        
        switch ($cdn_export_type) {
            case 'includes':
                $title = 'Includes export';
                $files = $w3_plugin_cdn->get_files_includes();
                break;
            
            case 'theme':
                $title = 'Theme export';
                $files = $w3_plugin_cdn->get_files_theme();
                break;
            
            case 'minify':
                $title = 'Minify export';
                $files = $w3_plugin_cdn->get_files_minify();
                break;
            
            default:
            case 'custom':
                $title = 'Custom files export';
                $files = $w3_plugin_cdn->get_files_custom();
                break;
        }
        
        include W3_PLUGIN_DIR . '/inc/popup/common/header.phtml';
        include W3_PLUGIN_DIR . '/inc/popup/cdn_export_file.phtml';
        include W3_PLUGIN_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN export process
     */
    function cdn_export_process()
    {
        set_time_limit(300);
        
        require_once dirname(__FILE__) . '/../Request.php';
        require_once dirname(__FILE__) . '/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $files = W3_Request::get_array('files');
        $results = array();
        
        $w3_plugin_cdn->upload($files, false, $results);
        
        echo "{results: [\r\n";
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{local_path: '%s', remote_path: '%s', result: %d, error: '%s'}", addslashes($result['local_path']), addslashes($result['remote_path']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN Test FTP
     */
    function cdn_test_ftp()
    {
        require_once dirname(__FILE__) . '/../Request.php';
        require_once dirname(__FILE__) . '/../Cdn.php';
        
        $host = W3_Request::get_string('host');
        $user = W3_Request::get_string('user');
        $pass = W3_Request::get_string('pass');
        $path = W3_Request::get_string('path');
        $pasv = W3_Request::get_string('pasv');
        
        $w3_cdn_ftp = & W3_Cdn::instance('ftp', array(
            'host' => $host, 
            'user' => $user, 
            'pass' => $pass, 
            'path' => $path, 
            'pasv' => $pasv
        ));
        
        $error = null;
        
        if ($w3_cdn_ftp->test($error)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = sprintf('Test failed. Error: %s', $error);
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * Insert plugin link into Blogroll
     */
    function link_insert()
    {
        $bookmarks = get_bookmarks();
        $exists = false;
        foreach ($bookmarks as $bookmark) {
            if ($bookmark->link_url == W3_PLUGIN_TOTALCACHE_LINK_URL) {
                $exists = true;
                break;
            }
        }
        
        if (! $exists) {
            require_once ABSPATH . 'wp-admin/includes/bookmark.php';
            wp_insert_link(array(
                'link_url' => W3_PLUGIN_TOTALCACHE_LINK_URL, 
                'link_name' => W3_PLUGIN_TOTALCACHE_LINK_NAME
            ));
        }
    }
    
    /**
     * Deletes plugin link from Blogroll
     */
    function link_delete()
    {
        $bookmarks = get_bookmarks();
        $link_id = 0;
        foreach ($bookmarks as $bookmark) {
            if ($bookmark->link_url == W3_PLUGIN_TOTALCACHE_LINK_URL) {
                $link_id = $bookmark->link_id;
                break;
            }
        }
        
        if ($link_id) {
            require_once ABSPATH . 'wp-admin/includes/bookmark.php';
            wp_delete_link($link_id);
        }
    }
    
    /**
     * Flush specified cache
     *
     * @param string $type
     */
    function flush($type)
    {
        if ($this->_config->get_string('dbcache.engine') == $type) {
            require_once W3_PLUGIN_DIR . '/lib/W3/Db.php';
            $w3_db = W3_Db::instance();
            $w3_db->flush();
        }
        
        if ($this->_config->get_string('pgcache.engine') == $type) {
            require_once W3_PLUGIN_DIR . '/lib/W3/PgCache.php';
            $w3_pgcache = W3_PgCache::instance();
            $w3_pgcache->flush();
        }
        
        if ($this->_config->get_string('minify.engine') == $type) {
            require_once W3_PLUGIN_DIR . '/lib/W3/Minify.php';
            $w3_minify = W3_Minify::instance();
            $w3_minify->flush();
        }
    }
    
    /**
     * Flush all caches
     */
    function flush_all()
    {
        $this->flush_memcached();
        $this->flush_apc();
        $this->flush_file();
    }
    
    /**
     * Flush memcached cache
     */
    function flush_memcached()
    {
        $this->flush('memcached');
    }
    
    /**
     * Flush APC cache
     */
    function flush_apc()
    {
        $this->flush('memcached');
    }
    
    /**
     * Flush file cache
     */
    function flush_file()
    {
        $this->flush('file');
    }
    
    /**
     * Checks if advanced-cache.php exists
     * 
     * @return boolean
     */
    function check_advanced_cache()
    {
        return (file_exists(WP_CONTENT_DIR . '/advanced-cache.php') && ($script_data = @file_get_contents(WP_CONTENT_DIR . '/advanced-cache.php')) && strstr($script_data, 'W3_PgCache') !== false);
    }
    
    /**
     * Checks if db.php exists
     *
     * @return boolean
     */
    function check_db()
    {
        return (file_exists(WP_CONTENT_DIR . '/db.php') && ($script_data = @file_get_contents(WP_CONTENT_DIR . '/db.php')) && strstr($script_data, 'W3_Db') !== false);
    }
    
    /**
     * Checks Memcache availability
     *
     * @return boolean
     */
    function check_memcache()
    {
        return class_exists('Memcache');
    }
    
    /**
     * Checks APC availability
     *
     * @return boolean
     */
    function check_apc()
    {
        return function_exists('apc_store');
    }
    
    /**
     * Output buffering callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        global $wpdb;
        
        if (strstr($buffer, '<html') === false) {
            return $buffer;
        }
        
        $host = gethostbyaddr($_SERVER['SERVER_ADDR']);
        $date = date('Y-m-d H:i:s');
        $time = timer_stop();
        
        if ($this->_config->get_boolean('common.support')) {
            $buffer .= sprintf("\r\n<!--Served from: %s @ %s in %.3f seconds by W3 Total Cache -->", $host, $date, $time);
        } else {
            $buffer .= <<<DATA
<!--
This site's performance optimized by W3 Total Cache:

W3 Total Cache improves the user experience of your blog by caching
frequent operations, reducing the weight of various files and providing
transparent content delivery network integration. The goal is to improve the
user experience for the readers of your blog without having to change
WordPress, your theme, your plugins or how you produce your content. When fully
utilized, your blog will be able to sustain extremely high traffic spikes
without requiring hardware upgrades or removing features or functionality from
your theme.
	
Features and benefits include:

	- Improved progressive render (non-blocking CSS and JS embedding)
	- Reduced HTTP Transactions, DNS lookups, reduced document load time
	- Transparent content delivery network (CDN) support with automated media
		library import
	- Bandwidth savings via Minify and HTTP compression (gzip / deflate) for HTML, CSS
		and JS
	- Minification (concatenation, white space removal) of inline, external or 3rd
		party JS and CSS with scheduled updates
	- Optional embedding of JS just above </body>
	- Support for caching pages, posts, feeds, database objects, CSS, JS in memory
		with APC or memcached or both
	- Caching of RSS/Atom Feeds (comments, page and site), URIs with query string
		variables (like search result pages), Database queries, Pages, Posts, CSS and JS
	- Complete header management including Etags
	- Increased web server concurrency and reduced resource consumption, increased scale


Learn more about our WordPress Plugins: http://www.w3-edge.com/wordpress-plugins/
DATA;
            
            $buffer .= "\r\n\r\n";
            
            if ($this->_config->get_boolean('minify.enabled', true)) {
                $buffer .= sprintf("Minified using %s\r\n", $this->_config->get_string('minify.engine', 'N/A'));
            }
            
            if ($this->_config->get_boolean('pgcache.enabled', true)) {
                $buffer .= sprintf("Page Caching using %s\r\n", $this->_config->get_string('pgcache.engine', 'N/A'));
            }
            
            if ($this->_config->get_boolean('dbcache.enabled', true) && is_a($wpdb, 'W3_Db')) {
                $buffer .= sprintf("Database Caching %d/%d queries in %.3f seconds using %s\r\n", $wpdb->query_total, $wpdb->query_hits, $wpdb->time_total, $this->_config->get_string('dbcache.engine', 'N/A'));
            }
            
            if ($this->_config->get_boolean('cdn.enabled', true)) {
                $buffer .= sprintf("Content Delivery Network via %s\r\n", $this->_config->get_string('cdn.domain', 'N/A'));
            }
            
            $buffer .= sprintf("\r\nServed from: %s @ %s in %.3f seconds -->", $host, $date, $time);
        }
        
        return $buffer;
    }
}