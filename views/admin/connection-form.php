<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        &ndash;
        <?php if ($action === 'add-connection'): ?>
            <?php _e('Add connection', 'mosparo-integration'); ?>
        <?php else: ?>
            <?php echo sprintf(__('Edit connection "%s"', 'mosparo-integration'), $connection->getName()); ?>
        <?php endif; ?>
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <div>

            <?php
                $configHelper = \MosparoIntegration\Helper\ConfigHelper::getInstance();
                $moduleHelper = \MosparoIntegration\Helper\ModuleHelper::getInstance();

                $key = $connection->getKey();
                $name = $connection->getName();
                $host = $connection->getHost();
                $uuid = $connection->getUuid();
                $publicKey = $connection->getPublicKey();
                $verifySsl = $connection->shouldVerifySsl();
            ?>
            <input type="hidden" name="mosparo_action" value="<?php echo esc_attr($action); ?>" />
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th>
                            <label for="key"><?php _e('Key', 'mosparo-integration'); ?></label>
                        </th>
                        <td>
                            <input name="key" type="text" id="key" value="<?php echo esc_attr($key); ?>" class="regular-text code" <?php if ($action === 'edit-connection'): ?>readonly<?php endif; ?>>
                            <p class="description">
                                <?php _e('The key is an internal identifier and can only contain lowercase alphanumeric characters, dashes, and underscores.', 'mosparo-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="name"><?php _e('Name', 'mosparo-integration'); ?></label>
                        </th>
                        <td>
                            <input name="name" type="text" id="name" value="<?php echo esc_attr($name); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="host"><?php _e('Host', 'mosparo-integration'); ?></label>
                        </th>
                        <td>
                            <input name="host" type="url" id="host" value="<?php echo esc_url($host); ?>" class="regular-text code" placeholder="https://" required>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="uuid"><?php _e('Unique identification number', 'mosparo-integration'); ?></label>
                        </th>
                        <td>
                            <input name="uuid" type="text" id="uuid" value="<?php echo esc_attr($uuid); ?>" class="regular-text code" required>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="publicKey"><?php _e('Public Key', 'mosparo-integration'); ?></label>
                        </th>
                        <td>
                            <input name="publicKey" type="text" id="publicKey" value="<?php echo esc_attr($publicKey); ?>" class="regular-text code" required>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="privateKey"><?php _e('Private Key', 'mosparo-integration'); ?></label>
                        </th>
                        <td>
                            <input name="privateKey" type="text" id="privateKey" value="" class="regular-text code" <?php echo ($action === 'add-connection') ? 'required' : ''; ?>>
                            <p class="description">
                                <?php _e('Please leave this field empty if you don\'t want to change the private key.', 'mosparo-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label for="verifySsl">
                                <input name="verifySsl" type="checkbox" id="verifySsl" value="1" <?php echo $verifySsl ? 'checked' : ''; ?>>
                                <?php _e('Verify SSL certificate', 'mosparo-integration'); ?>
                            </label>
                        </td>
                    </tr>
                    <?php
                        $isGeneral = false;
                        if ($connection->isDefaultFor('general') || $configHelper->getConnectionFor('general') === false) {
                            $isGeneral = true;
                        }
                    ?>
                    <tr>
                        <th><?php _e('Default', 'mosparo-integration'); ?></th>
                        <td>
                            <label for="defaultGeneral">
                                <input name="defaults[]" type="checkbox" id="defaultGeneral" value="general" <?php echo $isGeneral ? 'checked disabled' : ''; ?>>
                                <?php _e('Default connection', 'mosparo-integration'); ?>
                            </label>
                            <?php if ($isGeneral): ?>
                                <p class="description">
                                    <?php _e('You cannot unset the default connection. Please configure a new connection and mark it as the default connection.', 'mosparo-integration'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach ($moduleHelper->getActiveModules() as $module): ?>
                        <tr>
                            <th><?php echo $module->getName(); ?></th>
                            <td>
                                <label for="default_<?php echo $module->getDefaultKey(); ?>">
                                    <input name="defaults[]" type="checkbox" id="default_<?php echo $module->getDefaultKey(); ?>" value="<?php echo $module->getDefaultKey(); ?>" <?php echo $connection->isDefaultFor($module->getDefaultKey()) ? 'checked' : ''; ?>>
                                    <?php echo sprintf(__('Default connection for %s', 'mosparo-integration'), $module->getName()); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p>
            <?php
                wp_nonce_field($action, 'save-connection');
                submit_button(__('Save connection'), 'primary', 'submit', false);
            ?>
        </p>
    </form>
</div>