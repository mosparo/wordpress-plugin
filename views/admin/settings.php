<div class="wrap">

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php $this->displayAdminNotice(); ?>

    <form method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
        <div>
            <h2><?php _e('Connection', 'mosparo-wp'); ?></h2>

            <?php
                $configHelper = \MosparoWp\Helper\ConfigHelper::getInstance();

                $host = $configHelper->isActive() ? $configHelper->getHost() : 'https://';
                $publicKey = $configHelper->isActive() ? $configHelper->getPublicKey() : '';
                $privateKey = $configHelper->isActive() ? $configHelper->getPrivateKey() : '';
                $verifySsl = $configHelper->getVerifySsl();
                $loadResourcesAlways = $configHelper->getLoadResourcesAlways();
            ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th>
                            <label for="host"><?php _e('Host', 'mosparo-wp'); ?></label>
                        </th>
                        <td>
                            <input name="host" type="url" id="host" value="<?php echo $host; ?>" class="regular-text code" <?php if ($configHelper->isActive()): ?>readonly<?php endif; ?>>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="publicKey"><?php _e('Public Key', 'mosparo-wp'); ?></label>
                        </th>
                        <td>
                            <input name="publicKey" type="text" id="publicKey" value="<?php echo $publicKey; ?>" class="regular-text code" <?php if ($configHelper->isActive()): ?>readonly<?php endif; ?>>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="privateKey"><?php _e('Private Key', 'mosparo-wp'); ?></label>
                        </th>
                        <td>
                            <input name="privateKey" type="text" id="privateKey" value="<?php echo $this->getMaskedPrivateKey(); ?>" class="regular-text code" <?php if ($configHelper->isActive()): ?>readonly<?php endif; ?>>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label for="verifySsl">
                                <input name="verifySsl" type="checkbox" id="verifySsl" value="1" <?php echo $verifySsl ? 'checked' : ''; ?>>
                                <?php _e('Verify SSL certificate', 'mosparo-wp'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label for="loadResourcesAlways">
                                <input name="loadResourcesAlways" type="checkbox" id="loadResourcesAlways" value="1" <?php echo $loadResourcesAlways ? 'checked' : ''; ?>>
                                <?php _e('Load the mosparo resources on all pages.', 'mosparo-wp'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>
            <?php
                wp_nonce_field('mosparo-settings-form', 'save-settings');
                submit_button(null, 'primary', 'submit', false);

                if ($configHelper->isActive()):
            ?>
                <a href="<?php echo $this->buildConfigPageUrl(['action' => 'reset']); ?>" class="button-secondary">
                    <?php _e('Reset connection', 'mosparo-wp'); ?>
                </a>
            <?php endif; ?>
        </p>
    </form>
    <br />
    <div>
        <h2><?php _e('Modules', 'mosparo-wp'); ?></h2>

        <form method="post">
            <input type="hidden" name="page" value="">
            <?php
                $moduleTable = new \MosparoWp\Admin\ModuleListTable();
                $moduleTable->prepare_items();

                $moduleTable->display();
            ?>
        </form>
    </div>
</div>