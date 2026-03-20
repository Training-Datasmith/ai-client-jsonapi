<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
namespace Aimeos\Client\Json_Api;

use Psr\Http\Message\Response_Interface;
use Psr\Http\Message\Server_Request_Interface;
/**
 * JSON API standard client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard extends \Aimeos\Client\Json_Api\Base implements \Aimeos\Client\Json_Api\Iface
{
    /**
     * Retrieves the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function get(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $view = $this->view();
        /** client/jsonapi/template-get
         * Relative path to the default JSON API template for unknown GET request
         *
         * The template file contains the code and processing instructions
         * to generate the result shown in the JSON API body. The
         * configuration string is the path to the template file relative
         * to the templates directory (usually in templates/client/jsonapi).
         *
         * You can overwrite the template file configuration in extensions and
         * provide alternative templates. These alternative templates should be
         * named like the default one but with the string "standard" replaced by
         * an unique name. You may use the name of your project for this. If
         * you've implemented an alternative client class as well, "standard"
         * should be replaced by the name of the new class.
         *
         * @param string Relative path to the template creating the body for the JSON API GET response
         * @since 2017.05
         * @category Developer
         * @see client/jsonapi/template-options
         */
        $tplconf = 'client/jsonapi/template-get';
        $default = 'get-standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'OPTIONS')->with_header('Cache-Control', 'max-age=300')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status(200);
    }
    /**
     * Returns the available REST verbs and the available resources
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function options(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $view = $this->view();
        try {
            /** client/jsonapi/resources
             * A list of resource names whose clients are available for the JSON API
             *
             * The HTTP OPTIONS method returns a list of resources known by the
             * JSON API including their URLs. The list of available resources
             * can be exteded dynamically be implementing a new Jsonadm client
             * class handling request for this new domain.
             *
             * To add the new domain client to the list of resources returned
             * by the HTTP OPTIONS method, you have to add its name in lower case
             * to the existing configuration.
             *
             * @param array List of resource names
             * @since 2017.03
             * @category Developer
             */
            $resources = $this->context()->config()->get('client/jsonapi/resources', []);
            $view->locale = $this->context()->locale()->to_array();
            $view->resources = (array) $resources;
            $status = 200;
        } catch (\Exception $e) {
            $status = 500;
            $view->errors = $this->get_error_details($e);
        }
        /** client/jsonapi/template-options
         * Relative path to the JSON API template for OPTIONS requests
         *
         * The template file contains the code and processing instructions
         * to generate the result shown in the JSON API body. The
         * configuration string is the path to the template file relative
         * to the templates directory (usually in templates/client/jsonapi).
         *
         * You can overwrite the template file configuration in extensions and
         * provide alternative templates. These alternative templates should be
         * named like the default one but with the string "standard" replaced by
         * an unique name. You may use the name of your project for this. If
         * you've implemented an alternative client class as well, "standard"
         * should be replaced by the name of the new class.
         *
         * @param string Relative path to the template creating the body for the OPTIONS method of the JSON API
         * @since 2017.02
         * @category Developer
         * @see client/jsonapi/template-get
         */
        $tplconf = 'client/jsonapi/template-options';
        $default = 'options-standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'GET')->with_header('Cache-Control', 'max-age=300')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status($status);
    }
}