<?php

namespace MosparoIntegration\Module;

class ModuleSettings
{
    protected $settings = [];
    protected $settingsForm = [];

    public function __construct($fields, $form)
    {
        $this->settings = $fields;
        $this->settingsForm = $form;
    }

    public function getSettingsForm()
    {
        return $this->settingsForm;
    }

    public function getFields()
    {
        return $this->settings;
    }

    public function getFieldValue($key)
    {
        if (isset($this->settings[$key]['value'])) {
            return $this->settings[$key]['value'];
        }

        return null;
    }

    public function setSettings(array $fields)
    {
        $this->settings = $fields;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}

