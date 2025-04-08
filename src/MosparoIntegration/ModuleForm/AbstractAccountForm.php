<?php

namespace MosparoIntegration\ModuleForm;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Module\AbstractModule;

/**
 * Abstract account form for the WordPress and WooCommerce account forms.
 */
abstract class AbstractAccountForm
{
    protected AbstractModule $module;

    public function __construct(AbstractModule $module)
    {
        $this->module = $module;
    }

    /**
     * Checks that woocommerce-specific nonce values are present in POST request
     * Nonce checks have already been done at this point, only checks presence of token
     *
     * @var string $nonce
     * @return bool
     */
    public function isWoocommerceRequest($nonce) {
        $iswoo = false;

        if (class_exists('WooCommerce') && isset($_REQUEST[$nonce])) {
            $iswoo = true;
        }
        return $iswoo;
    }

    /**
     * WordPress or WooCommerce mutual exclusion for same hooks
     *
     * @param string $woocommerceNonce
     * @return bool
     */
    public function canProcessRequest($woocommerceNonce)
    {
        if ($this->isWoocommerceRequest($woocommerceNonce)) {
            return ($this->module->getKey() == 'woocommerceaccount');
        } else {
            return ($this->module->getKey() == 'account');
        }
    }

    public function displayMosparoField()
    {
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField($connection, [
            // We have to ignore the password fields by name because showing the password will change the type to 'text'
            // and the password is then sent to mosparo but will not be processed by the verification code, which results
            // in a wrong spam detection.
            'inputFieldSelector' => '[name]:not(.mosparo__ignored-field):not([name="pwd"]):not([name="password"])',
        ]);
    }
}
