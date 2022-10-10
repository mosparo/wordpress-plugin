<?php

namespace MosparoIntegration\Admin;

use MosparoIntegration\Helper\AdminHelper;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\ModuleHelper;
use WP_List_Table;

class ModuleListTable extends WP_List_Table
{
    protected $pageName;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'module',
            'plural' => 'modules',
            'ajax' => false
        ]);

        $this->pageName = $_REQUEST['page'];

        add_action('admin_head', [$this, 'addToAdminHeader']);
    }

    function addToAdminHeader()
    {
        if ($this->pageName != 'mosparo-configuration') {
            return;
        }

        echo '<style type="text/css">';
        echo '.wp-list-table .column-moduleName { width: 40%; }';
        echo '.wp-list-table .column-description { width: 35%; }';
        echo '.wp-list-table .column-dependencies { width: 20%;}';
        echo '</style>';
    }

    public function no_items()
    {
        _e('No modules found', 'mosparo-integration');
    }

    protected function get_table_classes()
    {
        $classes = parent::get_table_classes();
        $classes[] = 'plugins';

        return $classes;
    }

    public function single_row($item)
    {
        $class = 'inactive';
        $configHelper = ConfigHelper::getInstance();
        if ($configHelper->isModuleActive($item->getKey())) {
            $class = 'active';
        }

        echo '<tr class="' . $class . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    protected function column_default($item, $columnName)
    {
        switch ($columnName) {
            case 'description':
                return $item->getDescription();
            case 'dependencies':
                $html = '';

                foreach ($item->getDependencies() as $dependency) {
                    $html .= '- <a href="' . $dependency['url'] . '" target="_blank">' . $dependency['name'] . '</a><br />';
                }

                return $html;
            default:
                return '';
        }
    }

    protected function get_sortable_columns()
    {
        return [
            'moduleName'  => ['moduleName', false],
        ];
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'moduleName' => __('Module name', 'mosparo-integration'),
            'description' => __('Description', 'mosparo-integration'),
            'dependencies' => __('Dependencies', 'mosparo-integration')
        ];
    }

    protected function column_moduleName($item)
    {
        $adminHelper = AdminHelper::getInstance();
        $configHelper = ConfigHelper::getInstance();
        if ($configHelper->isModuleActive($item->getKey())) {
            $url = wp_nonce_url($adminHelper->buildConfigPageUrl(['action' => 'disable', 'module' => $item->getKey()]), 'change-module');
            $actions = [
                'disable' => sprintf('<a href="%s">%s</a>', $url, __('Disable', 'mosparo-integration')),
            ];
        } else {
            $url = wp_nonce_url($adminHelper->buildConfigPageUrl(['action' => 'enable', 'module' => $item->getKey()]), 'change-module');
            $actions = [
                'enable' => sprintf('<a href="%s">%s</a>', $url, __('Enable', 'mosparo-integration')),
            ];
        }

        return sprintf('<strong>%1$s</strong> %2$s', $item->getName(), $this->row_actions($actions, true));
    }

    public function sortModules($itemA, $itemB)
    {
        $orderBy = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'moduleName';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        $valA = '';
        $valB = '';
        if ($orderBy === 'moduleName') {
            $valA = $itemA->getName();
            $valB = $itemB->getName();
        }

        $result = strcmp($valA, $valB);
        return ($order === 'asc') ? $result : -$result;
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $moduleHelper = ModuleHelper::getInstance();
        $modules = $moduleHelper->getModules();

        usort($modules, array($this, 'sortModules'));
        $this->items = $modules;
    }

    protected function get_bulk_actions()
    {
        return [
            'enable' => __('Enable', 'mosparo-integration'),
            'disable' => __('Disable', 'mosparo-integration')
        ];
    }

    protected function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="module[]" value="%s" />', $item->getKey()
        );
    }
}