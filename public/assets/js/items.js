// public/assets/js/items.js - Front-End Interactions for Items & Claims
$(document).ready(function () {
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    // 1. Live Catalog Search & Filtering (Debounced)
    let timer = null;
    function filterItems() {
        $('#loading-indicator').removeClass('d-none');
        const params = {
            q: $('#filter_query').val() || '',
            type: $('input[name="type"]:checked').val() || '',
            category: $('#filter_category').val() || '',
            status: $('#filter_status').val() || ''
        };
        $.getJSON(baseUrl + '/api/items.php', params, function (res) {
            $('#loading-indicator').addClass('d-none');
            if (res.success) {
                $('#items-grid').html(res.html);
                $('#results-count-badge').text(res.count);
            }
        });
    }

    $('#filter_query').on('input', function () {
        clearTimeout(timer);
        timer = setTimeout(filterItems, 250);
    });
    $('.filter-type-radio, #filter_category, #filter_status').on('change', filterItems);
    $('#clear-all-filters-btn').on('click', function () {
        $('#filter_query').val('');
        $('#type_all').prop('checked', true);
        $('#filter_category, #filter_status').val('');
        filterItems();
    });

    // 2. Click Anywhere on Item Card
    $(document).on('click', '.item-card', function (e) {
        if (!$(e.target).closest('a, button').length && $(this).data('href')) {
            window.location.href = $(this).data('href');
        }
    });

    // 3. Single-Click AJAX Status Control (item-details.php)
    $('.status-toggle-btn').on('click', function () {
        const $btn = $(this), itemId = $btn.data('item-id'), status = $btn.data('status');
        $('#status-update-spinner').show();
        $.post(baseUrl + '/api/update-status.php', { item_id: itemId, status: status }, function (res) {
            $('#status-update-spinner').hide();
            if (res.success) {
                $('.status-toggle-btn').removeClass('active');
                $btn.addClass('active');
                $('#detail-status-badge').text(status)
                    .removeClass('badge-status-open badge-status-claim-requested badge-status-resolved')
                    .addClass(status === 'Open' ? 'badge-status-open' : (status === 'Resolved' ? 'badge-status-resolved' : 'badge-status-claim-requested'));
                $('#activity-log-list').prepend('<div class="list-group-item py-1 bg-success-subtle"><strong>Status:</strong> ' + status + '</div>');
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    // 4. AJAX Ownership Claim Submission
    $('#claimSubmitForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#submit-claim-btn').prop('disabled', true).text('Submitting...');
        $.ajax({
            url: baseUrl + '/api/claims.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).text('Submit Claim');
                if (res.success) {
                    alert(res.message);
                    bootstrap.Modal.getInstance(document.getElementById('claimModal'))?.hide();
                    location.reload();
                } else {
                    $('#claim-feedback').removeClass('d-none alert-success').addClass('alert-danger').text(res.message);
                }
            }
        });
    });

    // 5. Reporter/Owner Claim Decision (Accept / Reject)
    $('.user-claim-decision-btn').on('click', function () {
        const cid = $(this).data('claim-id'), decision = $(this).data('decision');
        const notes = prompt('Enter a note for ' + decision + ' (optional):', '') ?? '';
        $.post(baseUrl + '/api/admin-claim-action.php', { claim_id: cid, decision: decision, admin_notes: notes }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    });
});
