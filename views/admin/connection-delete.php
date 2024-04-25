<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        &ndash;
        <?php echo sprintf(__('Delete connection "%s"', 'mosparo-integration'), $connection->getName()); ?>
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <form method="post" action="<?php echo esc_url($this->buildConfigPostUrl($action)); ?>">
        <input type="hidden" name="connection" value="<?php echo esc_attr($connection->getKey()); ?>" />

        <div class="notice notice-warning">
            <p><?php echo sprintf(__('Are you sure you want to delete the connection "%s"?', 'mosparo-integration'), $connection->getName()); ?></p>
        </div>

        <p>
            <?php _e('If you delete the connection, you can no longer use the connection for mosparo in your forms.', 'mosparo-integration'); ?>
        </p>

        <p>
            <?php
            submit_button(__('Delete connection', 'mosparo-integration'), 'primary', 'submit', false);
            ?>
        </p>
    </form>
</div>
