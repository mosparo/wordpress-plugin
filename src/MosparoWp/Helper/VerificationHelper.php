<?php

namespace MosparoWp\Helper;

use Mosparo\ApiClient\Client;

class VerificationHelper
{
    private static $instance;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public function verifySubmission($submitToken, $validationToken, $formData): bool
    {
        $isValid = false;
        $configHelper = ConfigHelper::getInstance();

        $client = new Client(
            $configHelper->getHost(),
            $configHelper->getPublicKey(),
            $configHelper->getPrivateKey(),
            [
                'verify' => $configHelper->getVerifySsl()
            ]
        );

        $result = $client->validateSubmission($formData, $submitToken, $validationToken);

        return $result;
    }
}