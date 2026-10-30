// ====================================================================================
// public/assets/js/admin.js - Administrator AJAX Operations & Client-Side Interactivity
// ====================================================================================
// This script powers the administrative interfaces:
// 1. Claims Tab Filtering: In-memory table row filter by status (All, Pending, Approved, Rejected).
// 2. Decision Modal: Populating and opening the modal to approve/reject claims.
// 3. Claim Decision Submission: AJAX POST to api/admin-claim-action.php.
// 4. In-Place Item Status Dropdown: Instant AJAX update via api/update-status.php.
// 5. Item Deletion: Confirmation popup and AJAX delete via api/admin-item-delete.php.
// 6. User Role Toggle: Instant role elevation/demotion via api/admin-user-role.php.
// 7. Live Table Search: Debounced client-side filtering across item table rows.
//
// ------------------------------------------------------------------------------------
// HOW TO MODIFY / PROFESSOR TASKS:
// - TO ADD A CONFIRMATION TO ROLE CHANGES: Wrap block 6 in `if (confirm('Change role to ' + role + '?')) { ... }`
// - TO CHANGE NOTIFICATION AUTO-DISMISS TIME: In `notify()`, adjust `.delay(3000)` to any millisecond value.
// - TO AUTO-RELOAD THE PAGE ON CLAIM DECISION: Replace the DOM badge update with `window.location.reload();`
// - TO REQUIRE AN ADMIN NOTE ON REJECTIONS: In block 3, add `if (decision === 'Rejected' && !notes.trim()) { alert('Note required'); return; }`
// ====================================================================================

