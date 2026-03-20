<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
namespace Aimeos\Client\Json_Api\Basket;

use Psr\Http\Message\Response_Interface;
use Psr\Http\Message\Server_Request_Interface;
/**
 * JSON API basket client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard extends Base implements \Aimeos\Client\Json_Api\Iface
{
    /** client/jsonapi/basket/name
     * Class name of the used basket client implementation
     *
     * Each default JSON API client can be replace by an alternative imlementation.
     * To use this implementation, you have to set the last part of the class
     * name as configuration value so the client factory knows which class it
     * has to instantiate.
     *
     * For example, if the name of the default class is
     *
     *  \Aimeos\Client\JsonApi\Basket\Standard
     *
     * and you want to replace it with your own version named
     *
     *  \Aimeos\Client\JsonApi\Basket\Mybasket
     *
     * then you have to set the this configuration option:
     *
     *  client/jsonapi/basket/name = Mybasket
     *
     * The value is the last part of your own class name and it's case sensitive,
     * so take care that the configuration value is exactly named like the last
     * part of the class name.
     *
     * The allowed characters of the class name are A-Z, a-z and 0-9. No other
     * characters are possible! You should always start the last part of the class
     * name with an upper case character and continue only with lower case characters
     * or numbers. Avoid chamel case names like "MyBasket"!
     *
     * @param string Last part of the class name
     * @since 2017.03
     * @category Developer
     */
    /** client/jsonapi/basket/decorators/excludes
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
     * @see client/jsonapi/basket/decorators/global
     * @see client/jsonapi/basket/decorators/local
     */
    /** client/jsonapi/basket/decorators/global
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
     *  client/jsonapi/basket/decorators/global = array( 'decorator1' )
     *
     * This would add the decorator named "decorator1" defined by
     * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
     * "basket" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/basket/decorators/excludes
     * @see client/jsonapi/basket/decorators/local
     */
    /** client/jsonapi/basket/decorators/local
     * Adds a list of local decorators only to the JsonApi client
     *
     * Decorators extend the functionality of a class by adding new aspects
     * (e.g. log what is currently done), executing the methods of the underlying
     * class only in certain conditions (e.g. only for logged in users) or
     * modify what is returned to the caller.
     *
     * This option allows you to wrap local decorators
     * ("\Aimeos\Client\JsonApi\Basket\Decorator\*") around the JsonApi
     * client.
     *
     *  client/jsonapi/basket/decorators/local = array( 'decorator2' )
     *
     * This would add the decorator named "decorator2" defined by
     * "\Aimeos\Client\JsonApi\Basket\Decorator\Decorator2" only to the
     * "basket" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/basket/decorators/excludes
     * @see client/jsonapi/basket/decorators/global
     */
    private \Aimeos\Controller\Frontend\Basket\Iface $controller;
    /**
     * Initializes the client
     *
     * @param \Aimeos\MShop\ContextIface $context MShop context object
     */
    public function __construct(\Aimeos\M_Shop\Context_Iface $context)
    {
        parent::__construct($context);
        $this->controller = \Aimeos\Controller\Frontend::create($this->context(), 'basket');
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
        $view = $this->view();
        try {
            $this->clear_cache();
            $status = 200;
            $type = $view->param('id', 'default');
            $view->item = $this->controller->set_type($type)->clear()->get();
        } catch (\Aimeos\M_Shop\Plugin\Provider\Exception $e) {
            $status = 409;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Aimeos\M_Shop\Exception $e) {
            $status = 404;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Exception $e) {
            $status = $e->get_code() >= 100 && $e->get_code() < 600 ? $e->get_code() : 500;
            $view->errors = $this->get_error_details($e);
        }
        return $this->render($response, $view, $status);
    }
    /**
     * Returns the resource or the resource list
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    public function get(Server_Request_Interface $request, Response_Interface $response): \Psr\Http\Message\Response_Interface
    {
        $allow = false;
        $view = $this->view();
        $id = $view->param('id', 'default');
        $include = $view->param('include', 'basket/address,basket/coupon,basket/product,basket/service');
        $include = explode(',', str_replace('basket', 'order', str_replace('.', '/', $include)));
        try {
            try {
                $view->item = $this->controller->load($id, $include);
            } catch (\Aimeos\M_Shop\Exception $e) {
                $view->item = $this->controller->set_type($id)->get();
                $allow = true;
            }
            $status = 200;
        } catch (\Aimeos\M_Shop\Exception $e) {
            $status = 404;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Exception $e) {
            $status = $e->get_code() >= 100 && $e->get_code() < 600 ? $e->get_code() : 500;
            $view->errors = $this->get_error_details($e);
        }
        return $this->render($response, $view, $status, $allow);
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
        $view = $this->view();
        try {
            $this->clear_cache();
            $body = (string) $request->get_body();
            if (($payload = json_decode($body)) === null || !isset($payload->data->attributes)) {
                throw new \Aimeos\Client\Json_Api\Exception('Invalid JSON in body', 400);
            }
            $basket = $this->controller->set_type($view->param('id', 'default'))->add((array) $payload->data->attributes)->save()->get();
            $view->item = $basket;
            $status = 200;
        } catch (\Aimeos\M_Shop\Plugin\Provider\Exception $e) {
            $status = 409;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Aimeos\M_Shop\Exception $e) {
            $status = 404;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Exception $e) {
            $status = $e->get_code() >= 100 && $e->get_code() < 600 ? $e->get_code() : 500;
            $view->errors = $this->get_error_details($e);
        }
        return $this->render($response, $view, $status);
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
        $view = $this->view();
        try {
            $user_id = (string) $this->context()->user()?->get_id();
            $this->controller->set_type($view->param('id', 'default'));
            $this->controller->get()->set_channel('jsonapi')->set_customer_id($user_id)->check();
            $this->clear_cache();
            $item = $this->controller->store();
            $this->context()->session()->set('aimeos/order.id', $item->get_id());
            $view->item = $item;
            $status = 200;
        } catch (\Aimeos\M_Shop\Plugin\Provider\Exception $e) {
            $status = 409;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Aimeos\M_Shop\Exception $e) {
            $status = 404;
            $view->errors = $this->get_error_details($e, 'mshop');
        } catch (\Exception $e) {
            $status = $e->get_code() >= 100 && $e->get_code() < 600 ? $e->get_code() : 500;
            $view->errors = $this->get_error_details($e);
        }
        return $this->render($response, $view, $status);
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
        $view->attributes = ['order.comment' => ['label' => 'Customer comment for the order', 'type' => 'string', 'default' => '', 'required' => false], 'order.customerref' => ['label' => 'Own reference of the customer for the order', 'type' => 'string', 'default' => '', 'required' => false]];
        $tplconf = 'client/jsonapi/template-options';
        $default = 'options-standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'DELETE,GET,OPTIONS,PATCH,POST')->with_header('Cache-Control', 'max-age=300')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status(200);
    }
    /**
     * Returns the response object with the rendered header and body
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response object
     * @param \Aimeos\Base\View\Iface $view View instance
     * @param int $status HTTP status code
     * @param bool $allow True to allow all HTTP methods, false for GET only
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function render(Response_Interface $response, \Aimeos\Base\View\Iface $view, int $status, bool $allow = true): \Psr\Http\Message\Response_Interface
    {
        /** client/jsonapi/basket/template
         * Relative path to the basket JSON API template
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
         * @param string Relative path to the template creating the body for the JSON API
         * @since 2017.04
         * @category Developer
         */
        $tplconf = 'client/jsonapi/basket/template';
        $default = 'basket/standard';
        $body = $view->render($view->config($tplconf, $default));
        if ($allow === true) {
            $methods = 'DELETE,GET,OPTIONS,PATCH,POST';
        } else {
            $methods = 'GET';
        }
        return $response->with_header('Allow', $methods)->with_header('Cache-Control', 'no-cache, private')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status($status);
    }
}