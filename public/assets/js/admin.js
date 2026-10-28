// public/assets/js/admin.js - Administrator AJAX Operations

$(document).ready(function () {
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace('/index.php', '');

    function showBannerAlert(targetId, type, message) {
        const $el = $(targetId);
        $el.removeClass('d-none alert-success alert-danger bg-success-subtle text-success bg-danger-subtle text-danger');
        if (type === 'success') {
            $el.addClass('bg-success-subtle text-success');
        } else {
            $el.addClass('bg-danger-subtle text-danger');
        }
        $el.html(message).fadeIn();
        setTimeout(function () {
            $el.fadeOut();
        }, 4000);
    }

    // ==========================================
    // 1. CLAIMS TAB FILTERING (Client-side fast toggle)
    // ==========================================
    $('#claimFilterButtons button').on('click', function () {
        $('#claimFilterButtons button').removeClass('active');
        $(this).addClass('active');

        const filterStatus = $(this).data('claim-status');

        if (filterStatus === 'all') {
            $('.claim-row').show();
        } else {
            $('.claim-row').each(function () {
                const rowStatus = $(this).data('status');
                if (rowStatus === filterStatus) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // ==========================================
    // 2. ADMIN DECISION MODAL & AJAX SUBMIT
    // ==========================================
    $('.open-decision-modal-btn').on('click', function () {
        const claimId = $(this).data('claim-id');
        const itemTitle = $(this).data('item-title');
        const claimant = $(this).data('claimant');
        const proof = $(this).data('proof');
        const notes = $(this).data('notes');

        $('#modal_claim_id').val(claimId);
        $('#modal_item_title').text(itemTitle);
        $('#modal_claimant_name').text(claimant);
        $('#modal_proof_text').text(proof);
        $('#modal_admin_notes').val(notes);

        const modal = new bootstrap.Modal(document.getElementById('decisionModal'));
        modal.show();
    });

    $('.admin-submit-decision-btn').on('click', function () {
        const decision = $(this).data('decision');
        const claimId = $('#modal_claim_id').val();
        const notes = $('#modal_admin_notes').val();
        const $btn = $(this);

        $btn.prop('disabled', true).prepend('<i class="fa-solid fa-spinner fa-spin me-1"></i> ');

        $.ajax({
            url: baseUrl + '/api/admin-claim-action.php',
            type: 'POST',
            data: {
                claim_id: claimId,
                decision: decision,
                admin_notes: notes
            },
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).find('i.fa-spinner').remove();

                if (res.success) {
                    // Update table row status
                    const $row = $('#claim-row-' + claimId);
                    $row.data('status', decision);
                    const $badge = $row.find('.claim-status-badge');
                    $badge.text(decision).removeClass('bg-warning bg-success bg-danger text-dark text-white');

                    if (decision === 'Approved') {
                        $badge.addClass('bg-success text-white');
                    } else {
                        $badge.addClass('bg-danger text-white');
                    }

                    // Hide modal
                    const modalEl = document.getElementById('decisionModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    showBannerAlert('#admin-claim-alert', 'success', '<i class="fa-solid fa-check me-1"></i> ' + res.message);
                } else {
                    alert(res.message || 'Failed to submit decision.');
                }
            },
            error: function () {
                $btn.prop('disabled', false).find('i.fa-spinner').remove();
                alert('Server error updating claim decision.');
            }
        });
    });

    // ==========================================
    // 3. ADMIN ITEM STATUS DROPDOWN (AJAX without reload)
    // ==========================================
    $('.admin-status-select').on('change', function () {
        const $select = $(this);
        const itemId = $select.data('item-id');
        const newStatus = $select.val();

        $select.prop('disabled', true);

        $.ajax({
            url: baseUrl + '/api/update-status.php',
            type: 'POST',
            data: {
                item_id: itemId,
                status: newStatus,
                remarks: 'Updated by Administrator via inventory table'
            },
            dataType: 'json',
            success: function (res) {
                $select.prop('disabled', false);
                if (res.success) {
                    showBannerAlert('#admin-item-alert', 'success', '<i class="fa-solid fa-check me-1"></i> Item #' + itemId + ' status updated to ' + newStatus);
                } else {
                    alert(res.message || 'Failed to update item status.');
                }
            },
            error: function () {
                $select.prop('disabled', false);
                alert('Server error updating item status.');
            }
        });
    });

    // ==========================================
    // 4. ADMIN DELETE ITEM (AJAX with fadeOut)
    // ==========================================
    $('.admin-delete-item-btn').on('click', function () {
        const itemId = $(this).data('item-id');
        const itemTitle = $(this).data('item-title');

        if (!confirm('Are you sure you want to permanently delete item #' + itemId + ' ("' + itemTitle + '")? This cannot be undone.')) {
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: baseUrl + '/api/admin-item-delete.php',
            type: 'POST',
            data: { item_id: itemId },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#item-row-' + itemId).fadeOut(400, function () {
                        $(this).remove();
                        const remaining = $('.item-table-row').length;
                        $('#admin-item-count').text(remaining + ' items');
                    });
                    showBannerAlert('#admin-item-alert', 'success', '<i class="fa-solid fa-trash me-1"></i> ' + res.message);
                } else {
                    $btn.prop('disabled', false);
                    alert(res.message || 'Failed to delete item.');
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                alert('Server error processing deletion.');
            }
        });
    });

    // ==========================================
    // 5. QUICK SEARCH IN ADMIN INVENTORY
    // ==========================================
    $('#adminItemSearchInput').on('input', function () {
        const query = $(this).val().toLowerCase().trim();
        $('.item-table-row').each(function () {
            const text = $(this).text().toLowerCase();
            if (text.indexOf(query) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // ==========================================
    // 6. TOGGLE USER ROLE (AJAX)
    // ==========================================
    $('.admin-role-select').on('change', function () {
        const $select = $(this);
        const userId = $select.data('user-id');
        const newRole = $select.val();

        $select.prop('disabled', true);

        $.ajax({
            url: baseUrl + '/api/admin-user-role.php',
            type: 'POST',
            data: { user_id: userId, role: newRole },
            dataType: 'json',
            success: function (res) {
                $select.prop('disabled', false);
                if (res.success) {
                    const $badge = $('#user-badge-' + userId);
                    $badge.text(newRole).removeClass('bg-primary bg-secondary');
                    if (newRole === 'admin') {
                        $badge.addClass('bg-primary text-white');
                    } else {
                        $badge.addClass('bg-secondary text-white');
                    }
                    showBannerAlert('#admin-user-alert', 'success', '<i class="fa-solid fa-check me-1"></i> ' + res.message);
                } else {
                    alert(res.message || 'Failed to update user role.');
                }
            },
            error: function () {
                $select.prop('disabled', false);
                alert('Server error changing user role.');
            }
        });
    });
});

