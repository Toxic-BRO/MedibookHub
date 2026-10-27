<?php
declare(strict_types=1);

$layout = $layout ?? 'public';
?>

<?php if ($layout === 'dashboard'): ?>
        </main>
    </div>
</div>

<?php else: ?>
</main>

<footer class="mb-footer mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="mb-brand-mark" style="width:28px;height:28px;border-radius:8px"><?= icon('heart', 15) ?></span>
            <span class="fw-bold text-ink"><?= e(APP_NAME) ?></span>
            <span class="text-faint">·</span>
            <span>Book directly. No middleman.</span>
        </div>
        <div class="mb-footer-links d-flex align-items-center gap-3">
            <a class="text-muted" href="<?= e(url('index.php')) ?>">Find a doctor</a>
            <a class="text-muted" href="<?= e(url('auth/register.php')) ?>">For doctors</a>
            <div class="team-credit" aria-label="Website designed and built by Shan, Zannat, Amit, and Digonto">
                <span class="team-credit-label">Designed &amp; built by</span>
                <strong class="team-credit-names">Shan <i>·</i> Zannat <i>·</i> Amit <i>·</i> Digonto</strong>
            </div>
            <span class="text-faint">© <?= date('Y') ?></span>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="<?= e(url('assets/vendor/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
