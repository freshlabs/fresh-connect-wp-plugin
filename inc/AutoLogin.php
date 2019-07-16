<?php
class FS_Autologin
{

    private $context;
    private $sessionStore;
    private $nonceValidFor;
    private $nonceBlacklistedFor;

	public function __construct(FastPress_Context $context, FastPress_SessionStore $sessionStore, $nonceValidFor = 43200, $nonceBlacklistedFor = 86400)
    {
        $this->context       = $context;
		$this->sessionStore  = $sessionStore;
		$this->nonceValidFor       = $nonceValidFor;
        $this->nonceBlacklistedFor = $nonceBlacklistedFor;
    }
    function checkLoginToken()
    {
        $request = FastPress_Request::createFromGlobals();

        if ($request->getMethod() !== 'GET') {
            return;
        }

        if (!isset($request->query['auto_login'])) {
            return;
        }

        if (empty($request->query['auto_login']) || empty($request->query['signature']) || empty($request->query['message_id']) || !array_key_exists('goto', $request->query)) {
            return;
        }

        // Some sites will redirect from HTTP to HTTPS or from non-www to www URL too late; so handle that case here.
        $siteUrl           = $this->context->getSiteUrl();
        $isWww             = substr($request->server['HTTP_HOST'], 0, 4) === 'www.';
        $isHttps           = $this->context->isSsl();
        $shouldWww         = (bool)preg_match('{^https?://www\.}', $siteUrl);
        $shouldHttps       = $this->context->isSslAdmin();
        $alreadyRedirected = !empty($request->query['auto_login_fixed']);
        if (
            (
                ($isHttps !== $shouldHttps)
                || ($isWww !== $shouldWww)
            )
            && !$alreadyRedirected
        ) {
            $prefix = sprintf('%s://%s', $shouldHttps ? 'https' : 'http', $shouldWww ? 'www.' : '');
            // Replace the scheme and the www. prefix and remove the request URI.
            $redirectUri = $prefix.preg_replace('{^https?://(?:www\.)?([^/]+).*$}', '$1', $siteUrl);
            // Attach the current request URI to a fixed site URL.
            $redirectUri = $redirectUri.$request->server['REQUEST_URI'];
            // Prevent infinite loop with the added parameter.
            $redirectUri = $this->modifyUriParameters($redirectUri, array('auto_login_fixed' => 'yes'));

            return;
        } 
		
		if( empty($request->query['username']) ){
			$username = null;
		}else{
			$username = sanitize_user($request->query['username']);
		}

        if ($username === null) {
            $users = $this->context->getUsers(array('role' => 'administrator', 'number' => 1, 'orderby' => 'ID'));
            if (empty($users[0]->user_login)) {
                throw new Exception("We could not find an administrator user to use. Please contact support.");
            }
            $username = $users[0]->user_login;
        }

		if( isset($request->query['goto']) ){
			$where = sanitize_text_field($request->query['goto']);
		}else{
			$where = '';
		}

		if( isset($request->query['message_id']) ){
			$messageId = sanitize_text_field($request->query['message_id']);
		}else{
			$messageId = '';
		}
		
        $currentUser = $this->context->getCurrentUser();

        $adminUri    = rtrim($this->context->getAdminUrl(''), '/').'/'.$where;
        $redirectUri = $this->modifyUriParameters($adminUri, $request->query, array('signature', 'username', 'auto_login', 'message_id', 'goto', 'redirect', 'auto_login_fixed'));

        if ($currentUser->user_login === $username) {
            try {
                $this->useNonce($messageId);
            } catch (Exception $e) {
                // We are just using the nonce to make sure it can't be used again (no need to login)
            }
            
            update_option('fp_connection_status', true);

			wp_redirect($redirectUri);
            return;
        }

        /** @handled function */
         load_plugin_textdomain(FRESH_TEXT_DOMAIN);

        try {
            $this->useNonce($messageId);
        } catch (Exception $e) {
            $this->context->wpDie(esc_html__("The automatic login token is invalid. Please try again, or, if this keeps happening, contact support.", FRESH_TEXT_DOMAIN), '', 200);
        } catch (Exception $e) {
            $this->context->wpDie(esc_html__("The automatic login token has expired. Please try again, or, if this keeps happening, contact support.", FRESH_TEXT_DOMAIN), '', 200);
        } catch (Exception $e) {
            $this->context->wpDie(esc_html__("The automatic login token was already used. Please try again, or, if this keeps happening, contact support.", FRESH_TEXT_DOMAIN), '', 200);
        } 

        $publicKey = null;
        $message   = null;
        $signed    = null;

        $publicKey = $this->getPublicKey();
        $message   = $this->getConnectionKey().$where.$messageId;
        $signed    = base64_decode($request->query['signature']);

        if (empty($publicKey) || empty($message) || empty($signed)) {
            $this->context->wpDie(esc_html__('The automatic login token isn\'t properly signed. Please contact our support for help.', FRESH_TEXT_DOMAIN), '', 200);
        }

         if (!$this->verify($message, $signed, $publicKey)) {
            $this->context->wpDie(esc_html__('The automatic login token is invalid. Please check if this website is properly connected with your dashboard, or, if this keeps happening, contact support.', FRESH_TEXT_DOMAIN), '', 200);
        } 

        $user = $this->context->getUserByUsername($username);

        if ($user === null) {
            $this->context->wpDie(sprintf(esc_html__("User <strong>%s</strong> could not be found.", FRESH_TEXT_DOMAIN), htmlspecialchars($username)), '', 200);
        }

        $this->context->setCurrentUser($user);
        $this->attachSessionTokenListener();

        if (!$isHttps) { // when not on https login to both http and https
            $this->context->setAuthCookie($user, false, false); // login to http
            $this->context->setAuthCookie($user, false, true); // login to https
        } else {
            $this->context->setAuthCookie($user); // we are on https, only do the login to https to be safe
        }

        $this->context->setCookie($this->getCookieName(), '1');

        update_option('fp_connection_status', true);

		wp_redirect($redirectUri);
    }
	
