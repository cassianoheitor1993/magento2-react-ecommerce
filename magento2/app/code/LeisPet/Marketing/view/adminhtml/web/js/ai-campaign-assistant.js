define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    function parseConfig($element) {
        const raw = $element.attr('data-config');
        if (!raw) {
            return {};
        }

        try {
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
    }

    function maybeHandleAjaxExpired(responseLike) {
        if (responseLike && Number(responseLike.ajaxExpired || 0) === 1 && responseLike.ajaxRedirect) {
            window.location.href = responseLike.ajaxRedirect;
            return true;
        }

        return false;
    }

    function getFieldElement(fieldName) {
        const byId = $('#' + fieldName);
        if (byId.length) {
            return byId;
        }

        const byExactName = $('[name="' + fieldName + '"]');
        if (byExactName.length) {
            return byExactName.first();
        }

        const byScopedName = $('[name$="[' + fieldName + ']"]');
        if (byScopedName.length) {
            return byScopedName.first();
        }

        return $();
    }

    function getFieldValue(fieldName) {
        const $field = getFieldElement(fieldName);
        return $field.length ? ($field.val() || '') : '';
    }

    function setFieldValue(fieldName, value) {
        const $field = getFieldElement(fieldName);
        if (!$field.length) {
            return;
        }

        $field.val(value || '').trigger('change');
    }

    function getEditorContext() {
        return {
            name: getFieldValue('name'),
            subject: getFieldValue('subject'),
            sender_name: getFieldValue('sender_name'),
            sender_email: getFieldValue('sender_email'),
            template_identifier: getFieldValue('template_identifier'),
            audience_type: getFieldValue('audience_type'),
            audience_filter_json: getFieldValue('audience_filter_json')
        };
    }

    function applySuggestion(item) {
        setFieldValue('name', item.campaign_name || '');
        setFieldValue('subject', item.subject || '');
        setFieldValue('audience_type', item.audience_type || 'newsletter');

        if (item.template_identifier) {
            setFieldValue('template_identifier', item.template_identifier);
        }

        if (item.sender_name) {
            setFieldValue('sender_name', item.sender_name);
        }

        if (item.sender_email) {
            setFieldValue('sender_email', item.sender_email);
        }

        const audienceFilter = item.audience_filter_json || {};
        const filterText = JSON.stringify(audienceFilter, null, 2);
        setFieldValue('audience_filter_json', filterText);
    }

    return function init(config, element) {
        const $root = $(element);
        const runtime = parseConfig($root);
        const $status = $('#leispet-ai-campaign-status');
        const $results = $('#leispet-ai-campaign-results');
        const $button = $('#leispet-ai-load-suggestions');

        if (!runtime.enabled) {
            $button.prop('disabled', true);
            $status.text($t('AI integration is disabled in configuration.')).show();
            return;
        }

        const setStatus = function (message) {
            $status.text(message || '').show();
        };

        const renderSuggestions = function (suggestions, responseMeta) {
            $results.empty();

            if (!Array.isArray(suggestions) || !suggestions.length) {
                setStatus($t('No suggestions were returned.'));
                return;
            }

            suggestions.forEach(function (item, index) {
                const title = item.campaign_name || $t('Suggestion') + ' #' + (index + 1);
                const reason = item.reason || '';

                const $card = $('<div/>', { class: 'leispet-ai-campaign-card' });
                $('<div/>', { class: 'leispet-ai-campaign-card-title', text: title }).appendTo($card);
                $('<div/>', { text: $t('Subject: ') + (item.subject || '') }).appendTo($card);
                $('<div/>', { text: $t('Audience: ') + (item.audience_type || 'newsletter') }).appendTo($card);
                if (item.template_identifier) {
                    $('<div/>', { text: $t('Template: ') + item.template_identifier }).appendTo($card);
                }
                if (reason) {
                    $('<div/>', { class: 'leispet-ai-campaign-card-reason', text: reason }).appendTo($card);
                }

                $('<button/>', {
                    type: 'button',
                    class: 'action-secondary',
                    text: $t('Apply Suggestion')
                }).on('click', function () {
                    applySuggestion(item);
                    setStatus($t('Suggestion applied to form fields.'));
                }).appendTo($card);

                $results.append($card);
            });

            const source = responseMeta && responseMeta.source ? responseMeta.source : 'ai';
            if (source === 'fallback') {
                const reason = responseMeta && responseMeta.fallback_reason ? responseMeta.fallback_reason : '';
                if (reason) {
                    setStatus($t('Loaded fallback suggestions. Reason: ') + reason);
                } else {
                    setStatus($t('Loaded fallback suggestions.'));
                }
                return;
            }

            setStatus($t('Loaded 5 AI campaign suggestions.'));
        };

        $button.on('click', function () {
            $button.prop('disabled', true);
            setStatus($t('Generating suggestions...'));

            $.ajax({
                url: runtime.urls.suggestions,
                method: 'POST',
                dataType: 'json',
                data: {
                    form_key: runtime.formKey,
                    editor_context: getEditorContext()
                }
            }).done(function (response) {
                if (maybeHandleAjaxExpired(response)) {
                    return;
                }

                if (!response || !response.success) {
                    setStatus((response && response.message) ? response.message : $t('Unable to get suggestions.'));
                    return;
                }

                renderSuggestions(response.suggestions || [], response);
            }).fail(function (xhr) {
                const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                if (maybeHandleAjaxExpired(response)) {
                    return;
                }

                setStatus($t('Failed to load AI suggestions.'));
            }).always(function () {
                $button.prop('disabled', false);
            });
        });
    };
});
