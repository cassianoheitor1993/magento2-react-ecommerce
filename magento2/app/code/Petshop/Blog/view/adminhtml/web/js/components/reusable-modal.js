define(['jquery', 'Magento_Ui/js/modal/modal'], function ($, modal) {
    'use strict';

    return function createReusableModal(config) {
        const $container = $('<div class="petshop-reusable-modal"/>');
        const settings = Object.assign(
            {
                title: '',
                modalClass: 'petshop-reusable-modal',
                buttons: []
            },
            config || {}
        );

        modal(
            {
                type: 'popup',
                title: settings.title,
                modalClass: settings.modalClass,
                responsive: true,
                innerScroll: true,
                buttons: settings.buttons
            },
            $container
        );

        return {
            setTitle: function (title) {
                $container.modal('option', 'title', title || '');
            },
            setContent: function (html) {
                $container.html(html || '');
            },
            open: function () {
                $container.modal('openModal');
            },
            close: function () {
                $container.modal('closeModal');
            },
            element: $container
        };
    };
});
