// public/assets/js/admin.js - Admin Panel Operations
//
// WHAT THIS SCRIPT DOES:
// 1. Claims Filter Tabs: Client-side instant show/hide of table rows by claim status (All, Pending, Approved, Rejected).
// 2. Open Decision Modal: Extracts data attributes from the clicked claim row and populates #decisionModal.
// 3. Submit Claim Decision: Sends AJAX POST to /api/admin-claim-action.php with Approve/Reject decision and updates the row in-place.
// 4. In-Place Item Status Dropdown: Triggers /api/update-status.php on change without page reload.
// 5. Delete Item: Confirms with user, calls /api/admin-item-delete.php, and fades out the row.
// 6. User Role Toggle: Calls /api/admin-user-role.php to promote/demote users.
// 7. Quick Table Search: Instant live filtering of items table by matching typed text against row content.

$(document).ready(function () {
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    // =========================================================================
    // 1. Claims Filter Tabs (admin/claims.php)
    // =========================================================================
    $('#claimFilterButtons button').on('click', function () {
        $('#claimFilterButtons button').removeClass('active');
        $(this).addClass('active');

        const st = $(this).data('claim-status');
        // Toggle visibility of each claim row based on data-status attribute
        $('.claim-row').each(function () {
            $(this).toggle(st === 'all' || $(this).data('status') === st);
        });
    });

    // =========================================================================
    // 2. Open Decision Modal
    // =========================================================================
    $('.open-decision-modal-btn').on('click', function () {
        $('#modal_claim_id').val($(this).data('claim-id'));
        $('#modal_item_title').text($(this).data('item-title'));
        $('#modal_claimant_name').text($(this).data('claimant'));
        $('#modal_proof_text').text($(this).data('proof'));
        $('#modal_admin_notes').val($(this).data('notes') || '');

        new bootstrap.Modal(document.getElementById('decisionModal')).show();
    });

    // =========================================================================
    // 3. Submit Claim Decision (Approve / Reject)
    // =========================================================================
    $('.admin-submit-decision-btn').on('click', function () {
        const decision = $(this).data('decision');
        const id       = $('#modal_claim_id').val();
        const notes    = $('#modal_admin_notes').val();

        $.post(baseUrl + '/api/admin-claim-action.php', { claim_id: id, decision: decision, admin_notes: notes }, function (res) {
            if (res.success) {
                // Update table row in place without full reload
                const $row = $('#claim-row-' + id);
                $row.data('status', decision);
                $row.find('.claim-status-badge')
                    .text(decision)
                    .attr('class', 'badge claim-status-badge ' + (decision === 'Approved' ? 'bg-success' : 'bg-danger'));

                bootstrap.Modal.getInstance(document.getElementById('decisionModal'))?.hide();
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    // =========================================================================
    // 4. In-Place Item Status Dropdown (admin/items.php)
    // =========================================================================
    $('.admin-status-select').on('change', function () {
        const id = $(this).data('item-id');
        const st = $(this).val();

        $.post(baseUrl + '/api/update-status.php', { item_id: id, status: st }, function (res) {
            if (!res.success) {
                alert(res.message);
            }
        }, 'json');
    });

    // =========================================================================
    // 5. Delete Item with Confirmation Dialog
    // =========================================================================
    $('.admin-delete-item-btn').on('click', function () {
        const id = $(this).data('item-id');
        if (confirm('Permanently delete item #' + id + '? (All associated claims and activity logs will also be removed)')) {
            $.post(baseUrl + '/api/admin-item-delete.php', { item_id: id }, function (res) {
                if (res.success) {
                    $('#item-row-' + id).fadeOut();
                } else {
                    alert(res.message);
                }
            }, 'json');
        }
    });

    // =========================================================================
    // 6. User Role Toggle (admin/users.php)
    // =========================================================================
    $('.admin-role-select').on('change', function () {
        const id   = $(this).data('user-id');
        const role = $(this).val();

        $.post(baseUrl + '/api/admin-user-role.php', { user_id: id, role: role }, function (res) {
            if (res.success) {
                $('#user-badge-' + id)
                    .text(role.toUpperCase())
                    .attr('class', 'badge ' + (role === 'admin' ? 'bg-primary' : 'bg-secondary'));
            } else {
                alert(res.message);
                location.reload();
            }
        }, 'json');
    });

    // =========================================================================
    // 7. Quick Client-Side Table Search (admin/items.php)
    // =========================================================================
    $('#adminItemSearchInput').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        $('.item-table-row').each(function () {
            $(this).toggle(q === '' || $(this).text().toLowerCase().indexOf(q) > -1);
        });
    });
});
