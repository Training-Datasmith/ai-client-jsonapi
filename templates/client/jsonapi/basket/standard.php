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
$basket_id = isset($this->item) && $this->item->get_id() ? $this->item->get_id() : ($this->param('id') ?: 'default');
$ref = ['resource', 'id', 'filter', 'page', 'sort', 'include', 'fields'];
// no related/relatedid for basket self URL
$params = array_intersect_key($this->param(), array_flip($ref));
$pretty = $this->param('pretty') ? JSON_PRETTY_PRINT : 0;
$fields = $this->param('fields', []);
foreach ((array) $fields as $resource => $list) {
    $fields[$resource] = array_flip(explode(',', $list));
}
$entry_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item, $basket_id) use ($fields, $target, $cntl, $action, $config) {
    $allow = ['GET'];
    $attributes = $item->to_array();
    $params = ['resource' => 'basket', 'id' => $basket_id];
    if (($filter = $this->param('filter', [])) !== []) {
        $params['filter'] = $filter;
    }
    if ($item->get_id() === null) {
        $allow = ['DELETE', 'GET', 'PATCH', 'POST'];
    }
    if (isset($fields['basket'])) {
        $attributes = array_intersect_key($attributes, $fields['basket']);
    }
    $relationships = [];
    $types = explode(',', $this->param('include', 'basket.product,basket.service,basket.address,basket.coupon'));
    if (in_array('basket.product', $types)) {
        foreach ($item->get_products() as $position => $list) {
            $relationships['basket.product']['data'][] = ['type' => 'basket.product', 'id' => $position];
        }
    }
    if (in_array('basket.service', $types)) {
        foreach ($item->get_services() as $type => $list) {
            if (count($list) > 0) {
                $relationships['basket.service']['data'][] = ['type' => 'basket.service', 'id' => $type];
            }
        }
    }
    if (in_array('basket.address', $types)) {
        foreach ($item->get_addresses() as $type => $list) {
            $relationships['basket.address']['data'][] = ['type' => 'basket.address', 'id' => $type];
        }
    }
    if (in_array('basket.coupon', $types)) {
        foreach ($item->get_coupons() as $code => $list) {
            $relationships['basket.coupon']['data'][] = ['type' => 'basket.coupon', 'id' => $code];
        }
    }
    if ($customer = $item->get_customer_item()) {
        $relationships['customer']['data'][] = ['type' => 'customer', 'id' => $customer->get_id()];
    }
    return ['id' => $basket_id, 'type' => 'basket', 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => $allow]], 'attributes' => $attributes, 'relationships' => (object) $relationships];
};
$product_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item, $basket_id) use ($fields, $target, $cntl, $action, $config) {
    $result = [];
    foreach ($item->get_products() as $position => $order_product) {
        $entry = ['id' => $position, 'type' => 'basket.product'];
        $entry['attributes'] = $order_product->to_array();
        if (isset($fields['basket.product'])) {
            $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['basket.product']);
        }
        if ($item->get_id() === null && $order_product->get_flags() !== \Aimeos\M_Shop\Order\Item\Product\Base::FLAG_IMMUTABLE) {
            $params = ['resource' => 'basket', 'id' => $basket_id, 'related' => 'product', 'relatedid' => $position];
            $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'PATCH']]];
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
$service_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item, $basket_id) use ($fields, $target, $cntl, $action, $config) {
    $result = [];
    foreach ($item->get_services() as $type => $list) {
        foreach ($list as $order_service) {
            $entry = ['id' => $type, 'type' => 'basket.service'];
            $entry['attributes'] = $order_service->to_array();
            if (isset($fields['basket.service'])) {
                $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['basket.service']);
            }
            if ($item->get_id() === null) {
                $params = ['resource' => 'basket', 'id' => $basket_id, 'related' => 'service', 'relatedid' => $type];
                $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'PATCH']]];
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
$address_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item, $basket_id) use ($fields, $target, $cntl, $action, $config) {
    $list = [];
    foreach ($item->get_addresses() as $type => $addresses) {
        foreach ($addresses as $address) {
            $entry = ['id' => $type, 'type' => 'basket.address'];
            $entry['attributes'] = $address->to_array();
            if (isset($fields['basket.address'])) {
                $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['basket.address']);
            }
            if ($item->get_id() === null) {
                $params = ['resource' => 'basket', 'id' => $basket_id, 'related' => 'address', 'relatedid' => $type];
                $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'PATCH']]];
            }
            $list['order.address'][] = $entry;
        }
    }
    return $list;
};
$coupon_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item, $basket_id) use ($fields, $target, $cntl, $action, $config) {
    $coupons = [];
    foreach ($item->get_coupons() as $code => $list) {
        $entry = ['id' => $code, 'type' => 'basket.coupon'];
        if ($item->get_id() === null) {
            $params = ['resource' => 'basket', 'id' => $basket_id, 'related' => 'coupon', 'relatedid' => $code];
            $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE']]];
        }
        $coupons['order.coupon'][] = $entry;
    }
    return $coupons;
};
$customer_fcn = function (\Aimeos\M_Shop\Order\Item\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $result = [];
    $customer = $this->item->get_customer_item();
    if ($customer && $customer->is_available()) {
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
echo isset($this->item) ? 1 : 0;
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
		"self": {
			"href": "<?php 
echo $this->url($target, $cntl, $action, $params, [], $config);
?>",
			"allow": <?php 
echo isset($this->item) && $this->item->get_id() ? '["GET"]' : '["DELETE","GET","PATCH","POST"]';
?>

		}
		<?php 
if (isset($this->item)) {
    ?>
			<?php 
    if ($this->item->get_id() === null) {
        ?>
				,
				"basket.product": {
					"href": "<?php 
        echo $this->url($target, $cntl, $action, ['resource' => 'basket', 'id' => $basket_id, 'related' => 'product'], [], $config);
        ?>",
					"allow": ["DELETE", "POST"]
				},
				"basket.service": {
					"href": "<?php 
        echo $this->url($target, $cntl, $action, ['resource' => 'basket', 'id' => $basket_id, 'related' => 'service'], [], $config);
        ?>",
					"allow": ["DELETE", "POST"]
				},
				"basket.address": {
					"href": "<?php 
        echo $this->url($target, $cntl, $action, ['resource' => 'basket', 'id' => $basket_id, 'related' => 'address'], [], $config);
        ?>",
					"allow": ["DELETE", "POST"]
				},
				"basket.coupon": {
					"href": "<?php 
        echo $this->url($target, $cntl, $action, ['resource' => 'basket', 'id' => $basket_id, 'related' => 'coupon'], [], $config);
        ?>",
					"allow": ["DELETE", "POST"]
				}
			<?php 
    } else {
        ?>
				,
				"order": {
					"href": "<?php 
        echo $this->url($target, $cntl, $action, ['resource' => 'order'], [], $config);
        ?>",
					"allow": ["POST"]
				}
			<?php 
    }
    ?>
		<?php 
}
?>

	}
	<?php 
if (isset($this->errors)) {
    ?>
		,"errors": <?php 
    echo json_encode($this->errors, $pretty);
    ?>

	<?php 
} elseif (isset($this->item)) {
    ?>
		<?php 
    $included = [];
    $types = explode(',', $this->param('include', 'basket.product,basket.service,basket.address,basket.coupon'));
    if (in_array('basket.product', $types)) {
        $included = array_replace_recursive($included, $product_fcn($this->item, $basket_id));
    }
    if (in_array('basket.service', $types)) {
        $included = array_replace_recursive($included, $service_fcn($this->item, $basket_id));
    }
    if (in_array('basket.address', $types)) {
        $included = array_replace_recursive($included, $address_fcn($this->item, $basket_id));
    }
    if (in_array('basket.coupon', $types)) {
        $included = array_replace_recursive($included, $coupon_fcn($this->item, $basket_id));
    }
    if (in_array('customer', $types)) {
        $included = array_replace_recursive($included, $customer_fcn($this->item));
    }
    ?>

		,"data": <?php 
    echo json_encode($entry_fcn($this->item, $basket_id), $pretty);
    ?>

		,"included": <?php 
    echo map($included)->flat(1)->to_json($pretty);
    ?>

	<?php 
}
?>

}
