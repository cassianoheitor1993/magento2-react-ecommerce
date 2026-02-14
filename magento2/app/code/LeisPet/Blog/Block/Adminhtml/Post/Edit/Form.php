<?php
namespace LeisPet\Blog\Block\Adminhtml\Post\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use LeisPet\Blog\Model\Config as BlogConfig;

class Form extends Generic
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $registry,
        FormFactory $formFactory,
        private readonly WysiwygConfig $wysiwygConfig,
        private readonly BlogConfig $blogConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $post = $this->_coreRegistry->registry('leispet_blog_post');

        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', ['post_id' => $this->getRequest()->getParam('post_id')]),
                'method' => 'post'
            ]
        ]);

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Post Details')]);

        if ($this->blogConfig->isAiEnabled()) {
            $fieldset->addField('ai_note', 'note', [
                'text' => __('Use <strong>Create with AI</strong> for full generation or the wand buttons to regenerate specific fields.'),
            ]);
        }

        if ($post && $post->getId()) {
            $fieldset->addField('post_id', 'hidden', ['name' => 'post_id']);
        }

        $fieldset->addField('title', 'text', [
            'name' => 'title',
            'label' => __('Title'),
            'required' => true,
            'after_element_html' => $this->renderAiFieldActions('title')
        ]);

        $fieldset->addField('slug', 'text', [
            'name' => 'slug',
            'label' => __('Slug'),
            'note' => __('Leave empty to auto-generate from title.')
        ]);

        $fieldset->addField('author', 'text', [
            'name' => 'author',
            'label' => __('Author'),
            'after_element_html' => $this->renderAiFieldActions('author')
        ]);

        $fieldset->addField('tags', 'text', [
            'name' => 'tags',
            'label' => __('Tags'),
            'note' => __('Comma-separated tags, e.g. dogs,nutrition,promos'),
            'after_element_html' => $this->renderAiFieldActions('tags')
        ]);

        $fieldset->addField('is_published', 'select', [
            'name' => 'is_published',
            'label' => __('Published'),
            'values' => [
                ['value' => 1, 'label' => __('Yes')],
                ['value' => 0, 'label' => __('No')]
            ]
        ]);

        $fieldset->addField('excerpt', 'textarea', [
            'name' => 'excerpt',
            'label' => __('Excerpt'),
            'style' => 'height:100px;',
            'after_element_html' => $this->renderAiFieldActions('excerpt')
        ]);

        $fieldset->addField('content', 'editor', [
            'name' => 'content',
            'label' => __('Content'),
            'title' => __('Content'),
            'required' => true,
            'style' => 'height:20em;',
            'config' => $this->wysiwygConfig->getConfig([
                'add_variables' => false,
                'add_widgets' => false,
                'add_images' => true,
                'files_browser_window_url' => $this->getUrl('cms/wysiwyg_images/index')
            ]),
            'after_element_html' => $this->renderAiFieldActions('content')
        ]);

        if ($post) {
            $form->setValues($post->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function renderAiFieldActions(string $field): string
    {
        if (!$this->blogConfig->isAiEnabled()) {
            return '';
        }

        $buttonLabel = (string) __('Regenerate with AI');
        $badgeLabel = (string) __('AI generated');

        return sprintf(
            '<button type="button" class="action-secondary leispet-ai-field-action" data-field="%1$s" title="%2$s">✨</button><span id="leispet-ai-badge-%1$s" class="leispet-ai-badge is-hidden">%3$s</span>',
            $field,
            htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8')
        );
    }
}
