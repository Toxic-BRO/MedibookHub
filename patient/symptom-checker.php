<?php
/**
 * patient/symptom-checker.php — AI Health Assistant page.
 *
 * Patients type their symptoms in natural language. The AI engine:
 *   1. Matches symptoms to the best medical specialty
 *   2. Recommends the best-matching verified doctors from the database
 *   3. Provides do's and don'ts health advice for the condition
 *   4. Shows urgency level (normal / soon / urgent)
 *
 * Doctor lateness reminder: a button lets patients trigger a check
 * for doctors who haven't responded to pending appointments.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];

// Handle AJAX symptom analysis.
if (is_post() && post('action') === 'analyze') {
    verify_csrf();

    $symptomText = post('symptoms', '');
    header('Content-Type: application/json');

    if (mb_strlen(trim($symptomText)) < 3) {
        echo json_encode([
            'ok' => false,
            'error' => 'Please describe your symptoms in at least a few words.',
        ]);
        exit;
    }

    $result = ai_analyze_symptoms($symptomText);

    // Build doctor cards HTML.
    ob_start();
    if (!empty($result['doctors'])):
        foreach ($result['doctors'] as $doc): ?>
            <div class="col-md-6">
                <div class="card card-hover bd-doctor-card h-100">
                    <div class="card-body">
                        <div class="bd-doctor-card__top">
                            <?= doctor_avatar($doc, '') ?>
                            <div class="bd-doctor-card__profile">
                                <a class="bd-doctor-card__name" href="<?= e(url('patient/doctor.php?id=' . (int) $doc['id'])) ?>">
                                    <?= e($doc['name']) ?>
                                </a>
                                <span class="specialty-tag"><?= e($doc['specialty']) ?></span>
                            </div>
                            <div class="bd-doctor-card__fee">
                                <span>per visit</span>
                                <strong><?= e(money($doc['fee'])) ?></strong>
                            </div>
                        </div>
                        <?php if (!empty($doc['location'])): ?>
                            <div class="bd-doctor-card__details">
                                <span class="meta-row"><?= icon('pin', 14) ?> <?= e($doc['location']) ?></span>
                                <span class="meta-row"><?= icon('star', 14) ?>
                                    <?= $doc['avg_rating'] !== null ? number_format((float) $doc['avg_rating'], 1) . ' (' . (int) $doc['review_count'] . ')' : 'No reviews' ?>
                                </span>
                                <span class="meta-row"><?= icon('check', 14) ?> <?= (int) $doc['completed_count'] ?> visits</span>
                            </div>
                        <?php endif; ?>
                        <div class="bd-doctor-card__footer">
                            <span class="bd-doctor-card__verified">
                                <?= icon('shield', 14) ?> Verified doctor
                            </span>
                            <a class="btn btn-primary btn-sm" href="<?= e(url('patient/doctor.php?id=' . (int) $doc['id'])) ?>">
                                <?= icon('calendar', 14) ?> Book appointment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach;
    else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                No verified doctors found for this specialty. Please try searching from the <a href="<?= e(url('patient/doctors.php')) ?>">doctor directory</a>.
            </div>
        </div>
    <?php endif;

    $doctorHtml = ob_get_clean();

    // Build do's HTML.
    ob_start();
    foreach ($result['dos'] as $do): ?>
        <li class="ai-do-item">
            <span class="ai-do-icon ai-do-icon--do"><?= icon('check', 14) ?></span>
            <span><?= e($do) ?></span>
        </li>
    <?php endforeach;
    $dosHtml = ob_get_clean();

    // Build don'ts HTML.
    ob_start();
    foreach ($result['donts'] as $dont): ?>
        <li class="ai-do-item">
            <span class="ai-do-icon ai-do-icon--dont"><?= icon('x', 14) ?></span>
            <span><?= e($dont) ?></span>
        </li>
    <?php endforeach;
    $dontsHtml = ob_get_clean();

    echo json_encode([
        'ok'          => true,
        'matched'     => $result['matched'],
        'specialty'   => $result['specialty'],
        'urgency'     => $result['urgency'],
        'description' => $result['description'],
        'self_care'   => $result['self_care'],
        'dos'         => $dosHtml,
        'donts'       => $dontsHtml,
        'doctors'     => $doctorHtml,
        'urgency_badge' => ai_urgency_badge($result['urgency']),
    ]);
    exit;
}

// Handle lateness reminder check.
if (is_post() && post('action') === 'check_lateness') {
    verify_csrf();
    header('Content-Type: application/json');

    $result = ai_check_doctor_lateness();

    echo json_encode([
        'ok'      => true,
        'sent'    => $result['sent'],
        'details' => $result['details'],
    ]);
    exit;
}

// Check if the patient has any pending appointments.
$hasPending = db()->prepare(
    "SELECT COUNT(*) FROM appointments
      WHERE patient_id = ? AND status = 'pending'"
);
$hasPending->execute([$pid]);
$pendingCount = (int) $hasPending->fetchColumn();

$pageTitle  = 'AI Health Assistant';
$pageSub    = 'Describe your symptoms — get instant guidance on which doctor to see and what to do.';
$layout     = 'dashboard';
$activeNav  = 'symptom-checker';

require __DIR__ . '/../includes/header.php';
?>

<!-- AI Symptom Checker -->
<div class="row g-4">
    <div class="col-lg-8">

        <!-- Input card -->
        <div class="card mb-4 ai-input-card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="ai-icon-badge"><?= icon('stetho', 20) ?></span>
                <span>Describe your symptoms</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-3" style="font-size:.88rem">
                    Tell us what you are experiencing in your own words. The AI assistant will analyze your symptoms
                    and suggest the right specialist, recommended doctors, and health advice.
                </p>

                <form id="symptomForm" method="post" action="<?= e(url('patient/symptom-checker.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="analyze">

                    <div class="mb-3">
                        <label class="form-label" for="symptomText">What are your symptoms?</label>
                        <textarea class="form-control form-control-lg"
                                  id="symptomText"
                                  name="symptoms"
                                  rows="4"
                                  placeholder="e.g. I have been having chest pain and shortness of breath for the last two days. It gets worse when I walk up stairs."
                                  style="border-radius:14px;font-size:1.05rem"
                                  required></textarea>
                        <div class="form-text">
                            Describe in as much detail as you can — when it started, how often, and anything that makes it better or worse.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-lg" type="submit" id="analyzeBtn">
                            <?= icon('search', 18) ?> Analyze my symptoms
                        </button>
                        <button class="btn btn-outline-secondary btn-lg" type="button" id="clearBtn">
                            Clear
                        </button>
                    </div>
                </form>

                <!-- Quick-pick suggestion chips -->
                <div class="mt-3">
                    <div class="text-faint mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em">
                        Or try an example:
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="I have chest pain and my heart feels like it is beating irregularly">Chest pain</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="My child has had a fever for two days and is not eating">Child fever</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="I have a persistent rash on both arms that is very itchy">Skin rash</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="I have had a migraine headache for three days with sensitivity to light">Migraine</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="I have lower back pain that gets worse when I bend or lift things">Back pain</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="I have had stomach pain and acidity for a week, especially after meals">Stomach pain</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="I have a sore throat and my ear hurts when I swallow">Sore throat</button>
                        <button class="btn btn-sm btn-soft ai-chip" type="button" data-symptom="My tooth has been hurting for a few days, especially when I drink cold water">Toothache</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results placeholder -->
        <div id="aiResults" style="display:none">

            <!-- Urgency + description card -->
            <div class="card mb-4" id="aiSummaryCard">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="d-flex align-items-center gap-2">
                        <span class="ai-icon-badge ai-icon-badge--result"><?= icon('heart', 18) ?></span>
                        <span>AI Health Assessment</span>
                    </span>
                    <span id="aiUrgencyBadge"></span>
                </div>
                <div class="card-body p-4">
                    <div id="aiSpecialtyTag" class="mb-3"></div>
                    <p id="aiDescription" class="text-body mb-0" style="line-height:1.7;font-size:.92rem"></p>
                </div>
            </div>

            <!-- Do's and Don'ts -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card h-100 ai-dos-card">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="ai-do-icon-sm ai-do-icon-sm--do"><?= icon('check', 16) ?></span>
                            <span>What you SHOULD do</span>
                        </div>
                        <div class="card-body">
                            <ul class="ai-do-list" id="aiDosList"></ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 ai-donts-card">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="ai-do-icon-sm ai-do-icon-sm--dont"><?= icon('x', 16) ?></span>
                            <span>What you should AVOID</span>
                        </div>
                        <div class="card-body">
                            <ul class="ai-do-list" id="aiDontsList"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Self care advice -->
            <div class="card mb-4 ai-selfcare-card" id="aiSelfCareCard" style="display:none">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="ai-icon-badge"><?= icon('shield', 16) ?></span>
                    <span>Self-care advice before your visit</span>
                </div>
                <div class="card-body">
                    <p id="aiSelfCareText" class="mb-0" style="font-size:.9rem;line-height:1.65"></p>
                </div>
            </div>

            <!-- Recommended doctors -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <span class="ai-icon-badge"><?= icon('stetho', 18) ?></span>
                        <span>Recommended doctors</span>
                    </span>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('patient/doctors.php')) ?>">Browse all doctors</a>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="aiDoctorsList"></div>
                </div>
            </div>

            <!-- Emergency warning -->
            <div class="alert alert-danger d-flex gap-2 align-items-start" id="aiEmergencyAlert" style="display:none">
                <?= icon('alert', 20) ?>
                <div>
                    <strong>Emergency warning:</strong>
                    <span id="aiEmergencyText"></span>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="aiLoading" style="display:none">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem" role="status">
                        <span class="visually-hidden">Analyzing...</span>
                    </div>
                    <p class="text-muted mb-0">Analyzing your symptoms...</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

        <!-- How it works -->
        <div class="card mb-3">
            <div class="card-header">How the AI assistant works</div>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <div class="stat-ico flex-none mb-0" style="width:36px;height:36px"><?= icon('chat', 16) ?></div>
                    <div>
                        <div class="fw-semibold text-ink" style="font-size:.88rem">1. Describe your symptoms</div>
                        <div class="text-muted" style="font-size:.8rem">Tell us what you are feeling in your own words.</div>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div class="stat-ico flex-none mb-0" style="width:36px;height:36px"><?= icon('shield', 16) ?></div>
                    <div>
                        <div class="fw-semibold text-ink" style="font-size:.88rem">2. Get instant analysis</div>
                        <div class="text-muted" style="font-size:.8rem">AI matches your symptoms to the right specialist and shows urgency.</div>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div class="stat-ico flex-none mb-0" style="width:36px;height:36px"><?= icon('check', 16) ?></div>
                    <div>
                        <div class="fw-semibold text-ink" style="font-size:.88rem">3. Follow the advice</div>
                        <div class="text-muted" style="font-size:.8rem">See what to do, what to avoid, and book the right doctor.</div>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div class="stat-ico flex-none mb-0" style="width:36px;height:36px"><?= icon('calendar', 16) ?></div>
                    <div>
                        <div class="fw-semibold text-ink" style="font-size:.88rem">4. Book directly</div>
                        <div class="text-muted" style="font-size:.8rem">Choose a recommended doctor and grab a slot.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor lateness reminder -->
        <?php if ($pendingCount > 0): ?>
            <div class="card mb-3 ai-reminder-card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="ai-icon-badge" style="background:#fff3cd;color:#856404"><?= icon('bell', 16) ?></span>
                    <span>Doctor response check</span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3" style="font-size:.84rem">
                        You have <strong><?= $pendingCount ?></strong> pending appointment<?= $pendingCount === 1 ? '' : 's' ?>
                        waiting for a doctor's response. Check if any doctor is running late and send them a reminder.
                    </p>
                    <form id="latenessForm" method="post" action="<?= e(url('patient/symptom-checker.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="check_lateness">
                        <button class="btn btn-warning btn-sm w-100" type="submit" id="latenessBtn">
                            <?= icon('bell', 15) ?> Check &amp; remind doctors
                        </button>
                    </form>
                    <div id="latenessResult" class="mt-2" style="display:none"></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Privacy note -->
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="ai-icon-badge" style="background:#e8f5f2;color:#075f5c"><?= icon('lock', 16) ?></span>
                <span>Your privacy</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0" style="font-size:.82rem;line-height:1.6">
                    The AI assistant uses a built-in medical knowledge base — no external API calls are made.
                    Your symptom descriptions are not stored and are only used to provide suggestions during your session.
                </p>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card mt-3">
            <div class="card-header">Quick links</div>
            <div class="card-body d-flex flex-column gap-2">
                <a class="side-link" href="<?= e(url('patient/doctors.php')) ?>"><?= icon('search', 18) ?> <span>Find a doctor</span></a>
                <a class="side-link" href="<?= e(url('patient/appointments.php')) ?>"><?= icon('calendar', 18) ?> <span>My appointments</span></a>
                <a class="side-link" href="<?= e(url('patient/prescriptions.php')) ?>"><?= icon('file', 18) ?> <span>My prescriptions</span></a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
