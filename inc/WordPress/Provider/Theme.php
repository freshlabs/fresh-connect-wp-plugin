<?php
/*
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class FastPress_Provider_Theme
{
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_INHERITED = 'inherited';

    private $context;

    public function __construct(FastPress_Context $context)
    {
        $this->context = $context;
    }

    public function fetch(array $options = array())
    {
        $rawThemes       = $this->context->getThemes();
        $currentTheme    = $this->context->getCurrentTheme();
        $activeThemeSlug = $currentTheme['Stylesheet'];
        $themes          = array();

        $themeInfo = array(
            'name'    => 'Name',
            // Absolute path to theme directory.
            'root'    => 'Theme Root',
            // Absolute URL to theme directory.
            'rootUri' => 'Theme Root URI',

            'version'     => 'Version',
            'description' => 'Description',
            'author'      => 'Author',
            'authorUri'   => 'Author URI',
            'status'      => 'Status',
            'parent'      => 'Parent Theme',
        );

        if (empty($options['fetchDescription'])) {
            unset($themeInfo['description']);
        }
		
		/*if (empty($options['author'])) {
            unset($themeInfo['author']);
        }*/

        foreach ($rawThemes as $rawTheme) {
            $theme = array(
                // Theme directory, followed by slash and slug, to keep it consistent with plugin info; ie. "twentytwelve/twentytwelve".
                'basename' => $rawTheme['Template'].'/'.$rawTheme['Stylesheet'],
                // A.k.a. "stylesheet", for some reason. This is the theme identifier; ie. "twentytwelve".
                'slug'     => $rawTheme['Stylesheet'],
                'children' => array(),
            );

            foreach ($themeInfo as $property => $info) {
                if (empty($rawTheme[$info])) {
                    $theme[$property] = null;
                    continue;
                }

                $theme[$property] = $this->context->seemsUtf8($rawTheme[$info]) ? $rawTheme[$info] : utf8_encode($rawTheme[$info]);
				
				$theme[$property] = strip_tags($rawTheme[$info]);
            }

            // Check if this is the active theme
            $theme['active'] = ($theme['slug'] === $activeThemeSlug);

            $themes[$theme['name']] = $theme;
        }

        foreach ($themes as &$theme) {
            if (empty($theme['parent']) || empty($themes[$theme['parent']])) {
                continue;
            }

            $themes[$theme['parent']]['children'][] = $theme['slug'];
            $theme['parent']                        = $themes[$theme['parent']]['slug'];
        }

        return array_values($themes);
    }
	
	public function edit_themes($args)
    {
		@include_once ABSPATH.'wp-admin/includes/file.php';
		@include_once ABSPATH.'wp-admin/includes/theme.php';
		extract($args);
        $return = array();
		$result = true;
		if(empty($items)) {
			$return['error'] = "Invalid arguments";
		}else {
			foreach ($items as $item) {
				switch ($item['edit_action']) {
					case 'activate':
						switch_theme($item['path'], $item['stylesheet']);
						break;
					case 'delete':
						$result = delete_theme($item['stylesheet']);
						break;
					default:
						break;
				}

				if (is_wp_error($result)) {
					$result = array(
						'error' => $result->get_error_message(),
					);
				} elseif ($result === false) {
					$result = array(
						'error' => "Failed to perform action.",
					);
				} else {
					$result = "OK";
				}
				$return[$item['name']] = $result;
			}
		}
        
        return $return;
    }
}

