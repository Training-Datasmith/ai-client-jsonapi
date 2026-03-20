<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
namespace Aimeos\Client\Json_Api\Customer;

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
    /** client/jsonapi/customer/name
     * Class name of the used customer client implementation
     *
     * Each default JSON API client can be replace by an alternative imlementation.
     * To use this implementation, you have to set the last part of the class
     * name as configuration value so the client factory knows which class it
     * has to instantiate.
     *
     * For example, if the name of the default class is
     *
     *  \Aimeos\Client\JsonApi\Customer\Standard
     *
     * and you want to replace it with your own version named
     *
     *  \Aimeos\Client\JsonApi\Customer\Mycustomer
     *
     * then you have to set the this configuration option:
     *
     *  client/jsonapi/customer/name = Mycustomer
     *
     * The value is the last part of your own class name and it's case sensitive,
     * so take care that the configuration value is exactly named like the last
     * part of the class name.
     *
     * The allowed characters of the class name are A-Z, a-z and 0-9. No other
     * characters are possible! You should always start the last part of the class
     * name with an upper case character and continue only with lower case characters
     * or numbers. Avoid chamel case names like "MyCustomer"!
     *
     * @param string Last part of the class name
     * @since 2017.04
     * @category Developer
     */
    /** client/jsonapi/customer/decorators/excludes
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
     * @see client/jsonapi/customer/decorators/global
     * @see client/jsonapi/customer/decorators/local
     */
    /** client/jsonapi/customer/decorators/global
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
     *  client/jsonapi/customer/decorators/global = array( 'decorator1' )
     *
     * This would add the decorator named "decorator1" defined by
     * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
     * "customer" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/customer/decorators/excludes
     * @see client/jsonapi/customer/decorators/local
     */
    /** client/jsonapi/customer/decorators/local
     * Adds a list of local decorators only to the JsonApi client
     *
     * Decorators extend the functionality of a class by adding new aspects
     * (e.g. log what is currently done), executing the methods of the underlying
     * class only in certain conditions (e.g. only for logged in users) or
     * modify what is returned to the caller.
     *
     * This option allows you to wrap local decorators
     * ("\Aimeos\Client\JsonApi\Customer\Decorator\*") around the JsonApi
     * client.
     *
     *  client/jsonapi/customer/decorators/local = array( 'decorator2' )
     *
     * This would add the decorator named "decorator2" defined by
     * "\Aimeos\Client\JsonApi\Customer\Decorator\Decorator2" only to the
     * "customer" JsonApi client.
     *
     * @param array List of decorator names
     * @since 2017.07
     * @category Developer
     * @see client/jsonapi/common/decorators/default
     * @see client/jsonapi/customer/decorators/excludes
     * @see client/jsonapi/customer/decorators/global
     */
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
            \Aimeos\Controller\Frontend::create($this->context(), 'customer')->uses([])->delete();
            $status = 200;
        } catch (\Aimeos\Controller\Frontend\Customer\Exception $e) {
            $status = 403;
            $view->errors = $this->get_error_details($e, 'controller/frontend');
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
        $view = $this->view();
        try {
            $ref = ($ref = $view->param('include')) ? explode(',', str_replace('.', '/', $ref)) : [];
            $cntl = \Aimeos\Controller\Frontend::create($this->context(), 'customer');
            $view->item = $cntl->uses($ref)->get();
            $status = 200;
        } catch (\Aimeos\Controller\Frontend\Customer\Exception $e) {
            $status = 403;
            $view->errors = $this->get_error_details($e, 'controller/frontend');
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
            $body = (string) $request->get_body();
            $ref = ($inc = $view->param('include')) ? explode(',', $inc) : [];
            if (($payload = json_decode($body)) === null || !isset($payload->data->attributes)) {
                throw new \Aimeos\Client\Json_Api\Exception('Invalid JSON in body', 400);
            }
            $cntl = \Aimeos\Controller\Frontend::create($this->context(), 'customer')->uses($ref);
            $view->item = $cntl->add((array) $payload->data->attributes)->store()->get();
            $status = 200;
        } catch (\Aimeos\Controller\Frontend\Customer\Exception $e) {
            $status = 403;
            $view->errors = $this->get_error_details($e, 'controller/frontend');
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
            $body = (string) $request->get_body();
            if (($payload = json_decode($body)) === null || !isset($payload->data->attributes)) {
                throw new \Aimeos\Client\Json_Api\Exception('Invalid JSON in body', 400);
            }
            $cntl = \Aimeos\Controller\Frontend::create($this->context(), 'customer')->uses([]);
            $view->item = $cntl->add((array) $payload->data->attributes)->store()->get();
            $view->nodata = true;
            // only expose customer ID to attackers
            $status = 201;
        } catch (\Aimeos\Controller\Frontend\Customer\Exception $e) {
            $status = 403;
            $view->errors = $this->get_error_details($e, 'controller/frontend');
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
        $view->attributes = ['customer.salutation' => ['label' => 'Customer salutation, i.e. "comany" ,"mr", "ms" or ""', 'type' => 'string', 'default' => '', 'required' => false], 'customer.company' => ['label' => 'Company name', 'type' => 'string', 'default' => '', 'required' => false], 'customer.vatid' => ['label' => 'VAT ID of the company', 'type' => 'string', 'default' => '', 'required' => false], 'customer.title' => ['label' => 'Title of the customer', 'type' => 'string', 'default' => '', 'required' => false], 'customer.firstname' => ['label' => 'First name of the customer', 'type' => 'string', 'default' => '', 'required' => false], 'customer.lastname' => ['label' => 'Last name of the customer or full name', 'type' => 'string', 'default' => '', 'required' => true], 'customer.address1' => ['label' => 'First address part like street', 'type' => 'string', 'default' => '', 'required' => true], 'customer.address2' => ['label' => 'Second address part like house number', 'type' => 'string', 'default' => '', 'required' => false], 'customer.address3' => ['label' => 'Third address part like flat number', 'type' => 'string', 'default' => '', 'required' => false], 'customer.postal' => ['label' => 'Zip code of the city', 'type' => 'string', 'default' => '', 'required' => false], 'customer.city' => ['label' => 'Name of the town/city', 'type' => 'string', 'default' => '', 'required' => true], 'customer.state' => ['label' => 'Two letter code of the country state', 'type' => 'string', 'default' => '', 'required' => false], 'customer.countryid' => ['label' => 'Two letter ISO country code', 'type' => 'string', 'default' => '', 'required' => true], 'customer.languageid' => ['label' => 'Two or five letter ISO language code, e.g. "de" or "de_CH"', 'type' => 'string', 'default' => '', 'required' => false], 'customer.telephone' => ['label' => 'Telephone number consisting of option leading "+" and digits without spaces', 'type' => 'string', 'default' => '', 'required' => false], 'customer.telefax' => ['label' => 'Faximile number consisting of option leading "+" and digits without spaces', 'type' => 'string', 'default' => '', 'required' => false], 'customer.email' => ['label' => 'E-mail address', 'type' => 'string', 'default' => '', 'required' => false], 'customer.website' => ['label' => 'Web site including "http://" or "https://"', 'type' => 'string', 'default' => '', 'required' => false], 'customer.longitude' => ['label' => 'Longitude of the customer location as float value', 'type' => 'float', 'default' => '', 'required' => false], 'customer.latitude' => ['label' => 'Latitude of the customer location as float value', 'type' => 'float', 'default' => '', 'required' => false], 'customer.label' => ['label' => 'Label to identify the customer, will be firstname, lastname and company if empty', 'type' => 'string', 'default' => '', 'required' => true], 'customer.code' => ['label' => 'Unique customer identifier, will be the e-mail address if empty', 'type' => 'string', 'default' => '', 'required' => false], 'customer.password' => ['label' => 'Password of the customer, generated if emtpy', 'type' => 'string', 'default' => '', 'required' => false], 'customer.birthday' => ['label' => 'ISO date in YYYY-MM-DD format of the birthday', 'type' => 'string', 'default' => '', 'required' => false], 'customer.status' => ['label' => 'Customer account status, i.e. "0" for disabled, "1" for enabled and is enabled by default', 'type' => 'integer', 'default' => '1', 'required' => false]];
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
     * @param integer $status HTTP status code
     * @return \Psr\Http\Message\ResponseInterface Modified response object
     */
    protected function render(Response_Interface $response, \Aimeos\Base\View\Iface $view, int $status): \Psr\Http\Message\Response_Interface
    {
        /** client/jsonapi/customer/template
         * Relative path to the customer JSON API template
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
        $tplconf = 'client/jsonapi/customer/template';
        $default = 'customer/standard';
        $body = $view->render($view->config($tplconf, $default));
        return $response->with_header('Allow', 'DELETE,GET,OPTIONS,PATCH,POST')->with_header('Cache-Control', 'no-cache, private')->with_header('Content-Type', 'application/vnd.api+json')->with_body($view->response()->create_stream_from_string($body))->with_status($status);
    }
}