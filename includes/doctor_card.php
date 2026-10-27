<?php


declare(strict_types=1);

$doc     = $doc ?? [];
$compact = $compact ?? false;
$avg     = (float) ($doc['avg_rating'] ?? 0);
$total   = (int) ($doc['review_count'] ?? 0);
?>
<div class="card card-hover doctor-card">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <?= doctor_avatar($doc) ?>
            <div class="min-w-0 flex-grow-1">
                <a class="h6 d-block mb-1 text-ink text-decoration-none stretched-link"
                   href="<?= e(url('patient/doctor.php?id=' . (int) $doc['id'])) ?>">
                    <?= e($doc['name']) ?>
                </a>
                <span class="specialty-tag"><?= e($doc['specialty']) ?></span>
            </div>
            <div class="text-end flex-none">
                <div class="fee"><?= e(money($doc['fee'])) ?></div>
                <div class="text-faint" style="font-size:.72rem">per visit</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3">
            <span class="meta-row"><?= icon('pin', 15) ?> <?= e($doc['location'] ?: 'Location not set') ?></span>
            <span class="meta-row"><?= icon('stetho', 15) ?> <?= (int) $doc['experience'] ?> yrs exp</span>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-auto pt-1">
            <?= star_html($avg, $total) ?>
            <span class="btn btn-sm btn-soft">View slots <?= icon('arrow', 15) ?></span>
        </div>
    </div>
</div>
