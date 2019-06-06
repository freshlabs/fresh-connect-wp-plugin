<?php
/*
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fastpress_Action_GetState
{
    const USERS = 'users';

    const POSTS = 'posts';

    const COMMENTS = 'comments';

    const SQL_RESULT = 'sqlResult';

    const SINGLE_SQL_RESULT = 'singleSqlResult';

    const PLUGINS = 'plugins';
    
    const EDIT_PLUGINS = 'edit_plugins';

    const THEMES = 'themes';
	
    const EDIT_THEMES = 'edit_themes';

    const PLUGIN_UPDATES = 'pluginUpdates';

    const THEME_UPDATES = 'themeUpdates';

    const CORE_UPDATES = 'coreUpdates';

    const ROLES = 'roles';

    const SITE_INFO = 'siteInfo';

    const SERVER_INFO = 'serverInfo';
	
	private $context;
	
	public function __construct(FastPress_Context $context)
    {
        $this->context = $context;
    }

    public function execute(array $params = array())
    {
        $result = array();

        foreach ($params as $fieldName => $queryInfo) {
            $start              = microtime(true);
            $queryResult        = $this->getField($queryInfo['type'], $queryInfo['options']);
            $end                = sprintf("%.6f", microtime(true) - $start);
            $result = array(
                'type'      => $queryInfo['type'],
                'benchmark' => $end,
                'result'    => $queryResult,
            );
        }

        return $result;
    }

    private function getField($type, $options)
    {
        switch ($type) {
            case self::USERS:
                return $this->getUsers($options);
            case self::POSTS:
                return $this->getPosts($options);
            case self::COMMENTS:
                return $this->getComments($options);
            case self::SQL_RESULT:
                return $this->getSqlResult($options);
            case self::SINGLE_SQL_RESULT:
                return $this->getSingleSqlResult($options);
            case self::PLUGINS:
                return $this->getPlugins($options);
            case self::EDIT_PLUGINS:
                return $this->editPlugins($options);
            case self::THEMES:
                return $this->getThemes($options);
			case self::EDIT_THEMES:
                return $this->editThemes($options);
            case self::PLUGIN_UPDATES:
                return $this->getPluginUpdates($options);
            case self::THEME_UPDATES:
                return $this->getThemeUpdates($options);
            case self::CORE_UPDATES:
                return $this->getCoreUpdates($options);
            case self::ROLES:
                return $this->getRoles($options);
            case self::SITE_INFO:
                return $this->getSiteInfo($options);
            case self::SERVER_INFO:
                return $this->getServerInfo($options);
            default:
                return array('error' => 'Undefined field type provided: '.$type);
        }
    }

    protected function getUsers(array $options = array())
    {
        $users     = getUsers($options);
        return $users;
    }

    protected function getPosts(array $options = array())
    {
        $posts     = array('msg' => 'this feature will be available in future version.');

        return $posts;
    }

    protected function getComments(array $options = array())
    {
        $comments     = array('msg' => 'this feature will be available in future version.');

        return $comments;
    }

    protected function getSingleSqlResult(array $options = array())
    {
        $options += array(
            'query' => null,
        );

        return $this->context->getDb()->get_var($options['query']);
    }

    protected function getSqlResult(array $options = array())
    {
        $options += array(
            'query' => null,
        );

        return $this->context->getDb()->get_results($options['query']);
    }

    protected function getPlugins(array $options = array())
    {
        require_once(FRESH_CONNECT_DIR_PATH .'inc/WordPress/Provider/Plugin.php');
        require_once(FRESH_CONNECT_DIR_PATH .'inc/Updater/UpdateManager.php');
        $options += array(
            'fetchDescription'     => false,
            'fetchAutoUpdate'      => true,
            'fetchAvailableUpdate' => false,
            'fetchActivatedAt'     => true,
        );

        $pluginProvider    = new FastPress_Provider_Plugin($this->context);
        $plugins           = $pluginProvider->fetch($options);

        if ($options['fetchAvailableUpdate']) {
            $um = new Fastpress_Updater_UpdateManager($this->context);
            foreach ($plugins as &$plugin) {
                if (!isset($plugin['basename'])) {
                    continue;
                }

                $update = $um->getPluginUpdate($plugin['basename']);
                if ($update !== null) {
                    $plugin['updateVersion'] = $update->version;
                    $plugin['updatePackage'] = $update->package;
                }
            }
        }

        if ($options['fetchActivatedAt']) {
            $recentlyActivated = $this->context->optionGet('recently_activated');
            foreach ($plugins as &$plugin) {
                if (isset($recentlyActivated[$plugin['basename']])) {
                    $plugin['activatedAt'] = date('Y-m-d\TH:i:sO', $recentlyActivated[$plugin['basename']]);
                }
            }
        }

        return $plugins;
    }
    
    protected function editPlugins(array $options = array())
    {
        require_once(FRESH_CONNECT_DIR_PATH .'inc/WordPress/Provider/Plugin.php');
        
        $pluginProvider    = new FastPress_Provider_Plugin($this->context);
        $plugins           = $pluginProvider->edit_plugins($options);
        
        return $plugins;
    }

    protected function getThemes(array $options = array())
    {
        require_once(FRESH_CONNECT_DIR_PATH .'inc/WordPress/Provider/Theme.php');
        require_once(FRESH_CONNECT_DIR_PATH .'inc/Updater/UpdateManager.php');
        $options += array(
            'fetchDescription'     => false,
            'fetchAutoUpdate'      => true,
            'fetchAvailableUpdate' => false,
        );

        $themeProvider     = new FastPress_Provider_Theme($this->context);
        $themes            = $themeProvider->fetch($options);

        if ($options['fetchAvailableUpdate']) {
            require_once('Provider/Theme.php');
            $um = new Fastpress_Updater_UpdateManager($this->context);
            foreach ($themes as &$theme) {
                if (!isset($theme['slug'])) {
                    continue;
                }

                $update = $um->getThemeUpdate($theme['slug']);
                if ($update !== null) {
                    $theme['updateVersion'] = $update->version;
                    $theme['updatePackage'] = $update->package;
                }
            }
        }

        return $themes;
    }
	
	protected function editThemes(array $options = array())
	{
		require_once(FRESH_CONNECT_DIR_PATH .'inc/WordPress/Provider/Theme.php');
		
		$themeProvider     = new FastPress_Provider_Theme($this->context);
        $return            = $themeProvider->edit_themes($options);
		
		return $return;
	}

    public function getPluginUpdates(array $options = array())
    {
		require_once(FRESH_CONNECT_DIR_PATH .'inc/Updater/UpdateManager.php');
		$um = new Fastpress_Updater_UpdateManager($this->context);

        return $um->getPluginUpdates();
    }

    public function getThemeUpdates(array $options = array())
    {
		require_once(FRESH_CONNECT_DIR_PATH .'inc/Updater/UpdateManager.php');
        $um = new Fastpress_Updater_UpdateManager($this->context);

        return $um->getThemeUpdates();
    }

    public function getCoreUpdates(array $options = array())
    {
		require_once(FRESH_CONNECT_DIR_PATH .'inc/Updater/UpdateManager.php');
        $um = new Fastpress_Updater_UpdateManager($this->context);

        return $um->getCoreUpdates();
    }

    public function getRoles(array $options = array())
    {
        $options += array(
            'capabilities' => false,
        );

        $roles = $this->context->getUserRoles();

        $result = array();

        foreach ($roles as $roleSlug => $roleInfo) {
            $role = array(
                'slug' => $roleSlug,
                'name' => $roleInfo['name'],
            );
            if ($options['capabilities']) {
                $role['capabilities'] = $roleInfo['capabilities'];
            }
            $result[] = $role;
        }

        return $result;
    }

    public function getSiteInfo(array $options = array())
    {

        return array(
            'blogname'               => $this->context->getSiteTitle(),
            'blogdescription'         => $this->context->getSiteDescription(),
            'siteurl'             => $this->context->getSiteUrl(),
            'home'             => $this->context->getHomeUrl(),
            'admin_email'       => $this->context->getAdminEmail(),
            'masterSiteUrl'       => $this->context->getMasterSiteUrl(),
            'isMultisite'         => $this->context->isMultisite(),
            'public'              => $this->context->optionGet('blog_public'),
            'siteId'              => $this->context->getSiteId(),
            'currentUserId'       => $this->context->getCurrentUser()->ID,
            'currentUserUsername' => $this->context->getCurrentUser()->user_login,
        );
    }

    public function getServerInfo(array $options = array())
    {

        return array(
            // @todo: move the checks below to a separate component.
            'phpVersion'          => PHP_VERSION,
            'mysqlVersion'        => $this->context->getDb()->db_version(),
            'extensionPdoMysql'   => extension_loaded('pdo_mysql'),
            'extensionOpenSsl'    => extension_loaded('openssl'),
            'extensionFtp'        => extension_loaded('ftp'),
            'extensionZlib'       => extension_loaded('zlib'),
            'extensionBz2'        => extension_loaded('bz2'),
            'extensionZip'        => extension_loaded('zip'),
            'extensionCurl'       => extension_loaded('curl'),
            'extensionGd'         => extension_loaded('gd'),
            'extensionImagick'    => extension_loaded('imagick'),
            'extensionSockets'    => extension_loaded('sockets'),
            'extensionSsh2'       => extension_loaded('ssh2'),
            'processArchitecture' => strlen(decbin(~0)), // Results in 32 or 62.
            'uname'               => php_uname('a'),
            'hostname'            => php_uname('n'),
            'os'                  => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'windows' : 'unix',
        );
    }
}
