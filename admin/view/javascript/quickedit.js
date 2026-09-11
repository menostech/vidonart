(function($) {
    'use strict';

    function post(url, data, done, fail) {
        $.ajax({
            url: url,
            type: 'post',
            data: data,
            dataType: 'json',
            cache: false,
            success: function(json) {
                if (json['error']) {
                    fail(json['error']);
                } else {
                    done(json);
                }
            },
            error: function() {
                fail({ 'warning': 'Request failed' });
            }
        });
    }

    function showAlert(errors) {
        var text = '';

        for (var key in errors) {
            text += errors[key] + ' ';
        }

        $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + text + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
    }

    function flash(cell) {
        $(cell).addClass('table-primary');

        setTimeout(function() {
            $(cell).removeClass('table-primary');
        }, 600);
    }

    // ---- text / number cells ----
    $(document).on('click', '[data-quick-edit][data-quick-edit-type="text"], [data-quick-edit][data-quick-edit-type="number"]', function(e) {
        if ($(this).find('input').length) {
            return;
        }

        var cell = this;
        var field = $(cell).data('quick-edit');
        var type = $(cell).data('quick-edit-type');
        var url = $(cell).data('quick-edit-url');
        var id = $(cell).data('quick-edit-id');
        var original = $(cell).data('quick-edit-value');

        var input = $('<input>', {
            type: type == 'number' ? 'number' : 'text',
            value: original,
            class: 'form-control form-control-sm',
            style: 'min-width: 120px;'
        });

        $(cell).html(input);
        input.trigger('focus').select();

        var saving = false;

        function save() {
            if (saving) {
                return;
            }

            saving = true;

            var value = input.val();

            if (value === original) {
                $(cell).html($('<span>').text(original));
                return;
            }

            var data = {};
            data[$(cell).data('quick-edit-id-field')] = id;
            data['field'] = field;
            data['value'] = value;

            post(url, data, function(json) {
                $(cell).html($('<span>').text(json['value'] !== undefined ? json['value'] : value));
                $(cell).data('quick-edit-value', json['value'] !== undefined ? json['value'] : value);
                flash(cell);
            }, function(errors) {
                $(cell).html($('<span>').text(original));
                showAlert(errors);
            });
        }

        function cancel() {
            $(cell).html($('<span>').text(original));
        }

        input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                save();
            }

            if (e.key === 'Escape') {
                cancel();
            }
        });

        input.on('blur', function() {
            save();
        });
    });

    // ---- status cells ----
    $(document).on('click', '[data-quick-edit][data-quick-edit-type="status"]', function(e) {
        var cell = this;
        var url = $(cell).data('quick-edit-url');
        var id = $(cell).data('quick-edit-id');
        var current = $(cell).data('quick-edit-value');

        if ($(cell).hasClass('quick-editing')) {
            return;
        }

        $(cell).addClass('quick-editing');

        var next = current == '1' ? '0' : '1';

        var data = {};
        data[$(cell).data('quick-edit-id-field')] = id;
        data['field'] = 'status';
        data['value'] = next;

        post(url, data, function(json) {
            $(cell).data('quick-edit-value', next);
            $(cell).find('.fa-square-check, .fa-square').removeClass('fa-square-check fa-square').addClass(next == '1' ? 'fa-square-check' : 'fa-square');
            $(cell).closest('tr').toggleClass('table-active opacity-50', next != '1');
            flash(cell);
        }, function(errors) {
            showAlert(errors);
        }).always(function() {
            $(cell).removeClass('quick-editing');
        });
    });

    // ---- image cells (file manager pick -> auto save) ----
    var quickEditTarget = null;

    // record the target input when the image button opens the file manager
    $(document).on('click', '[data-quick-edit][data-quick-edit-type="image"] button[data-oc-toggle="image"]', function() {
        quickEditTarget = $(this).data('oc-target');
    });

    // after the file manager writes the value (its #modal-image delegated handler
    // runs before this document-level handler), POST the image field
    $(document).on('click', '#modal-image a.thumbnail', function() {
        if (!quickEditTarget) {
            return;
        }

        var input = $(quickEditTarget);
        var cell = input.closest('[data-quick-edit][data-quick-edit-type="image"]');

        if (!cell.length) {
            return;
        }

        var url = cell.data('quick-edit-url');
        var id = cell.data('quick-edit-id');
        var value = input.val();

        var data = {};
        data[cell.data('quick-edit-id-field')] = id;
        data['field'] = 'image';
        data['value'] = value;

        post(url, data, function(json) {
            flash(cell.get(0));
        }, function(errors) {
            showAlert(errors);
        });
    });
})(jQuery);