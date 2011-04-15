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

require_once 'Zend/Application/Resource/ResourceAbstract.php';

require_once 'IndexTank/Client.php';

/**
 * IndexTank application resource to handle global configuration
 *
 * Example configuration:
 * <pre>
 *   ; Include Resource in Plugins
 *   pluginPaths.IndexTank_Application_Resource = "IndexTank/Application/Resource"
 *
 *   ; Resource Configs
 *   resources.IndexTank.private_url = "http://:aXsj34xljlsdf@xp2lx9.api.indextank.com"
 *
 *   resources.IndexTank.api_key  = "aXsj34xljlsdf"  ; from URL
 *   resources.IndexTank.password = "aXsj34xljlsdf"  ; from URL
 *
 *   resources.IndexTank.use_ssl  = false            ; default
 * </pre>
 * 
 * @author Helpdesk <techies@helpdeskhq.com>
 * @category IndexTank
 */
class IndexTank_Application_Resource_IndexTank
    extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Instance of the IndexTank client 
     * 
     * @var IndexTank_Client
     */
    protected $_client;

    /**
     * Initialize resource 
     * 
     * @return IndexTank_Client
     */
    public function init() {
        return $this->getClient();
    }

    /**
     * Returns the IndexTank_Client 
     * 
     * @return IndexTank_Client
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $options = $this->getOptions();

            IndexTank_Client::$defaultOptions = $options;

            $this->_client = new IndexTank_Client();
        }

        return $this->_client;
    }
}
