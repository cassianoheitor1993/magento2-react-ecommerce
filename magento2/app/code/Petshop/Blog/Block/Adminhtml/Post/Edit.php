<?php
namespace Petshop\Blog\Block\Adminhtml\Post;

use Petshop\Blog\Model\Config;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    private Registry $coreRegistry;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        Registry $registry,
        private readonly Config $blogConfig,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'post_id';
        $this->_blockGroup = 'Petshop_Blog';
        $this->_controller = 'adminhtml_post';
        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Post'));
        $this->buttonList->update('delete', 'label', __('Delete Post'));
        $this->buttonList->add(
            'save_and_continue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']
                    ]
                ]
            ],
            -100
        );

        if ($this->blogConfig->isAiEnabled()) {
            $onclick = 'if (window.petshopBlogAiOpen) { window.petshopBlogAiOpen(); } return false;';

            $this->buttonList->add(
                'generate_with_ai',
                [
                    'label' => __('Create with AI'),
                    'class' => 'secondary',
                    'onclick' => $onclick
                ],
                -80
            );
        }
    }

    public function getHeaderText()
    {
        if ($this->coreRegistry->registry('petshop_blog_post')?->getId()) {
            return __('Edit Post: %1', $this->escapeHtml($this->coreRegistry->registry('petshop_blog_post')->getTitle()));
        }

        return __('New Blog Post');
    }
}
