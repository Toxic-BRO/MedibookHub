<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$q         = query('q');
$specialty = query('specialty');
$location  = query('location');

$doctors     = search_doctors($q, $specialty, $location, 60);
$specialties = get_specialties();
$locations   = get_locations();

$pageTitle = 'Find a doctor';
$layout    = 'public';
require __DIR__ . '/../includes/header.php';
?>

<section class="py-4" style="background:#fff;border-bottom:1px solid var(--mb-border)">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <h1 class="h3 mb-1">Find a doctor</h1>
                <p class="text-muted mb-0" style="font-size:.9rem">
                    <?= count($doctors) ?> verified doctor<?= count($doctors) === 1 ? '' : 's' ?> available
                </p>
            </div>
            <?php if (is_logged_in() && auth_user()['role'] === 'patient'): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('patient/dashboard.php')) ?>">Back to dashboard</a>
            <?php endif; ?>
        </div>

        <form class="mb-search" method="get" action="<?= e(url('patient/doctors.php')) ?>">
            <div class="flex-grow-1 d-flex align-items-center px-2" style="min-width:180px">
                <?= icon('search', 18, 'text-muted') ?>
                <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
                       placeholder="Name, specialty or condition" aria-label="Search">
            </div>
            <div class="divider"></div>
            <div style="min-width:160px">
                <select class="form-select" name="specialty" aria-label="Specialty">
                    <option value="">All specialties</option>
                    <?php foreach ($specialties as $s): ?>
                        <option value="<?= e($s['specialty']) ?>" <?= $specialty === $s['specialty'] ? 'selected' : '' ?>>
                            <?= e($s['specialty']) ?> (<?= (int) $s['total'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="divider"></div>
            <div style="min-width:150px">
                <select class="form-select" name="location" aria-label="Location">
                    <option value="">Any location</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= e($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary px-4" type="submit">Search</button>
        </form>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <?php if (!$doctors): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="ico"><?= icon('search', 26) ?></div>
                    <h2 class="h6">No doctors matched</h2>
                    <p class="mb-3" style="font-size:.875rem">Try a broader search, or clear the filters.</p>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('patient/doctors.php')) ?>">Clear filters</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($doctors as $doc): ?>
                    <div class="col-md-6 col-xl-4"><?php require __DIR__ . '/../includes/doctor_card.php'; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
