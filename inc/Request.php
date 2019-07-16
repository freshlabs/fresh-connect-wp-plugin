<?php
/**
 * Class FastPress_Request
 */
class FastPress_Request
{
    public $query;
    public $request;
    public $attributes;
    public $cookies;
    public $files;
    public $server;
    private $content;

    private $method;

    /**
     * @param array       $query      The GET parameters.
     * @param array       $request    The POST parameters.
     * @param array       $attributes The request attributes.
     * @param array       $cookies    The COOKIE parameters.
     * @param array       $files      The FILES parameters.
     * @param array       $server     The SERVER parameters.
     * @param null|string $content    The raw request body data. If null, it will be lazy-loaded.
     */
    public function __construct($query = array(), $request = array(), $attributes = array(), $cookies = array(), $files = array(), $server = array(), $content = null)
    {
        $this->query      = $query;
        $this->request    = $request;
        $this->attributes = $attributes;
        $this->cookies    = $cookies;
        $this->files      = $files;
        $this->server     = $server;
        $this->content    = $content;
    }

    /**
     * Gets the request method.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = isset($this->server['REQUEST_METHOD']) ? strtoupper($this->server['REQUEST_METHOD']) : 'GET';
        }

        return $this->method;
    }

    /**
     * Sets the request method.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server['REQUEST_METHOD'] = $method;
    }

    /**
     * FastPress factory.
     *
     * @return FastPress_Request
     */
    public static function createFromGlobals()
    {
        $request = new self($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);

        return $request;
    }

}
