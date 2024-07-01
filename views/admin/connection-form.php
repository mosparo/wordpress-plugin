<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        &ndash;
        <?php if ($action === 'mosparo-add-connection'): ?>
            <?php _e('Add connection', 'mosparo-integration'); ?>
        <?php else: ?>
            <?php echo sprintf(__('Edit connection "%s"', 'mosparo-integration'), $connection->getName()); ?>
        <?php endif; ?>
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <div class="mosparo-two-columns">
        <div class="left-column">
            <form method="post" action="<?php echo esc_url($this->buildConfigPostUrl($action)); ?>">
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
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="key"><?php _e('Key', 'mosparo-integration'); ?></label>
                                </th>
                                <td>
                                    <input name="key" type="text" id="key" value="<?php echo esc_attr($key); ?>" class="regular-text code" <?php if ($action === 'mosparo-edit-connection'): ?>readonly<?php endif; ?>>
                                    <p class="description">
                                        <?php _e('The key is an internal identifier and can only contain lowercase alphanumeric characters, dashes, and underscores.', 'mosparo-integration'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="name"><?php _e('Name', 'mosparo-integration'); ?></label>
                                </th>
                                <td>
                                    <input name="name" type="text" id="name" value="<?php echo esc_attr($name); ?>" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="host"><?php _e('Host', 'mosparo-integration'); ?></label>
                                </th>
                                <td>
                                    <input name="host" type="url" id="host" value="<?php echo esc_url($host); ?>" class="regular-text code" placeholder="https://" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="uuid"><?php _e('Unique identification number', 'mosparo-integration'); ?></label>
                                </th>
                                <td>
                                    <input name="uuid" type="text" id="uuid" value="<?php echo esc_attr($uuid); ?>" class="regular-text code" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="publicKey"><?php _e('Public Key', 'mosparo-integration'); ?></label>
                                </th>
                                <td>
                                    <input name="publicKey" type="text" id="publicKey" value="<?php echo esc_attr($publicKey); ?>" class="regular-text code" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="privateKey"><?php _e('Private Key', 'mosparo-integration'); ?></label>
                                </th>
                                <td>
                                    <input name="privateKey" type="text" id="privateKey" value="" class="regular-text code" <?php echo ($action === 'mosparo-add-connection') ? 'required' : ''; ?>>
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
                            <tr>
                                <th></th>
                                <td>
                                    <div class="notice notice-info inline">
                                        <p><?php _e('If you\'re using a plugin to minimize or optimize your website\'s JavaScript code, please check if you need to add an exception for the mosparo JavaScript file.', 'mosparo-integration'); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                $isGeneral = false;
                                $isGeneralEditable = true;
                                $defaultGeneralConnection = $configHelper->getConnectionFor('general');

                                if ($defaultGeneralConnection !== false && $defaultGeneralConnection->getOrigin() === \MosparoIntegration\Helper\ConfigHelper::ORIGIN_WP_CONFIG) {
                                    $isGeneralEditable = false;
                                } else if ($connection->isDefaultFor('general') || $defaultGeneralConnection === false) {
                                    $isGeneral = true;
                                }
                            ?>
                            <tr>
                                <th scope="row"><?php _e('General', 'mosparo-integration'); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><span><?php _e('General', 'mosparo-integration'); ?></span></legend>
                                        <label for="defaultGeneral">
                                            <input name="defaults[]" type="checkbox" id="defaultGeneral" value="general" <?php echo $isGeneral ? 'checked disabled' : ''; ?> <?php echo !$isGeneralEditable ? 'disabled' : ''; ?>>
                                            <?php _e('Default connection', 'mosparo-integration'); ?>
                                        </label>
                                    </fieldset>
                                    <?php if ($isGeneral): ?>
                                        <p class="description">
                                            <?php _e('You cannot unset the default connection. Please configure a new connection and mark it as the default connection.', 'mosparo-integration'); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!$isGeneralEditable): ?>
                                        <p class="description">
                                            <?php _e('You cannot change the default connection since the connection defined in the wp-config.php file is always the default connection.', 'mosparo-integration'); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php foreach ($moduleHelper->getActiveModules() as $module): ?>
                                <tr>
                                    <th scope="row"><?php echo $module->getName(); ?></th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span><?php echo $module->getName(); ?></span></legend>
                                            <label for="default_<?php echo $module->getDefaultKey(); ?>">
                                                <input name="defaults[]" type="checkbox" id="default_<?php echo $module->getDefaultKey(); ?>" value="<?php echo $module->getDefaultKey(); ?>" <?php echo $connection->isDefaultFor($module->getDefaultKey()) ? 'checked' : ''; ?>>
                                                <?php echo sprintf(__('Default connection for %s', 'mosparo-integration'), $module->getName()); ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p>
                    <?php
                        submit_button(__('Save connection'), 'primary', 'submit', false);
                    ?>
                </p>
            </form>
        </div>
        <div class="right-column">
            <?php $this->displayHowToUseBox(false); ?>
        </div>
    </div>
</div>
