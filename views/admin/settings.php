<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        <img src="<?php echo esc_url($this->pluginUrl); ?>assets/images/mosparo.svg" height="44" alt="<?php _e('mosparo', 'mosparo-integration'); ?>">
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <div>
        <h2>
            <?php _e('Connection', 'mosparo-integration'); ?>
            <a href="<?php echo esc_url($this->buildConfigPageUrl(['action' => 'mosparo-add-connection'])); ?>" class="page-title-action"><?php _e('Add connection', 'mosparo-integration'); ?></a>
        </h2>
        <?php
            // The action "mosparo-bulk-action" is required, because the admin file "wp-admin/network/edit.php"
            // redirects to the start page without an action value in the query ($_GET['action']).
            $action_url = $this->buildConfigPostUrl('mosparo-settings-bulk-actions', false);
        ?>
        <form method="post" action="<?php echo esc_url($action_url); ?>">
            <?php
                $connectionTable = new \MosparoIntegration\Admin\ConnectionListTable();
                $connectionTable->prepare_items();

                $connectionTable->display();
            ?>
        </form>
    </div>
    <br />
    <div>
        <h2><?php _e('Modules', 'mosparo-integration'); ?></h2>

        <form method="post" action="<?php echo esc_url($action_url); ?>">
            <?php
                $moduleTable = new \MosparoIntegration\Admin\ModuleListTable();
                $moduleTable->prepare_items();

                $moduleTable->display();
            ?>
        </form>
    </div>
</div>
