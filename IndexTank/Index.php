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

require_once 'Zend/Config.php';
require_once 'Zend/Date.php';

/**
 * IndexTank index. An index object stores, searches and removes documents from
 * the server.
 *
 * @author Helpdesk <techies@helpdeskhq.com>
 * @category IndexTank
 */
class IndexTank_Index
{
    /**
     * Client used to connect to IndexTank 
     * 
     * @var IndexTank_Client
     */
    protected $_client;

    /**
     * Name of this Index 
     * 
     * @var string
     */
    protected $_name;

    /**
     * Is this Index started? 
     * 
     * @var boolean
     */
    protected $_isStarted;

    /**
     * IndexTank identifier for this Index 
     * 
     * @var string
     */
    protected $_code;

    /**
     * Date this Index was created 
     * 
     * @var Zend_Date
     */
    protected $_creationTime;

    /**
     * Number of documents stored in this Index 
     * 
     * @var int
     */
    protected $_size;

    /**
     * Constructor 
     * 
     * @param array|Zend_Config $options  Options to initialize the Index
     * @param IndexTank_Client  $client   Client used to connect to IndexTank
     */
    public function __construct($name, $client, $options)
    {
        $this->_name = (string) $name;
        $this->_client = $client;
        $this->setOptions($options);
    }

    /**
     * Adds a document to this Index
     * 
     * @param  string $docId   Unique ID of the document in this Index
     * @param  array  $fields  Fields to be indexed
     * @param  array  $facets 
     * @return array           Data returned by IndexTank
     */
    public function addDocument($docId, $fields, $facets = array())
    {
        $data = $this->_client->_indexCall(
            $this, 'docs',
            array(
                'docid'  => $docId,
                'fields' => $fields
            ),
            'PUT'
        );

        return $data;
    }

    /**
     * Adds multiple documents to this Index at once
     *
     * The documents array should be in the format:
     *
     * array(
     *     array(
     *        'docid' => '...',
     *        'fields' => array(...)
     *     )
     * )
     * 
     * @param  array $documents
     * @return array
     */
    public function addDocuments($documents)
    {
        $data = $this->_client->_indexCall(
            $this, 'docs',
            $documents,
            'PUT'
        );

        return $data;
    }

    /**
     * Returns a list of autocomplete suggestions for the given text 
     *
     * The returned list is in the form
     * 
     * <code>
     *     array('suggestion', 'suggestive', 'sugar')
     * </code>
     * 
     * @param  string $searchText
     * @return array
     */
    public function autocomplete($searchText)
    {
        $data = $this->_client->_indexCall($this, 'autocomplete',
            array(
                'query' => $searchText,
            )
        );

        return $data['suggestions'];
    }

    /**
     * Runs a search query on this index 
     * 
     * @param  string $query          search query to run
     * @param  array  $fetchFields    list of fields to be returned for each result (default: text)
     * @param  mixed  $fetchSnippets  should highlighted snippets be returned for the fetched fields?
     * @return array
     */
    public function search($query, array $fetchFields = array('text'), $fetchSnippets = true)
    {
        $fieldsStr = implode(',', $fetchFields);

        $data = $this->_client->_indexCall(
            $this, 'search', array(
                'q' => $query,
                'fetch' => $fieldsStr,
                'snippet' => $fetchSnippets ? $fieldsStr : '',
                'function' => '1'
            )
        );

        $docs = array();

        foreach ($data['results'] as $doc) {
            $docData = array(
                'docid'    => $doc['docid'],
                'fields'   => array(),
                'snippets' => array()
            );

            foreach ($doc as $key => $val) {
                if ($key == 'docid') {
                    continue;
                } else if (strpos($key, 'snippet_') === 0) {
                    $snippetKey = substr($key, 8);
                    $docData['snippets'][$snippetKey] = $val;
                } else {
                    $docData['fields'][$key] = $val;
                }
            }

            $docs[$doc['docid']] = $docData;
        }

        return $docs;
    }

    /**
     * Refreshes the index metadata from IndexTank 
     * 
     * @return void
     */
    public function refreshMeta()
    {
        $index = $this->_client->getIndex($this->_name);

        $this->setOptions($index->toArray());

        return $this;
    }

    /**
     * Returns this Index' name 
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Checks if this Index is started and ready to accept documents 
     * 
     * @return boolean
     */
    public function isStarted()
    {
        $this->refreshMeta();

        return $this->_isStarted; 
    }

    /**
     * Sets the options of this Index
     * 
     * @param array|Zend_Config $options 
     * @return IndexTank_Index  Provides fluent interface
     */
    public function setOptions($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if (isset($options['name'])) {
            $this->_name = (string) $options['name'];
        }

        if (isset($options['started'])) {
            $this->_isStarted = (boolean) $options['started'];
        }

        if (isset($options['isStarted'])) {
            $this->_isStarted = (boolean) $options['isStarted'];
        }

        if (isset($options['code'])) {
            $this->_code = (string) $options['code'];
        }

        if (isset($options['creation_time'])) {
            $date = new Zend_Date();
            $date->set($options['creation_time'], Zend_Date::ISO_8601);
            $this->_creationTime = $date;
        }

        if (isset($options['creationTime'])) {
            if ($options['creationTime'] instanceof Zend_Date) {
                $this->_creationTime = $options['creationTime'];
            } else {
                $date = new Zend_Date();
                $date->set($options['creationTime'], Zend_Date::ISO_8601);
                $this->_creationTime = $date;
            }
        }

        if (isset($options['size'])) {
            $this->_size = (int) $options['size'];
        }

        return $this;
    }

    /**
     * Returns the index metadata in array form 
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'name'         => $this->_name,
            'isStarted'    => $this->_isStarted,
            'code'         => $this->_code,
            'creationTime' => $this->_creationTime,
            'size'         => $this->_size
        );
    }
}
