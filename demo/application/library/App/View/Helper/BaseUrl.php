<?php
/**
 * Zym Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @category Zym
 * @package Zym_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2008 Zym. (http://www.zym-project.com/)
 * @license http://www.zym-project.com/license New BSD License
 */

/**
 * @see Zend_Controller_Front
 */
require_once 'Zend/Controller/Front.php';

/**
 * BaseUrl helper
 *
 * @author Geoffrey Tran
 * @license http://www.zym-project.com/license New BSD License
 * @package Zym_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2008 Zym. (http://www.zym-project.com/)
 */
class App_View_Helper_BaseUrl
{
    /**
     * Returns site's base url
     *
     * $file is appended to the base url for simplicity
     *
     * @param string $file
     * @return string
     */
    public function baseUrl($file = null)
    {
        // Get baseUrl
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        // Remove trailing slashes
        $file = ($file !== null) ? ltrim($file, '/\\') : null;

        // Build return
        $return = rtrim($baseUrl, '/\\') . (($file !== null) ? ('/' . $file) : '');
        return $return;
    }
}