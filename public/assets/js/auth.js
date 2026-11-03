// public/assets/js/auth.js - Authentication Helpers & Quick Demo Login
//
// WHAT THIS SCRIPT DOES:
// 1. Password Visibility Toggle: Switches password input between 'password' and 'text' and toggles eye icon.
// 2. Real-Time Password Match Feedback: Listens to input events on registration password fields and gives instant visual feedback.
// 3. Demo Login Quick-Fill: 1-click button that populates pre-seeded credentials for Admin or Student demos.

$(document).ready(function () {
    // =========================================================================
    // 1. Password Visibility Toggle
    // =========================================================================
    $(document).on('click', '.toggle-password-btn', function () {
        const input = $($(this).data('target'));
        const isPass = input.attr('type') === 'password';
        input.attr('type', isPass ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // =========================================================================
    // 2. Real-Time Password Match Feedback (register.php)
    // =========================================================================
    $('#reg_password, #reg_confirm').on('input', function () {
        const p = $('#reg_password').val();
        const c = $('#reg_confirm').val();

        if (!c) {
            $('#password-match-feedback').text('');
            return;
        }

        const match = p === c;
        $('#password-match-feedback')
            .text(match ? 'Passwords match.' : 'Passwords do not match.')
            .attr('class', 'small mt-1 ' + (match ? 'text-success' : 'text-danger'));
    });

    // =========================================================================
    // 3. Demo Login Quick-Fill (login.php)
    // =========================================================================
    // Allows 1-click credential filling for presentation demos
    $(document).on('click', '.fill-demo-btn', function () {
        const role = $(this).data('role');
        const email = role === 'admin' ? 'admin@campus.edu' : 'student@campus.edu';

        $('#login_email, #email, input[name="email"]').val(email);
        $('#login_password, #password, input[name="password"]').val('admin123');
    });
});
