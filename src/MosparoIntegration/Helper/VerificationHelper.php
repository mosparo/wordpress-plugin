<?php

namespace MosparoIntegration\Helper;

use MosparoDependencies\Mosparo\ApiClient\Client;
use MosparoDependencies\Mosparo\ApiClient\Exception;
use MosparoDependencies\Mosparo\ApiClient\VerificationResult;
use MosparoIntegration\Entity\Connection;

class VerificationHelper
{
    private static $instance;

    /**
     * @var \MosparoDependencies\Mosparo\ApiClient\Exception;
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

    public function verifySubmission(Connection $connection, $submitToken, $validationToken, $formData): ?VerificationResult
    {
        $client = new Client(
            $connection->getHost(),
            $connection->getPublicKey(),
            $connection->getPrivateKey(),
            [
                'verify' => ($connection->shouldVerifySsl())
            ]
        );

        try {
            $result = $client->verifySubmission($formData, $submitToken, $validationToken);
        } catch (Exception $e) {
            $this->lastException = $e;
            $result = null;
        }

        return $result;
    }

    public function getLastException()
    {
        return $this->lastException;
    }
}