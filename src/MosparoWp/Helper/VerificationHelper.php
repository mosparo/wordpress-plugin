<?php

namespace MosparoWp\Helper;

use Mosparo\ApiClient\Client;
use Mosparo\ApiClient\Exception;
use Mosparo\ApiClient\VerificationResult;

class VerificationHelper
{
    private static $instance;

    /**
     * @var \Mosparo\ApiClient\Exception;
     */
    protected $lastException;

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

    public function verifySubmission($submitToken, $validationToken, $formData): ?VerificationResult
    {
        $configHelper = ConfigHelper::getInstance();

        $client = new Client(
            $configHelper->getHost(),
            $configHelper->getPublicKey(),
            $configHelper->getPrivateKey(),
            [
                'verify' => $configHelper->getVerifySsl()
            ]
        );

        try {
            $result = $client->validateSubmission($formData, $submitToken, $validationToken);
        } catch (Exception $e) {
            $this->lastException = $e;
            $result = null;
        }

        return $result;
    }
}