	private function getPublicKey(){
		$fp = fopen(plugin_dir_path(__FILE__).'../public/pubkey.pub', "r");
		$public_key_pem = fread($fp, 8192);
		fclose($fp);

		return $public_key_pem;
	}
	
	private function getConnectionKey(){
		return get_option('fp_connection_keys');
	}
	
	private function modifyUriParameters($uri, array $addParameters, array $omitParameters = array())
    {
        $currentUrl = parse_url($uri) + array('port' => '', 'path' => '', 'query' => '');
        parse_str($currentUrl['query'], $query);

        $query = array_merge($query, $addParameters);

        foreach ($omitParameters as $key) {
            if (array_key_exists($key, $query)) {
                unset($query[$key]);
            }
        }

        $currentUrl['query'] = http_build_query($query);

        return sprintf(
            '%s://%s%s%s%s',
            $currentUrl['scheme'],
            $currentUrl['host'],
            $currentUrl['port'] ? ':'.$currentUrl['port'] : '',
            $currentUrl['path'] ? '/'.ltrim($currentUrl['path'], '/') : '/',
            $currentUrl['query'] ? '?'.$currentUrl['query'] : ''
        );
    }
	
	private function attachSessionTokenListener()
    {
        if (!$this->context->getSessionTokens($this->context->getCurrentUser()->ID)) {
            return;
        }

        $this->context->addAction('set_auth_cookie', array($this, 'storeSessionToken'), 10, 1);
    }
	
	private function getCookieName()
    {
         return 'wordpress_'.md5($this->context->getSiteUrl()).'_xframe';
    }
	
	private function verify($data, $signature, $publicKey)
    {
        /** @handled function */
       $verify = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($verify === -1) {
            $error     = $errorRow = '';
            $lastError = error_get_last();

            /** @handled function */
            while (($errorRow = openssl_error_string()) !== false) {
                $error = $errorRow."\n".$error;
            }

            throw new Exception('error', "There was an error while trying to use OpenSSL to verify a message.", array(
                'openSslError' => $error,
                'error'        => isset($lastError['message']) ? $lastError['message'] : null,
            ));
        }

        return (bool) $verify;
    }
	public function useNonce($nonce)
    {
        $parts = explode('_', $nonce);

        if (count($parts) !== 2) {
            throw new Exception();
        }

        list($nonceValue, $issuedAt) = $parts;

        $issuedAt = (int) $issuedAt;

        if (!$nonceValue || !$issuedAt) {
            throw new Exception();
        }

        if ($issuedAt + $this->nonceValidFor < time()) {
            throw new Exception();
        }

        // There was a bug where the generated nonce was 42 characters long.
        $transientKey = substr('n_'.$nonceValue, 0, 40);

		$nonceUsed    = $this->context->transientGet($transientKey);

        if ($nonceUsed !== false) {
            throw new Exception();
        }

        $this->context->transientSet($transientKey, $issuedAt, $this->nonceBlacklistedFor);
    }
	
	public function storeSessionToken($cookieValue)
    {
        $cookieElements = explode('|', $cookieValue);

        if (empty($cookieElements[2])) {
            return;
        }

        $token = $cookieElements[2];

        $this->sessionStore->add($this->context->getCurrentUser()->ID, $token);
    }
}

$context = new FastPress_Context();
$sessionstore = new FastPress_SessionStore($context);
$authrequest = new FS_Autologin($context, $sessionstore);
$authrequest->checkLoginToken();
?>