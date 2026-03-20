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
 * JSON API common client
 *
 * @package Client
 * @subpackage JsonApi
 */
abstract class Base implements \Aimeos\Client\Json_Api\Iface, \Aimeos\Macro\Iface
{
    use \Aimeos\Macro\Macroable;
    private \Aimeos\M_Shop\Context_Iface $context;
    private ?\Aimeos\Base\View\Iface $view = null;
    /**
     * Initializes the client
     *
     * @param \Aimeos\MShop\ContextIface $context MShop context object
     */
    public function __construct(\Aimeos\M_Shop\Context_Iface $context)
    {
        $this->context = $context;
    }
    /**
     * Deletes the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function delete(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        return $this->default_action($request, $response);
    }
    /**
     * Retrieves the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function get(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        return $this->default_action($request, $response);
    }
    /**
     * Updates the resource or the resource list partitially
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function patch(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        return $this->default_action($request, $response);
    }
    /**
     * Creates or updates the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function post(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        return $this->default_action($request, $response);
    }
    /**
     * Creates or updates the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function put(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        return $this->default_action($request, $response);
    }
    /**
     * Creates or updates the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function options(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        return $this->default_action($request, $response);
    }
    /**
     * Sets the view object that will generate the admin output.
     *
     * @param \Aimeos\Base\View\Iface $view The view object which generates the admin output
     * @return \Aimeos\Client\JsonApi\Iface Reference to this object for fluent calls
     */
    public function set_view(\Aimeos\Base\View\Iface $view): \Aimeos\Client\Json_Api\Iface
    {
        $this->view = $view;
        return $this;
    }
    /**
     * Returns the default response for the resource
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function default_action(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $status = 403;
        $view = $this->view();
        $view->errors = [['title' => 'Not allowed for this resource']];
        /** client/jsonapi/template-error
         * Relative path to the default JSON API template
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
         * @param string Relative path to the template creating the body for the JSON API response
         * @since 2017.02
         * @category Developer
         * @see client/jsonapi/template-delete
         * @see client/jsonapi/template-patch
         * @see client/jsonapi/template-post
         * @see client/jsonapi/template-get
         * @see client/jsonapi/template-options
         */
        $tplconf = 'client/jsonapi/template-error';
        $default = 'error-standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status($status);
    }
    /**
     * Returns the context item object
     *
     * @return \Aimeos\MShop\ContextIface Context object
     */
    protected function context(): \Aimeos\M_Shop\Context_Iface
    {
        return $this->context;
    }
    /**
     * Returns the translated title and the details of the error
     *
     * @param \Exception $e Thrown exception
     * @param string|null $domain Translation domain
     * @param string|null $msg Additional error details
     * @return array Associative list with "title" and "detail" key (if debug config is enabled)
     */
    protected function get_error_details(\Exception $e, ?string $domain = null): array
    {
        $details = [];
        if ($domain !== null) {
            $details['title'] = $this->context->translate($domain, $e->get_message());
        } else {
            $details['title'] = $this->context->translate('admin', 'An error occured and has been added to the logs');
            $this->context->logger()->log($e->get_message() . PHP_EOL . $e->get_trace_as_string());
        }
        if ($e instanceof \Aimeos\M_Shop\Plugin\Provider\Exception) {
            $details['detail'] = join("\n", $this->translate_plugin_error_codes($e->get_error_codes()));
        }
        /** client/jsonapi/debug
         * Send debug information withing responses to clients if an error occurrs
         *
         * By default, the Aimeos client JSON REST API won't send any details
         * besides the error message to the client if an error occurred. This
         * prevents leaking sensitive information to attackers. For debugging
         * your requests it's helpful to see the stack strace. If you set this
         * configuration option to true, the stack trace will be returned too.
         *
         * @param boolean True to return the stack trace in JSON response, false for error message only
         * @since 2017.07
         * @category Developer
         */
        if ($this->context->config()->get('client/jsonapi/debug', false) == true) {
            $details['title'] = $e->get_message();
            $details['detail'] = (isset($details['detail']) ? $details['detail'] . "\n" : '') . $e->get_trace_as_string();
        }
        return [$details];
        // jsonapi.org requires a list of error objects
    }
    /**
     * Returns the available REST verbs and the available parameters
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @param string $allow Allowed HTTP methods
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function get_options_response(Server_Request_Interface $request, Response_Interface $response, string $allow): \Psr\Http\Message\Response_Interface
    {
        $view = $this->view();
        $tplconf = 'client/jsonapi/template-options';
        $default = 'options-standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', $allow)->with_header('Cache-Control', 'max-age=300')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status(200);
    }
    /**
     * Initializes the criteria object based on the given parameter
     *
     * @param \Aimeos\Base\Criteria\Iface $criteria Criteria object
     * @param array $params List of criteria data with condition, sorting and paging
     * @return \Aimeos\Base\Criteria\Iface Initialized criteria object
     */
    protected function init_criteria(\Aimeos\Base\Criteria\Iface $criteria, array $params): \Aimeos\Base\Criteria\Iface
    {
        return $criteria->order($params['sort'] ?? [])->add($criteria->parse($params['filter'] ?? []))->slice($params['page']['offset'] ?? 0, $params['page']['limit'] ?? 25);
    }
    /**
     * Translates the plugin error codes to human readable error strings.
     *
     * @param array $codes Associative list of scope and object as key and error code as value
     * @return array List of translated error messages
     */
    protected function translate_plugin_error_codes(array $codes): array
    {
        $errors = [];
        $i18n = $this->context()->i18n();
        foreach ($codes as $scope => $list) {
            foreach ($list as $object => $errcode) {
                $key = $scope . (!in_array($scope, ['coupon', 'product']) ? '.' . $object : '') . '.' . $errcode;
                $errors[] = sprintf($i18n->dt('mshop/code', $key), $object);
            }
        }
        return array_unique($errors);
    }
    /**
     * Returns the view object that will generate the admin output.
     *
     * @return \Aimeos\Base\View\Iface The view object which generates the admin output
     */
    protected function view(): \Aimeos\Base\View\Iface
    {
        if (!isset($this->view)) {
            throw new \Aimeos\Admin\Json_Adm\Exception('No view available');
        }
        return $this->view;
    }
}