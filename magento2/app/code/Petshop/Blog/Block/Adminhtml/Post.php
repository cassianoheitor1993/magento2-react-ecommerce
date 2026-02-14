<?php
namespace Petshop\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Post extends Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_post';
        $this->_blockGroup = 'Petshop_Blog';
        $this->_headerText = __('Petshop Blog Posts');
        $this->_addButtonLabel = __('Add New Post');
        parent::_construct();
    }
}
