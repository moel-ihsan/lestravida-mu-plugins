(function ($) {
    'use strict';

    $(document.body).on('change', '.lvc-upload-input', function () {
        var input = this;
        var file = input.files && input.files[0] ? input.files[0] : null;
        var target = $(input).data('target');
        var $status = $(input).closest('.form-row').find('.lvc-upload-status');

        if (!file || !target) {
            return;
        }

        $('#' + target).val('');

        $status
            .html('Mengupload...')
            .removeClass('lvc-upload-success lvc-upload-error');

        var fd = new FormData();
        fd.append('action', 'lvc_temp_upload');
        fd.append('nonce', lvcUpload.nonce);
        fd.append('file', file);

        $.ajax({
            url: lvcUpload.ajaxUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,

            beforeSend: function () {
                $(input).prop('disabled', true).addClass('lvc-is-uploading');
            },

            success: function (res) {
                if (res && res.success && res.data && res.data.token) {
                    $('#' + target).val(res.data.token).trigger('change');

                    $status
                        .html('✅ Berhasil diupload')
                        .removeClass('lvc-upload-error')
                        .addClass('lvc-upload-success');

                    return;
                }

                $status
                    .html('❌ Upload gagal')
                    .removeClass('lvc-upload-success')
                    .addClass('lvc-upload-error');

                $('#' + target).val('');
                input.value = '';
            },

            error: function () {
                $status
                    .html('❌ Upload gagal')
                    .removeClass('lvc-upload-success')
                    .addClass('lvc-upload-error');

                $('#' + target).val('');
                input.value = '';
            },

            complete: function () {
                $(input).prop('disabled', false).removeClass('lvc-is-uploading');
            }
        });
    });
})(jQuery);