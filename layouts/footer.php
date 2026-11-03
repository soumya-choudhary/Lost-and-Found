<?php
// layouts/footer.php - Global Page Footer & JavaScript Loader
//
// WHAT THIS FILE DOES:
// 1. Closes the main HTML content wrapper `<main>`.
// 2. Renders the copyright footer with dynamic year (`date('Y')`).
// 3. Imports jQuery 3.7.1 and Bootstrap 5.3.3 JavaScript bundle.
// 4. Imports public/assets/js/auth.js (for password toggle and demo buttons).
// 5. Outputs any page-specific extra scripts passed via `$extraScripts` (e.g. items.js or admin.js).
// 6. Closes the `<body>` and `<html>` tags.
?>
    </main>

    <footer class="bg-white border-top py-3 mt-4 text-center text-muted small">
        <div class="container">
            <?= sanitize(APP_NAME) ?> &copy; <?= date('Y') ?> &bull; Campus Lost &amp; Found System
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('public/assets/js/auth.js') ?>"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
