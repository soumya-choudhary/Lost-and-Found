// public/assets/js/items.js - Live Search/Filtering, Dynamic Status Updates & Claim Submissions

$(document).ready(function () {
    // Determine Base URL dynamically
    const baseUrl = $('nav.navbar a.navbar-brand').attr('href').replace('/index.php', '');

    // ==========================================
    // 1. LIVE SEARCH & FILTERING (Without Reload)
    // ==========================================
    let searchTimeout = null;

    function fetchFilteredItems() {
        $('#loading-indicator').removeClass('d-none');

        const params = {
            q: $('#filter_query').val() || '',
            type: $('input[name="type"]:checked').val() || '',
            category: $('#filter_category').val() || '',
            status: $('#filter_status').val() || '',
            sort: $('#filter_sort').val() || 'newest'
        };

        $.ajax({
            url: baseUrl + '/api/items.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function (res) {
                $('#loading-indicator').addClass('d-none');
                if (res.success) {
                    $('#items-grid').html(res.html);
                    $('#results-count-badge').text(res.count);
                }
            },
            error: function () {
                $('#loading-indicator').addClass('d-none');
                $('#items-grid').html('<div class="col-12 text-center text-danger py-4">Error loading items. Please check database connection.</div>');
            }
        });
    }

    // Debounce search input for snappy, smooth typing without flooding requests
    $('#filter_query').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchFilteredItems, 250);
    });

    // Listen to radio and dropdown changes
    $('.filter-type-radio, #filter_category, #filter_status, #filter_sort').on('change', function () {
        fetchFilteredItems();
    });

    // Clear filters
    $(document).on('click', '#clear-all-filters-btn, #reset-filters-btn', function () {
        $('#filter_query').val('');
        $('#type_all').prop('checked', true);
        $('#filter_category').val('');
        $('#filter_status').val('');
        $('#filter_sort').val('newest');
        fetchFilteredItems();
    });


    // ==========================================
    // 2. LIVE STATUS OPERATION (Without Reload)
    // ==========================================
    $('.status-toggle-btn').on('click', function () {
        const $btn = $(this);
        const itemId = $btn.data('item-id');
        const newStatus = $btn.data('status');
        const $spinner = $('#status-update-spinner');

        $spinner.show();

        $.ajax({
            url: baseUrl + '/api/update-status.php',
            type: 'POST',
            data: {
                item_id: itemId,
                status: newStatus,
                remarks: 'Updated via single-click status control'
            },
            dataType: 'json',
            success: function (res) {
                $spinner.hide();
                if (res.success) {
                    // Update active button state
                    $('.status-toggle-btn').removeClass('active');
                    $btn.addClass('active');

                    // Update Status Badge on the page
                    const $badge = $('#detail-status-badge');
                    $badge.text(newStatus);
                    $badge.removeClass('badge-status-open badge-status-claim-requested badge-status-resolved');

                    if (newStatus === 'Open') {
                        $badge.addClass('badge-status-open');
                    } else if (newStatus === 'Claim Requested') {
                        $badge.addClass('badge-status-claim-requested');
                    } else if (newStatus === 'Resolved') {
                        $badge.addClass('badge-status-resolved');
                    }

                    // Append to audit log
                    const now = new Date();
                    const timeStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' +
                                    now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });

                    const newLogHtml = `
                        <div class="list-group-item py-2 small bg-success-subtle">
                            <div class="d-flex justify-content-between">
                                <strong class="text-success"><i class="fa-solid fa-check me-1"></i> Status Updated</strong>
                                <span class="text-muted">${timeStr}</span>
                            </div>
                            <div class="text-secondary">${res.message}</div>
                        </div>
                    `;
                    $('#activity-log-list').prepend(newLogHtml);
                } else {
                    alert(res.message || 'Failed to update status.');
                }
            },
            error: function () {
                $spinner.hide();
                alert('Server error occurred while updating item status.');
            }
        });
    });


    // ==========================================
    // 3. AJAX CLAIM SUBMISSION (Without Reload)
    // ==========================================
    $('#claimSubmitForm').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $('#submit-claim-btn');
        const $feedback = $('#claim-feedback');
        const formData = new FormData(this);

        $submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...');
        $feedback.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: baseUrl + '/api/claims.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                $submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Submit Claim');

                if (res.success) {
                    $feedback.removeClass('d-none').addClass('alert-success').html(
                        '<i class="fa-solid fa-circle-check me-1"></i> ' + res.message
                    );

                    // Update live badge on item page
                    const $badge = $('#detail-status-badge');
                    $badge.text('Claim Requested').removeClass('badge-status-open badge-status-resolved').addClass('badge-status-claim-requested');

                    // Update status buttons
                    $('.status-toggle-btn').removeClass('active');
                    $('.status-toggle-btn[data-status="Claim Requested"]').addClass('active');

                    // Reset form after delay
                    setTimeout(function () {
                        const modalEl = document.getElementById('claimModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        $feedback.addClass('d-none');
                        $form[0].reset();
                    }, 2000);
                } else {
                    $feedback.removeClass('d-none').addClass('alert-danger').html(
                        '<i class="fa-solid fa-circle-exclamation me-1"></i> ' + res.message
                    );
                }
            },
            error: function () {
                $submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Submit Claim');
                $feedback.removeClass('d-none').addClass('alert-danger').html(
                    '<i class="fa-solid fa-triangle-exclamation me-1"></i> Server error processing claim.'
                );
            }
        });
    });


    // ==========================================
    // 4. IMAGE UPLOAD PREVIEW IN REPORT FORM
    // ==========================================
    const $imageInput = $('#item_image_input');
    if ($imageInput.length) {
        $imageInput.on('change', function () {
            const file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image exceeds 5MB limit. Please choose a smaller image.');
                    $(this).val('');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#image_preview').attr('src', e.target.result);
                    $('#preview_container').removeClass('d-none');
                    $('#upload_prompt').addClass('d-none');
                };
                reader.readAsDataURL(file);
            }
        });

        $('#remove_image_btn').on('click', function () {
            $imageInput.val('');
            $('#image_preview').attr('src', '');
            $('#preview_container').addClass('d-none');
            $('#upload_prompt').removeClass('d-none');
        });
    }

    // ==========================================
    // 5. CLICKING WHOLE ITEM CARD OPENS DETAILS
    // ==========================================
    $(document).on('click', '.item-card', function (e) {
        // Do not navigate if user clicked directly on an interactive child element
        if ($(e.target).closest('a, button, select, input, label').length) {
            return;
        }

        const targetUrl = $(this).data('href') || $(this).find('a[href*="item-details.php"]').attr('href');
        if (targetUrl) {
            window.location.href = targetUrl;
        }
    });

    // ==========================================
    // 6. REPORTER / ADMIN CLAIM DECISION (Without Reload)
    // ==========================================
    $(document).on('click', '.user-claim-decision-btn', function () {
        const $btn = $(this);
        const claimId = $btn.data('claim-id');
        const decision = $btn.data('decision');
        const confirmMsg = (decision === 'Approved') 
            ? 'Are you sure you want to approve this ownership claim? The item will automatically be marked as Resolved.' 
            : 'Are you sure you want to reject this claim?';

        if (!confirm(confirmMsg)) {
            return;
        }

        const defaultNote = (decision === 'Approved') ? 'Ownership verified and confirmed.' : 'Proof did not match item features.';
        const notes = prompt('Add optional note for the claimant:', defaultNote);
        if (notes === null) return; // User pressed Cancel

        $btn.prop('disabled', true);

        $.ajax({
            url: baseUrl + '/api/claim-action.php',
            type: 'POST',
            data: {
                claim_id: claimId,
                decision: decision,
                admin_notes: notes
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    const $card = $('#claim-card-' + claimId);
                    const $badge = $card.find('.claim-card-status-badge');
                    $badge.text(decision).removeClass('bg-warning bg-success bg-danger text-dark text-white');

                    if (decision === 'Approved') {
                        $badge.addClass('bg-success text-white');
                        // Update item status on the page
                        const $itemBadge = $('#detail-status-badge');
                        $itemBadge.text('Resolved').removeClass('badge-status-open badge-status-claim-requested').addClass('badge-status-resolved');
                        $('.status-toggle-btn').removeClass('active');
                        $('.status-toggle-btn[data-status="Resolved"]').addClass('active');
                    } else {
                        $badge.addClass('bg-danger text-white');
                    }

                    // Remove decision buttons after decision
                    $card.find('.user-claim-decision-btn').parent().remove();

                    alert(res.message);
                } else {
                    $btn.prop('disabled', false);
                    alert(res.message || 'Error updating claim.');
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                alert('Server error updating claim decision.');
            }
        });
    });
});

