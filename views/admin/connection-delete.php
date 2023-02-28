<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        &ndash;
        <?php echo sprintf(__('Delete connection "%s"', 'mosparo-integration'), $connection->getName()); ?>
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="mosparo_action" value="delete-connection" />
        <input type="hidden" name="connection" value="<?php echo esc_attr($connection->getKey()); ?>" />

        <div class="notice notice-warning">
            <p><?php echo sprintf(__('Are you sure you want to delete the connection "%s"?', 'mosparo-integration'), $connection->getName()); ?></p>
        </div>

        <p>
            <?php _e('If you delete the connection, you can only use it in a form once you add it again.', 'mosparo-integration'); ?>
        </p>

        <p>
            <?php
            wp_nonce_field('delete-connection', 'save-connection');
            submit_button(__('Delete connection'), 'primary', 'submit', false);
            ?>
        </p>
    </form>
</div>