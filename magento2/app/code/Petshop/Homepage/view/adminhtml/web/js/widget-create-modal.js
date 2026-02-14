define([
    'jquery',
    'Petshop_Homepage/js/widget-config-editor',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/calendar'
], function ($, ConfigEditor, modal) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var rootConfig = {};
        var modalInitialized = false;

        try {
            rootConfig = JSON.parse($root.attr('data-config') || '{}');
        } catch (e) {
            rootConfig = {};
        }

        var createUrl = rootConfig.urls && rootConfig.urls.create ? rootConfig.urls.create : '';
        var updateUrl = rootConfig.urls && rootConfig.urls.update ? rootConfig.urls.update : '';
        var getWidgetUrl = rootConfig.urls && rootConfig.urls.getWidget ? rootConfig.urls.getWidget : '';
        var generateUrl = rootConfig.urls && rootConfig.urls.generate ? rootConfig.urls.generate : '';
        var validateScheduleUrl = rootConfig.urls && rootConfig.urls.validateSchedule ? rootConfig.urls.validateSchedule : '';
        var getSchemaUrl = rootConfig.urls && rootConfig.urls.getSchema ? rootConfig.urls.getSchema : '';
        var formKey = rootConfig.formKey || '';
        var $modal = $('#petshop-create-widget-modal');
        var $form = $('#petshop-create-widget-form');
        var $schedule = $('#petshop_schedule_fields');
        var $configJson = $('#petshop_config_json');
        var $widgetId = $('#petshop_widget_id');
        var $configEditorWrapper = $('#petshop_config_editor_wrapper');
        var $configEditorContainer = $('#petshop_config_editor');
        var $errorBox = $('#petshop-widget-modal-errors');
        var $errorContent = $('#petshop-widget-modal-errors-content');
        var $successBox = $('#petshop-widget-modal-success');
        var $scheduleHint = $('#petshop-schedule-validation-hint');
        var generatedReady = false;
        var generatedWidgetType = '';
        var scheduleValidationTimeout = null;
        var scheduleIsValid = false;

        // ── Edit-mode state ──────────────────────────────────────────
        var editMode = false;      // true when editing an existing widget
        var editWidgetId = 0;      // the widget_id being edited

        // Initialize config editor
        var configEditor = new ConfigEditor({
            schemaUrl: getSchemaUrl,
            formKey: formKey
        });

        if (!$modal.length || !$form.length) {
            return;
        }

        // ── Modal initializer ────────────────────────────────────────

        function initializeModalIfNeeded() {
            if (modalInitialized) {
                return;
            }

            modal({
                type: 'popup',
                title: $.mage.__('Add Homepage Widget'),
                responsive: true,
                innerScroll: true,
                buttons: [
                    {
                        text: $.mage.__('Cancel'),
                        class: 'action-secondary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $.mage.__('Save Widget'),
                        class: 'action-primary petshop-modal-save-btn',
                        click: function () {
                            handleSave(this);
                        }
                    }
                ]
            }, $modal);

            modalInitialized = true;
        }

        // ── Update modal title & button labels per mode ──────────────

        function updateModalChrome() {
            var modalWidget = $modal.data('mageModal') || $modal.data('modal');

            if (modalWidget && modalWidget.options) {
                modalWidget.options.title = editMode
                    ? $.mage.__('Edit Widget #%1').replace('%1', editWidgetId)
                    : $.mage.__('Add Homepage Widget');

                // Refresh title in the DOM
                var $title = $modal.closest('.modal-inner-wrap').find('.modal-title');
                if ($title.length) {
                    $title.text(modalWidget.options.title);
                }
            }
        }

        // ── Save handler (works for both create & update) ────────────

        function handleSave(modalRef) {
            if (!validateBeforeSave()) {
                return;
            }

            // Serialize the config editor fields into config_json
            var serialized = configEditor.serialize();
            $configJson.val(JSON.stringify(serialized));

            var payload = $form.serialize();
            var saveUrl = editMode ? updateUrl : createUrl;

            if (!saveUrl) {
                showErrors($.mage.__('Save endpoint is not configured.'), []);
                return;
            }

            $.ajax({
                url: saveUrl,
                type: 'POST',
                dataType: 'json',
                data: payload
            }).done(function (response) {
                if (response && response.success) {
                    var msg = editMode
                        ? $.mage.__('Widget updated successfully.')
                        : $.mage.__('Widget created successfully.');
                    showSuccess(msg);
                    modalRef.closeModal();
                    window.location.reload();
                    return;
                }

                showErrors(
                    (response && response.messages && response.messages.length)
                        ? response.messages
                        : (response && response.message) || $.mage.__('Failed to save widget.'),
                    (response && response.invalid_fields) || []
                );
            }).fail(function (xhr) {
                var fallbackMessage = $.mage.__('Failed to save widget. Please try again.');
                var detailMessage = fallbackMessage;

                if (xhr && xhr.responseJSON) {
                    if (Array.isArray(xhr.responseJSON.messages) && xhr.responseJSON.messages.length) {
                        showErrors(xhr.responseJSON.messages, xhr.responseJSON.invalid_fields || []);
                        return;
                    }

                    if (xhr.responseJSON.message) {
                        detailMessage = xhr.responseJSON.message;
                    }
                }

                showErrors(detailMessage, []);
            });
        }

        // ── Open modal in Create mode ────────────────────────────────

        function openCreateModal() {
            editMode = false;
            editWidgetId = 0;
            resetForm();

            initializeModalIfNeeded();
            updateModalChrome();
            initializeDateTimePickers();

            // In create mode, widget_type should be editable
            $('#petshop_widget_type').prop('disabled', false);

            if (typeof $modal.modal === 'function') {
                $modal.modal('openModal');
            } else {
                $modal.show();
            }
        }

        // ── Open modal in Edit mode ──────────────────────────────────

        function openEditModal(widgetId) {
            if (!getWidgetUrl) {
                return;
            }

            editMode = true;
            editWidgetId = widgetId;
            resetForm();

            initializeModalIfNeeded();
            updateModalChrome();
            initializeDateTimePickers();

            // Show a loading state
            showSuccess($.mage.__('Loading widget data...'));

            if (typeof $modal.modal === 'function') {
                $modal.modal('openModal');
            } else {
                $modal.show();
            }

            // Fetch widget data
            $.ajax({
                url: getWidgetUrl,
                type: 'GET',
                dataType: 'json',
                data: { widget_id: widgetId, form_key: formKey }
            }).done(function (response) {
                if (response && response.success && response.widget) {
                    populateFormFromWidget(response.widget);
                    return;
                }

                showErrors(
                    (response && response.message) || $.mage.__('Failed to load widget data.'),
                    []
                );
            }).fail(function () {
                showErrors($.mage.__('Failed to load widget data. Please try again.'), []);
            });
        }

        // ── Populate all form fields from widget data ────────────────

        function populateFormFromWidget(widget) {
            $widgetId.val(widget.widget_id || '');

            // Title
            $('#petshop_widget_title').val(widget.title || '');

            // Widget type (set and disable to prevent accidental change)
            var $typeSelect = $('#petshop_widget_type');
            $typeSelect.val(widget.widget_type || '');
            $typeSelect.prop('disabled', true);
            // Need a hidden field for disabled select to be submitted
            ensureHiddenWidgetType(widget.widget_type);

            // Sort order
            $('#petshop_sort_order').val(widget.sort_order || 0);

            // Config JSON — parse and render in the visual editor
            var configJsonStr = widget.config_json || '{}';
            $configJson.val(configJsonStr);

            var parsedData = {};
            try {
                parsedData = JSON.parse(configJsonStr);
            } catch (e) {
                parsedData = {};
            }

            generatedReady = true;
            generatedWidgetType = widget.widget_type || '';

            $configEditorWrapper.show();
            configEditor.load($configEditorContainer, widget.widget_type, parsedData, function () {
                // Show schedule fields and populate dates
                updateScheduleVisibility();

                // Set dates after schedule section is visible
                $('#petshop_starts_at').val(formatDateForInput(widget.starts_at || ''));
                $('#petshop_ends_at').val(formatDateForInput(widget.ends_at || ''));

                // Set active status
                $('#petshop_is_active').val(String(widget.is_active || 0));

                // Run schedule validation
                scheduleValidationDebounced();
            });

            $successBox.hide().empty();
        }

        /**
         * Disabled <select> elements don't submit. Add a hidden input so
         * widget_type is always included in the POST payload.
         */
        function ensureHiddenWidgetType(value) {
            var $hidden = $form.find('input[type="hidden"][name="widget_type"]');
            if (!$hidden.length) {
                $hidden = $('<input type="hidden" name="widget_type" />');
                $form.prepend($hidden);
            }
            $hidden.val(value || '');
        }

        /**
         * Convert "2026-02-13 10:00:00" to the format the datepicker expects.
         */
        function formatDateForInput(dateStr) {
            if (!dateStr) return '';
            // The calendar widget uses "YYYY-MM-DD HH:MM:SS" format
            return dateStr.replace('T', ' ').replace(/\.\d+$/, '');
        }

        // ── Form reset ───────────────────────────────────────────────

        function resetForm() {
            generatedReady = false;
            scheduleIsValid = false;
            generatedWidgetType = '';

            $form[0].reset();
            $widgetId.val('');
            $configJson.val('');
            $('#petshop_widget_title').val('');
            $('#petshop_sort_order').val('0');

            // Remove any hidden widget_type input from edit mode
            $form.find('input[type="hidden"][name="widget_type"]').remove();
            $('#petshop_widget_type').prop('disabled', false);

            configEditor.destroy();
            $configEditorWrapper.hide();
            $errorBox.hide();
            $successBox.hide().empty();
            clearScheduleHint();
            clearFieldErrors();
            updateScheduleVisibility();
        }

        // ── Date/time pickers ────────────────────────────────────────

        function initializeDateTimePickers() {
            var $start = $('#petshop_starts_at');
            var $end = $('#petshop_ends_at');

            [$start, $end].forEach(function ($input) {
                if (!$input.length || $input.data('petshopCalendarInit')) {
                    return;
                }

                $input.calendar({
                    showsTime: true,
                    dateFormat: 'yy-mm-dd',
                    timeFormat: 'HH:mm:ss',
                    showOn: 'button',
                    buttonText: 'Open calendar'
                });

                $input.data('petshopCalendarInit', true);
            });
        }

        // ── Error / success display ──────────────────────────────────

        function clearFieldErrors() {
            $form.find('.admin__field').removeClass('_error');
        }

        function markFieldError(fieldName) {
            var $field = $form.find('[name="' + fieldName + '"]');
            if ($field.length) {
                $field.closest('.admin__field').addClass('_error');
            }
        }

        function showErrors(messages, invalidFields) {
            var list = Array.isArray(messages) ? messages : [messages];
            var html = '<ul style="margin:0; padding-left:16px;">';

            list.forEach(function (message) {
                html += '<li>' + $('<div/>').text(message || '').html() + '</li>';
            });
            html += '</ul>';

            clearFieldErrors();
            (invalidFields || []).forEach(markFieldError);

            $successBox.hide().empty();
            $errorContent.html(html);
            $errorBox.show();
        }

        function showSuccess(message) {
            clearFieldErrors();
            $errorBox.hide();
            $errorContent.empty();
            $successBox.text(message).show();
        }

        // ── Schedule hint ────────────────────────────────────────────

        function setScheduleHint(type, message) {
            if (!$scheduleHint.length) {
                return;
            }

            $scheduleHint.removeClass('message-success message-warning message-error');
            $scheduleHint.addClass('message-' + type);
            $scheduleHint.text(message || '');
            $scheduleHint.show();
        }

        function clearScheduleHint() {
            scheduleIsValid = false;
            if (scheduleValidationTimeout) {
                clearTimeout(scheduleValidationTimeout);
                scheduleValidationTimeout = null;
            }
            if ($scheduleHint.length) {
                $scheduleHint.hide().removeClass('message-success message-warning message-error').text('');
            }
        }

        // ── Config validation ────────────────────────────────────────

        function isGeneratedConfigValid() {
            if (configEditor && configEditor.$container && configEditor.$container.children().length > 0) {
                return true;
            }

            var configValue = $.trim(String($configJson.val() || ''));
            if (!configValue) {
                return false;
            }

            try {
                var decoded = JSON.parse(configValue);
                return !!decoded && typeof decoded === 'object';
            } catch (e) {
                return false;
            }
        }

        // ── Schedule visibility ──────────────────────────────────────

        function updateScheduleVisibility() {
            var widgetType = $.trim(String($('#petshop_widget_type').val() || ''));
            var shouldShow = !!widgetType && generatedReady && isGeneratedConfigValid();

            if (shouldShow) {
                $schedule.prop('disabled', false).show();
                return;
            }

            $schedule.prop('disabled', true).hide();
            scheduleIsValid = false;
            clearScheduleHint();
        }

        // ── Schedule validation ──────────────────────────────────────

        function runScheduleValidation() {
            if (!validateScheduleUrl || !generatedReady) {
                clearScheduleHint();
                return;
            }

            var startsAt = $.trim(String($('#petshop_starts_at').val() || ''));
            var endsAt = $.trim(String($('#petshop_ends_at').val() || ''));
            var isActive = String($('#petshop_is_active').val() || '1');
            var widgetType = $.trim(String($('#petshop_widget_type').val() || ''));

            if (!startsAt || !endsAt) {
                clearScheduleHint();
                return;
            }

            setScheduleHint('warning', $.mage.__('Checking schedule conflicts...'));

            var validationData = {
                form_key: formKey,
                starts_at: startsAt,
                ends_at: endsAt,
                is_active: isActive,
                widget_type: widgetType
            };

            // In edit mode, exclude current widget from conflict check
            if (editMode && editWidgetId > 0) {
                validationData.widget_id = editWidgetId;
            }

            $.ajax({
                url: validateScheduleUrl,
                type: 'POST',
                dataType: 'json',
                data: validationData
            }).done(function (response) {
                if (response && response.valid) {
                    scheduleIsValid = true;
                    setScheduleHint('success', response.message || $.mage.__('No scheduling conflicts detected.'));
                    return;
                }

                scheduleIsValid = false;
                setScheduleHint('error', (response && response.message) || $.mage.__('Schedule conflict detected.'));
            }).fail(function () {
                scheduleIsValid = false;
                setScheduleHint('error', $.mage.__('Unable to validate schedule right now.'));
            });
        }

        function scheduleValidationDebounced() {
            if (scheduleValidationTimeout) {
                clearTimeout(scheduleValidationTimeout);
            }

            scheduleValidationTimeout = setTimeout(runScheduleValidation, 350);
        }

        // ── Pre-save validation ──────────────────────────────────────

        function validateBeforeSave() {
            var widgetType = $.trim(String($('#petshop_widget_type').val() || ''));
            var startsAt = $.trim(String($('#petshop_starts_at').val() || ''));
            var endsAt = $.trim(String($('#petshop_ends_at').val() || ''));
            var errors = [];
            var invalidFields = [];

            clearFieldErrors();

            if (!widgetType) {
                errors.push($.mage.__('Widget Type is required.'));
                invalidFields.push('widget_type');
            }

            if (!generatedReady || !isGeneratedConfigValid()) {
                errors.push($.mage.__('Widget content is required. Generate with AI or load existing.'));
                invalidFields.push('context');
            }

            if (!startsAt) {
                errors.push($.mage.__('Visible From is required.'));
                invalidFields.push('starts_at');
            }

            if (!endsAt) {
                errors.push($.mage.__('Visible Until is required.'));
                invalidFields.push('ends_at');
            }

            if (!scheduleIsValid) {
                errors.push($.mage.__('Resolve scheduling conflicts before saving.'));
                invalidFields.push('starts_at');
                invalidFields.push('ends_at');
            }

            if (startsAt && endsAt) {
                var start = new Date(startsAt);
                var end = new Date(endsAt);

                if (isNaN(start.getTime())) {
                    errors.push($.mage.__('Visible From has an invalid date/time value.'));
                    invalidFields.push('starts_at');
                }

                if (isNaN(end.getTime())) {
                    errors.push($.mage.__('Visible Until has an invalid date/time value.'));
                    invalidFields.push('ends_at');
                }

                if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && start >= end) {
                    errors.push($.mage.__('Visible From must be before Visible Until.'));
                    invalidFields.push('starts_at');
                    invalidFields.push('ends_at');
                }
            }

            if (errors.length) {
                showErrors(errors, invalidFields);
                return false;
            }

            return true;
        }

        // ── Event Bindings ───────────────────────────────────────────

        // Dismiss errors
        $(document).on('click', '#petshop-widget-modal-errors-close', function () {
            $errorBox.hide();
            $errorContent.empty();
        });

        // Generate content with AI
        $(document).on('click', '#petshop_generate_widget_content', function () {
            var widgetType = $.trim(String($('#petshop_widget_type').val() || ''));
            var contextText = $.trim(String($('#petshop_widget_context').val() || ''));
            var $button = $(this);

            clearFieldErrors();

            if (!widgetType) {
                showErrors($.mage.__('Select a widget type before generating content.'), ['widget_type']);
                return;
            }

            if (!generateUrl) {
                showErrors($.mage.__('AI generation endpoint is not configured.'), ['context']);
                return;
            }

            $button.prop('disabled', true);
            showSuccess($.mage.__('Generating widget content...'));

            $.ajax({
                url: generateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: formKey,
                    widget_type: widgetType,
                    context: contextText
                }
            }).done(function (response) {
                if (response && response.success && response.config_json) {
                    generatedReady = true;
                    generatedWidgetType = widgetType;

                    $configJson.val(response.config_json);
                    $configEditorWrapper.show();

                    var parsedData;
                    try {
                        parsedData = JSON.parse(response.config_json);
                    } catch (e) {
                        parsedData = {};
                    }

                    configEditor.load($configEditorContainer, widgetType, parsedData, function () {
                        updateScheduleVisibility();
                        scheduleValidationDebounced();
                    });

                    if (response.source === 'ai') {
                        showSuccess($.mage.__('Content generated by DeepSeek AI. Edit the fields below, then schedule widget visibility.'));
                    } else {
                        showSuccess($.mage.__('AI unavailable; fallback content generated. Edit the fields below and schedule it.'));
                    }
                    return;
                }

                generatedReady = false;
                generatedWidgetType = '';
                updateScheduleVisibility();
                clearScheduleHint();
                showErrors(
                    (response && response.message) || $.mage.__('Unable to generate content.'),
                    ['context']
                );
            }).fail(function () {
                generatedReady = false;
                generatedWidgetType = '';
                updateScheduleVisibility();
                clearScheduleHint();
                showErrors($.mage.__('Unable to generate content. Please try again.'), ['context']);
            }).always(function () {
                $button.prop('disabled', false);
            });
        });

        // Widget type change
        $(document).on('change', '#petshop_widget_type', function () {
            var currentType = $.trim(String($(this).val() || ''));

            if (generatedWidgetType && currentType !== generatedWidgetType) {
                generatedReady = false;
                generatedWidgetType = '';
                $configJson.val('');
                configEditor.destroy();
                $configEditorWrapper.hide();
                clearScheduleHint();
            }

            updateScheduleVisibility();
        });

        // Schedule date/active changes
        $(document).on('change input', '#petshop_starts_at, #petshop_ends_at, #petshop_is_active', function () {
            scheduleValidationDebounced();
        });

        // ── "Add Widget" button → Create mode ────────────────────────

        $(document).on('click', '#petshop-open-create-widget-modal', function () {
            openCreateModal();
        });

        // ── Grid "Edit" action click → Edit mode ─────────────────────
        // The WidgetActions column generates hrefs like "#edit-widget-123".
        // Intercept those clicks so they open the modal instead of navigating.

        $(document).on('click', 'a[href^="#edit-widget-"]', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var href = $(this).attr('href') || '';
            var match = href.match(/#edit-widget-(\d+)/);
            if (match && match[1]) {
                openEditModal(parseInt(match[1], 10));
            }
        });

        updateScheduleVisibility();
    };
});
