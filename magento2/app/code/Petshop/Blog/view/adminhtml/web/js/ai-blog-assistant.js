define([
    'jquery',
    'mage/translate',
    'Petshop_Blog/js/components/reusable-modal'
], function ($, $t, createReusableModal) {
    'use strict';

    const POLL_INTERVAL_MS = 2500;

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

    function getValue(field) {
        if (field === 'content' && window.tinyMCE && window.tinyMCE.get('content')) {
            return window.tinyMCE.get('content').getContent() || '';
        }

        const $input = $('#' + field);
        return $input.length ? ($input.val() || '') : '';
    }

    function setValue(field, value) {
        if (field === 'content' && window.tinyMCE && window.tinyMCE.get('content')) {
            window.tinyMCE.get('content').setContent(value || '');
            return;
        }

        const $input = $('#' + field);
        if ($input.length) {
            $input.val(value || '').trigger('change');
        }
    }

    function collectEditorContext() {
        return {
            title: getValue('title'),
            slug: getValue('slug'),
            author: getValue('author'),
            tags: getValue('tags'),
            excerpt: getValue('excerpt'),
            content: getValue('content')
        };
    }

    function ensurePostId(runtime, postId) {
        if (!postId) {
            return;
        }

        runtime.postId = postId;
        if (!$('#post_id').length) {
            $('<input/>', {
                type: 'hidden',
                id: 'post_id',
                name: 'post_id',
                value: postId
            }).appendTo('#edit_form');
        } else {
            $('#post_id').val(postId);
        }
    }

    function getJobStorageKey(runtime) {
        return 'petshop_blog_ai_job_' + String(runtime.postId || 'new');
    }

    function saveActiveJob(runtime, jobId) {
        if (window.localStorage && jobId) {
            window.localStorage.setItem(getJobStorageKey(runtime), String(jobId));
        }
    }

    function readActiveJob(runtime) {
        if (!window.localStorage) {
            return 0;
        }

        const value = window.localStorage.getItem(getJobStorageKey(runtime));
        return value ? Number(value) : 0;
    }

    function clearActiveJob(runtime) {
        if (window.localStorage) {
            window.localStorage.removeItem(getJobStorageKey(runtime));
        }
    }

    function maybeHandleAjaxExpired(responseLike) {
        if (
            responseLike &&
            Number(responseLike.ajaxExpired || 0) === 1 &&
            responseLike.ajaxRedirect
        ) {
            window.location.href = responseLike.ajaxRedirect;
            return true;
        }

        return false;
    }

    return function initAiAssistant(config, element) {
        const $root = $(element);
        const runtime = parseConfig($root);

        if (!runtime.enabled) {
            return;
        }

        let selectedTopic = null;
        let selectedPetType = 'all pets';
        let selectedTone = 'helpful and professional';
        const $status = $('#petshop-ai-status');
        let pollTimer = null;
        let isPollingRequestInFlight = false;
        let consecutivePollFailures = 0;

        const updateStatus = function (message, isLoading) {
            if (!$status.length) {
                return;
            }

            $status.text(message || '');
            $status.toggleClass('is-loading', !!isLoading);
            $status.show();
        };

        const setBadgeVisible = function (field) {
            const $badge = $('#petshop-ai-badge-' + field);
            if ($badge.length) {
                $badge.removeClass('is-hidden');
            }
        };

        const applyResult = function (result) {
            if (!result) {
                return;
            }

            if (result.data) {
                Object.keys(result.data).forEach(function (field) {
                    setValue(field, result.data[field]);
                });
            }

            if (result.field && typeof result.value !== 'undefined') {
                setValue(result.field, result.value || '');
            }

            if (result.slug) {
                setValue('slug', result.slug);
            }

            (result.generated_fields || []).forEach(function (field) {
                setBadgeVisible(field);
            });

            if (result.post_id) {
                ensurePostId(runtime, result.post_id);
            }
        };

        const stopPolling = function () {
            if (pollTimer) {
                window.clearInterval(pollTimer);
                pollTimer = null;
            }

            isPollingRequestInFlight = false;
            consecutivePollFailures = 0;
        };

        const pollJob = function (jobId, callbacks) {
            if (!jobId) {
                return;
            }

            stopPolling();
            saveActiveJob(runtime, jobId);

            const onComplete = callbacks && callbacks.onComplete ? callbacks.onComplete : function () {};
            const onError = callbacks && callbacks.onError ? callbacks.onError : function () {};
            const onFinally = callbacks && callbacks.onFinally ? callbacks.onFinally : function () {};

            const tick = function () {
                if (isPollingRequestInFlight) {
                    return;
                }

                isPollingRequestInFlight = true;

                $.ajax({
                    url: runtime.urls.process,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: runtime.formKey,
                        job_id: jobId
                    }
                }).done(function (processResponse) {
                    if (maybeHandleAjaxExpired(processResponse)) {
                        stopPolling();
                        onFinally();
                        return;
                    }

                    $.ajax({
                        url: runtime.urls.status,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            form_key: runtime.formKey,
                            job_id: jobId
                        }
                    }).done(function (statusResponse) {
                        if (maybeHandleAjaxExpired(statusResponse)) {
                            stopPolling();
                            onFinally();
                            return;
                        }

                        if (!statusResponse || !statusResponse.success) {
                            consecutivePollFailures += 1;
                            if (consecutivePollFailures >= 3) {
                                stopPolling();
                                updateStatus($t('AI status check failed. Refresh the page and try again.'), false);
                                onError($t('AI status check failed.'));
                                onFinally();
                            }
                            return;
                        }

                        consecutivePollFailures = 0;

                        const status = statusResponse.status || 'queued';
                        if (status === 'completed') {
                            stopPolling();
                            clearActiveJob(runtime);
                            applyResult(statusResponse.result || {});
                            updateStatus($t('AI generation completed successfully.'), false);
                            onComplete(statusResponse.result || {});
                            onFinally();
                            return;
                        }

                        if (status === 'failed') {
                            stopPolling();
                            clearActiveJob(runtime);
                            updateStatus(statusResponse.error_message || $t('AI generation failed.'), false);
                            onError(statusResponse.error_message || $t('AI generation failed.'));
                            onFinally();
                            return;
                        }

                        updateStatus($t('AI generation in progress...'), true);
                    }).fail(function (xhr) {
                        const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                        if (maybeHandleAjaxExpired(response)) {
                            stopPolling();
                            onFinally();
                            return;
                        }

                        consecutivePollFailures += 1;
                        if (consecutivePollFailures >= 3) {
                            stopPolling();
                            updateStatus($t('Unable to check AI job status.'), false);
                            onError($t('Unable to check AI job status.'));
                            onFinally();
                        }
                    }).always(function () {
                        isPollingRequestInFlight = false;
                    });
                }).fail(function (xhr) {
                    const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                    if (maybeHandleAjaxExpired(response)) {
                        stopPolling();
                        onFinally();
                        return;
                    }

                    consecutivePollFailures += 1;
                    if (consecutivePollFailures >= 3) {
                        stopPolling();
                        updateStatus($t('AI processing failed. Refresh and retry generation.'), false);
                        onError($t('AI processing failed.'));
                        onFinally();
                    }
                }).always(function () {
                    if (!pollTimer) {
                        isPollingRequestInFlight = false;
                    }
                });
            };

            tick();
            pollTimer = window.setInterval(tick, POLL_INTERVAL_MS);
        };

        const enqueueJob = function (payload, callbacks) {
            $.ajax({
                url: runtime.urls.enqueue,
                method: 'POST',
                dataType: 'json',
                data: Object.assign(
                    {
                        form_key: runtime.formKey,
                        post_id: runtime.postId,
                        editor_context: collectEditorContext()
                    },
                    payload || {}
                )
            }).done(function (response) {
                if (maybeHandleAjaxExpired(response)) {
                    return;
                }

                if (!response || !response.success || !response.job_id) {
                    updateStatus(response && response.message ? response.message : $t('Unable to enqueue AI job.'), false);
                    if (callbacks && callbacks.onError) {
                        callbacks.onError();
                    }
                    return;
                }

                updateStatus($t('AI task queued...'), true);
                pollJob(response.job_id, callbacks || {});
            }).fail(function () {
                updateStatus($t('Failed to enqueue AI job.'), false);
                if (callbacks && callbacks.onError) {
                    callbacks.onError();
                }
            });
        };

        const openAssistantModal = function () {
            const modal = createReusableModal({
                title: $t('AI Blog Assistant'),
                modalClass: 'petshop-ai-assistant-modal',
                buttons: [
                    {
                        text: $t('Close'),
                        class: 'action-secondary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $t('Generate Blog with AI'),
                        class: 'action-primary',
                        click: function () {
                            if (!selectedTopic) {
                                updateStatus($t('Select a topic before generating.'), false);
                                return;
                            }

                            this.closeModal();
                            updateStatus($t('AI is generating the blog post...'), true);

                            enqueueJob({
                                action_type: 'full_post',
                                topic: selectedTopic,
                                pet_type: selectedPetType,
                                tone: selectedTone
                            });
                        }
                    }
                ]
            });

            modal.setContent('<div class="message message-notice"><div>' + $t('Loading AI suggestions...') + '</div></div>');
            modal.open();

            $.ajax({
                url: runtime.urls.context,
                method: 'GET',
                dataType: 'json',
                data: {
                    editor_context: collectEditorContext()
                }
            }).done(function (response) {
                if (maybeHandleAjaxExpired(response)) {
                    return;
                }

                if (!response || !response.success) {
                    modal.setContent('<div class="message message-error"><div>' + (response && response.message ? response.message : $t('Unable to load AI context.')) + '</div></div>');
                    return;
                }

                const $modalElement = modal.element;
                const topics = (response.topics || []).map(function (topic) {
                    return {
                        title: topic.title || '',
                        reason: topic.reason || '',
                        petType: topic.pet_type || 'all pets',
                        tone: topic.tone || 'helpful and professional'
                    };
                });
                let sortField = 'title';
                let sortDirection = 'asc';

                const renderTable = function () {
                    const sorted = topics.slice().sort(function (a, b) {
                        const left = String(a[sortField] || '').toLowerCase();
                        const right = String(b[sortField] || '').toLowerCase();
                        const result = left.localeCompare(right);
                        return sortDirection === 'asc' ? result : -result;
                    });

                    const rows = sorted
                        .map(function (topic) {
                            const escapedTitle = $('<div/>').text(topic.title).html();
                            const escapedReason = $('<div/>').text(topic.reason).html();
                            const escapedPetType = $('<div/>').text(topic.petType).html();
                            const escapedTone = $('<div/>').text(topic.tone).html();
                            const checked = selectedTopic
                                ? selectedTopic === topic.title
                                : sorted[0] && sorted[0].title === topic.title;

                            return (
                                '<tr class="petshop-ai-topic-row">' +
                                    '<td class="petshop-ai-topic-cell petshop-ai-topic-cell-select">' +
                                        '<input type="radio" name="petshop-ai-topic" value="' +
                                        escapedTitle +
                                        '" data-pet-type="' +
                                        escapedPetType +
                                        '" data-tone="' +
                                        escapedTone +
                                        '" ' +
                                        (checked ? 'checked="checked"' : '') +
                                        '/>' +
                                    '</td>' +
                                    '<td class="petshop-ai-topic-cell petshop-ai-topic-title">' + escapedTitle + '</td>' +
                                    '<td class="petshop-ai-topic-cell">' + escapedPetType + '</td>' +
                                    '<td class="petshop-ai-topic-cell">' + escapedTone + '</td>' +
                                    '<td class="petshop-ai-topic-cell petshop-ai-topic-reason">' + escapedReason + '</td>' +
                                '</tr>'
                            );
                        })
                        .join('');

                    const bodyRows =
                        rows ||
                        '<tr><td class="petshop-ai-topic-cell" colspan="5">' +
                            $t('No suggestions were returned. Please try again.') +
                        '</td></tr>';

                    const sortIndicator = function (field) {
                        if (sortField !== field) {
                            return '↕';
                        }

                        return sortDirection === 'asc' ? '↑' : '↓';
                    };

                    modal.setContent(
                        '<div>' +
                            '<p>' + $t('Select one suggested topic:') + '</p>' +
                            '<table class="petshop-ai-topic-table">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th>' + $t('Use') + '</th>' +
                                        '<th><button type="button" class="petshop-ai-sort" data-sort-field="title">' + $t('Topic') + ' ' + sortIndicator('title') + '</button></th>' +
                                        '<th><button type="button" class="petshop-ai-sort" data-sort-field="petType">' + $t('Pet Type') + ' ' + sortIndicator('petType') + '</button></th>' +
                                        '<th><button type="button" class="petshop-ai-sort" data-sort-field="tone">' + $t('Tone') + ' ' + sortIndicator('tone') + '</button></th>' +
                                        '<th>' + $t('Why this topic') + '</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' +
                                    bodyRows +
                                '</tbody>' +
                            '</table>' +
                        '</div>'
                    );

                    const $first = $modalElement.find('input[name="petshop-ai-topic"]:first');
                    if ($first.length && !selectedTopic) {
                        selectedTopic = $first.val();
                        selectedPetType = $first.data('pet-type') || selectedPetType;
                        selectedTone = $first.data('tone') || selectedTone;
                    }
                };

                renderTable();

                $modalElement.on('change', 'input[name="petshop-ai-topic"]', function () {
                    selectedTopic = $(this).val() || '';
                    selectedPetType = $(this).data('pet-type') || 'all pets';
                    selectedTone = $(this).data('tone') || 'helpful and professional';
                });

                $modalElement.on('click', '.petshop-ai-sort', function () {
                    const field = $(this).data('sort-field');
                    if (!field) {
                        return;
                    }

                    if (sortField === field) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortField = field;
                        sortDirection = 'asc';
                    }

                    renderTable();
                });
            }).fail(function (xhr) {
                if (maybeHandleAjaxExpired(xhr && xhr.responseJSON ? xhr.responseJSON : null)) {
                    return;
                }

                modal.setContent('<div class="message message-error"><div>' + $t('Unable to fetch AI suggestions right now.') + '</div></div>');
            });
        };

        window.petshopBlogAiOpen = openAssistantModal;

        const resumedJobId = readActiveJob(runtime);
        if (resumedJobId > 0) {
            updateStatus($t('Resuming previous AI generation...'), true);
            pollJob(resumedJobId, {});
        }

        $(document).on('click', '.petshop-ai-field-action', function (event) {
            event.preventDefault();
            const field = $(this).data('field');
            if (!field) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);
            updateStatus($t('AI is regenerating field: ') + field + ' ...', true);

            enqueueJob(
                {
                    action_type: 'regenerate_field',
                    field: field,
                    pet_type: 'all pets',
                    tone: 'helpful and professional'
                },
                {
                    onFinally: function () {
                        $button.prop('disabled', false);
                    },
                    onError: function () {
                        $button.prop('disabled', false);
                    }
                }
            );
        });

    };
});
