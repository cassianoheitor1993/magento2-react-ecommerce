<?php
namespace LeisPet\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Post extends Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_post';
        $this->_blockGroup = 'LeisPet_Blog';
        $this->_headerText = __('LeisPet Blog Posts');
        $this->_addButtonLabel = __('Add New Post');
        parent::_construct();
    }
}
