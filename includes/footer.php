<?php
require_once __DIR__ . '/../config/config.php';
?>

<!-- Footer -->
<footer class="site-footer">
    <div class="container px-3 px-lg-5">
        <div class="row g-4 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <div class="d-flex align-items-center gap-2 mb-2 justify-content-center justify-content-md-start flex-wrap">
                    <span class="brand-icon-box" style="width:28px; height:28px; font-size:0.9rem;">
                        <i class="bi bi-buildings"></i>
                    </span>
                    <strong class="text-dark font-monospace"><?= APP_NAME ?></strong>
                    <span class="text-muted">| <?= APP_TITLE ?></span>
                </div>
                <p class="small text-muted mb-0">
                    Developed by <strong>Rahil Shah</strong> | HostelHub Management System
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="small text-muted mb-1">
                    &copy; <?= date('Y') ?> HostelHub. All rights reserved.
                </p>
                <div class="d-flex gap-3 justify-content-center justify-content-md-end small">
                    <a href="<?= BASE_URL ?>/index.php#features" class="text-decoration-none text-muted">Features</a>
                    <a href="<?= BASE_URL ?>/index.php#about" class="text-decoration-none text-muted">About</a>
                    <a href="<?= BASE_URL ?>/index.php#contact" class="text-decoration-none text-muted">Support</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom App Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
