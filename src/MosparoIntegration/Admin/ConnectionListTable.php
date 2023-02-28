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

        add_action('admin_head', [$this, 'addToAdminHeader']);
    }

    function addToAdminHeader()
    {
        if ($this->pageName != 'mosparo-configuration') {
            return;
        }

        echo '<style type="text/css">';
        echo '.wp-list-table .column-connection_name { width: 25%; }';
        echo '.wp-list-table .column-connection_host { width: 35%; }';
        echo '.wp-list-table .column-connection_uuid { width: 20%;}';
        echo '.wp-list-table .column-connection_defaults { width: 20%;}';
        echo '</style>';
    }

    public function no_items()
    {
        _e('No connections found', 'mosparo-integration');
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
                return $this->translateDefaults($item->getDefaults());
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
            'defaults' => __('Default connections', 'mosparo-integration'),
        ];
    }

    protected function column_name($item)
    {
        $adminHelper = AdminHelper::getInstance();
        $editUrl = wp_nonce_url($adminHelper->buildConfigPageUrl(['action' => 'edit-connection', 'connection' => $item->getKey()]), 'edit-connection');
        $refreshCssCacheUrl = wp_nonce_url($adminHelper->buildConfigPageUrl(['action' => 'refresh-css-cache', 'connection' => $item->getKey()]), 'refresh-css-cache');

        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', $editUrl, __('Edit', 'mosparo-integration')),
            'refresh-css-cache' => sprintf('<a href="%s">%s</a>', $refreshCssCacheUrl, __('Refresh CSS cache', 'mosparo-integration')),
        ];

        if (!$item->isDefaultFor('general')) {
            $deleteUrl = $adminHelper->buildConfigPageUrl(['action' => 'delete-connection', 'connection' => $item->getKey()]);
            $actions['delete'] = sprintf('<a href="%s">%s</a>', $deleteUrl, __('Delete', 'mosparo-integration'));
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
            'refresh-css-cache' => __('Refresh CSS cache', 'mosparo-integration'),
        ];
    }

    protected function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="connection[]" value="%s" />', $item->getKey()
        );
    }

    protected function translateDefaults($defaults)
    {
        $moduleHelper = ModuleHelper::getInstance();
        $strings = [];

        // The general should always be the first default string
        if (in_array('general', $defaults)) {
            $strings[] = '<strong>' . __('General', 'mosparo-integration') . '</strong>';
        }

        foreach ($defaults as $default) {
            if (strpos($default, 'module_') === 0) {
                $moduleKey = substr($default, 7);
                $module = $moduleHelper->getActiveModule($moduleKey);

                if ($module === null) {
                    continue;
                }

                $strings[] = $module->getName();
            }
        }

        return implode(', ', $strings);
    }
}