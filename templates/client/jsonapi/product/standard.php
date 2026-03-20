<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package Client
 * @subpackage JsonApi
 */
$enc = $this->encoder();
/** client/jsonapi/url/target
 * Destination of the URL where the client specified in the URL is known
 *
 * The destination can be a page ID like in a content management system or the
 * module of a software development framework. This "target" must contain or know
 * the client that should be called by the generated URL.
 *
 * @param string Destination of the URL
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/controller
 * @see client/jsonapi/url/action
 * @see client/jsonapi/url/config
 */
$target = $this->config('client/jsonapi/url/target');
/** client/jsonapi/url/controller
 * Name of the client whose action should be called
 *
 * In Model-View-Controller (MVC) applications, the client contains the methods
 * that create parts of the output displayed in the generated HTML page. Controller
 * names are usually alpha-numeric.
 *
 * @param string Name of the client
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/target
 * @see client/jsonapi/url/action
 * @see client/jsonapi/url/config
 */
$cntl = $this->config('client/jsonapi/url/controller', 'jsonapi');
/** client/jsonapi/url/action
 * Name of the action that should create the output
 *
 * In Model-View-Controller (MVC) applications, actions are the methods of a
 * client that create parts of the output displayed in the generated HTML page.
 * Action names are usually alpha-numeric.
 *
 * @param string Name of the action
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/target
 * @see client/jsonapi/url/controller
 * @see client/jsonapi/url/config
 */
$action = $this->config('client/jsonapi/url/action', 'get');
/** client/jsonapi/url/config
 * Associative list of configuration options used for generating the URL
 *
 * You can specify additional options as key/value pairs used when generating
 * the URLs, like
 *
 *  client/jsonapi/url/config = array( 'absoluteUri' => true )
 *
 * The available key/value pairs depend on the application that embeds the e-commerce
 * framework. This is because the infrastructure of the application is used for
 * generating the URLs. The full list of available config options is referenced
 * in the "see also" section of this page.
 *
 * @param string Associative list of configuration options
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/target
 * @see client/jsonapi/url/controller
 * @see client/jsonapi/url/action
 */
$config = $this->config('client/jsonapi/url/config', []);
$total = $this->get('total', 0);
$offset = max($this->param('page/offset', 0), 0);
$limit = max($this->param('page/limit', 48), 1);
$first = $offset > 0 ? 0 : null;
$prev = $offset - $limit >= 0 ? $offset - $limit : null;
$next = $offset + $limit < $total ? $offset + $limit : null;
$last = (int) ($total / $limit) * $limit > $offset ? (int) ($total / $limit) * $limit : null;
$ref = ['resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields'];
$params = array_intersect_key($this->param(), array_flip($ref));
$pretty = $this->param('pretty') ? JSON_PRETTY_PRINT : 0;
$fields = $this->param('fields', []);
foreach ((array) $fields as $resource => $list) {
    $fields[$resource] = array_flip(explode(',', $list));
}
$entry_fcn = function (\Aimeos\M_Shop\Product\Item\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $id = $item->get_id();
    $attributes = $item->to_array();
    $type = $item->get_resource_type();
    $params = ['resource' => $type, 'id' => $id];
    $basket_params = ['resource' => 'basket', 'id' => 'default', 'related' => 'product'];
    if (isset($fields[$type])) {
        $attributes = array_intersect_key($attributes, $fields[$type]);
    }
    $entry = ['id' => $id, 'type' => $type, 'links' => ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']], 'basket.product' => ['href' => $this->url($target, $cntl, $action, $basket_params, [], $config), 'allow' => ['POST']]], 'attributes' => $attributes];
    if ($type_item = $item->get_type_item()) {
        $entry['relationships'][$type . '.type']['data'][] = ['id' => $type_item->get_id(), 'type' => $type . '.type'];
    }
    foreach ($item->get_property_items() as $property_item) {
        $rtype = str_replace('/', '.', $property_item->get_resource_type());
        $entry['relationships'][$rtype]['data'][] = ['id' => $property_item->get_id(), 'type' => $rtype];
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
    foreach ($item->get_stock_items() as $stock_item) {
        if ($stock_item->is_available()) {
            $entry['relationships']['stock']['data'][] = ['id' => $stock_item->get_id(), 'type' => 'stock'];
        }
    }
    if ($site_item = $item->get_site_item()) {
        $entry['relationships']['locale.site']['data'][] = ['id' => $site_item->get_id(), 'type' => 'locale.site'];
    }
    return $entry;
};
$include_fcn = function (\Aimeos\M_Shop\Product\Item\Iface $item) use ($fields, $target, $cntl, $action, $config) {
    $result = [];
    foreach ($item->get_stock_items() as $id => $stock_item) {
        if ($stock_item->is_available()) {
            $params = ['resource' => 'stock', 'id' => $id];
            $entry = ['id' => $id, 'type' => 'stock'];
            $entry['attributes'] = $stock_item->to_array();
            if (isset($fields['stock'])) {
                $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['stock']);
            }
            $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]];
            $result['stock'][$id] = $entry;
        }
    }
    if ($site_item = $item->get_site_item()) {
        $params = ['resource' => 'locale.site', 'id' => $site_item->get_id()];
        $entry = ['id' => $site_item->get_id(), 'type' => 'locale.site'];
        $entry['attributes'] = $site_item->to_array();
        if (isset($fields['locale.site'])) {
            $entry['attributes'] = array_intersect_key($entry['attributes'], $fields['locale.site']);
        }
        $entry['links'] = ['self' => ['href' => $this->url($target, $cntl, $action, $params, [], $config), 'allow' => ['GET']]];
        $result['locale.site'][$site_item->get_id()] = $entry;
    }
    return $result;
};
?>
{
	"meta": {
		"total": <?php 
echo $total;
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
    $data = $included = [];
    $items = $this->get('items', map());
    if (is_map($items)) {
        foreach ($items as $item) {
            $data[] = $entry_fcn($item);
            $included = array_replace_recursive($included, $include_fcn($item));
        }
    } else {
        $data = $entry_fcn($items);
        $included = array_replace_recursive($included, $include_fcn($items));
    }
    ?>

		,"data": <?php 
    echo json_encode($data, $pretty);
    ?>

		,"included": <?php 
    echo map($this->jincluded($items, $fields))->replace($included)->flat(1)->to_json($pretty);
    ?>

	<?php 
}
?>

}
