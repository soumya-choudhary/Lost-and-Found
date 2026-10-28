<?php
// layouts/footer.php
?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-4 mt-auto text-muted">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 fw-medium">
                        <i class="fa-solid fa-graduation-cap me-1 text-primary"></i>
                        <?= sanitize(APP_NAME) ?> &copy; <?= date('Y') ?>
                    </p>
                    <small class="text-secondary">Official Campus Relational Database Lost &amp; Found System</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="d-inline-flex gap-3 small">
                        <a href="<?= url('index.php') ?>" class="text-secondary text-decoration-none hover-primary">Home</a>
                        <span class="text-secondary">&bull;</span>
                        <a href="<?= url('report-item.php') ?>" class="text-secondary text-decoration-none hover-primary">Report an Item</a>
                        <span class="text-secondary">&bull;</span>
                        <a href="<?= url('login.php') ?>" class="text-secondary text-decoration-none hover-primary">Campus Login</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Bootstrap 5.3.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Project JS -->
    <script src="<?= url('public/assets/js/auth.js') ?>"></script>
    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>

