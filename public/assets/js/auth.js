// public/assets/js/auth.js - Authentication, Form Validation & Demo Credentials

$(document).ready(function () {
    // =========================================================================
    // 1. PASSWORD VISIBILITY TOGGLE
    // Click eye icon to switch password input between hidden and visible text.
    // =========================================================================
    $(document).on('click', '.toggle-password-btn', function () {
        const targetId = $(this).data('target');
        const input = $(targetId);
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // =========================================================================
    // 2. REAL-TIME REGISTRATION PASSWORD MATCH VALIDATION
    // Compares password and confirm_password fields as user types.
    // =========================================================================
    const $regForm = $('#registerForm');
    if ($regForm.length) {
        const $password = $('#reg_password, #password');
        const $confirm = $('#reg_confirm, #confirm_password');
        const $feedback = $('#password-match-feedback');

        function checkPasswordMatch() {
            const pass = $password.val();
            const conf = $confirm.val();

            if (!conf) {
                $feedback.text('').removeClass('text-danger text-success');
                return;
            }

            // TO CHANGE MATCH FEEDBACK: Modify messages or classes below
            if (pass !== conf) {
                $feedback.html('<i class="fa-solid fa-circle-xmark me-1"></i> Passwords do not match.')
                         .removeClass('text-success').addClass('text-danger');
            } else {
                $feedback.html('<i class="fa-solid fa-circle-check me-1"></i> Passwords match.')
                         .removeClass('text-danger').addClass('text-success');
            }
        }

        $password.on('input', checkPasswordMatch);
        $confirm.on('input', checkPasswordMatch);

        // Client-side submit guard
        $regForm.on('submit', function (e) {
            const pass = $password.val();
            const conf = $confirm.val();

            // TO CHANGE MINIMUM PASSWORD LENGTH: Edit 6 below
            if (pass.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                $password.focus();
                return false;
            }

            if (pass !== conf) {
                e.preventDefault();
                alert('Passwords do not match. Please verify.');
                $confirm.focus();
                return false;
            }
        });
    }

    // =========================================================================
    // 3. QUICK-FILL DEMO CREDENTIALS (FOR PRESENTATIONS / TESTING)
    // Supports both #login_email / #login_password and #email / #password
    // =========================================================================
    $(document).on('click', '.fill-demo-btn', function () {
        const role = $(this).data('role');
        const $emailInput = $('#login_email, #email, input[name="email"]');
        const $passInput  = $('#login_password, #password, input[name="password"]');

        // TO CHANGE DEMO CREDENTIALS: Modify email or password below
        if (role === 'admin') {
            $emailInput.val('admin@campus.edu');
            $passInput.val('admin123');
        } else if (role === 'student') {
            $emailInput.val('student@campus.edu');
            $passInput.val('admin123');
        }

        // Highlight inputs briefly so user sees the fill in action
        $emailInput.addClass('bg-light');
        $passInput.addClass('bg-light');
        setTimeout(() => {
            $emailInput.removeClass('bg-light');
            $passInput.removeClass('bg-light');
        }, 500);
    });
});
