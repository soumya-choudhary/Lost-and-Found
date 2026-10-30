// public/assets/js/items.js - Front-End AJAX Controller for Items, Catalog & Claims
//
// WHAT THIS JQUERY SCRIPT DOES:
// 1. Module 1: Live Catalog Search & Multi-Criteria Filtering (Debounced)
//    - Listens to typing in #filter_query and change events on category/status dropdowns.
//    - Sends GET request to api/items.php and replaces #items-grid without reloading the page.
// 2. Module 2: Single-Click AJAX Status Control (Item Details)
//    - Listens to .status-toggle-btn clicks on item-details.php.
//    - Sends POST to api/update-status.php, updates the badge, button states, and prepends to audit log.
// 3. Module 3: AJAX Ownership Claim Submission
//    - Intercepts #claimSubmitForm submission.
//    - Sends FormData (with optional image file) to api/claims.php.
//    - Auto-dismisses modal on success and updates status badge.
// 4. Module 4: Click Entire Item Card
//    - Clicking anywhere on an item card navigates to item-details.php (ignoring button clicks).
// 5. Module 5: Reporter / Admin Claim Decision Actions
//    - Listens to .user-claim-decision-btn clicks (Accept / Reject) in the received claims section.
//    - Prompts for an optional note and sends POST to api/admin-claim-action.php.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO CHANGE DEBOUNCE DELAY: In searchTimer setTimeout below, edit 200 (milliseconds).
// - TO ADD A NEW FILTER PARAMETER: In filterItems(), add the new field to the GET params object.

$(document).ready(function () {
    // Dynamic Base URL calculated from the navbar brand link
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    // =========================================================================
    // 1. LIVE CATALOG SEARCH & MULTI-CRITERIA FILTERING (Debounced AJAX)
    // =========================================================================
    let searchTimer = null;

    function filterItems() {
        $('#loading-indicator').removeClass('d-none');
        
        // Collect current filter values
        const params = {
            q: $('#filter_query').val() || '',
            type: $('input[name="type"]:checked').val() || '',
            category: $('#filter_category').val() || '',
            status: $('#filter_status').val() || ''
        };

        // Query api/items.php
        $.getJSON(baseUrl + '/api/items.php', params, function (res) {
            $('#loading-indicator').addClass('d-none');
            if (res.success) {
                // Dynamically replace grid with server-rendered matching item cards
                $('#items-grid').html(res.html);
                $('#results-count-badge').text(res.count);
            }
        }).fail(function () {
            $('#loading-indicator').addClass('d-none');
        });
    }

    // Debounce typing (200ms delay) to prevent sending unnecessary requests while user types
    $('#filter_query').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(filterItems, 200);
    });

    // Instant filter trigger on radio or dropdown change
    $('.filter-type-radio, #filter_category, #filter_status').on('change', filterItems);

    // Reset button
    $('#clear-all-filters-btn').on('click', function () {
        $('#filter_query').val('');
        $('#type_all').prop('checked', true);
        $('#filter_category, #filter_status').val('');
        filterItems();
    });

    // =========================================================================
    // 2. SINGLE-CLICK AJAX STATUS CONTROL (item-details.php)
    // =========================================================================
    $('.status-toggle-btn').on('click', function () {
        const $btn = $(this);
        const itemId = $btn.data('item-id');
        const status = $btn.data('status');
        $('#status-update-spinner').show();

        $.post(baseUrl + '/api/update-status.php', { item_id: itemId, status: status }, function (res) {
            $('#status-update-spinner').hide();
            if (res.success) {
                // Toggle active button style
                $('.status-toggle-btn').removeClass('active');
                $btn.addClass('active');

                // Update detail status badge
                $('#detail-status-badge').text(status)
                    .removeClass('badge-status-open badge-status-claim-requested badge-status-resolved')
                    .addClass(status === 'Open' ? 'badge-status-open' : (status === 'Resolved' ? 'badge-status-resolved' : 'badge-status-claim-requested'));

                // Prepend new activity entry to audit log
                $('#activity-log-list').prepend('<div class="list-group-item py-1 bg-success-subtle"><strong>Status Updated</strong>: ' + status + '</div>');
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    // =========================================================================
    // 3. AJAX OWNERSHIP CLAIM SUBMISSION (Modal in item-details.php)
    // =========================================================================
    $('#claimSubmitForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#submit-claim-btn').prop('disabled', true);
        const $fb = $('#claim-feedback').addClass('d-none');

        // FormData handles both text inputs and file uploads
        $.ajax({
            url: baseUrl + '/api/claims.php',
            type: 'POST',
            data: new FormData(this),
            processData: false, 
            contentType: false, 
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false);
                $fb.removeClass('d-none').toggleClass('alert-success', res.success).toggleClass('alert-danger', !res.success).text(res.message);
                if (res.success) {
                    $('#detail-status-badge').text('Claim Requested');
                    // Auto-hide modal after 1.5 seconds
                    setTimeout(() => { 
                        bootstrap.Modal.getInstance(document.getElementById('claimModal'))?.hide(); 
                    }, 1500);
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                $fb.removeClass('d-none').addClass('alert-danger').text('Server error submitting claim.');
            }
        });
    });

    // =========================================================================
    // 4. CLICK ENTIRE CARD NAVIGATION
    // =========================================================================
    $(document).on('click', '.item-card', function (e) {
        // Ignore clicks on child buttons or links
        if (!$(e.target).closest('a, button').length) {
            window.location.href = $(this).data('href');
        }
    });

    // =========================================================================
    // 5. REPORTER / ADMIN CLAIM DECISION (item-details.php received claims)
    // =========================================================================
    $(document).on('click', '.user-claim-decision-btn', function () {
        const $btn = $(this);
        const claimId = $btn.data('claim-id');
        const decision = $btn.data('decision');
        const note = prompt('Add optional note for the claimant (e.g. proof verified):');
        if (note === null) return; // User pressed Cancel

        $.post(baseUrl + '/api/admin-claim-action.php', { claim_id: claimId, decision: decision, admin_notes: note }, function (res) {
            if (res.success) {
                const $c = $('#claim-card-' + claimId);
                // Update badge color: green for Approved, red for Rejected
                $c.find('.badge').text(decision).attr('class', 'badge ' + (decision === 'Approved' ? 'bg-success' : 'bg-danger'));
                // Remove decision buttons once decided
                $c.find('.user-claim-decision-btn').parent().remove();
                // If approved, update item status badge to Resolved
                if (decision === 'Approved') $('#detail-status-badge').text('Resolved');
            } else { 
                alert(res.message); 
            }
        }, 'json');
    });
});
