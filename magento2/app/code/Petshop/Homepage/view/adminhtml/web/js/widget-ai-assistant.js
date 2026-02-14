define([
    'jquery',
    'uiRegistry',
    'Petshop_Homepage/js/widget-config-editor',
    'mage/translate'
], function ($, uiRegistry, ConfigEditor) {
    'use strict';

    // UI Component paths for the form fields
    var FORM_NS = 'petshop_homepage_widget_form.petshop_homepage_widget_form';
    var CONFIG_JSON_PATH = FORM_NS + '.general.config_json';
    var WIDGET_TYPE_PATH = FORM_NS + '.general.widget_type';

    return function (config, element) {
        var $root = $(element);
        var rootConfig = {};

        try {
            rootConfig = JSON.parse($root.attr('data-config') || '{}');
        } catch (e) {
            rootConfig = {};
        }

        var generateUrl = rootConfig.urls && rootConfig.urls.generate ? rootConfig.urls.generate : '';
        var getSchemaUrl = rootConfig.urls && rootConfig.urls.getSchema ? rootConfig.urls.getSchema : '';
        var formKey = rootConfig.formKey || '';
        var $button = $('#petshop-widget-ai-generate');
        var $status = $('#petshop-widget-ai-status');
        var $editorContainer = $('#petshop-widget-edit-config-editor');

        // References to UI components (set once resolved)
        var configJsonComponent = null;
        var widgetTypeComponent = null;
        var editorInitialized = false;

        // Initialize config editor
        var configEditor = new ConfigEditor({
            schemaUrl: getSchemaUrl,
            formKey: formKey
        });

        if (!$button.length) {
            return;
        }

        // ── Helpers to read/write through the KO observable ──────────

        function getConfigJsonValue() {
            if (configJsonComponent) {
                return configJsonComponent.value() || '';
            }
            // Fallback to DOM
            var $el = $('[name="config_json"]');
            return $el.length ? $el.val() || '' : '';
        }

        function setConfigJsonValue(json) {
            if (configJsonComponent) {
                configJsonComponent.value(json);
            }
            // Also set DOM in case anything reads it directly
            var $el = $('[name="config_json"]');
            if ($el.length) {
                $el.val(json);
            }
        }

        function getWidgetTypeValue() {
            if (widgetTypeComponent) {
                return widgetTypeComponent.value() || '';
            }
            var $el = $('[name="widget_type"]');
            return $el.length ? $el.val() || '' : '';
        }

        // ── Sync editor → config_json KO observable ──────────────────

        function syncEditorToField() {
            if (configEditor && configEditor.$container && configEditor.$container.children().length > 0) {
                var serialized = configEditor.serialize();
                setConfigJsonValue(JSON.stringify(serialized));
            }
        }

        // ── Render existing config_json as editable fields ───────────

        function initEditor() {
            if (editorInitialized) return;

            var widgetType = getWidgetTypeValue();
            var currentJson = getConfigJsonValue();

            if (!widgetType || !currentJson || !$editorContainer.length) {
                return;
            }

            var parsedData;
            try {
                parsedData = JSON.parse(currentJson);
            } catch (e) {
                return;
            }

            if (parsedData && typeof parsedData === 'object' && Object.keys(parsedData).length > 0) {
                editorInitialized = true;
                configEditor.load($editorContainer, widgetType, parsedData, function () {
                    startPeriodicSync();
                });
            }
        }

        // ── Periodic sync keeps the KO observable in sync ────────────
        // This guarantees that when Magento's form provider collects
        // field values for the AJAX save, config_json is up-to-date.

        var syncInterval = null;
        function startPeriodicSync() {
            if (syncInterval) return;
            syncInterval = setInterval(function () {
                syncEditorToField();
            }, 2000);
        }

        function stopPeriodicSync() {
            if (syncInterval) {
                clearInterval(syncInterval);
                syncInterval = null;
            }
        }

        // ── Also sync on every save-button click (immediate) ─────────

        $(document).on('click', '[data-ui-id="save-button"], [data-ui-id="save-button-button"], #save, #save_and_continue, .save', function () {
            syncEditorToField();
        });

        $('form').on('submit', function () {
            syncEditorToField();
        });

        // ── Generate button ──────────────────────────────────────────

        $button.on('click', function () {
            var widgetType = getWidgetTypeValue();
            var context = $('#petshop-widget-ai-context').val();

            if (!widgetType) {
                $status.text($.mage.__('Select a widget type first.')).show();
                return;
            }

            if (!generateUrl) {
                $status.text($.mage.__('AI generation endpoint is not configured.')).show();
                return;
            }

            $button.prop('disabled', true);
            $status.text($.mage.__('Generating...')).show();

            $.ajax({
                url: generateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: formKey,
                    widget_type: widgetType,
                    context: context
                }
            }).done(function (response) {
                if (response && response.success && response.config_json) {
                    // Store in KO observable
                    setConfigJsonValue(response.config_json);

                    // Parse and render in the config editor
                    var parsedData;
                    try {
                        parsedData = JSON.parse(response.config_json);
                    } catch (e) {
                        parsedData = {};
                    }

                    editorInitialized = true;
                    configEditor.load($editorContainer, widgetType, parsedData, function () {
                        startPeriodicSync();
                    });

                    if (response.source === 'ai') {
                        $status.text($.mage.__('AI content generated. Edit the fields below.')).show();
                    } else {
                        $status.text($.mage.__('Fallback content generated. Edit the fields below.')).show();
                    }
                } else {
                    $status.text((response && response.message) || $.mage.__('Unable to generate content.')).show();
                }
            }).fail(function () {
                $status.text($.mage.__('Unable to generate content.')).show();
            }).always(function () {
                $button.prop('disabled', false);
            });
        });

        // ── Wait for UI components via uiRegistry, then init ─────────
        // This is the key: uiRegistry.get() waits until the KO
        // component is actually instantiated and its value populated
        // by the DataProvider AJAX response — no arbitrary timeouts.

        uiRegistry.get(CONFIG_JSON_PATH, function (comp) {
            configJsonComponent = comp;
            // Try init now; widget_type might already be available
            initEditor();
        });

        uiRegistry.get(WIDGET_TYPE_PATH, function (comp) {
            widgetTypeComponent = comp;

            // Re-render editor when widget_type is changed on the form
            comp.on('value', function () {
                var widgetType = comp.value();
                var currentJson = getConfigJsonValue();

                if (!widgetType) {
                    stopPeriodicSync();
                    configEditor.destroy();
                    editorInitialized = false;
                    return;
                }

                var parsedData = {};
                if (currentJson) {
                    try { parsedData = JSON.parse(currentJson); } catch (e) { parsedData = {}; }
                }

                editorInitialized = true;
                configEditor.load($editorContainer, widgetType, parsedData, function () {
                    startPeriodicSync();
                });
            });

            // Try init now; config_json might already be available
            initEditor();
        });
    };
});
