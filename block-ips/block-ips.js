jQuery(document).ready(function($) {
    const ajax_url = choutAioBlockIps.ajax_url;
    const nonce = choutAioBlockIps.nonce;
    const texts = choutAioBlockIps.texts;

    function showMessage(msg, isError = false) {
        if (typeof caioShowToast === 'function') {
            caioShowToast(msg, isError);
        } else {
            alert(msg);
        }
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

    // =============================================
    // Block History
    // =============================================

    // Select All history rows
    $('#cb-history-select-all').on('change', function() {
        $('#history-list input[type="checkbox"]').prop('checked', $(this).is(':checked'));
        toggleHistoryBulkDeleteBtn();
    });

    $('#history-list').on('change', 'input[type="checkbox"]', function() {
        toggleHistoryBulkDeleteBtn();
    });

    function toggleHistoryBulkDeleteBtn() {
        if ($('#history-list input[type="checkbox"]:checked').length > 0) {
            $('#caio_history_bulk_delete_btn').show();
        } else {
            $('#caio_history_bulk_delete_btn').hide();
            $('#cb-history-select-all').prop('checked', false);
        }
    }

    // Delete single history entry
    $('#history-list').on('click', '.caio-history-delete-btn', function() {
        if (!confirm(texts.confirm_delete_history || 'Are you sure you want to delete this entry?')) return;

        const index = $(this).data('index');
        const row = $(this).closest('tr');

        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'delete_history_entries',
            nonce: nonce,
            indexes: [index]
        }, function(res) {
            if (res.success) {
                row.fadeOut(function() {
                    $(this).remove();
                    if ($('#history-list tr:visible').length === 0) {
                        $('#history-list').html('<tr class="no-items"><td class="colspanchange" colspan="6">No block history yet.</td></tr>');
                        $('#caio_clear_history_btn, #caio_history_bulk_delete_btn').hide();
                        $('.caio-history-badge').remove();
                    }
                });
                showMessage(res.data.message);
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Bulk delete history entries
    $('#caio_history_bulk_delete_btn').on('click', function() {
        if (!confirm(texts.confirm_delete_history || 'Are you sure you want to delete the selected entries?')) return;

        const indexes = [];
        $('#history-list input[type="checkbox"]:checked').each(function() {
            indexes.push(parseInt($(this).val()));
        });

        if (indexes.length === 0) return;

        $(this).prop('disabled', true);
        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'delete_history_entries',
            nonce: nonce,
            indexes: indexes
        }, function(res) {
            $('#caio_history_bulk_delete_btn').prop('disabled', false);
            if (res.success) {
                showMessage(res.data.message);
                setTimeout(() => location.reload(), 500);
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Clear all history
    $('#caio_clear_history_btn').on('click', function() {
        if (!confirm(texts.confirm_clear_history || 'Are you sure you want to clear all block history?')) return;

        $(this).prop('disabled', true);
        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type: 'clear_block_history',
            nonce: nonce
        }, function(res) {
            $('#caio_clear_history_btn').prop('disabled', false);
            if (res.success) {
                showMessage(res.data.message);
                setTimeout(() => location.reload(), 500);
            } else {
                showMessage(res.data.message || texts.error, true);
            }
        });
    });

    // Client-side Search in history table
    $('#caio_search_history').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('#history-list tr:not(.no-items)').each(function() {
            const ipText   = $(this).find('.history-ip-col').text().toLowerCase();
            const urlText  = $(this).find('.caio-url-text').text().toLowerCase();
            const uaText   = $(this).find('.caio-ua-text').text().toLowerCase();
            const dateText = $(this).find('.history-date-col').text().toLowerCase();
            if (ipText.indexOf(val) > -1 || urlText.indexOf(val) > -1 || uaText.indexOf(val) > -1 || dateText.indexOf(val) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // =============================================
    // Auto-refresh Block History (every 5 seconds)
    // =============================================

    // Fast HTML escaping function instead of using jQuery
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return String(unsafe)
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // Build a single table row HTML from a history entry object
    function buildHistoryRow(index, entry) {
        const ip      = escapeHtml(entry.ip);
        const date    = escapeHtml(entry.date);
        const url     = escapeHtml(entry.url);
        const urlAttr = escapeHtml(entry.url); // already escaped
        const ua      = escapeHtml(entry.user_agent);
        const delLabel = texts.delete_label || 'Delete';
        return `<tr class="hide" data-index="${index}">
            <th scope="row" class="check-column">
                <input type="checkbox" name="history_index[]" value="${index}">
            </th>
            <td class="history-ip-col" title="${ip}">${ip}</td>
            <td class="history-date-col">${date}</td>
            <td class="history-url-col">
                <span class="hide-text" hidden>
                    <button type="button" class="caio-ua-toggle" title="Show User Agent" aria-expanded="false">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                    <span class="caio-url-text hide" hidden title="${urlAttr}">
                        <span><strong>URL:</strong><br></span>
                        ${url}
                    </span>
                    <span class="caio-ua-text hide" hidden>
                        <strong>User Agent:</strong><br>
                        ${ua}
                    </span>
                </span>
            </td>
            <td>
                <button type="button" class="button button-secondary caio-history-delete-btn" data-index="${index}">${delLabel}</button>
            </td>
        </tr>`;
    }

    // Toggle User Agent visibility
    $('#history-list').on('click', '.caio-ua-toggle', function() {
        const $btn  = $(this);
        const $text = $btn.siblings('.hide');
        const expanded = $btn.attr('aria-expanded') === 'true';

        if (expanded) {
            $btn.parent().parent().parent().addClass('hide');
            $text.prop('hidden', true);
            $btn.attr('aria-expanded', 'false')
                .find('.dashicons')
                .removeClass('dashicons-hidden')
                .addClass('dashicons-visibility');
            
        } else {
            $btn.parent().parent().parent().removeClass('hide');
            $text.prop('hidden', false);
            $btn.attr('aria-expanded', 'true')
                .find('.dashicons')
                .removeClass('dashicons-visibility')
                .addClass('dashicons-hidden');
        }
    });

    // Hash to track history state (uses total count + first entry's IP and Date)
    let lastHistoryHash = '';

    function isUserInteractingWithHistory() {
        // Pause polling if user is searching or has rows checked
        const hasSearch   = $('#caio_search_history').val().trim() !== '';
        const hasChecked  = $('#history-list input[type="checkbox"]:checked').length > 0;
        return hasSearch || hasChecked;
    }

    const POLL_INTERVAL = 10000; // 10 seconds
    let pollTimer = null;

    function schedulePoll() {
        pollTimer = setTimeout(function() {
            // Skip entirely if tab is hidden (Page Visibility API)
            if (document.visibilityState === 'hidden') {
                schedulePoll();
                return;
            }
            refreshHistory();
        }, POLL_INTERVAL);
    }

    function refreshHistory() {
        if (isUserInteractingWithHistory() || document.visibilityState === 'hidden') {
            schedulePoll();
            return;
        }

        $.post(ajax_url, {
            action: 'chout_aio_block_ips_action',
            type:   'get_block_history',
            nonce:  nonce
        }, function(res) {
            if (res.success) {
                const history = res.data.history;
                const count   = history ? history.length : 0;
                
                // Determine new hash based on count and the first record's properties
                let newHash = count + '-';
                if (count > 0 && history[0]) {
                    newHash += history[0].ip + '-' + history[0].date;
                }

                if (newHash !== lastHistoryHash) {
                    lastHistoryHash = newHash;

                    if (count > 0) {
                        if ($('.caio-history-badge').length) {
                            $('.caio-history-badge').text(count);
                        } else {
                            $('#caio-history-card h2').append(`<span class="caio-history-badge">${count}</span>`);
                        }
                    } else {
                        $('.caio-history-badge').remove();
                    }

                    if (!history || count === 0) {
                        $('#history-list').html('<tr class="no-items"><td class="colspanchange" colspan="6">No block history yet.</td></tr>');
                        $('#caio_clear_history_btn').hide();
                        $('#caio_history_bulk_delete_btn').hide();
                        $('#cb-history-select-all').prop('checked', false);
                    } else {
                        // Use array mapping for performance
                        const rows = history.map((entry, i) => buildHistoryRow(i, entry));
                        $('#history-list').html(rows.join(''));

                        if ($('#caio_clear_history_btn').length === 0) {
                            $('#caio_history_bulk_delete_btn').before(
                                `<button type="button" id="caio_clear_history_btn" class="button button-secondary caio-clear-history-btn">Clear All</button>`
                            );
                        }
                        $('#caio_clear_history_btn').show();

                        const searchVal = $('#caio_search_history').val().toLowerCase();
                        if (searchVal) {
                            $('#history-list tr:not(.no-items)').each(function() {
                                const match = $(this).text().toLowerCase().indexOf(searchVal) > -1;
                                $(this).toggle(match);
                            });
                        }
                    }
                }
            }
        }).always(function() {
            // Schedule next poll ONLY after this one finishes
            schedulePoll();
        });
    }

    // Resume polling when tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            clearTimeout(pollTimer);
            refreshHistory();
        }
    });

    // Initialize hash from initial HTML to prevent first-load flash
    const initCount = $('#history-list tr:not(.no-items)').length;
    if (initCount > 0) {
        const $firstRow = $('#history-list tr:not(.no-items)').first();
        const initIp = $firstRow.find('.history-ip-col').text();
        const initDate = $firstRow.find('.history-date-col').text();
        lastHistoryHash = initCount + '-' + initIp + '-' + initDate;
    } else {
        lastHistoryHash = '0-';
    }

    // Kick off first poll
    schedulePoll();
});
