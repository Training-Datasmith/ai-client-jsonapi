<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2026
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
$entry_fcn = function (\Aimeos\M_Shop\Common\Item\Property\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $id = $item->get_id();
    $attributes = $item->to_array();
    $rtype = str_replace('/', '.', $item->get_resource_type());
    $params = ['resource' => 'customer', 'id' => $item->get_parent_id(), 'related' => 'property', 'relatedid' => $id];
    if (isset($fields[$rtype])) {
        $attributes = array_intersect_key($attributes, $fields[$rtype]);
    }
    $entry = ['id' => $id, 'type' => $rtype, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['DELETE', 'GET', 'PATCH']]], 'attributes' => $attributes];
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
    $items = $this->get('items', []);
    if (is_map($items)) {
        foreach ($items as $prop_item) {
            $data[] = $entry_fcn($prop_item);
        }
    } else {
        $data = $entry_fcn($items);
    }
    ?>

		,"data": <?php 
    echo json_encode($data, $pretty);
    ?>

	<?php 
}
?>

}
