<?php
// layouts/footer.php - Global Footer
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
