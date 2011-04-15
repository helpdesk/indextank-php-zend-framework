<?php
/**
 * IndexTank ZF Client
 * 
 * Copyright 2011 Helpdesk, www.helpdeskhq.com. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 *    1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 
 *    2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY HELPDESK ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL HELPDESK OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * The views and conclusions contained in the software and documentation are
 * those of the authors and should not be interpreted as representing official
 * policies, either expressed or implied, of Helpdesk.
 */

require_once 'Zend/Json.php';

require_once 'Zend/Http/Client.php';
require_once 'Zend/Http/Exception.php';

require_once 'IndexTank/Index.php';

/**
 * IndexTank ZF Client
 * 
 * @author Helpdesk <techies@helpdeskhq.com>
 * @category IndexTank
 */
class IndexTank_Client
{
    /**
     * Client release version
     *
     * @var string
     */
    const VERSION = '1.0';

    /**
     * Global default settings for IndexTank
     *
     * @var array
     */
    public static $defaultOptions = array();

    /**
     * The password used to authenticate with the API 
     * 
     * @var string
     */
    protected $_password;

    /**
     * The API key used to identify with the API 
     *
     * http://XXXXX.api.indextank.com
     * 
     * @var string
     */
    protected $_apiKey;

    /**
     * Use SSL when connecting to the API 
     * 
     * @var boolean
     */
    protected $_useSsl = false;

    /**
     * Constructor 
     * 
     * @param string|array|Zend_Config $options  the private url or an array
     *                                            or Zend_Config of options
     */
    public function __construct($options = null)
    {
        if ($options === null) {
            $options = self::$defaultOptions;
        }

        if (is_string($options)) {
            $this->setPrivateUrl($options);
        } else {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }

            if (isset($options['privateUrl'])) {
                $this->setPrivateUrl($options['privateUrl']);
            }
            if (isset($options['private_url'])) {
                $this->setPrivateUrl($options['private_url']);
            }

            if (isset($options['apiKey'])) {
                $this->setApiKey($options['apiKey']);
            }
            if (isset($options['api_key'])) {
                $this->setApiKey($options['api_key']);
            }

            if (isset($options['password'])) {
                $this->setPassword($options['password']);
            }

            if (isset($options['useSsl'])) {
                $this->setUseSsl($options['useSsl']);
            }
            if (isset($options['use_ssl'])) {
                $this->setUseSsl($options['use_ssl']);
            }
        }
    }

    /**
     * Sets the private URL to be used with the client
     *
     * This URL can be found on the IndexTank dashboard
     * 
     * @param  string $url 
     * @return IndexTank_Client  Provides fluent interface
     */
    public function setPrivateUrl($url)
    {
        $parts = parse_url($url);

        $this->_password = $parts['pass'];

        list($key,) = explode('.', $parts['host']);
        $this->_apiKey = $key;

        $this->_useSsl = $parts['scheme'] == 'https';

        return $this;
    }

    /**
     * Returns the API password 
     * 
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Sets the API password 
     * 
     * @param  string $password 
     * @return IndexTank_Client  Provides fluent interface
     */
    public function setPassword($password)
    {
        $this->_password = (string) $password;

        return $this;
    }

    /**
     * Returns the API key 
     * 
     * @return string
     */
    public function getApiKey()
    {
        return $this->_apiKey;
    }

    /**
     * Sets the API key 
     * 
     * @param  string $apiKey 
     * @return IndexTank_Client  Provides fluent interface
     */
    public function setApiKey($apiKey)
    {
        $this->_apiKey = (string) $apiKey;

        return $this;
    }

    /**
     * Checks if this client uses SSL 
     * 
     * @return boolean
     */
    public function doesUseSsl()
    {
        return $this->_useSsl;
    }

    /**
     * Set this client to use SSL 
     * 
     * @param  boolean $useSsl 
     * @return IndexTank_Client  Provides fluent interface
     */
    public function setUseSsl($useSsl)
    {
        $this->_useSsl = (boolean) $useSsl;

        return $this;
    }

    /**
     * Returns an array of all indexes on the account 
     *
     * The array is in the form ('<index name>' => <IndexTank_Index>, ...)
     * 
     * @return array
     */
    public function getAllIndexes()
    {
        $data = $this->_call('/v1/indexes/');

        $indexes = array();

        foreach ($data as $name => $options) {
            $index = new IndexTank_Index($name, $this, $options);
            $indexes[$name] = $index;
        }

        return $indexes;
    }

    /**
     * Returns the given index 
     * 
     * @param  string $name 
     * @return IndexTank_Index
     */
    public function getIndex($name)
    {
        $data = $this->_call($this->_getIndexUri($name));

        $index = new IndexTank_Index($name, $this, $data);

        return $index;
    }

    /**
     * Creates a new index on the server 
     * 
     * @param string $name              Name of the new index
     * @param mixed  $waitUntilStarted  Wait until the new index is ready,
     *                                   or return immediately
     * @return IndexTank_Index
     */
    public function createIndex($name, $waitUntilStarted = true)
    {
        $this->_call($this->_getIndexUri($name), array(), 'PUT');

        $index = $this->getIndex($name);

        if ($waitUntilStarted) {
            while (!$index->isStarted()) {
                sleep(2);
            }
        }

        return $index;
    }

    /**
     * Deletes an index on the server 
     * 
     * @param  string $name 
     * @return void
     */
    public function deleteIndex($name)
    {
        $this->_call($this->_getIndexUri($name), array(), 'DELETE');
    }

    /**
     * (Non-Public) Handle calls from an Index object to the server
     * 
     * @param IndexTank_Index $index
     * @param string $function
     * @param array $params
     * @param string $method
     * @return array
     */
    public function _indexCall($index, $function, $params = array(), $method = 'GET')
    {
       $uri = $this->_getIndexUri($index->getName()) . '/' . $function;

       return $this->_call($uri, $params, $method);
    }

    /**
     * Returns the URI for the given index name 
     * 
     * @param string $name 
     * @return string
     */
    protected function _getIndexUri($name)
    {
        return '/v1/indexes/' . urlencode(str_replace('/', '', $name));
    }

    /**
     * Makes an API call to the server and returns the parsed result 
     * 
     * @param string $uri 
     * @param array  $params 
     * @param string $method 
     * @return array
     */
    protected function _call($uri, $params = array(), $method = 'GET')
    {
        if (empty($this->_apiKey)) {
            require_once 'IndexTank/Exception.php';
            throw new IndexTank_Exception('Client is not configured (no API key)');
        }
        
        $url = ($this->_useSsl ? 'https' : 'http') . '://' .
            $this->_apiKey . '.api.indextank.com' .
            $uri;

        $client = new Zend_Http_Client($url, array(
            'useragent' => 'IndexTank ZF Client (' . self::VERSION . ')'
        ));

        $client->setAuth('', $this->_password);

        if ($method == 'GET') {
            $client->setParameterGet($params);
        } else {
            $client->setRawData(Zend_Json::encode($params), 'application/json');
        }

        try {
            $response = $client->request($method);
        } catch (Zend_Http_Exception $e) {
            require_once 'IndexTank/Exception.php';
            throw new IndexTank_Exception('Connection failed: ' . $e->getMessage());
        }

        if (!$response->isSuccessful()) {
            require_once 'IndexTank/Exception.php';
            throw new IndexTank_Exception('Request failed: (' . $response->getStatus() . ') ' .
                $response->getMessage() . ': ' . $response->getBody());
        }

        $data = Zend_Json::decode($response->getBody());

        return $data;
    }
}
