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

        $this->pageName = sanitize_key($_REQUEST['page']);
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

        echo '<tr class="' . esc_attr($class) . '">';
        $this->single_row_columns($item);
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
            'module_name'  => ['module_name', false],
        ];
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'module_name' => __('Module name', 'mosparo-integration'),
            'description' => __('Description', 'mosparo-integration'),
            'dependencies' => __('Dependencies', 'mosparo-integration')
        ];
    }

    protected function column_module_name($item)
    {
        $adminHelper = AdminHelper::getInstance();
        $configHelper = ConfigHelper::getInstance();

        $actions = [];
        if ($configHelper->isModuleActive($item->getKey())) {
            // The user should be able to edit the module settings on both levels (network admin and website admin).
            if ($item->getSettings()) {
                $url = $adminHelper->buildConfigPageUrl(['action' => 'mosparo-module-settings', 'module' => $item->getKey()]);
                $actions['mosparo-module-settings'] = sprintf('<a href="%s">%s</a>', $url, __('Settings', 'mosparo-integration'));
            }

            if ($configHelper->getOriginOfModuleActivation($item->getKey()) === ConfigHelper::ORIGIN_LOCAL) {
                $url = $adminHelper->buildConfigPostUrl(['action' => 'mosparo-disable-module', 'module' => $item->getKey()]);
                $actions['mosparo-disable-module'] = sprintf('<a href="%s">%s</a>', $url, __('Disable', 'mosparo-integration'));
            } else {
                $actions['network_active'] = __('Enabled in network', 'mosparo-integration');
            }
        } else {
            if (!$item->canInitialize()) {
                $actions['missing_dependencies'] = __('Missing required plugin', 'mosparo-integration');
            } else {
                $url = $adminHelper->buildConfigPosturl(['action' => 'mosparo-enable-module', 'module' => $item->getKey()]);
                $actions['mosparo-enable-module'] = sprintf('<a href="%s">%s</a>', $url, __('Enable', 'mosparo-integration'));
            }
        }
        return sprintf('<strong>%1$s</strong> %2$s', $item->getName(), $this->row_actions($actions, true));
    }

    public function sortModules($itemA, $itemB)
    {
        $orderBy = (!empty($_GET['orderby'])) ? sanitize_key($_GET['orderby']) : 'module_name';
        $order = (!empty($_GET['order'])) ? sanitize_key($_GET['order']) : 'asc';

        $valA = '';
        $valB = '';
        if ($orderBy === 'module_name') {
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
            'mosparo-enable-module' => __('Enable', 'mosparo-integration'),
            'mosparo-disable-module' => __('Disable', 'mosparo-integration')
        ];
    }

    protected function column_cb($item)
    {
        $configHelper = ConfigHelper::getInstance();

        $disabled = '';
        if (
            !$item->canInitialize() ||
            ($configHelper->isModuleActive($item->getKey()) && $configHelper->getOriginOfModuleActivation($item->getKey()) === ConfigHelper::ORIGIN_NETWORK)
        ) {
            $disabled = 'disabled';
        }

        return sprintf(
            '<input type="checkbox" name="module[]" value="%s" %s />', $item->getKey(), $disabled
        );
    }

    /**
     * Replace the IDs for the HTML elements in the table since WordPress originally only allows one table per page.
     *
     * @return void
     */
    public function display()
    {
        ob_start(function ($buffer) {
            return str_replace(
                [
                    '"bulk-action-',
                    '"doaction',
                    '\'cb\'',
                    '"cb-'
                ],
                [
                    '"modules_bulk-action-',
                    '"modules_doaction',
                    '\'modules_cb\'',
                    '"modules_cb-',
                ],
                $buffer
            );
        });

        parent::display();

        ob_end_flush();
    }
}
