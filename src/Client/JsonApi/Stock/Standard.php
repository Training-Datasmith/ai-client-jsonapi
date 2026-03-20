<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
namespace Aimeos\Client\Json_Api\Stock;

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
    /** client/jsonapi/stock/name
     * Class name of the used stock client implementation
     *
     * Each default JSON API client can be replace by an alternative imlementation.
     * To use this implementation, you have to set the last part of the class
     * name as configuration value so the client factory knows which class it
     * has to instantiate.
     *
     * For example, if the name of the default class is
     *
     *  \Aimeos\Client\JsonApi\Stock\Standard
     *
     * and you want to replace it with your own version named
     *
     *  \Aimeos\Client\JsonApi\Stock\Mystock
     *
     * then you have to set the this configuration option:
     *
     *  client/jsonapi/stock/name = Mystock
     *
     * The value is the last part of your own class name and it's case sensitive,
     * so take care that the configuration value is exactly named like the last
     * part of the class name.
     *
     * The allowed characters of the class name are A-Z, a-z and 0-9. No other
     * characters are possible! You should always start the last part of the class
     * name with an upper case character and continue only with lower case characters
     * or numbers. Avoid chamel case names like "MyStock"!
     *
     * @param string Last part of the class name
     * @since 2017.03
     * @category Developer
     */
    /** client/jsonapi/stock/decorators/excludes
     * Excludes decorators added by the "common" option from the JSON API clients
     *
     * Decorators extend the functionality of a class by adding new aspects
     * (e.g. log what is currently done), executing the methods of the underlying
     * class only in certain conditions (e.g. only for logged in users) or
     * modify what is returned to the caller.
     *
     * This option allows you to remove a decorator added via
     * "client/jsonapi/common/decorators/default" before they are wrapped
     * around the JsonApi client.
     *
     *  client/jsonapi/decorators/excludes = array( 'decorator1' )
     *
     * This would remove the decorator named "decorator1" from the list of
     * common decorators ("\Aimeos\Client\JsonApi\Common\Decorator\*") added via
     * "client/jsonapi/common/decorators/default" for the JSON API client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/stock/decorators/global
     * @see client/jsonapi/stock/decorators/local
     */
    /** client/jsonapi/stock/decorators/global
     * Adds a list of globally available decorators only to the JsonApi client
     *
     * Decorators extend the functionality of a class by adding new aspects
     * (e.g. log what is currently done), executing the methods of the underlying
     * class only in certain conditions (e.g. only for logged in users) or
     * modify what is returned to the caller.
     *
     * This option allows you to wrap global decorators
     * ("\Aimeos\Client\JsonApi\Common\Decorator\*") around the JsonApi
     * client.
     *
     *  client/jsonapi/stock/decorators/global = array( 'decorator1' )
     *
     * This would add the decorator named "decorator1" defined by
     * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
     * "stock" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/stock/decorators/excludes
     * @see client/jsonapi/stock/decorators/local
     */
    /** client/jsonapi/stock/decorators/local
     * Adds a list of local decorators only to the JsonApi client
     *
     * Decorators extend the functionality of a class by adding new aspects
     * (e.g. log what is currently done), executing the methods of the underlying
     * class only in certain conditions (e.g. only for logged in users) or
     * modify what is returned to the caller.
     *
     * This option allows you to wrap local decorators
     * ("\Aimeos\Client\JsonApi\Stock\Decorator\*") around the JsonApi
     * client.
     *
     *  client/jsonapi/stock/decorators/local = array( 'decorator2' )
     *
     * This would add the decorator named "decorator2" defined by
     * "\Aimeos\Client\JsonApi\Stock\Decorator\Decorator2" only to the
     * "stock" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/stock/decorators/excludes
     * @see client/jsonapi/stock/decorators/global
     */
    /**
     * Returns the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function get(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $view = $this->view();
        try {
            if ($view->param('id')) {
                $response = $this->get_item($view, $request, $response);
            } else {
                $response = $this->get_items($view, $request, $response);
            }
            $status = 200;
        } catch (\Aimeos\M_Shop\Exception $e) {
            $status = 404;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Exception $e) {
            $status = $e->get_code() >= 100 && $e->get_code() < 600 ? $e->get_code() : 500;
            $view->errors = $this->get_error_details($e);
        }
        /** client/jsonapi/stock/template
         * Relative path to the catalog lists JSON API template
         *
         * The template file contains the code and processing instructions
         * to generate the result shown in the JSON API body. The
         * configuration string is the path to the template file relative
         * to the templates directory (usually in templates/client/jsonapi).
         *
         * You can overwrite the template file configuration in extensions and
         * provide alternative templates. These alternative templates should be
         * named like the default one but with the string "default" replaced by
         * an unique name. You may use the name of your project for this. If
         * you've implemented an alternative client class as well, "standard"
         * should be replaced by the name of the new class.
         *
         * @param string Relative path to the template creating the body for the GET method of the JSON API
         * @since 2017.03
         * @category Developer
         */
        $tplconf = 'client/jsonapi/stock/template';
        $default = 'stock/standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'GET,OPTIONS')->with_header('Cache-Control', 'no-cache, private')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status($status);
    }
    /**
     * Returns the available REST verbs and the available parameters
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function options(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $view = $this->view();
        $view->filter = ['s_prodid' => ['label' => 'List of product IDs for which the stock level should be returned', 'type' => 'array', 'default' => '[]', 'required' => false], 's_prodcode' => ['label' => 'Deprecated: List of product codes for which the stock level should be returned', 'type' => 'array', 'default' => '[]', 'required' => false], 's_typecode' => ['label' => 'List of warehouse/location codes (stock type)', 'type' => 'array', 'default' => '[]', 'required' => false]];
        $tplconf = 'client/jsonapi/template-options';
        $default = 'options-standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'GET,OPTIONS')->with_header('Cache-Control', 'max-age=300')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status(200);
    }
    /**
     * Retrieves the item and adds the data to the view
     *
     * @param \Aimeos\Base\View\Iface $view View instance
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function get_item(\Aimeos\Base\View\Iface $view, Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $ref = $view->param('include', []);
        if (is_string($ref)) {
            $ref = explode(',', str_replace('.', '/', $ref));
        }
        $view->items = \Aimeos\Controller\Frontend::create($this->context(), 'stock')->uses($ref)->get($view->param('id'));
        $view->total = 1;
        return $response;
    }
    /**
     * Retrieves the items and adds the data to the view
     *
     * @param \Aimeos\Base\View\Iface $view View instance
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function get_items(\Aimeos\Base\View\Iface $view, Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $total = 0;
        $params = $view->param('filter', []);
        $prod_ids = (array) $view->param('filter/s_prodid', []);
        if (isset($params['s_prodcode'])) {
            // backwards compatibility
            $manager = \Aimeos\M_Shop::create($this->context(), 'product');
            $filter = $manager->filter()->slice(0, count((array) $params['s_prodcode']))->add(['product.code' => $view->param('filter/s_prodcode')]);
            $prod_ids = array_merge($prod_ids, $manager->search($filter)->keys()->to_array());
        }
        unset($params['s_prodid'], $params['s_prodcode'], $params['s_typecode']);
        $ref = $view->param('include', []);
        if (is_string($ref)) {
            $ref = explode(',', str_replace('.', '/', $ref));
        }
        $items = \Aimeos\Controller\Frontend::create($this->context(), 'stock')->uses($ref)->slice($view->param('page/offset', 0), $view->param('page/limit', 100))->product($prod_ids)->type($view->param('filter/s_typecode'))->sort($view->param('sort'))->parse($params)->search($total);
        $view->items = $items;
        $view->total = $total;
        return $response;
    }
}