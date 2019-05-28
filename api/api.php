<?php
/**
 * Class API
 *
 *
 */
class API
{
	var $post = array();
	var $client_id = '';
	var $secret_id = '';
	var $error = false;
	var $errormessage = '';
	var $siteurl = '';
	
	function __construct($client_id, $secret_id, $siteurl)
    {
        $this->client_id = $client_id;
		$this->secret_id = $secret_id;
		$this->siteurl = $siteurl;

        $this->initialize();
    }
	
	function initialize()
	{
		if (!isset($_POST) || empty($_POST))
        {
            $this->error = true;
            $this->errormessage = 'No POST data provided';
            $this->output();
        }
		
		$this->post = $_POST;

        if (!isset($this->post['client_id']))
        {
            $this->error = true;
            $this->errormessage = 'Client id missing';
            $this->output();
        }
		
		$this->validate();
		
		# Check if the method requested exists, and pass on to that
        if (method_exists($this,$this->post['function']))
        {
            $this->{$this->post['function']}();
        }
        else
        {
            $this->error = true;
            $this->errormessage = 'API function not found';
            $this->output();
        }
	}
	
	private function validate()
    {
        # First just check that the key is correct
        if ($this->client_id != $this->post['client_id'])
        {
            $this->error = true;
            $this->errormessage = 'Invalid Client id - please check your Client Id is correct';
            $this->output();
        }

        $hash = md5($this->secret_id.$this->post['timestamp']);

        if ($hash !== $this->post['hash'])
        {
            $this->error = true;
            $this->errormessage = 'Invalid hash - check your secret id is correct';
            $this->output();
        }
        else
        {
            return true;
        }
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
        exit();
	}
	
	function setWPLogin()
	{
		
		if(empty($_POST['username']))
		{
			$this->error = true;
			$this->errormessage = 'username is missing';
			$this->output();
		}
		
		if(empty($_POST['password']))
		{
			$this->error = true;
			$this->errormessage = 'password is missing';
			$this->output();
		}
		
		$creds = array();
		$creds['user_login'] = $_POST['username'];
		$creds['user_password'] = $_POST['password'];
		$creds['remember'] = true;
		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error($user) ){
			$this->error = true;
			$this->errormessage = 'Invalid username and password';
		}
		else
		{
			$this->apioutput['status'] = 'success';
		}
		
		$this->apioutput['siteurl'] = $this->siteurl;
		
        $this->output();
	}
}