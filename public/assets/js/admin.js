// public/assets/js/admin.js - Administrator AJAX Operations

$(document).ready(function () {
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    function notify(selector, msg) {
        $(selector).removeClass('d-none alert-success bg-success-subtle text-success').addClass('bg-success-subtle text-success').html(msg).fadeIn().delay(3000).fadeOut();
    }

    // 1. Claims Table Tab Filter
    $('#claimFilterButtons button').on('click', function () {
        $('#claimFilterButtons button').removeClass('active');
        $(this).addClass('active');
        const st = $(this).data('claim-status');
        $('.claim-row').each(function () {
            $(this).toggle(st === 'all' || $(this).data('status') === st);
        });
    });

    // 2. Open Decision Modal
    $('.open-decision-modal-btn').on('click', function () {
        $('#modal_claim_id').val($(this).data('claim-id'));
        $('#modal_item_title').text($(this).data('item-title'));
        $('#modal_claimant_name').text($(this).data('claimant'));
        $('#modal_proof_text').text($(this).data('proof'));
        $('#modal_admin_notes').val($(this).data('notes'));
        new bootstrap.Modal(document.getElementById('decisionModal')).show();
    });

    // 3. Submit Claim Decision
    $('.admin-submit-decision-btn').on('click', function () {
        const decision = $(this).data('decision');
        const id = $('#modal_claim_id').val();
        const notes = $('#modal_admin_notes').val();

        $.post(baseUrl + '/api/admin-claim-action.php', { claim_id: id, decision: decision, admin_notes: notes }, function (res) {
            if (res.success) {
                const $row = $('#claim-row-' + id);
                $row.data('status', decision);
                $row.find('.claim-status-badge').text(decision).attr('class', 'badge claim-status-badge ' + (decision === 'Approved' ? 'bg-success' : 'bg-danger'));
                bootstrap.Modal.getInstance(document.getElementById('decisionModal'))?.hide();
                notify('#admin-claim-alert', res.message);
            } else { alert(res.message); }
        }, 'json');
    });

    // 4. Item Status Dropdown (In-place AJAX)
    $('.admin-status-select').on('change', function () {
        const id = $(this).data('item-id');
        const st = $(this).val();
        $.post(baseUrl + '/api/update-status.php', { item_id: id, status: st }, function (res) {
            notify('#admin-item-alert', res.message);
        }, 'json');
    });

    // 5. Delete Item
    $('.admin-delete-item-btn').on('click', function () {
        const id = $(this).data('item-id');
        if (confirm('Permanently delete item #' + id + '?')) {
            $.post(baseUrl + '/api/admin-item-delete.php', { item_id: id }, function (res) {
                if (res.success) $('#item-row-' + id).fadeOut();
            }, 'json');
        }
    });

    // 6. User Role Toggle
    $('.admin-role-select').on('change', function () {
        const id = $(this).data('user-id');
        const role = $(this).val();
        $.post(baseUrl + '/api/admin-user-role.php', { user_id: id, role: role }, function (res) {
            if (res.success) {
                $('#user-badge-' + id).text(role.toUpperCase()).attr('class', 'badge ' + (role === 'admin' ? 'bg-primary' : 'bg-secondary'));
                notify('#admin-user-alert', res.message);
            }
        }, 'json');
    });

    // 7. Quick Item Search
    $('#adminItemSearchInput').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('.item-table-row').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) > -1);
        });
    });
});
