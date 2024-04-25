<?php

namespace MosparoIntegration\Admin;

use MosparoIntegration\Helper\AdminHelper;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\ModuleHelper;
use WP_List_Table;

class ConnectionListTable extends WP_List_Table
{
    protected $pageName;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'connection',
            'plural' => 'connections',
            'ajax' => false
        ]);

        $this->pageName = sanitize_key($_REQUEST['page']);
    }

    public function no_items()
    {
        $adminHelper = AdminHelper::getInstance();
        $adminHelper->displayHowToUseBox();
    }

    public function single_row($item)
    {
        echo '<tr>';
        $this->single_row_columns($item);
        echo '</tr>';
    }

    protected function column_default($item, $columnName)
    {
        switch ($columnName) {
            case 'host':
                return $item->getHost();
            case 'uuid':
                return $item->getUuid();
            case 'defaults':
                return $this->translateDefaults($item);
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
            'name' => __('Name', 'mosparo-integration'),
            'host' => __('mosparo Host', 'mosparo-integration'),
            'uuid' => __('mosparo UUID', 'mosparo-integration'),
            'defaults' => __('Default connection for', 'mosparo-integration'),
        ];
    }

    protected function column_name($item)
    {
        $adminHelper = AdminHelper::getInstance();
        $configHelper = ConfigHelper::getInstance();
        $actions = [];

        $isSameOrigin = $item->getOrigin() === ConfigHelper::ORIGIN_LOCAL || (is_network_admin() && $item->getOrigin() === ConfigHelper::ORIGIN_NETWORK);
        if ($isSameOrigin) {
            $editUrl = $adminHelper->buildConfigPageUrl(['action' => 'mosparo-edit-connection', 'connection' => $item->getKey()]);
            $actions['mosparo-edit-connection'] = sprintf('<a href="%s">%s</a>', $editUrl, __('Edit', 'mosparo-integration'));
        }

        $refreshCssCacheUrl = $adminHelper->buildConfigPostUrl(['action' => 'mosparo-refresh-css-cache', 'connection' => $item->getKey()]);
        $actions['mosparo-refresh-css-cache'] = sprintf('<a href="%s">%s</a>', $refreshCssCacheUrl, __('Refresh CSS cache', 'mosparo-integration'));

        if (!$configHelper->isConnectionDefaultConnectionFor($item, 'general') && $isSameOrigin) {
            $deleteUrl = $adminHelper->buildConfigPostUrl(['action' => 'mosparo-delete-connection', 'connection' => $item->getKey()]);
            $actions['mosparo-delete-connection'] = sprintf('<a href="%s">%s</a>', $deleteUrl, __('Delete', 'mosparo-integration'));
        }

        if ($item->getOrigin() === ConfigHelper::ORIGIN_WP_CONFIG) {
            $actions['network_active'] = __('Configured in wp-config.php', 'mosparo-integration');
        } else if (!is_network_admin() && $item->getOrigin() === ConfigHelper::ORIGIN_NETWORK) {
            $actions['network_active'] = __('Configured in network', 'mosparo-integration');
        }

        return sprintf('<strong>%1$s</strong> %2$s', $item->getName(), $this->row_actions($actions, true));
    }

    public function sortConnections($itemA, $itemB)
    {
        $orderBy = (!empty($_GET['orderby'])) ? sanitize_key($_GET['orderby']) : 'name';
        $order = (!empty($_GET['order'])) ? sanitize_key($_GET['order']) : 'asc';

        $valA = '';
        $valB = '';
        if ($orderBy === 'name') {
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

        $configHelper = ConfigHelper::getInstance();
        $connections = $configHelper->getConnections();

        usort($connections, array($this, 'sortConnections'));
        $this->items = $connections;
    }

    protected function get_bulk_actions()
    {
        return [
            'mosparo-refresh-css-cache' => __('Refresh CSS cache', 'mosparo-integration'),
        ];
    }

    protected function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="connection[]" value="%s" />', $item->getKey()
        );
    }

    protected function translateDefaults($item)
    {
        $configHelper = ConfigHelper::getInstance();
        $moduleHelper = ModuleHelper::getInstance();
        $strings = [];

        $defaultConnections = $configHelper->getDefaultConnections();

        // The general should always be the first default string
        if (isset($defaultConnections['general']) && $defaultConnections['general']->getKey() === $item->getKey()) {
            $strings[] = sprintf('<strong>%s</strong>', __('General', 'mosparo-integration'));
        }

        foreach ($defaultConnections as $moduleKey => $defaultConnection) {
            if ($defaultConnection->getKey() === $item->getKey() || $item->isDefaultFor($moduleKey)) {
                $moduleKey = substr($moduleKey, 7);
                $module = $moduleHelper->getActiveModule($moduleKey);

                if ($module === null) {
                    continue;
                }

                if ($defaultConnection->getKey() !== $item->getKey() || $item->isDefaultFor($moduleKey)) {
                    $strings[] = sprintf(
                        '<s>%s</s> <i>(%s)</i>',
                        $module->getName(),
                        sprintf(__('Overwritten by &laquo;%s&raquo;', 'mosparo-integration'), $defaultConnection->getName())
                    );
                } else {
                    $strings[] = $module->getName();
                }
            }
        }

        return implode(', ', $strings);
    }
}
