<?php

declare(strict_types=1);

namespace Petshop\Marketing\Block\Adminhtml\Campaign\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Save Campaign'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'button' => [
                        'event' => 'save'
                    ]
                ],
                'form-role' => 'save'
            ],
            'sort_order' => 20
        ];
    }
}
