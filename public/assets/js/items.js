// public/assets/js/items.js - AJAX Filter, Live Status & Claims

$(document).ready(function () {
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    // 1. Live Catalog Search & Filter (Debounced)
    let searchTimer = null;
    function filterItems() {
        $('#loading-indicator').removeClass('d-none');
        $.getJSON(baseUrl + '/api/items.php', {
            q: $('#filter_query').val() || '',
            type: $('input[name="type"]:checked').val() || '',
            category: $('#filter_category').val() || '',
            status: $('#filter_status').val() || ''
        }, function (res) {
            $('#loading-indicator').addClass('d-none');
            if (res.success) {
                $('#items-grid').html(res.html);
                $('#results-count-badge').text(res.count);
            }
        });
    }

    $('#filter_query').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(filterItems, 200);
    });
    $('.filter-type-radio, #filter_category, #filter_status').on('change', filterItems);
    $('#clear-all-filters-btn').on('click', function () {
        $('#filter_query').val('');
        $('#type_all').prop('checked', true);
        $('#filter_category, #filter_status').val('');
        filterItems();
    });

    // 2. Single-Click AJAX Status Toggle (Item Details)
    $('.status-toggle-btn').on('click', function () {
        const $btn = $(this);
        const itemId = $btn.data('item-id');
        const status = $btn.data('status');
        $('#status-update-spinner').show();

        $.post(baseUrl + '/api/update-status.php', { item_id: itemId, status: status }, function (res) {
            $('#status-update-spinner').hide();
            if (res.success) {
                $('.status-toggle-btn').removeClass('active');
                $btn.addClass('active');
                $('#detail-status-badge').text(status)
                    .removeClass('badge-status-open badge-status-claim-requested badge-status-resolved')
                    .addClass(status === 'Open' ? 'badge-status-open' : (status === 'Resolved' ? 'badge-status-resolved' : 'badge-status-claim-requested'));
                $('#activity-log-list').prepend('<div class="list-group-item py-1 bg-success-subtle"><strong>Status Updated</strong>: ' + status + '</div>');
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    // 3. AJAX Claim Submission
    $('#claimSubmitForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#submit-claim-btn').prop('disabled', true);
        const $fb = $('#claim-feedback').addClass('d-none');

        $.ajax({
            url: baseUrl + '/api/claims.php',
            type: 'POST',
            data: new FormData(this),
            processData: false, contentType: false, dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false);
                $fb.removeClass('d-none').toggleClass('alert-success', res.success).toggleClass('alert-danger', !res.success).text(res.message);
                if (res.success) {
                    $('#detail-status-badge').text('Claim Requested');
                    setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('claimModal'))?.hide(); }, 1500);
                }
            }
        });
    });

    // 4. Click Entire Card to Open
    $(document).on('click', '.item-card', function (e) {
        if (!$(e.target).closest('a, button').length) {
            window.location.href = $(this).data('href');
        }
    });

    // 5. Reporter/Admin Claim Decision (Accept / Reject)
    $(document).on('click', '.user-claim-decision-btn', function () {
        const $btn = $(this);
        const claimId = $btn.data('claim-id');
        const decision = $btn.data('decision');
        const note = prompt('Optional note for claimant:');
        if (note === null) return;

        $.post(baseUrl + '/api/admin-claim-action.php', { claim_id: claimId, decision: decision, admin_notes: note }, function (res) {
            if (res.success) {
                const $c = $('#claim-card-' + claimId);
                $c.find('.badge').text(decision).attr('class', 'badge ' + (decision === 'Approved' ? 'bg-success' : 'bg-danger'));
                $c.find('.user-claim-decision-btn').parent().remove();
                if (decision === 'Approved') $('#detail-status-badge').text('Resolved');
            } else { alert(res.message); }
        }, 'json');
    });
});
