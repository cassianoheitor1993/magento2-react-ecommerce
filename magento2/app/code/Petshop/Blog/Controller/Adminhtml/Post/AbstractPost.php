<?php
namespace Petshop\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;

abstract class AbstractPost extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Blog::posts';

    protected function releaseSessionLock(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }
    }
}
