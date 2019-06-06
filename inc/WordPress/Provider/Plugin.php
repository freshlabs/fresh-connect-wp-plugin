<?php
/*
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class FastPress_Provider_Plugin
{

    const STATUS_ACTIVE_NETWORK = 'active-network';

    const STATUS_ACTIVE = 'active';

    const STATUS_MUST_USE = 'must-use';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_DROP_IN = 'drop-in';

    private $context;

    public function __construct(FastPress_Context $context)
    {
        $this->context = $context;
    }

    public function fetch(array $options = array())
    {
        $regularPlugins = $this->context->getPlugins();
        $mustUsePlugins = $this->context->getMustUsePlugins();
        $dropInPlugins  = $this->context->getDropInPlugins();
        $plugins        = array();

        $pluginInfo = array(
            'name'        => 'Name',
            'pluginUri'   => 'PluginURI',
            'version'     => 'Version',
            'description' => 'Description',
            'author'      => 'Author',
            'authorUri'   => 'AuthorURI',
        );

        if (empty($options['fetchDescription'])) {
            unset($pluginInfo['description']);
        }

        foreach ($regularPlugins as $basename => $details) {
			
			if($this->getSlugFromBasename($basename) == 'fresh-connect')
				continue;
			
            $plugin = array(
                // This is the plugin identifier; ie. "worker/init.php".
                'basename'    => $basename,
                // Plugin's own directory name (if it exists), or filename minus ".php" extension Ie. "worker".
                'slug'        => $this->getSlugFromBasename($basename),
                // 'Network' property can have a valid value or 'false' and is always present.
                // It signifies whether the plugin can only be activated network wide.
                'networkOnly' => $details['Network'],
            );

            foreach ($pluginInfo as $property => $info) {
                if (empty($details[$info])) {
                    $plugin[$property] = null;
                    continue;
                }

                $plugin[$property] = $this->context->seemsUtf8($details[$info]) ? $details[$info] : utf8_encode($details[$info]);
				
				$plugin[$property] = strip_tags($details[$info]);
            }

            $plugin['status'] = $this->getPluginStatus($basename);

            $plugins[] = $plugin;
        }

        foreach ($mustUsePlugins as $basename => $details) {
            $plugin = array(
                'basename' => $basename,
                'slug'     => $this->getSlugFromBasename($basename),
            );

            foreach ($pluginInfo as $property => $info) {
                $plugin[$property] = !empty($details[$info]) ? $details[$info] : null;
            }

            $plugin['status'] = self::STATUS_MUST_USE;
            $plugins[]        = $plugin;
        }

        foreach ($dropInPlugins as $basename => $details) {
            $plugin = array(
                'basename' => $basename,
                'type'     => self::STATUS_DROP_IN,
            );

            foreach ($pluginInfo as $property => $info) {
                $plugin[$property] = !empty($details[$info]) ? $details[$info] : null;
            }

            $plugins[] = $plugin;
        }

        return $plugins;
    }
    
    public function edit_plugins($args)
    {
		@include_once ABSPATH.'wp-admin/includes/file.php';
		@include_once ABSPATH.'wp-admin/includes/plugin.php';
        extract($args);
        $return = array();
		
		if(empty($items)) {
			$return['error'] = "Invalid arguments";
		}else {
			foreach ($items as $item) {
				$networkwide = isset($item['networkWide']) ? $item['networkWide'] : false;
				switch ($item['edit_action']) {
					case 'activate':
						$result = activate_plugin($item['path'], '', $networkwide);
						break;
					case 'deactivate':
						$result = deactivate_plugins(
							array(
								$item['path'],
							),
							false,
							$networkwide
						);
						break;
					case 'delete':
						$result = delete_plugins(
							array(
								$item['path'],
							)
						);
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

    private function getSlugFromBasename($file)
    {
        if (false === strpos($file, '/')) {
            $slug = basename($file, '.php');
        } else {
            $slug = dirname($file);
        }

        return $slug;
    }

    private function getPluginStatus($file)
    {
        if ($this->context->isPluginActiveForNetwork($file)) {
            return self::STATUS_ACTIVE_NETWORK;
        } elseif ($this->context->isPluginActive($file)) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_INACTIVE;
    }
}
