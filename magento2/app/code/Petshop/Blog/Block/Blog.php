<?php
namespace Petshop\Blog\Block;

use Magento\Framework\View\Element\Template;
use Petshop\Blog\Model\ResourceModel\Post\CollectionFactory;

class Blog extends Template
{
    private CollectionFactory $postCollectionFactory;

    public function __construct(
        Template\Context $context,
        CollectionFactory $postCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->postCollectionFactory = $postCollectionFactory;
    }

    public function getPosts(){
        return $this->postCollectionFactory->create()
        ->setOrder('created_at', 'DESC');
    }
}