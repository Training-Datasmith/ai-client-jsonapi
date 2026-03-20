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
$entry_fcn = function (\Aimeos\M_Shop\Service\Item\Iface $item, \Aimeos\Map $prices, array $fe_config) use ($fields, $target, $cntl, $action, $config) {
    $metadata = [];
    $id = $item->get_id();
    $type = $item->get_resource_type();
    $attributes = $item->to_array();
    unset($attributes['service.config']);
    // don't expose private information
    $params = ['resource' => $type, 'id' => $id];
    $basket_params = ['resource' => 'basket', 'id' => 'default', 'related' => 'service', 'relatedid' => $item->get_type()];
    if (isset($fields[$type])) {
        $attributes = array_intersect_key($attributes, $fields[$type]);
    }
    if (($price = $prices->get($id)) !== null) {
        $attributes['price'] = $price->to_array();
    }
    if (isset($fe_config[$id])) {
        foreach ($fe_config[$id] as $code => $attr) {
            $metadata[$code] = $attr->to_array();
        }
    }
    $entry = ['id' => $id, 'type' => $type, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']], 'basket.service' => ['href' => $this->url($target, $cntl, $action, $basket_params, [], $config), 'allow' => ['POST'], 'meta' => $metadata]], 'attributes' => $attributes];
    if ($type_item = $item->get_type_item()) {
        $entry['relationships'][$type . '.type']['data'][] = ['id' => $type_item->get_id(), 'type' => $type . '.type'];
    }
    foreach ($item->get_list_items() as $list_item) {
        if (($ref_item = $list_item->get_ref_item()) !== null && $ref_item->is_available()) {
            $ltype = str_replace('/', '.', $list_item->get_resource_type());
            $rtype = str_replace('/', '.', $ref_item->get_resource_type());
            $attributes = $list_item->to_array();
            if (isset($fields[$ltype])) {
                $attributes = array_intersect_key($attributes, $fields[$ltype]);
            }
            $data = ['id' => $ref_item->get_id(), 'type' => $rtype, 'attributes' => $attributes];
            $entry['relationships'][$rtype]['data'][] = $data;
        }
    }
    return $entry;
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
    $data = [];
    $items = $this->get('items', map());
    $prices = $this->get('prices', map());
    $fe_config = $this->get('attributes', []);
    $included = $this->jincluded($this->items, $fields);
    if (is_map($items)) {
        foreach ($items as $item) {
            $data[] = $entry_fcn($item, $prices, $fe_config);
        }
    } else {
        $data = $entry_fcn($items, $prices, $fe_config);
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
