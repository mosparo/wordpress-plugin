<?php

namespace MosparoWp\Module\Comments;

use MosparoWp\Module\AbstractModule;

class CommentsModule extends AbstractModule
{
    protected $key = 'comments';

    public function __construct()
    {
        $this->name = __('Comments', 'mosparo-wp');
        $this->description = __('Protects the comments form with mosparo.', 'mosparo-wp');
        $this->dependencies = [];
    }

    public function initializeModule()
    {
        /*if (!function_exists('wpcf7_add_form_tag')) {
            return;
        }
        
        $mosparoWpCf7Field = MosparoWpField::getInstance();
        $mosparoWpCf7Field->registerHooks();*/
    }
}