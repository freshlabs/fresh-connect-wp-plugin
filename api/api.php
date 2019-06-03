<?php
/**
 * Class API
 *
 *
 */
class API
{
	private $context;
	private $fp_status;
	private $con_key;
	private $getstate;
	private $siteurl;
	private $wp_version;
	private $userdata;
	private $post = array();
	
	public function __construct(FastPress_Context $context, Fastpress_Action_GetState $getstate, $fp_status, $con_key)
    {
        $this->context = $context;
		$this->fp_status = $fp_status;
		$this->getstate = $getstate;
		$this->con_key = $con_key;
		$this->siteurl = site_url();
		$this->wp_version = $context->getVersion();
		$this->initialize();
    }
	
	public function initialize()
	{
		if (!isset($_POST) || empty($_POST))
        {
            $this->error = true;
            $this->errormessage = 'No POST data provided';
            $this->output();
        }
		
		if(!$this->fp_status)
		{
			$this->error = true;
            $this->errormessage = 'It seems Fresh Connect plugin is not activated on wp site. Please activate it.';
            $this->output();
		}
		
		$this->post = $_POST;
		
		$this->validate();
		
		# Check if the method requested exists, and pass on to that
        if (method_exists($this,$this->post['request_method']))
        {
            $this->{$this->post['request_method']}();
        }
        else
        {
            $this->error = true;
            $this->errormessage = 'No api method found.';
            $this->output();
        }
	}
	
	private function validate()
    {
		$username = empty($this->post['username']) ? null : $this->post['username'];
		
		if ( ! username_exists( $username ) ){
			$users = getUsers(array('role' => 'administrator', 'number' => 1, 'orderby' => 'ID'));
			
            if (empty($users[0]->user_login)) {
                $this->error = true;
				$this->errormessage = 'We could not find an administrator user to use. Please contact support.';
				$this->output();
            }
			
            $this->post['username'] = $users[0]->user_login;
		}
		
		$userdata = get_user_by('login', $this->post['username']);
		
		if(!in_array('administrator', $userdata->roles)){
			$this->error = true;
			$this->errormessage = "User {$this->post['username']} have not required permission to access the data.";
			$this->output();
		}
		
		$this->userdata = $userdata;
		
        $hash = md5($this->con_key.$this->post['timestamp']);

        if ($hash !== $this->post['hash'])
        {
            $this->error = true;
            $this->errormessage = 'Invalid hash - Authentication faild, please check connection key is correct.';
            $this->output();
        }
        
		return true;
    }
	
	private function output()
	{
		if ($this->error)
        {
            $this->apioutput['status'] = 'error';
            $this->apioutput['errormessage'] = $this->errormessage;
        }
        else
        {
            $this->apioutput['status'] = 'success';
        }

        echo json_encode($this->apioutput);
		//echo $this->apioutput;
        exit();
	}
	
	function getSSLStatus()
	{
		$status = $this->context->isSsl();
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['ssl_status'] = $status;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		$this->output();
	}
	
	function setSSL()
	{
		$value = empty($this->post['ssl_value']) ? '0' : $this->post['ssl_value'];
		
		$this->context->setSSL($value);
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		$this->output();
	}
	
	function getWPVersion()
	{	
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		$this->output();
	}
	
	function getOption()
	{
		$option = empty($this->post['option_key']) ? null : $this->post['option_key'];
		$value = $this->context->optionGet($option);
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['option_key'] = $option;
		$this->apioutput['option_value'] = $value;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		$this->output();
	}
	
	function getGeneralOptions()
	{
		$data = $this->getstate->getSiteInfo();
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['general_options'] = $data;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		$this->output();
	}
	
	function setGeneralOptions()
	{
		$options = empty($this->post['general_options']) ? null : $this->post['general_options'];
		
		$this->context->setMultipleOptions($options);

		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		$this->output();
	}
	
	public function getAllThemes()
	{
	    $options = is_array($this->post['theme_options']) ? $this->post['theme_options'] : array();
		
		$themes = $this->getstate->execute(array('themes' => array('type' => 'themes', 'options' => $options)));
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['themes'] = $themes;
		$this->apioutput['wp_version'] = $this->wp_version;
		$this->output();
	}
	
	public function getAllPlugins()
	{
	    $options = is_array($this->post['plugin_options']) ? $this->post['plugin_options'] : array();
		
		$plugins = $this->getstate->execute(array('plugins' => array('type' => 'plugins', 'options' => $options)));
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['plugins'] = $plugins;
		$this->apioutput['wp_version'] = $this->wp_version;
		$this->output();
	}
	
	public function getAlldata()
	{
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		
		$status = $this->context->isSsl();
		$this->apioutput['ssl_status'] = $status;
		
		$genoptions = $this->getstate->getSiteInfo();
		$this->apioutput['general_options'] = $genoptions;
		
		$theme_options = is_array($this->post['theme_options']) ? $this->post['theme_options'] : array();
		$themes = $this->getstate->execute(array('themes' => array('type' => 'themes', 'options' => $theme_options)));
		$this->apioutput['themes'] = $themes;
		
		$plugin_options = is_array($this->post['plugin_options']) ? $this->post['plugin_options'] : array();
		$plugins = $this->getstate->execute(array('plugins' => array('type' => 'plugins', 'options' => $plugin_options)));
		$this->apioutput['plugins'] = $plugins;
		
		$this->apioutput['wp_version'] = $this->wp_version;
		
		$this->output();
	}
}