jQuery(document).ready(function($) {
    const ajax_url = choutAioBlockIps.ajax_url;
    const nonce = choutAioBlockIps.nonce;
    const texts = choutAioBlockIps.texts;

    function showMessage(msg, isError = false) {
        const id = isError ? '#caio-error' : '#caio-message';
        $(id).find('p').text(msg);
        $(id).show().delay(3000).fadeOut();
        if(!isError) $('#caio-error').hide();
        else $('#caio-message').hide();
    }

    // Toggle AIO IPs
    $('#caio_use_aio_ips').on('change', function() {
        const use_aio = $(this).is(':checked') ? 1 : 0;
        if(use_aio) {
            $('.caio-aio-list-container').slideDown();
        } else {
            $('.caio-aio-list-container').slideUp();
        }

        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'save_aio_setting',
            nonce: nonce,
            use_aio: use_aio
        }, function(res) {
            if(res.success) {
                showMessage(res.data.message);
                // Optionally reload to see new IPs if just enabled, but let's just show success
                if(use_aio) {
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Add IP
    $('#caio_add_ip_btn').on('click', function() {
        const ip = $('#caio_new_ip').val().trim();
        const note = $('#caio_new_note').val().trim();

        if(!ip) {
            showMessage(texts.empty_ip || 'Please enter an IP address.', true);
            return;
        }

        $(this).prop('disabled', true);
        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'add_ip',
            nonce: nonce,
            ip: ip,
            note: note
        }, function(res) {
            $('#caio_add_ip_btn').prop('disabled', false);
            if(res.success) {
                showMessage(res.data.message);
                setTimeout(() => location.reload(), 500); // Reload to see new item in sorted list
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Trigger file selection
    $('#caio_upload_csv_btn').on('click', function() {
        $('#caio_csv_file').click();
    });

    // Auto upload on file selection
    $('#caio_csv_file').on('change', function() {
        const fileInput = this;
        if(fileInput.files.length === 0) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'chout_aio_block_ips_action');
        formData.append('type', 'upload_csv');
        formData.append('nonce', nonce);
        formData.append('csv_file', fileInput.files[0]);

        $('#caio_upload_csv_btn').prop('disabled', true);
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#caio_upload_csv_btn').prop('disabled', false);
                $(fileInput).val(''); // Reset file input
                if(res.success) {
                    showMessage(res.data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(res.data.message || texts.error, true);
                }
            },
            error: function() {
                $('#caio_upload_csv_btn').prop('disabled', false);
                $(fileInput).val(''); // Reset file input
                showMessage(texts.error, true);
            }
        });
    });

    // Delete Single IP
    $('.caio-delete-btn').on('click', function() {
        if(!confirm(texts.confirm_delete)) return;
        
        const ip = $(this).data('ip');
        const row = $(this).closest('tr');

        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'delete_ips',
            nonce: nonce,
            ips: [ip]
        }, function(res) {
            if(res.success) {
                row.fadeOut(function() { $(this).remove(); });
                showMessage(res.data.message);
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Bulk Delete
    $('#cb-select-all').on('change', function() {
        $('#the-list input[type="checkbox"]').prop('checked', $(this).is(':checked'));
        toggleBulkDeleteBtn();
    });

    $('#the-list').on('change', 'input[type="checkbox"]', function() {
        toggleBulkDeleteBtn();
    });

    function toggleBulkDeleteBtn() {
        if($('#the-list input[type="checkbox"]:checked').length > 0) {
            $('#caio_bulk_delete_btn').show();
        } else {
            $('#caio_bulk_delete_btn').hide();
            $('#cb-select-all').prop('checked', false);
        }
    }

    $('#caio_bulk_delete_btn').on('click', function() {
        if(!confirm(texts.confirm_delete)) return;

        const ips = [];
        $('#the-list input[type="checkbox"]:checked').each(function() {
            ips.push($(this).val());
        });

        if(ips.length === 0) return;

        $(this).prop('disabled', true);
        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'delete_ips',
            nonce: nonce,
            ips: ips
        }, function(res) {
            $('#caio_bulk_delete_btn').prop('disabled', false).hide();
            if(res.success) {
                showMessage(res.data.message);
                setTimeout(() => location.reload(), 500);
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Client-side Search
    $('#caio_search_ip').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('#the-list tr:not(.no-items)').each(function() {
            const ipText = $(this).find('.ip-col').text().toLowerCase();
            const noteText = $(this).find('.note-col').text().toLowerCase();
            if(ipText.indexOf(val) > -1 || noteText.indexOf(val) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Client-side Sort
    $('#caio_ips_table th.sortable a').on('click', function(e) {
        e.preventDefault();
        const th = $(this).parent();
        const sortType = th.data('sort'); // 'ip' or 'date'
        let isAsc = th.hasClass('asc');
        
        // Reset all sort classes
        $('#caio_ips_table th.sortable').removeClass('asc desc');
        
        // Toggle new sort
        isAsc = !isAsc;
        th.addClass(isAsc ? 'asc' : 'desc');

        const rows = $('#the-list tr:not(.no-items)').get();
        rows.sort(function(a, b) {
            let valA = $(a).find(sortType === 'ip' ? '.ip-col' : '.date-col').text().trim().toLowerCase();
            let valB = $(b).find(sortType === 'ip' ? '.ip-col' : '.date-col').text().trim().toLowerCase();
            
            if(valA < valB) return isAsc ? -1 : 1;
            if(valA > valB) return isAsc ? 1 : -1;
            return 0;
        });

        $.each(rows, function(index, row) {
            $('#the-list').append(row);
        });
    });
});
