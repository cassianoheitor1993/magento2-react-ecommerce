/**
 * Petshop Widget Config Editor
 *
 * Dynamic form renderer that reads a schema definition (from GetSchema controller)
 * and an optional data payload (parsed JSON), then renders labelled, grouped,
 * editable form fields. Supports text, textarea, url, image, number, boolean,
 * select, and repeatable (array-of-objects) field types.
 *
 * Usage:
 *   configEditor.render(containerEl, schema, data);
 *   var json = configEditor.serialize();          // → nested JSON object
 *   var jsonStr = configEditor.serializeString();  // → JSON string
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    /**
     * @param {Object} options
     * @param {string} options.schemaUrl – AJAX endpoint for GetSchema
     * @param {string} options.formKey
     */
    function ConfigEditor(options) {
        this.options = options || {};
        this.$container = null;
        this.schema = [];
        this.widgetType = '';
    }

    // ── Public API ───────────────────────────────────────────────────────

    /**
     * Fetch the schema for a widget type, parse data, and render fields.
     *
     * @param {jQuery|HTMLElement} container
     * @param {string} widgetType
     * @param {Object|string} data – parsed object or JSON string
     * @param {Function} [callback] – called when render is done
     */
    ConfigEditor.prototype.load = function (container, widgetType, data, callback) {
        var self = this;
        this.$container = $(container);
        this.widgetType = widgetType;

        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { data = {}; }
        }
        data = data || {};

        if (!this.options.schemaUrl) {
            this.renderRawFallback(data);
            if (callback) callback();
            return;
        }

        $.ajax({
            url: this.options.schemaUrl,
            type: 'GET',
            dataType: 'json',
            data: { widget_type: widgetType, form_key: this.options.formKey }
        }).done(function (response) {
            if (response && response.success && response.schema) {
                self.schema = response.schema;
                self.render(data);
            } else {
                self.renderRawFallback(data);
            }
        }).fail(function () {
            self.renderRawFallback(data);
        }).always(function () {
            if (callback) callback();
        });
    };

    /**
     * Render the schema-driven form fields into the container.
     *
     * @param {Object} data
     */
    ConfigEditor.prototype.render = function (data) {
        var self = this;
        var $c = this.$container;
        $c.empty();

        if (!this.schema || !this.schema.length) {
            this.renderRawFallback(data);
            return;
        }

        // Group fields by their "group" property
        var groups = this.groupFields(this.schema);
        var groupOrder = [];
        var groupMap = {};

        this.schema.forEach(function (field) {
            var g = field.group || 'General';
            if (!groupMap[g]) {
                groupMap[g] = [];
                groupOrder.push(g);
            }
            groupMap[g].push(field);
        });

        groupOrder.forEach(function (groupName) {
            var $group = $('<fieldset class="lp-config-group">' +
                '<legend class="lp-config-group-legend">' + self.escapeHtml(groupName) + '</legend>' +
                '</fieldset>');

            groupMap[groupName].forEach(function (field) {
                var $field = self.renderField(field, data);
                $group.append($field);
            });

            $c.append($group);
        });

        // Raw JSON toggle
        var rawJson = JSON.stringify(data, null, 2);
        var $toggle = $('<div class="lp-config-raw-toggle">' +
            '<a href="#" class="lp-config-raw-toggle-link">' + $t('Advanced: View/Edit Raw JSON') + '</a>' +
            '</div>');
        var $rawWrap = $('<div class="lp-config-raw-wrap" style="display:none;">' +
            '<textarea class="admin__control-textarea lp-config-raw-textarea" rows="10"></textarea>' +
            '<button type="button" class="action-secondary lp-config-raw-apply" style="margin-top:6px;">' +
            $t('Apply Raw JSON') + '</button>' +
            '</div>');
        $rawWrap.find('textarea').val(rawJson);

        $toggle.on('click', '.lp-config-raw-toggle-link', function (e) {
            e.preventDefault();
            // Update the raw JSON textarea with current form values before showing
            $rawWrap.find('textarea').val(JSON.stringify(self.serialize(), null, 2));
            $rawWrap.toggle();
        });

        $rawWrap.on('click', '.lp-config-raw-apply', function () {
            var rawVal = $rawWrap.find('textarea').val();
            try {
                var parsed = JSON.parse(rawVal);
                self.render(parsed);
            } catch (e) {
                alert($t('Invalid JSON. Please check your syntax.'));
            }
        });

        $c.append($toggle);
        $c.append($rawWrap);
    };

    /**
     * Serialize all form fields back into a nested JSON object.
     *
     * @return {Object}
     */
    ConfigEditor.prototype.serialize = function () {
        var result = {};
        var self = this;

        if (!this.$container) return result;

        // Collect all repeatable groups first
        this.$container.find('[data-repeatable-path]').each(function () {
            var path = $(this).attr('data-repeatable-path');
            var items = [];
            $(this).find('.lp-config-repeatable-item').each(function () {
                var item = {};
                $(this).find('[data-child-path]').each(function () {
                    var childPath = $(this).attr('data-child-path');
                    var val = self.getInputValue($(this).find('.lp-config-input'));
                    item[childPath] = val;
                });
                items.push(item);
            });
            self.setNestedValue(result, path, items);
        });

        // Collect all simple fields
        this.$container.find('[data-field-path]').each(function () {
            var path = $(this).attr('data-field-path');
            var $input = $(this).find('.lp-config-input');
            var val = self.getInputValue($input);
            self.setNestedValue(result, path, val);
        });

        return result;
    };

    /**
     * @return {string}
     */
    ConfigEditor.prototype.serializeString = function () {
        return JSON.stringify(this.serialize(), null, 2);
    };

    /**
     * Destroy and empty the container.
     */
    ConfigEditor.prototype.destroy = function () {
        if (this.$container) {
            this.$container.empty();
        }
        this.schema = [];
        this.widgetType = '';
    };

    // ── Field Renderers ──────────────────────────────────────────────────

    ConfigEditor.prototype.renderField = function (field, data) {
        if (field.type === 'repeatable') {
            return this.renderRepeatableField(field, data);
        }
        return this.renderSimpleField(field, data);
    };

    ConfigEditor.prototype.renderSimpleField = function (field, data) {
        var value = this.getNestedValue(data, field.path);
        if (value === undefined || value === null) {
            value = field['default'] !== undefined ? field['default'] : '';
        }

        var requiredMark = field.required ? ' <span class="lp-config-required">*</span>' : '';
        var $wrapper = $('<div class="lp-config-field" data-field-path="' + this.escapeAttr(field.path) + '"></div>');
        var $label = $('<label class="lp-config-label">' + this.escapeHtml(field.label) + requiredMark + '</label>');
        var $input = this.createInput(field, value);

        $wrapper.append($label).append($input);
        return $wrapper;
    };

    ConfigEditor.prototype.createInput = function (field, value) {
        var $input;

        switch (field.type) {
            case 'textarea':
                $input = $('<textarea class="admin__control-textarea lp-config-input" rows="3"></textarea>');
                $input.val(String(value || ''));
                break;

            case 'boolean':
                $input = $('<select class="admin__control-select lp-config-input">' +
                    '<option value="true">' + $t('Yes') + '</option>' +
                    '<option value="false">' + $t('No') + '</option>' +
                    '</select>');
                var boolVal = (value === true || value === 'true' || value === 1 || value === '1') ? 'true' : 'false';
                $input.val(boolVal);
                break;

            case 'select':
                $input = $('<select class="admin__control-select lp-config-input"></select>');
                $input.append('<option value="">' + $t('-- Select --') + '</option>');
                (field.options || []).forEach(function (opt) {
                    var selected = String(opt.value) === String(value) ? ' selected' : '';
                    $input.append('<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>');
                });
                break;

            case 'number':
                $input = $('<input type="number" class="admin__control-text lp-config-input" />');
                $input.val(value !== '' && value !== undefined ? value : (field['default'] || ''));
                break;

            case 'url':
            case 'image':
                $input = $('<input type="text" class="admin__control-text lp-config-input" />');
                $input.val(String(value || ''));
                if (field.type === 'url') {
                    $input.attr('placeholder', 'https://...');
                } else {
                    $input.attr('placeholder', 'Image URL or path');
                }
                break;

            default: // text
                $input = $('<input type="text" class="admin__control-text lp-config-input" />');
                $input.val(String(value || ''));
                break;
        }

        if (field.required) {
            $input.attr('data-required', 'true');
        }

        return $input;
    };

    ConfigEditor.prototype.renderRepeatableField = function (field, data) {
        var self = this;
        var items = data[field.path] || [];
        if (!Array.isArray(items)) items = [];

        var requiredMark = field.required ? ' <span class="lp-config-required">*</span>' : '';
        var $wrapper = $('<div class="lp-config-repeatable" data-repeatable-path="' + this.escapeAttr(field.path) + '"></div>');
        var $header = $('<div class="lp-config-repeatable-header">' +
            '<span class="lp-config-repeatable-title">' + this.escapeHtml(field.label) + requiredMark + '</span>' +
            '<button type="button" class="action-secondary lp-config-repeatable-add">' +
            '<span>+ ' + $t('Add Item') + '</span></button>' +
            '</div>');
        var $itemsContainer = $('<div class="lp-config-repeatable-items"></div>');

        items.forEach(function (item, index) {
            $itemsContainer.append(self.renderRepeatableItem(field, item, index));
        });

        // If required and no items, add one empty row
        if (field.required && items.length === 0) {
            $itemsContainer.append(self.renderRepeatableItem(field, {}, 0));
        }

        $header.on('click', '.lp-config-repeatable-add', function () {
            var count = $itemsContainer.find('.lp-config-repeatable-item').length;
            $itemsContainer.append(self.renderRepeatableItem(field, {}, count));
        });

        $wrapper.append($header).append($itemsContainer);
        return $wrapper;
    };

    ConfigEditor.prototype.renderRepeatableItem = function (field, itemData, index) {
        var self = this;
        var children = field.children || [];
        var $item = $('<div class="lp-config-repeatable-item" data-item-index="' + index + '"></div>');
        var $itemHeader = $('<div class="lp-config-repeatable-item-header">' +
            '<span class="lp-config-repeatable-item-number">#' + (index + 1) + '</span>' +
            '<button type="button" class="action-secondary lp-config-repeatable-remove" title="' + $t('Remove') + '">✕</button>' +
            '</div>');
        var $itemBody = $('<div class="lp-config-repeatable-item-body"></div>');

        children.forEach(function (child) {
            var value = itemData[child.path];
            if (value === undefined || value === null) {
                value = child['default'] !== undefined ? child['default'] : '';
            }

            var requiredMark = child.required ? ' <span class="lp-config-required">*</span>' : '';
            var $field = $('<div class="lp-config-field lp-config-child-field" data-child-path="' + self.escapeAttr(child.path) + '"></div>');
            var $label = $('<label class="lp-config-label lp-config-child-label">' + self.escapeHtml(child.label) + requiredMark + '</label>');
            var $input = self.createInput(child, value);
            $field.append($label).append($input);
            $itemBody.append($field);
        });

        $itemHeader.on('click', '.lp-config-repeatable-remove', function () {
            $item.remove();
            // Re-number remaining items
            $item.parent().find('.lp-config-repeatable-item').each(function (i) {
                $(this).attr('data-item-index', i);
                $(this).find('.lp-config-repeatable-item-number').text('#' + (i + 1));
            });
        });

        $item.append($itemHeader).append($itemBody);
        return $item;
    };

    // ── Fallback (no schema) ─────────────────────────────────────────────

    ConfigEditor.prototype.renderRawFallback = function (data) {
        var $c = this.$container;
        $c.empty();

        var rawJson = (typeof data === 'string') ? data : JSON.stringify(data || {}, null, 2);
        var $textarea = $('<textarea class="admin__control-textarea lp-config-raw-textarea lp-config-input" rows="12" data-field-path="__raw__"></textarea>');
        $textarea.val(rawJson);
        $c.append($textarea);
    };

    // ── Helpers ──────────────────────────────────────────────────────────

    ConfigEditor.prototype.getInputValue = function ($input) {
        if (!$input.length) return '';

        var val = $input.val();
        var isRequired = $input.attr('data-required') === 'true';

        // Determine the type from the element
        if ($input.is('select')) {
            // Check if it's a boolean select
            if (val === 'true') return true;
            if (val === 'false') return false;
            return val;
        }

        if ($input.attr('type') === 'number') {
            return val !== '' ? Number(val) : (isRequired ? 0 : '');
        }

        return val;
    };

    ConfigEditor.prototype.getNestedValue = function (obj, path) {
        if (!obj || !path) return undefined;
        var keys = path.split('.');
        var current = obj;

        for (var i = 0; i < keys.length; i++) {
            if (current === undefined || current === null || typeof current !== 'object') {
                return undefined;
            }
            current = current[keys[i]];
        }

        return current;
    };

    ConfigEditor.prototype.setNestedValue = function (obj, path, value) {
        var keys = path.split('.');
        var current = obj;

        for (var i = 0; i < keys.length - 1; i++) {
            if (current[keys[i]] === undefined || typeof current[keys[i]] !== 'object') {
                current[keys[i]] = {};
            }
            current = current[keys[i]];
        }

        current[keys[keys.length - 1]] = value;
    };

    ConfigEditor.prototype.groupFields = function (schema) {
        var groups = {};
        schema.forEach(function (field) {
            var g = field.group || 'General';
            if (!groups[g]) groups[g] = [];
            groups[g].push(field);
        });
        return groups;
    };

    ConfigEditor.prototype.escapeHtml = function (str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    };

    ConfigEditor.prototype.escapeAttr = function (str) {
        return String(str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    };

    return ConfigEditor;
});
