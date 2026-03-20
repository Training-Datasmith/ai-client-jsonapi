<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
$enc = $this->encoder();
$target = $this->config('client/jsonapi/url/target');
$cntl = $this->config('client/jsonapi/url/controller', 'jsonapi');
$action = $this->config('client/jsonapi/url/action', 'get');
$config = $this->config('client/jsonapi/url/config', []);
$ref = ['resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields'];
$params = array_intersect_key($this->param(), array_flip($ref));
$pretty = $this->param('pretty') ? JSON_PRETTY_PRINT : 0;
$fields = $this->param('fields', []);
foreach ((array) $fields as $resource => $list) {
    $fields[$resource] = array_flip(explode(',', $list));
}
$entry_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item, ?\Aimeos\M_Shop\Common\Helper\Form\Iface $form = null) use ($fields, $target, $cntl, $action, $config) {
    $relationships = [];
    $id = $item->get_id();
    $attributes = $item->to_array();
    $type = $item->get_resource_type();
    $params = ['resource' => $type, 'id' => $id];
    if (isset($fields[$type])) {
        $attributes = array_intersect_key($attributes, $fields[$type]);
    }
    foreach ($item->get_products() as $product) {
        $relationships['order.product']['data'][] = ['type' => 'order.product', 'id' => $product->get_id()];
    }
    foreach ($item->get_services() as $list) {
        foreach ($list as $service) {
            $relationships['order.service']['data'][] = ['type' => 'order.service', 'id' => $service->get_id()];
        }
    }
    foreach ($item->get_addresses() as $list) {
        foreach ($list as $address) {
            $relationships['order.address']['data'][] = ['type' => 'order.address', 'id' => $address->get_id()];
        }
    }
    foreach ($item->get_coupons() as $code => $x) {
        $relationships['order.coupon']['data'][] = ['type' => 'order.coupon', 'id' => $code];
    }
    if ($customer = $item->get_customer_item()) {
        $relationships['customer']['data'][] = ['type' => 'customer', 'id' => $customer->get_id()];
    }
    $entry = ['id' => $id, 'type' => $type, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]], 'attributes' => $attributes, 'relationships' => (object) $relationships];
    if ($form !== null) {
        $entry['links']['process']['href'] = $form->get_url();
        $entry['links']['process']['allow'] = [$form->get_method() !== 'REDIRECT' ? $form->get_method() : 'GET'];
        $entry['links']['process']['meta'] = [];
        foreach ($form->get_values() as $key => $attr) {
            $entry['links']['process']['meta'][$key] = $attr->to_array();
        }
    }
    return $entry;
};
$product_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item) use ($fields) {
    $result = [];
    foreach ($item->get_products() as $order_product) {
        $entry = ['id' => $order_product->get_id(), 'type' => 'order.product'];
        $entry['attributes'] = $order_product->to_array();
        if (isset($fields['order.product'])) {
            $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['order.product']);
        }
        foreach ($order_product->get_products() as $sub_product) {
            $sub_entry = $sub_product->to_array();
            foreach ($sub_product->get_attribute_items() as $attribute) {
                $sub_entry['attribute'][] = $attribute->to_array();
            }
            $entry['attributes']['product'][] = $sub_entry;
        }
        foreach ($order_product->get_attribute_items() as $attribute) {
            $entry['attributes']['attribute'][] = $attribute->to_array();
        }
        if ($product = $order_product->get_product_item()) {
            $entry['relationships']['product']['data'][] = ['type' => 'product', 'id' => $product->get_id()];
            $result = array_merge($result, $this->jincluded($product, $fields));
        }
        $result['order.product'][] = $entry;
    }
    return $result;
};
$service_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item) use ($fields) {
    $result = [];
    foreach ($item->get_services() as $type => $list) {
        foreach ($list as $order_service) {
            $entry = ['id' => $order_service->get_id(), 'type' => 'order.service'];
            $entry['attributes'] = $order_service->to_array();
            if (isset($fields['order.service'])) {
                $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['order.service']);
            }
            foreach ($order_service->get_attribute_items() as $attribute) {
                $entry['attributes']['attribute'][] = $attribute->to_array();
            }
            if ($service = $order_service->get_service_item()) {
                $entry['relationships']['service']['data'][] = ['type' => 'service', 'id' => $service->get_id()];
                $result = array_merge($result, $this->jincluded($service, $fields));
            }
            $result['order.service'][] = $entry;
        }
    }
    return $result;
};
$address_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item) use ($fields) {
    $list = [];
    foreach ($item->get_addresses() as $type => $addresses) {
        foreach ($addresses as $address) {
            $entry = ['id' => $address->get_id(), 'type' => 'order.address'];
            $entry['attributes'] = $address->to_array();
            if (isset($fields['order.address'])) {
                $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['order.address']);
            }
            $list['order.address'][] = $entry;
        }
    }
    return $list;
};
$coupon_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item) {
    $coupons = [];
    foreach ($item->get_coupons() as $code => $list) {
        $coupons['order.coupon'][] = ['id' => $code, 'type' => 'order.coupon'];
    }
    return $coupons;
};
$customer_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $result = [];
    if (($customer = $item->get_customer_item()) !== null && $customer->is_available()) {
        $params = ['resource' => 'customer', 'id' => $customer->get_id()];
        $entry = ['id' => $customer->get_id(), 'type' => 'customer'];
        $entry['attributes'] = $customer->to_array();
        if (isset($fields['customer'])) {
            $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['customer']);
        }
        $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]];
        $result['customer'][$customer->get_id()] = $entry;
        $result = array_replace_recursive($result, $this->jincluded($customer, $fields));
    }
    return $result;
};
?>
{
	"meta": {
		"total": <?php 
echo $this->get('total', 0);
?>,
		"prefix": <?php 
echo json_encode($this->get('prefix'));
?>,
		"content-baseurl": "<?php 
echo $this->config('resource/fs/baseurl');
?>",
		"content-baseurls": {
			"fs-media": "<?php 
echo $this->config('resource/fs-media/baseurl');
?>",
			"fs-mimeicon": "<?php 
echo $this->config('resource/fs-mimeicon/baseurl');
?>",
			"fs-theme": "<?php 
echo $this->config('resource/fs-theme/baseurl');
?>"
		}
		<?php 
if ($this->csrf()->name() != '') {
    ?>
			, "csrf": {
				"name": "<?php 
    echo $this->csrf()->name();
    ?>",
				"value": "<?php 
    echo $this->csrf()->value();
    ?>"
			}
		<?php 
}
?>

	},
	"links": {
		"self": "<?php 
echo $this->url($target, $cntl, $action, $params, [], $config);
?>"
	}
	<?php 
if (isset($this->errors)) {
    ?>
		,"errors": <?php 
    echo json_encode($this->errors, $pretty);
    ?>

	<?php 
} elseif (isset($this->items)) {
    ?>
		<?php 
    $data = $included = [];
    $items = $this->get('items', map());
    if (is_map($items)) {
        foreach ($items as $item) {
            $data[] = $entry_fcn($item, $this->get('form'));
            $included = array_replace_recursive($included, $coupon_fcn($item));
            $included = array_replace_recursive($included, $address_fcn($item));
            $included = array_replace_recursive($included, $product_fcn($item));
            $included = array_replace_recursive($included, $service_fcn($item));
            $included = array_replace_recursive($included, $customer_fcn($item));
        }
    } else {
        $data = $entry_fcn($items, $this->get('form'));
        $included = array_replace_recursive($included, $coupon_fcn($items));
        $included = array_replace_recursive($included, $address_fcn($items));
        $included = array_replace_recursive($included, $product_fcn($items));
        $included = array_replace_recursive($included, $service_fcn($items));
        $included = array_replace_recursive($included, $customer_fcn($items));
    }
    ?>

		,"data": <?php 
    echo json_encode($data, $pretty);
    ?>

		,"included": <?php 
    echo map($included)->flat(1)->to_json($pretty);
    ?>

	<?php 
}
?>

}
