// public/assets/js/auth.js - Auth Helpers & Demo Login
$(document).ready(function () {
    // 1. Password Visibility Toggle
    $(document).on('click', '.toggle-password-btn', function () {
        const input = $($(this).data('target'));
        const isPass = input.attr('type') === 'password';
        input.attr('type', isPass ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // 2. Real-Time Password Match Feedback
    $('#reg_password, #reg_confirm').on('input', function () {
        const p = $('#reg_password').val(), c = $('#reg_confirm').val();
        if (!c) { $('#password-match-feedback').text(''); return; }
        const match = p === c;
        $('#password-match-feedback')
            .text(match ? 'Passwords match.' : 'Passwords do not match.')
            .attr('class', 'small mt-1 ' + (match ? 'text-success' : 'text-danger'));
    });

    // 3. Demo Login Quick-Fill
    $(document).on('click', '.fill-demo-btn', function () {
        const role = $(this).data('role');
        const email = role === 'admin' ? 'admin@campus.edu' : 'student@campus.edu';
        $('#login_email, #email, input[name="email"]').val(email);
        $('#login_password, #password, input[name="password"]').val('admin123');
    });
});
