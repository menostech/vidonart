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
    $(document).on('change', '[data-quick-edit][data-quick-edit-type="image"] input[type="hidden"]', function() {
        var cell = $(this).closest('[data-quick-edit][data-quick-edit-type="image"]');
        var url = cell.data('quick-edit-url');
        var id = cell.data('quick-edit-id');
        var value = $(this).val();

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