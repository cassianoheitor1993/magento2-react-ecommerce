<?php
namespace Petshop\Blog\Block\Adminhtml\Post;

use Petshop\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magento\Backend\Block\Widget\Grid\Extended;

class Grid extends Extended
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        private readonly CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('petshop_blog_post_grid');
        $this->setDefaultSort('post_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $this->setCollection($this->collectionFactory->create());
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('post_id', ['header' => __('ID'), 'index' => 'post_id']);
        $this->addColumn('title', ['header' => __('Title'), 'index' => 'title']);
        $this->addColumn('slug', ['header' => __('Slug'), 'index' => 'slug']);
        $this->addColumn('author', ['header' => __('Author'), 'index' => 'author']);
        $this->addColumn('is_published', [
            'header' => __('Published'),
            'index' => 'is_published',
            'type' => 'options',
            'options' => [0 => __('No'), 1 => __('Yes')]
        ]);
        $this->addColumn('created_at', ['header' => __('Created At'), 'index' => 'created_at']);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['post_id' => $row->getId()]);
    }
}
