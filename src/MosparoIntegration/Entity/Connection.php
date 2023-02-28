<?php

namespace MosparoIntegration\Entity;

class Connection
{
    protected $key;

    protected $name;

    protected $host;

    protected $uuid;

    protected $publicKey;

    protected $privateKey;

    protected $defaults = [];

    protected $verifySsl;

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function shouldVerifySsl()
    {
        return ($this->verifySsl);
    }

    public function setVerifySsl($verifySsl)
    {
        $this->verifySsl = $verifySsl;
    }

    public function isDefaultFor($default)
    {
        return in_array($default, $this->defaults);
    }

    public function getDefaults()
    {
        return $this->defaults;
    }

    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    public function removeDefault($needleDefault)
    {
        foreach ($this->defaults as $key => $default) {
            if ($needleDefault === $default) {
                unset($this->defaults[$key]);
            }
        }
    }
}