// public/assets/js/items.js - Front-End Interactions for Items & Claims
//
// WHAT THIS SCRIPT DOES:
// 1. filterItems(): Debounced live catalog search (250ms delay). Sends $.getJSON to /api/items.php
//    and dynamically swaps the HTML of #items-grid without a full page refresh.
// 2. Whole-Card Click: Clicking anywhere on an item card navigates to its detail page.
// 3. Status Control: Sends AJAX POST to /api/update-status.php and updates the status badge in place.
// 4. Claim Submission: Submits claim form via $.ajax with FormData (supports file uploads).
// 5. Claim Decision: Prompts reviewer for an optional note and posts decision to /api/admin-claim-action.php.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "Debouncing prevents flooding the server with queries while the user is actively typing.
// Every keystroke resets a 250ms timer; only after typing pauses does the AJAX request fire."

$(document).ready(function () {
    // Derive base URL dynamically from the navbar brand anchor
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    // =========================================================================
    // 1. Live Catalog Search & Filtering (Debounced)
    // =========================================================================
    let timer = null;

    /**
     * Gathers all active filter inputs and sends an asynchronous request to /api/items.php.
     */
    function filterItems() {
        $('#loading-indicator').removeClass('d-none');
        const params = {
            q: $('#filter_query').val() || '',
            type: $('input[name="type"]:checked').val() || '',
            category: $('#filter_category').val() || '',
            status: $('#filter_status').val() || ''
        };

        // Asynchronous GET request returning JSON: { success, count, html }
        $.getJSON(baseUrl + '/api/items.php', params, function (res) {
            $('#loading-indicator').addClass('d-none');
            if (res.success) {
                // Dynamically replace the grid container with newly rendered cards
                $('#items-grid').html(res.html);
                // Update the counter badge
                $('#results-count-badge').text(res.count);
            }
        });
    }

    // Debounced input handler on keyword search (waits 250ms after last keystroke)
    $('#filter_query').on('input', function () {
        clearTimeout(timer);
        timer = setTimeout(filterItems, 250);
    });

    // Immediate filter trigger on radio button or dropdown select changes
    $('.filter-type-radio, #filter_category, #filter_status').on('change', filterItems);

    // Reset button click handler: clears all inputs and re-runs filter
    $('#clear-all-filters-btn').on('click', function () {
        $('#filter_query').val('');
        $('#type_all').prop('checked', true);
        $('#filter_category, #filter_status').val('');
        filterItems();
    });

    // =========================================================================
    // 2. Click Anywhere on Item Card
    // =========================================================================
    // Delegated click event: navigates to data-href unless a nested button/link was clicked
    $(document).on('click', '.item-card', function (e) {
        if (!$(e.target).closest('a, button').length && $(this).data('href')) {
            window.location.href = $(this).data('href');
        }
    });

    // =========================================================================
    // 3. Single-Click AJAX Status Control (item-details.php)
    // =========================================================================
    // Allows the item reporter or admin to toggle item status instantly without page reload
    $('.status-toggle-btn').on('click', function () {
        const $btn = $(this);
        const itemId = $btn.data('item-id');
        const status = $btn.data('status');

        $('#status-update-spinner').show();

        // AJAX POST to status update endpoint
        $.post(baseUrl + '/api/update-status.php', { item_id: itemId, status: status }, function (res) {
            $('#status-update-spinner').hide();
            if (res.success) {
                // Update active button styling
                $('.status-toggle-btn').removeClass('active');
                $btn.addClass('active');

                // Update status badge text and color class
                $('#detail-status-badge')
                    .text(status)
                    .removeClass('badge-status-open badge-status-claim-requested badge-status-resolved')
                    .addClass(status === 'Open' ? 'badge-status-open' : (status === 'Resolved' ? 'badge-status-resolved' : 'badge-status-claim-requested'));

                // Prepend an entry to the visible activity log list
                $('#activity-log-list').prepend('<div class="list-group-item py-1 bg-success-subtle"><strong>Status:</strong> ' + status + '</div>');
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    // =========================================================================
    // 4. AJAX Ownership Claim Submission (Modal Form)
    // =========================================================================
    $('#claimSubmitForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#submit-claim-btn').prop('disabled', true).text('Submitting...');

        // FormData object serializes form fields and file attachments automatically
        $.ajax({
            url: baseUrl + '/api/claims.php',
            type: 'POST',
            data: new FormData(this),
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).text('Submit Claim');
                if (res.success) {
                    alert(res.message);
                    // Hide modal dialog and reload to reflect new 'Claim Requested' status
                    bootstrap.Modal.getInstance(document.getElementById('claimModal'))?.hide();
                    location.reload();
                } else {
                    $('#claim-feedback')
                        .removeClass('d-none alert-success')
                        .addClass('alert-danger')
                        .text(res.message);
                }
            }
        });
    });

    // =========================================================================
    // 5. Reporter/Owner Claim Decision (Accept / Reject)
    // =========================================================================
    $('.user-claim-decision-btn').on('click', function () {
        const cid = $(this).data('claim-id');
        const decision = $(this).data('decision');
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
