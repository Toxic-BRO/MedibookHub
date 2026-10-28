<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$requestedLanguage = query('lang');
if (in_array($requestedLanguage, ['bn', 'en'], true)) {
    $_SESSION['language'] = $requestedLanguage;
}
$isBangla = app_language() === 'bn';

$q         = query('q');
$specialty = query('specialty');
$location  = query('location');

$isSearching = ($q !== '' || $specialty !== '' || $location !== '');

$doctors    = search_doctors($q, $specialty, $location, $isSearching ? 60 : 6);
$specialties = get_specialties();
$locations   = get_locations();

$statDoctors = (int) db()->query(
    "SELECT COUNT(*) FROM doctors d JOIN users u ON u.id = d.user_id
      WHERE d.is_verified = 1 AND u.status = 'active'"
)->fetchColumn();

$statAppointments = (int) db()->query(
    "SELECT COUNT(*) FROM appointments WHERE status IN ('confirmed','completed')"
)->fetchColumn();

$statReviews = db()->query('SELECT ROUND(AVG(rating), 1) FROM reviews')->fetchColumn();

$pageTitle = 'Book a doctor directly';
$layout    = 'public';
require __DIR__ . '/includes/header.php';
?>

<section class="mb-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-flex justify-content-end mb-3">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Choose website language">
                        <button class="btn <?= $isBangla ? 'btn-primary' : 'btn-outline-primary' ?>"
                                type="button" data-site-language="bn" aria-pressed="<?= $isBangla ? 'true' : 'false' ?>">বাংলা</button>
                        <button class="btn <?= $isBangla ? 'btn-outline-primary' : 'btn-primary' ?>"
                                type="button" data-site-language="en" aria-label="English" aria-pressed="<?= $isBangla ? 'false' : 'true' ?>">EN</button>
                    </div>
                </div>
                <span class="eyebrow"><?= icon('shield', 14) ?> No receptionist. No middleman.</span>

                <h1 class="mt-3 mb-3">
                    See your doctor.<br>
                    <span style="color:var(--mb-primary)">Skip the front desk.</span>
                </h1>

                <p class="lead mb-4">
                    Find a specialist, pick a slot that actually works, and book it yourself.
                    Your doctor confirms directly — no phone tag, no waiting room queue.
                </p>

                <form class="mb-search" method="get" action="<?= e(url('index.php')) ?>" role="search">
                    <div class="flex-grow-1 d-flex align-items-center px-2" style="min-width:180px">
                        <?= icon('search', 18, 'text-muted') ?>
                        <input class="form-control" type="search" name="q"
                               value="<?= e($q) ?>" placeholder="Doctor name or condition"
                               aria-label="Search doctors">
                    </div>
                    <div class="divider"></div>
                    <div style="min-width:150px">
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
                    <div style="min-width:140px">
                        <select class="form-select" name="location" aria-label="Location">
                            <option value="">Any location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= e($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                                    <?= e($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary px-4" type="submit">Search</button>
                </form>

                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div>
                        <div class="fw-bold text-ink" style="font-size:1.15rem"><?= $statDoctors ?></div>
                        <div class="text-muted" style="font-size:.8rem">Verified doctors</div>
                    </div>
                    <div>
                        <div class="fw-bold text-ink" style="font-size:1.15rem"><?= $statAppointments ?></div>
                        <div class="text-muted" style="font-size:.8rem">Appointments booked</div>
                    </div>
                    <div>
                        <div class="fw-bold text-ink" style="font-size:1.15rem"><?= $statReviews !== null && $statReviews !== false ? number_format((float) $statReviews, 1) : '—' ?></div>
                        <div class="text-muted" style="font-size:.8rem">Average rating</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="card border-0" style="box-shadow:var(--mb-shadow-lg)">
                    <div class="card-body p-4" data-health-tips lang="<?= $isBangla ? 'bn' : 'en' ?>">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <span class="small-caps" data-health-label-bn <?= $isBangla ? '' : 'hidden' ?>>সচেতন থাকুন, সুস্থ থাকুন</span>
                                <span class="small-caps" data-health-label-en <?= $isBangla ? 'hidden' : '' ?>>Stay informed, stay well</span>
                                <div class="mt-1">
                                    <span class="badge rounded-pill bg-success" data-health-badge-bn <?= $isBangla ? '' : 'hidden' ?>>স্বাস্থ্য পরামর্শ</span>
                                    <span class="badge rounded-pill bg-success" data-health-badge-en <?= $isBangla ? 'hidden' : '' ?>>Health tips</span>
                                </div>
                            </div>
                        </div>

                        <div data-health-language="bn" <?= $isBangla ? '' : 'hidden' ?>>
                            <h2 class="h4 mb-2">ডাক্তারের কাছে যাওয়ার আগে</h2>
                            <p class="text-muted mb-4" style="font-size:.9rem">
                                আগে থেকে একটু প্রস্তুতি নিলে আপনার সমস্যার কথা চিকিৎসককে গুছিয়ে বলতে সুবিধা হবে।
                            </p>

                            <div class="d-flex gap-3 mb-3">
                                <div class="stat-ico flex-none mb-0"><?= icon('file', 18) ?></div>
                                <div>
                                    <div class="fw-semibold text-ink">লক্ষণগুলো লিখে রাখুন</div>
                                    <div class="text-muted" style="font-size:.82rem">কখন শুরু হয়েছে, কতদিন ধরে হচ্ছে—মনে রাখুন।</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3 mb-3">
                                <div class="stat-ico flex-none mb-0"><?= icon('plus', 18) ?></div>
                                <div>
                                    <div class="fw-semibold text-ink">ওষুধ ও অ্যালার্জির তথ্য জানান</div>
                                    <div class="text-muted" style="font-size:.82rem">যে ওষুধ খান এবং কোনো ওষুধে অ্যালার্জি থাকলে চিকিৎসককে বলুন।</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="stat-ico flex-none mb-0"><?= icon('chat', 18) ?></div>
                                <div>
                                    <div class="fw-semibold text-ink">প্রশ্ন থাকলে নির্দ্বিধায় করুন</div>
                                    <div class="text-muted" style="font-size:.82rem">পরীক্ষা, চিকিৎসা ও পরবর্তী করণীয় বুঝে নিন।</div>
                                </div>
                            </div>
                        </div>

                        <div data-health-language="en" <?= $isBangla ? 'hidden' : '' ?>>
                            <h2 class="h4 mb-2">Before visiting your doctor</h2>
                            <p class="text-muted mb-4" style="font-size:.9rem">
                                A little preparation can help you explain your concerns clearly during your visit.
                            </p>

                            <div class="d-flex gap-3 mb-3">
                                <div class="stat-ico flex-none mb-0"><?= icon('file', 18) ?></div>
                                <div>
                                    <div class="fw-semibold text-ink">Write down your symptoms</div>
                                    <div class="text-muted" style="font-size:.82rem">Note when they started and how long they have been happening.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3 mb-3">
                                <div class="stat-ico flex-none mb-0"><?= icon('plus', 18) ?></div>
                                <div>
                                    <div class="fw-semibold text-ink">Share medicines and allergies</div>
                                    <div class="text-muted" style="font-size:.82rem">Tell your doctor about medicines you take and any known allergies.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="stat-ico flex-none mb-0"><?= icon('chat', 18) ?></div>
                                <div>
                                    <div class="fw-semibold text-ink">Ask any questions you have</div>
                                    <div class="text-muted" style="font-size:.82rem">Make sure you understand tests, treatment, and what to do next.</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 mt-4 mb-0" style="font-size:.8rem">
                            <span data-health-warning-bn <?= $isBangla ? '' : 'hidden' ?>>জরুরি বা গুরুতর উপসর্গ হলে অনলাইনে অপেক্ষা না করে নিকটস্থ জরুরি চিকিৎসা সেবা নিন।</span>
                            <span data-health-warning-en <?= $isBangla ? 'hidden' : '' ?>>For urgent or severe symptoms, seek emergency medical care nearby instead of waiting online.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('[data-site-language]').forEach(function (button) {
    button.addEventListener('click', function () {
        var params = new URLSearchParams(window.location.search);
        params.set('lang', button.getAttribute('data-site-language'));
        window.location.search = params.toString();
    });
});
</script>

<section class="py-5 doctor-directory-section">
    <div class="container">

        <?php if ($isSearching): ?>
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
                <div>
                    <h2 class="h4 mb-1"><?= count($doctors) ?> doctor<?= count($doctors) === 1 ? '' : 's' ?> found</h2>
                    <p class="text-muted mb-0" style="font-size:.875rem">
                        <?php if ($specialty !== ''): ?>Specialty: <strong><?= e($specialty) ?></strong><?php endif; ?>
                        <?php if ($location !== ''): ?> · Location: <strong><?= e($location) ?></strong><?php endif; ?>
                        <?php if ($q !== ''): ?> · Matching “<strong><?= e($q) ?></strong>”<?php endif; ?>
                    </p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('index.php')) ?>">Clear filters</a>
            </div>

            <?php if (!$doctors): ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="ico"><?= icon('search', 26) ?></div>
                        <h3 class="h6">No doctors matched that search</h3>
                        <p class="mb-3">Try a different specialty, or clear the filters to see everyone.</p>
                        <a class="btn btn-primary btn-sm" href="<?= e(url('index.php')) ?>">Show all doctors</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3 doctor-results">
                    <?php foreach ($doctors as $doc): ?>
                        <div class="col-12"><?php require __DIR__ . '/includes/directory_doctor_card.php'; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-end mb-3">
                        <div>
                            <h2 class="h4 mb-1">Top-rated doctors</h2>
                            <p class="text-muted mb-0" style="font-size:.875rem">Verified accounts only — pending applications never show up here.</p>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('patient/doctors.php')) ?>">Browse all</a>
                    </div>

                    <div class="row g-3 doctor-results">
                        <?php foreach ($doctors as $doc): ?>
                            <div class="col-12"><?php require __DIR__ . '/includes/directory_doctor_card.php'; ?></div>
                        <?php endforeach; ?>
                        <?php if (!$doctors): ?>
                            <div class="col-12">
                                <div class="card"><div class="empty-state">
                                    <div class="ico"><?= icon('stetho', 26) ?></div>
                                    <h3 class="h6">No verified doctors yet</h3>
                                    <p class="mb-0">An administrator needs to verify a doctor account first.</p>
                                </div></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">Browse by specialty</div>
                        <div class="card-body d-flex flex-wrap gap-2">
                            <?php foreach ($specialties as $s): ?>
                                <a class="specialty-tag"
                                   href="<?= e(url('index.php?specialty=' . urlencode($s['specialty']))) ?>">
                                    <?= e($s['specialty']) ?> · <?= (int) $s['total'] ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$specialties): ?>
                                <span class="text-muted small">Nothing listed yet.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mt-3" style="background:linear-gradient(160deg,#0f766e,#0d9488);border:0">
                        <div class="card-body p-4 text-white">
                            <div class="mb-2"><?= icon('stetho', 22) ?></div>
                            <h3 class="h6 text-white mb-1">Are you a doctor?</h3>
                            <p class="mb-3" style="font-size:.85rem;opacity:.85">
                                Publish your weekly availability and let patients book themselves.
                                Accounts go live after a quick admin check.
                            </p>
                            <a class="btn btn-light btn-sm fw-semibold" href="<?= e(url('auth/register.php?role=doctor')) ?>">
                                Join as a doctor
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>

<section class="py-5 how-it-works" id="how" style="background:#fff;border-top:1px solid var(--mb-border);border-bottom:1px solid var(--mb-border)">
    <div class="container">
        <div class="text-center mb-5">
            <span class="small-caps">How it works</span>
            <h2 class="h3 mt-2 mb-2">Four steps, zero phone calls</h2>
            <p class="text-muted mb-0">The whole loop happens inside the app — including the paperwork.</p>
        </div>

        <div class="row g-4 how-steps">
            <?php
            $steps = [
                ['search', 'Find your doctor', 'Filter by name, specialty or location. Every profile shows the fee up front.'],
                ['calendar', 'Pick a real slot', 'Only future, unbooked slots are shown — generated from the doctor\'s weekly hours.'],
                ['check', 'Doctor confirms', 'Your request lands in their queue. Accept or reject, you get notified either way.'],
                ['file', 'Notes &amp; prescription', 'After the visit the doctor files notes and a prescription you can read anytime.'],
            ];
            foreach ($steps as $i => [$ico, $title, $body]): ?>
                <div class="col-md-6 col-lg-3 how-step">
                    <div class="d-flex gap-3">
                        <div class="stat-ico flex-none mb-0"><?= icon($ico, 20) ?></div>
                        <div>
                            <div class="small-caps mb-1">Step <?= $i + 1 ?></div>
                            <h3 class="h6 mb-1"><?= $title ?></h3>
                            <p class="text-muted mb-0" style="font-size:.85rem"><?= $body ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 homepage-cta">
    <div class="container">
        <div class="card border-0" style="background:var(--mb-primary-soft);border:1px solid var(--mb-primary-line)">
            <div class="card-body p-4 p-lg-5 d-flex flex-wrap align-items-center justify-content-between gap-4">
                <div>
                    <h2 class="h4 mb-1">Ready when you are.</h2>
                    <p class="text-muted mb-0">Create an account in under a minute — patients and doctors both.</p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= e(url('auth/register.php')) ?>">Create free account</a>
                    <a class="btn btn-outline-primary" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
