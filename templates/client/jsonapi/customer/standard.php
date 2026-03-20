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
$entry_fcn = function (\Aimeos\M_Shop\Customer\Item\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $id = $item->get_id();
    $type = $item->get_resource_type();
    $params = ['resource' => $type, 'id' => $id];
    $attributes = $item->to_array();
    if (isset($fields[$type])) {
        $attributes = array_intersect_key($attributes, $fields[$type]);
    }
    $entry = ['id' => $id, 'type' => $type, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'GET', 'PATCH']]], 'attributes' => ['customer.id' => $id]];
    if ($this->get('nodata', false) == true) {
        // don't expose more data to attackers
        return $entry;
    }
    $entry['attributes'] = $attributes;
    foreach ($item->get_address_items() as $addr_item) {
        $rtype = str_replace('/', '.', $addr_item->get_resource_type());
        $entry['relationships'][$rtype]['data'][] = ['id' => $addr_item->get_id(), 'type' => $rtype];
    }
    foreach ($item->get_list_items() as $list_id => $list_item) {
        $rtype = str_replace('/', '.', $list_item->get_domain());
        $params = ['resource' => $rtype, 'id' => $id, 'related' => 'relationships', 'relatedid' => $list_id];
        $entry['relationships'][$rtype]['data'][] = ['id' => $list_item->get_ref_id(), 'type' => $rtype, 'attributes' => $list_item->to_array(), 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'PATCH']]]];
    }
    foreach ($item->get_property_items() as $prop_item) {
        $rtype = str_replace('/', '.', $prop_item->get_resource_type());
        $entry['relationships'][$rtype]['data'][] = ['id' => $prop_item->get_id(), 'type' => $rtype];
    }
    return $entry;
};
$cust_addr_fcn = function (\Aimeos\M_Shop\Customer\Item\Address\Iface $item, array $entry) use ($target, $cntl, $action, $config) {
    $params = ['resource' => 'customer', 'id' => $item->get_parent_id(), 'related' => 'address', 'relatedid' => $item->get_id()];
    $basket_params = ['resource' => 'basket', 'id' => 'default', 'related' => 'address', 'relatedid' => 'payment'];
    $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'GET', 'PATCH']], 'basket.address' => ['href' => $this->url($target, $cntl, $action, $basket_params, [], $config), 'allow' => ['POST']]];
    return $entry;
};
$cust_prop_fcn = function (\Aimeos\M_Shop\Common\Item\Property\Iface $item, array $entry) use ($target, $cntl, $action, $config) {
    $params = ['resource' => 'customer', 'id' => $item->get_parent_id(), 'related' => 'property', 'relatedid' => $item->get_id()];
    $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'GET', 'PATCH']]];
    return $entry;
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
		"self": "<?php 
echo $this->url($target, $cntl, $action, $params, [], $config);
?>"
		<?php 
if (isset($this->item)) {
    ?>
			,"customer/address": {
				"href": "<?php 
    echo $this->url($target, $cntl, $action, ['resource' => 'customer', 'id' => $this->item->get_id(), 'related' => 'address'], [], $config);
    ?>",
				"allow": ["GET","POST"]
			}
			,"customer/property": {
				"href": "<?php 
    echo $this->url($target, $cntl, $action, ['resource' => 'customer', 'id' => $this->item->get_id(), 'related' => 'property'], [], $config);
    ?>",
				"allow": ["GET","POST"]
			}
			,"customer/relationships": {
				"href": "<?php 
    echo $this->url($target, $cntl, $action, ['resource' => 'customer', 'id' => $this->item->get_id(), 'related' => 'relationships'], [], $config);
    ?>",
				"allow": ["GET","POST"]
			}
			,"customer/review": {
				"href": "<?php 
    echo $this->url($target, $cntl, $action, ['resource' => 'customer', 'id' => $this->item->get_id(), 'related' => 'review'], [], $config);
    ?>",
				"allow": ["GET","POST"]
			}
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
		,"data": <?php 
    echo json_encode($entry_fcn($this->item), $pretty);
    ?>

		,"included": <?php 
    echo map($this->jincluded($this->item, $fields, ['customer.address' => $cust_addr_fcn, 'customer.property' => $cust_prop_fcn]))->flat(1)->to_json($pretty);
    ?>

	<?php 
}
?>

}
