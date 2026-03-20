<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020-2026
 * @package Client
 * @subpackage JsonApi
 */
$enc = $this->encoder();
$target = $this->config('client/jsonapi/url/target');
$cntl = $this->config('client/jsonapi/url/controller', 'jsonapi');
$action = $this->config('client/jsonapi/url/action', 'get');
$config = $this->config('client/jsonapi/url/config', []);
$total = $this->get('total', 0);
$offset = max($this->param('page/offset', 0), 0);
$limit = max($this->param('page/limit', 10), 1);
$first = $offset > 0 ? 0 : null;
$prev = $offset - $limit >= 0 ? $offset - $limit : null;
$next = $offset + $limit < $total ? $offset + $limit : null;
$last = (int) ($total / $limit) * $limit > $offset ? (int) ($total / $limit) * $limit : null;
$ref = ['resource', 'id', 'filter', 'page', 'sort', 'include', 'fields'];
$params = array_intersect_key($this->param(), array_flip($ref));
$pretty = $this->param('pretty') ? JSON_PRETTY_PRINT : 0;
$fields = $this->param('fields', []);
foreach ((array) $fields as $resource => $list) {
    $fields[$resource] = array_flip(explode(',', $list));
}
$entry_fcn = function (\Aimeos\M_Shop\Review\Item\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $id = $item->get_id();
    $attributes = $item->to_array();
    $type = $item->get_resource_type();
    $params = ['resource' => $type, 'id' => $id];
    if (isset($fields[$type])) {
        $attributes = array_intersect_key($attributes, $fields[$type]);
    }
    $entry = ['id' => $id, 'type' => $type, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]], 'attributes' => $attributes];
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
		<?php 
if (is_map($this->get('items'))) {
    ?>
			<?php 
    if ($first !== null) {
        ?>
				"first": "<?php 
        $params['page']['offset'] = $first;
        echo $this->url($target, $cntl, $action, $params, [], $config);
        ?>",
			<?php 
    }
    ?>
			<?php 
    if ($prev !== null) {
        ?>
				"prev": "<?php 
        $params['page']['offset'] = $prev;
        echo $this->url($target, $cntl, $action, $params, [], $config);
        ?>",
			<?php 
    }
    ?>
			<?php 
    if ($next !== null) {
        ?>
				"next": "<?php 
        $params['page']['offset'] = $next;
        echo $this->url($target, $cntl, $action, $params, [], $config);
        ?>",
			<?php 
    }
    ?>
			<?php 
    if ($last !== null) {
        ?>
				"last": "<?php 
        $params['page']['offset'] = $last;
        echo $this->url($target, $cntl, $action, $params, [], $config);
        ?>",
			<?php 
    }
    ?>
		<?php 
}
?>
		"self": "<?php 
$params['page']['offset'] = $offset;
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

	<?php 
}
?>

}