$(document).ready(function () {
    // Determine base project URL dynamically from the navbar brand anchor
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace(/\/index\.php$/, '');

    /**
     * Helper to display temporary feedback alerts
     * @param {string} selector - Target element selector (e.g. #admin-claim-alert)
     * @param {string} msg - Message HTML or text to display
     */
    function notify(selector, msg) {
        $(selector)
            .removeClass('d-none alert-success bg-success-subtle text-success')
            .addClass('bg-success-subtle text-success')
            .html(msg)
            .fadeIn()
            .delay(3000) // Adjust delay in milliseconds if professor asks to keep alerts longer
            .fadeOut();
    }

    // ================================================================================
    // 1. CLAIMS TABLE TAB FILTER
    // Filters rows in the claims table by their data-status attribute without reloading.
    // ================================================================================
    $('#claimFilterButtons button').on('click', function () {
        $('#claimFilterButtons button').removeClass('active');
        $(this).addClass('active');

        const filterStatus = $(this).data('claim-status'); // 'all', 'Pending', 'Approved', 'Rejected'
        $('.claim-row').each(function () {
            const rowStatus = $(this).data('status');
            // Show all if 'all' is selected, or if the row's data-status matches the active filter
            $(this).toggle(filterStatus === 'all' || rowStatus === filterStatus);
        });
    });

    // ================================================================================
    // 2. OPEN DECISION MODAL
    // Gathers metadata from button data-* attributes and injects into modal inputs.
    // ================================================================================
    $('.open-decision-modal-btn').on('click', function () {
        $('#modal_claim_id').val($(this).data('claim-id'));
        $('#modal_item_title').text($(this).data('item-title'));
        $('#modal_claimant_name').text($(this).data('claimant'));
        $('#modal_proof_text').text($(this).data('proof'));
        $('#modal_admin_notes').val($(this).data('notes') || '');

        // Launch Bootstrap 5 modal instance
        new bootstrap.Modal(document.getElementById('decisionModal')).show();
    });

    // ================================================================================
    // 3. SUBMIT CLAIM DECISION (Approve / Reject)
    // Sends AJAX POST to api/admin-claim-action.php with decision and review notes.
    // ================================================================================
    $('.admin-submit-decision-btn').on('click', function () {
        const decision = $(this).data('decision'); // 'Approved' or 'Rejected'
        const claimId  = $('#modal_claim_id').val();
        const notes    = $('#modal_admin_notes').val();

        // [OPTIONAL VALIDATION]: If professor asks to enforce review notes for rejections:
        // if (decision === 'Rejected' && !notes.trim()) {
        //     alert('Please provide a reason or note when rejecting a claim.');
        //     return;
        // }

        $.post(baseUrl + '/api/admin-claim-action.php', {
            claim_id: claimId,
            decision: decision,
            admin_notes: notes
        }, function (res) {
            if (res.success) {
                // Update table row DOM in real-time
                const $row = $('#claim-row-' + claimId);
                $row.data('status', decision);

                // Update badge color & text dynamically
                const badgeClass = (decision === 'Approved') ? 'bg-success' : 'bg-danger';
                $row.find('.claim-status-badge')
                    .text(decision)
                    .attr('class', 'badge claim-status-badge ' + badgeClass);

                // Close the modal
                bootstrap.Modal.getInstance(document.getElementById('decisionModal'))?.hide();

                // Show success banner
                notify('#admin-claim-alert', res.message);
            } else {
                alert(res.message);
            }
        }, 'json').fail(function () {
            alert('Server error processing claim decision. Please check your network and session.');
        });
    });

    // ================================================================================
    // 4. ITEM STATUS DROPDOWN (In-place AJAX Status Change)
    // Allows admins to change item status directly from admin/items.php without reloading.
    // ================================================================================
    $('.admin-status-select').on('change', function () {
        const itemId = $(this).data('item-id');
        const newStatus = $(this).val();

        $.post(baseUrl + '/api/update-status.php', {
            item_id: itemId,
            status: newStatus
        }, function (res) {
            if (res.success) {
                notify('#admin-item-alert', res.message);
            } else {
                alert(res.message);
            }
        }, 'json').fail(function () {
            alert('Failed to update item status. Please try again.');
        });
    });

    // ================================================================================
    // 5. DELETE ITEM (AJAX with Confirmation Dialog)
    // Removes an item and all its associated claims & activity logs.
    // ================================================================================
    $('.admin-delete-item-btn').on('click', function () {
        const itemId = $(this).data('item-id');

        // Confirm before permanent deletion
        if (confirm('Are you sure you want to permanently delete item #' + itemId + '? All associated claims will be removed.')) {
            $.post(baseUrl + '/api/admin-item-delete.php', { item_id: itemId }, function (res) {
                if (res.success) {
                    // Smoothly fade out the table row
                    $('#item-row-' + itemId).fadeOut(300, function () {
                        $(this).remove();
                    });
                    notify('#admin-item-alert', res.message);
                } else {
                    alert(res.message);
                }
            }, 'json').fail(function () {
                alert('Server error deleting item.');
            });
        }
    });

    // ================================================================================
    // 6. USER ROLE TOGGLE (Admin <-> Student)
    // Sends AJAX POST to api/admin-user-role.php to elevate or demote user accounts.
    // ================================================================================
    $('.admin-role-select').on('change', function () {
        const userId  = $(this).data('user-id');
        const newRole = $(this).val();

        $.post(baseUrl + '/api/admin-user-role.php', {
            user_id: userId,
            role: newRole
        }, function (res) {
            if (res.success) {
                // Update role badge styling dynamically
                const badgeClass = (newRole === 'admin') ? 'bg-primary' : 'bg-secondary';
                $('#user-badge-' + userId)
                    .text(newRole.toUpperCase())
                    .attr('class', 'badge ' + badgeClass);

                notify('#admin-user-alert', res.message);
            } else {
                alert(res.message);
                // Reset select box back on error (e.g. self-demotion prevention)
                window.location.reload();
            }
        }, 'json').fail(function () {
            alert('Failed to update user role.');
        });
    });

    // ================================================================================
    // 7. QUICK CLIENT-SIDE TABLE SEARCH (Admin Items & Users)
    // Matches text across all table columns in real time as the user types.
    // ================================================================================
    $('#adminItemSearchInput').on('input', function () {
        const query = $(this).val().toLowerCase().trim();

        $('.item-table-row').each(function () {
            const rowText = $(this).text().toLowerCase();
            // Show if query is empty or contained anywhere within the row text
            $(this).toggle(query === '' || rowText.indexOf(query) > -1);
        });
    });
});
