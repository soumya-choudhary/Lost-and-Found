// public/assets/js/auth.js - jQuery Authentication Validation & UI Helpers

$(document).ready(function () {
    // 1. Password Visibility Toggle
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

    // 2. Client-side Real-time Validation for Register Form
    const $regForm = $('#registerForm');
    if ($regForm.length) {
        const $password = $('#reg_password');
        const $confirm = $('#reg_confirm');
        const $feedback = $('#password-match-feedback');

        function checkPasswordMatch() {
            const pass = $password.val();
            const conf = $confirm.val();

            if (!conf) {
                $feedback.text('').removeClass('text-danger text-success');
                return;
            }

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

        $regForm.on('submit', function (e) {
            const pass = $password.val();
            const conf = $confirm.val();

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

    // 3. Quick-fill Demo Credentials
    $('.fill-demo-btn').on('click', function () {
        const role = $(this).data('role');
        if (role === 'admin') {
            $('#login_email').val('admin@campus.edu');
            $('#login_password').val('admin123');
        } else if (role === 'student') {
            $('#login_email').val('student@campus.edu');
            $('#login_password').val('admin123');
        }
    });
});

