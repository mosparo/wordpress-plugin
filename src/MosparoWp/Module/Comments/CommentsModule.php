<?php

namespace MosparoWp\Module\Comments;

use MosparoWp\Module\AbstractModule;
use MosparoWp\Module\Comments\CommentForm;

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
        $commentForm = CommentForm::getInstance();
        $commentForm->registerHooks();
    }
}