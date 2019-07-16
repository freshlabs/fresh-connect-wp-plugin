<?php
/**
 * Proxy class for WordPress' function calls. This is the only class that should be able to use WordPress' internal functions.
 * The rule of thumb is that if a function does not exist since WordPress 3.0.0, it should be defined here.
 */
class FastPress_Context
{

    private $context;

    private $constants;
    
    private $results;
    
    private $columns;

    public function __construct(array &$globals = null, array $constants = null)
    {
        if ($globals !== null) {
            $this->context = $globals;
        } else {
            $this->context = &$GLOBALS;
        }

        if ($constants !== null) {
            $this->constants = $constants;
        }
    }

    public function set($name, $value)
    {
        $this->context[$name] = $value;
    }

    public function get($name)
    {
        return isset($this->context[$name]) ? $this->context[$name] : null;
    }

    /**
     * @return wpdb
     */
    public function getDb()
    {
        return $this->context['wpdb'];
    }

    /**
     * Escapes data for use in a MySQL query.
     *
     * Usually you should prepare queries using wpdb::prepare().
     * Sometimes, spot-escaping is required or useful. One example
     * is preparing an array for use in an IN clause.
     *
     * @param array|string $data
     *
     * @return array|string
     */
    public function escapeParameter($data)
    {
        return esc_sql($data);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->context['wp_version'];
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    public function isVersionAtLeast($version)
    {
        if (version_compare($version, $this->getVersion(), '<=')) {
            return true;
        }

        return false;
    }

    /**
     * @param string   $tag
     * @param Callable $functionToAdd
     * @param int      $priority
     * @param int      $acceptedArgs
     *
     * @see  add_action()
     * @link http://codex.wordpress.org/Function_Reference/add_action
     */
    public function addAction($tag, $functionToAdd, $priority = 10, $acceptedArgs = 1)
    {
        add_action($tag, $functionToAdd, $priority, $acceptedArgs);
    }

    /**
     * @param string $name
     * @param array  $args
     */
    public function doAction($name, array $args = array())
    {
        do_action($name, $args);
    }

    /**
     * @param string $optionName The option to delete.
     * @param bool   $global Whether to delete the option from the whole network. Used for network un-installation.
     *
     * @see  delete_site_option()
     * @see  delete_option()
     * @link http://codex.wordpress.org/Function_Reference/register_uninstall_hook
     */
    public function optionDelete($optionName, $global = false)
    {
        if ($global && is_multisite()) {
            $db      = $this->getDb();
            $blogIDs = $db->get_col("SELECT blog_id FROM $db->blogs");
            foreach ($blogIDs as $blogID) {
                delete_blog_option($blogID, $optionName);
            }
        } else {
            delete_option($optionName);
        }
    }

    /**
     * @param string $optionName
     * @param mixed  $optionValue
     * @param bool   $global
     *
     * @see update_site_option()
     * @see update_option()
     * @link
     */
    public function optionSet($optionName, $optionValue, $global = false)
    {
        if ($global && is_multisite()) {
            $db      = $this->getDb();
            $blogIDs = $db->get_col("SELECT blog_id FROM $db->blogs");
            foreach ($blogIDs as $blogID) {
                update_blog_option($blogID, $optionName, $optionValue);
            }
        } else {
            update_option($optionName, $optionValue, true);
        }
    }
    
    public function setMultipleOptions($options)
    {
        if(!empty($options)){
            foreach($options as $key => $value){
                update_option($key, $value);
            }
        }
    }

    /**
     * @param string $option Name of option to retrieve.
     * @param mixed  $default Optional. Default value to return if the option does not exist.
     * @param int    $siteId Site ID to update. Only used in multisite installations.
     * @param bool   $useCache Whether to use cache. Multisite only.
     *
     * @return mixed Value set for the option.
     *
     * @see  get_option()
     * @link http://codex.wordpress.org/Function_Reference/get_option
     */
    public function optionGet($option, $default = false, $siteId = null, $useCache = true)
    {
        if ($siteId !== null && is_multisite()) {
            return get_site_option($option, $default, $useCache);
        }

        return get_option($option, $default);
    }

    /**
     * WordPress' function get_current_blog_id() is not available before version 3.1.0.
     *
     * @return int
     *
     * @see get_current_blog_id()
     */
    public function getCurrentBlogId()
    {
        return abs(intval($this->context['blog_id']));
    }

    /**
     * @param string $constant
     *
     * @return bool
     */
    public function hasConstant($constant)
    {
        if (is_array($this->constants)) {
            return isset($this->constants[$constant]);
        }

        return defined($constant);
    }

    /**
     * @param string $constant
     *
     * @return int|string
     * @throws Exception If the constant does not exist.
     */
    public function getConstant($constant)
    {
        if (!$this->hasConstant($constant)) {
            throw new Exception(sprintf('The constant "%s" is not defined', $constant));
        }

        if (is_array($this->constants)) {
            return $this->constants[$constant];
        }

        return constant($constant);
    }

    public function setConstant($name, $value, $throw = true)
    {
        if ($this->hasConstant($name)) {
            if ($throw) {
                throw new Exception(sprintf('The constant "%s" is already defined', $name));
            }

            return;
        }

        if (is_array($this->constants)) {
            $this->constants[$name] = $value;

            return;
        }

        define($name, $value);
    }

    /**
     * @return string
     *
     * @see plugin_basename()
     */
    public function getPluginBasename()
    {
        $dirName = explode('/', plugin_basename(__FILE__), 2);
        $dirName = $dirName[0];

        return $dirName.'/init.php';
    }

    public function getPlugins()
    {
        if (!function_exists('get_mu_plugins')) {
            require_once($this->getConstant('ABSPATH').'wp-admin/includes/plugin.php');
        }

        return get_plugins();
    }

    public function getMustUsePlugins()
    {
        if (!function_exists('get_mu_plugins')) {
            require_once($this->getConstant('ABSPATH').'wp-admin/includes/plugin.php');
        }

        return get_mu_plugins();
    }

    public function isPluginActive($pluginBasename)
    {
        return is_plugin_active($pluginBasename);
    }

    public function isPluginActiveForNetwork($pluginBasename)
    {
        return is_plugin_active_for_network($pluginBasename);
    }

    public function getThemes()
    {
        // When the plugin is MU-loaded, the WordPress theme directories are not set.
        if (empty($this->context['wp_theme_directories'])) {
            // Register the default theme directory root.
            register_theme_directory(get_theme_root());
        }

        if ($this->isVersionAtLeast('3.4')) {
            return wp_get_themes();
        }

        return get_themes();
    }

    public function getCurrentTheme()
    {
        // When the plugin is MU-loaded, the WordPress theme directories are not set.
        if ($this->isMustUse() && empty($this->context['wp_theme_directories'])) {
            // Register the default theme directory root.
            register_theme_directory(get_theme_root());
        }

        if ($this->isVersionAtLeast('3.4')) {
            return wp_get_theme();
        }

        return get_current_theme();
    }

    public function getStylesheetDirectory()
    {
        return get_stylesheet_directory();
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $expire Expiration time in seconds from now.
     *
     * @return bool
     */
    public function transientSet($key, $value, $expire = 0)
    {
        return set_site_transient($key, $value, $expire);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function transientGet($key)
    {
        return get_site_transient($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function transientDelete($key)
    {
        return delete_site_transient($key);
    }

    private function isMustUse()
    {
        if (empty($this->context['mwp_is_mu'])) {
            return false;
        }

        return true;
    }

    /**
     * @param string   $tag
     * @param Callable $functionToAdd
     * @param int      $priority
     * @param int      $acceptedArgs
     */
    public function addFilter($tag, $functionToAdd, $priority = 10, $acceptedArgs = 1)
    {
        add_filter($tag, $functionToAdd, $priority, $acceptedArgs);
    }

    public function enqueueScript($handle, $src = false, $dependencies = array(), $ver = false, $inFooter = false)
    {
        wp_enqueue_script($handle, $src, $dependencies, $ver, $inFooter);
    }

    public function enqueueStyle($handle, $src = false, $dependencies = array(), $ver = false, $media = 'all')
    {
        wp_enqueue_style($handle, $src, $dependencies, $ver, $media);
    }

    public function addMenuPage($pageTitle, $menuTitle, $capability, $slug, $callback = '', $iconUrl = '', $position = null)
    {
        add_menu_page($pageTitle, $menuTitle, $capability, $slug, $callback, $iconUrl, $position);
    }

    public function translate($text, $domain = 'default')
    {
        return translate($text, $domain);
    }

    public function output($content)
    {
        echo esc_html($content);
    }

    public function getCurrentUser()
    {
        $this->requirePluggable();
        $this->requireCookieConstants();

        return wp_get_current_user();
    }

    public function getHomeUrl()
    {
        return get_home_url();
    }

    public function sendMail($to, $subject, $message, $headers = '', $attachments = array())
    {
        $this->requirePluggable();

        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    public function getAdminUrl($where)
    {
        return admin_url($where);
    }

    public function isInAdminPanel()
    {
        return is_admin();
    }

    public function isGranted($capability)
    {
        $this->requirePluggable();
        $this->requireCookieConstants();

        return current_user_can($capability);
    }

    /**
     * @param string $name Value name.
     *
     * @return mixed Context (global) value. Null if one doesn't exist.
     *
     * @throws Exception If the context value does not exist.
     */
    public function &getContextValue($name)
    {
        if (!$this->hasContextValue($name)) {
            throw new Exception(sprintf('Context value "%s" does not exist', $name));
        }

        return $this->context[$name];
    }

    /**
     * @param string $name Value name.
     *
     * @return bool
     */
    public function hasContextValue($name)
    {
        return array_key_exists($name, $this->context);
    }

    public function getDropInPlugins()
    {
        if (!function_exists('get_dropins')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }

        return get_dropins();
    }

    public function requirePluggable()
    {
        require_once $this->getConstant('ABSPATH').$this->getConstant('WPINC').'/pluggable.php';
    }

    public function requireCookieConstants()
    {
        wp_cookie_constants();
    }

    public function requireAdminUserLibrary()
    {
        require_once $this->getConstant('ABSPATH').'wp-admin/includes/user.php';
    }

    public function getUserRoles()
    {
        $this->requireAdminUserLibrary();

        return get_editable_roles();
    }

    /**
     * @param string $username
     *
     * @return WP_User|stdClass|null
     */
    public function getUserByUsername($username)
    {
        $this->requirePluggable();

        $user = get_user_by('login', $username);

        if (!$user) {
            return null;
        }

        return $user;
    }

    /**
     * @param $criteria
     *
     * @return WP_User[]|stdClass[]
     *
     * @link http://codex.wordpress.org/Function_Reference/get_users
     *
     * Defaults:
     *  'blog_id'      => $GLOBALS['blog_id']
     *  'role'         => ''
     *  'meta_key'     => ''
     *  'meta_value'   => ''
     *  'meta_compare' => ''
     *  'meta_query'   => array()
     *  'include'      => array()
     *  'exclude'      => array()
     *  'orderby'      => 'login'
     *  'order'        => 'ASC'
     *  'offset'       => ''
     *  'search'       => ''
     *  'number'       => ''
     *  'count_total'  => false
     *  'fields'       => 'all'
     *  'who'          => ''
     */
    public function getUsers($criteria)
    {
        return get_users($criteria);
    }

    public function isPluginEnabled($pluginBasename)
    {
        $plugins = (array)$this->optionGet('active_plugins', array());

        return in_array($pluginBasename, $plugins);
    }

    /**
     * @param WP_User|stdClass $user
     */
    public function setCurrentUser($user)
    {
        $this->requirePluggable();

        wp_set_current_user($user->ID);
    }

    /**
     * @param WP_User|stdClass $user
     * @param bool             $remember
     * @param string           $secure
     */
    public function setAuthCookie($user, $remember = false, $secure = '')
    {
        $this->requireCookieConstants();

        wp_set_auth_cookie($user->ID, $remember, $secure);
    }

    public function wpDie($message = '', $title = '', $args = array())
    {
        wp_die($message, $title, $args);
        // This is just a stub, the script will have exit()-ed just before this point.
        exit();
    }

    /**
     * Returns current site's URL.
     *
     * @return string|void
     */
    public function getSiteUrl()
    {
        return get_bloginfo('wpurl');
    }

    public function requireWpRewrite()
    {
        if (isset($this->context['wp_rewrite']) && $this->context['wp_rewrite'] instanceof WP_Rewrite) {
            return;
        }

        /** @handled class */
        $this->context['wp_rewrite'] = new WP_Rewrite();
    }

    public function requireTaxonomies()
    {
        if (!empty($this->context['wp_taxonomies'])) {
            return;
        }

        create_initial_taxonomies();
    }

    public function requirePostTypes()
    {
        if (!empty($this->context['wp_post_types'])) {
            return;
        }

        create_initial_post_types();
    }

    public function requireTheme()
    {
        if (!empty($this->context['wp_theme_directories'])) {
            return;
        }

        register_theme_directory(get_theme_root());
    }

    public function getLocale()
    {
        return get_locale();
    }

    public function tryDeserialize($content)
    {
        return maybe_unserialize($content);
    }

    public function getSiteTitle()
    {
        return get_bloginfo('name');
    }

    public function getSiteDescription()
    {
        return get_bloginfo('description');
    }
    
    public function getAdminEmail()
    {
        return get_option('admin_email');
    }

    /**
     * Always returns main site's url (in multisite installations).
     *
     * @see getSiteUrl
     *
     * @return string|void
     */
    public function getMasterSiteUrl()
    {
        return site_url();
    }

    public function isMultisite()
    {
        return is_multisite();
    }

    public function isMainSite()
    {
        return is_main_site();
    }

    public function isNetworkAdmin()
    {
        return is_network_admin();
    }

    public function getSiteId()
    {
        return get_current_blog_id();
    }

    public function getDbName()
    {
        return $this->getConstant('DB_NAME');
    }

    /**
     * @param int    $attachmentId
     * @param string $style
     *
     * @return null
     */
    public function getImageInfo($attachmentId, $style)
    {
        $info = wp_get_attachment_image_src($attachmentId, $style);

        if (!$info) {
            return null;
        }

        return array(
            'url'      => $info[0],
            'width'    => $info[1],
            'height'   => $info[2],
            'original' => !$info[3],
        );
    }

    public function addImageStyle($name, $width = 0, $height = 0, $crop = false)
    {
        add_image_size($name, $width, $height);
    }

    public function setCookie($name, $value, $expire = 0)
    {
        setcookie($name, $value, $expire, $this->getConstant('SITECOOKIEPATH'), $this->getConstant('COOKIE_DOMAIN'), $this->isSsl(), true);
    }

    /**
     * @return bool
     */
    public function isSsl()
    {
        return (bool)is_ssl();
    }
    
    public function setSSL($value)
    {
        $url = site_url();
        if($value){
            $url = str_replace('http://', 'https://', $url);
            update_option('siteurl', $url);
            update_option('home', $url);
        }else{
            $url = str_replace('https://', 'http://', $url);
            update_option('siteurl', $url);
            update_option('home', $url);
        }
    }

    /**
     * @return bool
     */
    public function isSslAdmin()
    {
        return $this->isSsl() || force_ssl_admin();
    }

    public function removeAction($tag, $function, $priority = 10)
    {
        remove_action($tag, $function, $priority);
    }

    public function addSubMenuPage($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function = '')
    {
        return add_submenu_page($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function);
    }

    public function wpNonceUrl($url, $action = -1, $name = '_wpnonce')
    {
        return wp_nonce_url($url, $action, $name);
    }

    /**
     * @param int    $userId
     * @param string $metaKey
     *
     * @return mixed
     */
    public function getUserMeta($userId, $metaKey)
    {
        return get_user_meta($userId, $metaKey, true);
    }

    /**
     * @param int    $userId
     * @param string $metaKey
     * @param mixed  $metaValue
     *
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
     */
    public function setUserMeta($userId, $metaKey, $metaValue)
    {
        return update_user_meta($userId, $metaKey, $metaValue);
    }

    /**
     * @param int $userId
     *
     * @return WP_Session_Tokens|null Returns null if the class does not exist, ie. before WordPress version 4.0.0.
     */
    public function getSessionTokens($userId)
    {
        if (!class_exists('WP_Session_Tokens', false)) {
            return null;
        }

        /** @handled static */

        return WP_Session_Tokens::get_instance($userId);
    }

    public function getCurrentTime()
    {
        return new DateTime('@'.current_time('timestamp'));
    }

    /**
     * @param int    $userId
     * @param string $key
     * @param mixed  $value
     *
     * @return bool|int
     */
    public function updateUserMeta($userId, $key, $value)
    {
        return update_user_meta($userId, $key, $value);
    }

    /**
     * @param string $str
     *
     * @return bool
     */
    public function seemsUtf8($str)
    {
        return seems_utf8($str);
    }
	
	public function strpos_arr($haystack, $needle) {
		if(!is_array($needle)) $needle = array($needle);
		foreach($needle as $what) {
			if((0 === strpos($haystack, $what))!==false) return 1;
		}
		return false;
	}
    
    public function unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {
        try {
            if ( false !== is_serialized( $data ) ) {
                $unserialized = unserialize( $data );
                $data = $this->unserialize_replace( $from, $to, $unserialized, true );
            }
            elseif ( is_array( $data ) ) {
                $_tmp = array( );
                foreach ( $data as $key => $value ) {
                    $_tmp[ $key ] = $this->unserialize_replace( $from, $to, $value, false );
                }
                $data = $_tmp;
                unset( $_tmp );
            }
            else {
                if ( is_string( $data ) )
                    $data = str_replace( $from, $to, $data );
            }
            if ( $serialised )
                return serialize( $data );
        } catch( Exception $error ) {
        }
        return $data;
    }
    
    public function update_urls($oldurl,$newurl){
        $db = $this->getDb();
        
        $options = array('content', 'excerpts', 'links', 'attachments', 'custom' => array('commentmeta', 'postmeta', 'termmeta', 'usermeta', 'options'), 'comments', 'term_taxonomy');
        
        $core_tables = array('commentmeta', 'comments', 'links', 'options', 'postmeta', 'posts', 'term_relationships', 'term_taxonomy', 'termmeta', 'terms', 'usermeta', 'users');

        $this->results = array();
		
		$prefix = $db->prefix;
		$tables_mysql = $db->get_results('SHOW TABLES', ARRAY_N);
		
		if (!$tables_mysql) {
			return array( 'result' => array('error' => 'Could not get list of tables') );
		}
        
        $queries = array(
            'content' =>        array("UPDATE $db->posts SET post_content = replace(post_content, %s, %s)",  __('Content Items (Posts, Pages, Custom Post Types, Revisions)', FRESH_TEXT_DOMAIN) ),
            'excerpts' =>       array("UPDATE $db->posts SET post_excerpt = replace(post_excerpt, %s, %s)", __('Excerpts', FRESH_TEXT_DOMAIN) ),
            'attachments' =>    array("UPDATE $db->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'",  __('Attachments', FRESH_TEXT_DOMAIN) ),
            'links' =>          array("UPDATE $db->links SET link_url = replace(link_url, %s, %s)", __('Links', FRESH_TEXT_DOMAIN) ),
            'custom' =>         array("UPDATE $db->postmeta SET meta_value = replace(meta_value, %s, %s)",  __('Custom Fields', FRESH_TEXT_DOMAIN) ),
            'comments' =>        array("UPDATE $db->comments SET comment_content = replace(comment_content, %s, %s)",  __('Comments', FRESH_TEXT_DOMAIN) ),
            'term_taxonomy' =>        array("UPDATE $db->term_taxonomy SET description = replace(description, %s, %s)",  __('Taxonomy', FRESH_TEXT_DOMAIN) ),
            'guids' =>          array("UPDATE $db->posts SET guid = replace(guid, %s, %s)",  __('GUIDs', FRESH_TEXT_DOMAIN) )
        );
        
        foreach($options as $option){
            if(is_array($option)) {
                foreach($option as $table){
                    $this->process_row_meta($table, $oldurl, $newurl);
                }
            }
            else
            {
                $result = $db->query( $db->prepare( $queries[$option][0], $oldurl, $newurl) );
                $this->results[$option] = array($result, $queries[$option][1]);
            }
        }
        
        $tables = array();
        foreach ($tables_mysql as $table) {
        
            if (0 === strpos($table[0], $prefix)) {
                $tablename = $table[0];
            
                $stripped_table = substr($tablename, strlen($prefix));
                
                if( !in_array($stripped_table, $core_tables) ){
                    $tables[$tablename] = $stripped_table;
                }
            }
        }
        
        if( !empty($tables) ){
            $this->migrator_srdb_replacer($oldurl, $newurl, $tables);
        }
        
        return $this->results;            
    }
    
    public function migrator_srdb_replacer( $oldurl, $newurl, $tables ) {
        $db = $this->getDb();
        
        $field_types = array('varchar', 'text', 'longtext', 'mediumtext');
		
		foreach ($tables as $table => $stripped_table) {
			// Get a list of columns in this table
			$fields = $db->get_results('DESCRIBE '.$table, ARRAY_A);
			
			$this->columns = array();
			$prikey_field = false;
			foreach ($fields as $column) {
				$primary_key = ('PRI' == $column['Key']) ? true : false;
				if ($primary_key) $prikey_field = $column['Field'];
				
				if( $this->strpos_arr($column['Type'], $field_types) ){
					$this->columns[] = array('Field' => $column['Field'], 'Type' => $column['Type'], 'Key' => $prikey_field);
				}
			}
			
			if( !empty($this->columns) && $prikey_field ){
				$this->process_columns($table, $oldurl, $newurl, $stripped_table);
			}
		}
    }
    
    public function process_row_meta($table, $oldurl, $newurl) {
        $db = $this->getDb();
        $tbl_name = $db->$table;

        $n = 0;
        $row_count = $db->get_var( "SELECT COUNT(*) FROM $tbl_name" );
        $page_size = 10000;
        $pages = ceil( $row_count / $page_size );
        
        if($table == 'options'){
            $mt_value = 'option_value';
            $mt_option = 'option_name';
            $mt_id = 'option_id';
        }
        else
        {
            $mt_value = 'meta_value';
            $mt_id = 'meta_id';
        }
        
        for( $page = 0; $page < $pages; $page++ ) {
            $current_row = 0;
            $start = $page * $page_size;
            $end = $start + $page_size;
            $pmquery = "SELECT * FROM $tbl_name WHERE $mt_value <> ''";
            $items = $db->get_results( $pmquery );
            foreach( $items as $item ){
            $value = $item->$mt_value;
            if( trim($value) == '' )
                continue;
            
                $edited = $this->unserialize_replace( $oldurl, $newurl, $value );
            
                if( $edited != $value ){
                    if($table == 'options'){
                        $fix = $db->query("UPDATE $tbl_name SET $mt_value = '".$edited."' WHERE $mt_id = '".$item->$mt_id."' AND $mt_option != 'siteurl' AND $mt_option != 'home'" );
                    }else{
                        $fix = $db->query("UPDATE $tbl_name SET $mt_value = '".$edited."' WHERE $mt_id = ".$item->$mt_id );
                    }
                    
                    if( $fix )
                        $n++;
                }
            }
        }
        $this->results[$table] = array($n);
    }
	
	public function process_columns( $table, $oldurl, $newurl, $stripped_table ) {
		$db = $this->getDb();
		$n = 0;
        $row_count = $db->get_var( "SELECT COUNT(*) FROM $table" );
        $page_size = 5000;
        $pages = ceil( $row_count / $page_size );
		
		for( $page = 0; $page < $pages; $page++ ) {
			$current_row = 0;
            $start = $page * $page_size;
            $end = $start + $page_size;
			
			foreach ($this->columns as $column){
				$pri_key = $column['Key'];
				$col_name = $column['Field'];
				$pmquery = "SELECT $pri_key, $col_name FROM $table WHERE $col_name <> ''";
				$items = $db->get_results( $pmquery );
				
				foreach( $items as $item ){
					$value = $item->$col_name;
					if( trim($value) == '' )
						continue;
					
						$edited = $this->unserialize_replace( $oldurl, $newurl, $value );
					
						if( $edited != $value ){
							$fix = $db->query("UPDATE $table SET $col_name = '".$edited."' WHERE $pri_key = ".$item->$pri_key );
							
							if( $fix )
							$n++;
						}
				}
			}
		}
		$this->results[$stripped_table] = array($n);
    }
    
    /**
	 * Test if the REST API is accessible.
	 *
	 * Various security measures may block the REST API from working, or it may have been disabled in general.
	 * This is required for the new block editor to work, so we explicitly test for this.
	 *
	 * @return array The test results.
	 */
	public function get_test_rest_availability() {
		$result = array(
			'label'       => __( 'The REST API is available' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Performance' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'The REST API is one way WordPress, and other applications, communicate with the server. One example is the block editor screen, which relies on this to display, and save, your posts and pages.' )
			),
			'actions'     => '',
			'test'        => 'rest_availability',
		);

		$cookies = wp_unslash( $_COOKIE );
		$timeout = 10;
		$headers = array(
			'Cache-Control' => 'no-cache',
			'X-WP-Nonce'    => wp_create_nonce( 'wp_rest' ),
		);

		// Include Basic auth in loopback requests.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

        $url = rest_url( 'wp/v2/types/post' );
        
		$url = add_query_arg(
			array(
				'context' => 'edit',
			),
			$url
		);

		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

		if ( is_wp_error( $r ) ) {
			$result['status'] = 'critical';

			$result['label'] = __( 'The REST API encountered an error' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					'%s<br>%s',
					__( 'The REST API request failed due to an error.' ),
					sprintf(
						/* translators: 1: The HTTP response code. 2: The error message returned. */
						__( 'Error: [%1$s] %2$s' ),
						wp_remote_retrieve_response_code( $r ),
						$r->get_error_message()
					)
				)
			);
		} 

		return $result;
	}
}
