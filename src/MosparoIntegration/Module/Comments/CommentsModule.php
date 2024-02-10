<?php

namespace MosparoIntegration\Module\Comments;

use MosparoIntegration\Module\AbstractModule;

class CommentsModule extends AbstractModule
{
    protected $key = 'comments';

    public function __construct()
    {
        $this->name = __('Comments', 'mosparo-integration');
        $this->description = __('Protects the comments form with mosparo. It is also compatible with WooCommerce reviews.', 'mosparo-integration');
        $this->dependencies = [];
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $commentForm = CommentForm::getInstance();
        $commentForm->registerHooks();
    }
}