<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
namespace Aimeos\Client\Json_Api\Basket\Address;

use Psr\Http\Message\Response_Interface;
use Psr\Http\Message\Server_Request_Interface;
/**
 * JSON API basket/address client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard extends \Aimeos\Client\Json_Api\Basket\Base implements \Aimeos\Client\Json_Api\Iface
{
    /** client/jsonapi/basket/address/name
     * Class name of the used basket/address client implementation
     *
     * Each default JSON API client can be replace by an alternative imlementation.
     * To use this implementation, you have to set the last part of the class
     * name as configuration value so the client factory knows which class it
     * has to instantiate.
     *
     * For example, if the name of the default class is
     *
     *  \Aimeos\Client\JsonApi\Basket\Address\Standard
     *
     * and you want to replace it with your own version named
     *
     *  \Aimeos\Client\JsonApi\Basket\Address\Mybasket/address
     *
     * then you have to set the this configuration option:
     *
     *  client/jsonapi/basket/address/name = Mybasket/address
     *
     * The value is the last part of your own class name and it's case sensitive,
     * so take care that the configuration value is exactly named like the last
     * part of the class name.
     *
     * The allowed characters of the class name are A-Z, a-z and 0-9. No other
     * characters are possible! You should always start the last part of the class
     * name with an upper case character and continue only with lower case characters
     * or numbers. Avoid chamel case names like "MyAddress"!
     *
     * @param string Last part of the class name
     * @since 2017.03
     * @category Developer
     */
    /** client/jsonapi/basket/address/decorators/excludes
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
     * @see client/jsonapi/basket/address/decorators/global
     * @see client/jsonapi/basket/address/decorators/local
     */
    /** client/jsonapi/basket/address/decorators/global
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
     *  client/jsonapi/basket/address/decorators/global = array( 'decorator1' )
     *
     * This would add the decorator named "decorator1" defined by
     * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
     * "basket" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/basket/address/decorators/excludes
     * @see client/jsonapi/basket/address/decorators/local
     */
    /** client/jsonapi/basket/address/decorators/local
     * Adds a list of local decorators only to the JsonApi client
     *
     * Decorators extend the functionality of a class by adding new aspects
     * (e.g. log what is currently done), executing the methods of the underlying
     * class only in certain conditions (e.g. only for logged in users) or
     * modify what is returned to the caller.
     *
     * This option allows you to wrap local decorators
     * ("\Aimeos\Client\JsonApi\Basket\Address\Decorator\*") around the JsonApi
     * client.
     *
     *  client/jsonapi/basket/address/decorators/local = array( 'decorator2' )
     *
     * This would add the decorator named "decorator2" defined by
     * "\Aimeos\Client\JsonApi\Basket\Address\Decorator\Decorator2" only to the
     * "basket address" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/basket/address/decorators/excludes
     * @see client/jsonapi/basket/address/decorators/global
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
            $this->controller->set_type($view->param('id', 'default'));
            $rel_id = $view->param('relatedid');
            $body = (string) $request->get_body();
            if ($rel_id === '' || $rel_id === null) {
                if (($payload = json_decode($body)) === null || !isset($payload->data)) {
                    throw new \Aimeos\Client\Json_Api\Exception('Invalid JSON in body', 400);
                }
                if (!is_array($payload->data)) {
                    $payload->data = [$payload->data];
                }
                foreach ($payload->data as $entry) {
                    if (!isset($entry->id)) {
                        throw new \Aimeos\Client\Json_Api\Exception('Type (ID) is missing', 400);
                    }
                    $this->controller->delete_address($entry->id);
                }
            } else {
                $this->controller->delete_address($rel_id);
            }
            $view->item = $this->controller->get();
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
            $this->controller->set_type($view->param('id', 'default'));
            $body = (string) $request->get_body();
            if (($payload = json_decode($body)) === null || !isset($payload->data)) {
                throw new \Aimeos\Client\Json_Api\Exception('Invalid JSON in body', 400);
            }
            if (!is_array($payload->data)) {
                $payload->data = [$payload->data];
            }
            if ($rel_id = $view->param('relatedid')) {
                $this->controller->delete_address($rel_id);
            }
            foreach ($payload->data as $entry) {
                if (!isset($entry->id) || !isset($entry->attributes)) {
                    throw new \Aimeos\Client\Json_Api\Exception('Address type or attributes are missing', 400);
                }
                $this->controller->add_address($entry->id, (array) $entry->attributes);
            }
            $view->item = $this->controller->get();
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
            $this->clear_cache();
            $this->controller->set_type($view->param('id', 'default'));
            $body = (string) $request->get_body();
            if (($payload = json_decode($body)) === null || !isset($payload->data)) {
                throw new \Aimeos\Client\Json_Api\Exception('Invalid JSON in body', 400);
            }
            if (!is_array($payload->data)) {
                $payload->data = [$payload->data];
            }
            foreach ($payload->data as $entry) {
                if (!isset($entry->id) || !isset($entry->attributes)) {
                    throw new \Aimeos\Client\Json_Api\Exception('Address type or attributes are missing', 400);
                }
                $this->controller->add_address($entry->id, (array) $entry->attributes);
            }
            $view->item = $this->controller->get();
            $status = 201;
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
        $view->attributes = ['addressid' => ['label' => 'ID of the customer address', 'type' => 'string', 'default' => '', 'required' => false], 'salutation' => ['label' => 'Customer salutation, i.e. "comany" ,"mr", "ms" or ""', 'type' => 'string', 'default' => '', 'required' => false], 'company' => ['label' => 'Company name', 'type' => 'string', 'default' => '', 'required' => false], 'vatid' => ['label' => 'VAT ID of the company', 'type' => 'string', 'default' => '', 'required' => false], 'title' => ['label' => 'Title of the customer', 'type' => 'string', 'default' => '', 'required' => false], 'firstname' => ['label' => 'First name of the customer', 'type' => 'string', 'default' => '', 'required' => false], 'lastname' => ['label' => 'Last name of the customer or full name', 'type' => 'string', 'default' => '', 'required' => true], 'address1' => ['label' => 'First address part like street', 'type' => 'string', 'default' => '', 'required' => true], 'address2' => ['label' => 'Second address part like house number', 'type' => 'string', 'default' => '', 'required' => false], 'address3' => ['label' => 'Third address part like flat number', 'type' => 'string', 'default' => '', 'required' => false], 'postal' => ['label' => 'Zip code of the city', 'type' => 'string', 'default' => '', 'required' => false], 'city' => ['label' => 'Name of the town/city', 'type' => 'string', 'default' => '', 'required' => true], 'state' => ['label' => 'Two letter code of the country state', 'type' => 'string', 'default' => '', 'required' => false], 'countryid' => ['label' => 'Two letter ISO country code', 'type' => 'string', 'default' => '', 'required' => true], 'languageid' => ['label' => 'Two or five letter ISO language code, e.g. "de" or "de_CH"', 'type' => 'string', 'default' => '', 'required' => false], 'telephone' => ['label' => 'Telephone number consisting of optional leading "+" and digits without spaces', 'type' => 'string', 'default' => '', 'required' => false], 'telefax' => ['label' => 'Facsimile number consisting of optional leading "+" and digits without spaces', 'type' => 'string', 'default' => '', 'required' => false], 'email' => ['label' => 'E-mail address', 'type' => 'string', 'default' => '', 'required' => false], 'website' => ['label' => 'Web site including "http://" or "https://"', 'type' => 'string', 'default' => '', 'required' => false], 'longitude' => ['label' => 'Longitude of the customer location as float value', 'type' => 'float', 'default' => '', 'required' => false], 'latitude' => ['label' => 'Latitude of the customer location as float value', 'type' => 'float', 'default' => '', 'required' => false]];
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
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function render(Response_Interface $response, \Aimeos\Base\View\Iface $view, int $status): \Psr\Http\Message\Response_Interface
    {
        $tplconf = 'client/jsonapi/basket/template';
        $default = 'basket/standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'DELETE,GET,OPTIONS,PATCH,POST')->with_header('Cache-Control', 'no-cache, private')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status($status);
    }
}