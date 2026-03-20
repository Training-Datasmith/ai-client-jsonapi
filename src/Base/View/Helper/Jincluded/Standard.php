<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2026
 * @package MW
 * @subpackage View
 */
namespace Aimeos\Base\View\Helper\Jincluded;

/**
 * View helper class for generating "included" data used by JSON:API
 *
 * @package MW
 * @subpackage View
 */
class Standard extends \Aimeos\Base\View\Helper\Base implements Iface
{
    private array $map = [];
    /**
     * Returns the included data for the JSON:API response
     *
     * @param \Aimeos\MShop\Common\Item\Iface|\Aimeos\MShop\Common\Item\Iface[] $item Object or objects to generate the included data for
     * @param array $fields Associative list of resource types as keys and field names to output as values
     * @param array $fcn Associative list of resource types as keys and anonymous functions for generating the array entries as values
     * @return array List of entries to include in the JSON:API response
     */
    public function transform($item, array $fields, array $fcn = []): array
    {
        if (is_map($item) || is_array($item)) {
            foreach ($item as $entry) {
                $this->entry($entry, $fields, $fcn);
            }
        } else {
            $this->entry($item, $fields, $fcn);
        }
        return $this->map;
    }
    /**
     * Processes a single item to create the included data for the JSON:API response
     *
     * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
     * @param array $fields Associative list of resource types as keys and field names to output as values
     * @param array $fcn Associative list of resource types as keys and anonymous functions for generating the array entries as values
     */
    protected function entry(\Aimeos\M_Shop\Common\Item\Iface $item, array $fields, array $fcn = [])
    {
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Tree\Iface) {
            foreach ($item->get_children() as $cat_item) {
                if ($cat_item->is_available()) {
                    $this->map($cat_item, $fields, $fcn);
                }
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
            foreach ($item->get_address_items() as $addr_item) {
                $this->map($addr_item, $fields, $fcn);
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
            foreach ($item->get_list_items() as $list_item) {
                if ($ref_item = $list_item->get_ref_item()) {
                    $this->map($ref_item, $fields, $fcn);
                }
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
            foreach ($item->get_property_items() as $prop_item) {
                $this->map($prop_item, $fields, $fcn);
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Type_Ref\Iface && $type_item = $item->get_type_item()) {
            $this->map($type_item, $fields, $fcn);
        }
        if ($item instanceof \Aimeos\M_Shop\Product\Item\Iface) {
            foreach ($item->get_stock_items() as $stock_item) {
                $this->map($stock_item, $fields, $fcn);
            }
        }
    }
    /**
     * Populates the map class property with the included data for the JSON:API response
     *
     * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
     * @param array $fields Associative list of resource types as keys and field names to output as values
     * @param array $fcn Associative list of resource types as keys and anonymous functions for generating the array entries as values
     */
    protected function map(\Aimeos\M_Shop\Common\Item\Iface $item, array $fields, array $fcn = [])
    {
        $id = $item->get_id();
        $type = str_replace('/', '.', $item->get_resource_type());
        if (isset($this->map[$type][$id]) || !$item->is_available()) {
            return;
        }
        $attributes = $item->to_array();
        if (isset($fields[$type])) {
            $attributes = array_intersect_key($attributes, $fields[$type]);
        }
        $entry = ['id' => $id, 'type' => $type, 'attributes' => $attributes];
        if (isset($fcn[$type]) && $fcn[$type] instanceof \Closure) {
            $entry = $fcn[$type]($item, $entry);
        }
        $this->map[$type][$id] = $entry;
        // first content, avoid infinite loops
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Tree\Iface) {
            foreach ($item->get_children() as $child_item) {
                if ($child_item->is_available()) {
                    $rtype = str_replace('/', '.', $child_item->get_resource_type());
                    $rtype = ($pos = strrpos($rtype, '/')) !== false ? substr($rtype, $pos + 1) : $rtype;
                    $entry['relationships'][$rtype]['data'][] = ['id' => $child_item->get_id(), 'type' => $rtype];
                    $this->map($child_item, $fields, $fcn);
                }
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
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
                    $this->map($ref_item, $fields, $fcn);
                }
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
            foreach ($item->get_property_items() as $prop_item) {
                if ($prop_item->is_available()) {
                    $prop_id = $prop_item->get_id();
                    $rtype = str_replace('/', '.', $prop_item->get_resource_type());
                    $entry['relationships'][$rtype]['data'][] = ['id' => $prop_id, 'type' => $rtype];
                    $this->map($prop_item, $fields, $fcn);
                }
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Common\Item\Type_Ref\Iface) {
            if ($type_item = $item->get_type_item()) {
                $type_id = $type_item->get_id();
                $rtype = str_replace('/', '.', $type_item->get_resource_type());
                $entry['relationships'][$rtype]['data'][] = ['id' => $type_id, 'type' => $rtype];
                $this->map($type_item, $fields, $fcn);
            }
        }
        if ($item instanceof \Aimeos\M_Shop\Product\Item\Iface) {
            foreach ($item->get_stock_items() as $stock_item) {
                if ($stock_item->is_available()) {
                    $stock_id = $stock_item->get_id();
                    $rtype = str_replace('/', '.', $stock_item->get_resource_type());
                    $entry['relationships'][$rtype]['data'][] = ['id' => $stock_id, 'type' => $rtype];
                    $this->map($stock_item, $fields, $fcn);
                }
            }
        }
        $this->map[$type][$id] = $entry;
        // full content
    }
}