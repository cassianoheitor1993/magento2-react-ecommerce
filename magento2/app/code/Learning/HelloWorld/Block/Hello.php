<?php
namespace Learning\HelloWorld\Block;

use Magento\Framework\View\Element\Template;

class Hello extends Template
{
    public function getGreeting(): string
    {
        return 'Hello from Magento 2! 🎉';
    }
}
