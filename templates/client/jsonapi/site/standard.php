<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2026
 * @package Client
 * @subpackage JsonApi
 */
$enc = $this->encoder();
$target = $this->config('client/jsonapi/url/target');
$cntl = $this->config('client/jsonapi/url/controller', 'jsonapi');
$action = $this->config('client/jsonapi/url/action', 'get');
$config = $this->config('client/jsonapi/url/config', []);
$offset = max($this->param('page/offset', 0), 0);
$limit = max($this->param('page/limit', 100), 1);
$ref = ['resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields'];
$params = array_intersect_key($this->param(), array_flip($ref));
$pretty = $this->param('pretty') ? JSON_PRETTY_PRINT : 0;
$fields = $this->param('fields', []);
foreach ((array) $fields as $resource => $list) {
    $fields[$resource] = array_flip(explode(',', $list));
}
$entry_fcn = function (\Aimeos\M_Shop\Locale\Item\Site\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    if ($item->is_available() === false) {
        return [];
    }
    $id = $item->get_id();
    $type = $item->get_resource_type();
    $params = ['resource' => 'site', 'id' => $item->get_id()];
    $attributes = $item->to_array();
    if (isset($fields[$type])) {
        $attributes = array_intersect_key($attributes, $fields[$type]);
    }
    $entry = ['id' => $id, 'type' => $type, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]], 'attributes' => $attributes];
    foreach ($item->get_children() as $cat_item) {
        if ($cat_item->is_available()) {
            $entry['relationships']['site']['data'][] = ['id' => $cat_item->get_id(), 'type' => 'site'];
        }
    }
    return $entry;
};
$site_fcn = function (\Aimeos\M_Shop\Locale\Item\Site\Iface $item, array $entry) use ($target, $cntl, $action, $config) {
    $params = ['resource' => 'site', 'id' => $item->get_id()];
    $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]];
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
    $included = $this->jincluded($items, $fields, ['site' => $site_fcn]);
    if (is_map($items)) {
        foreach ($items as $item) {
            $data[] = $entry_fcn($item);
        }
    } else {
        $data = $entry_fcn($items);
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
