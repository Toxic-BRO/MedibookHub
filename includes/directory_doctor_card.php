<?php

declare(strict_types=1);

$avg = (float) ($doc['avg_rating'] ?? 0);
$total = (int) ($doc['review_count'] ?? 0);
$profileUrl = url('patient/doctor.php?id=' . (int) $doc['id']);
?>
<article class="card card-hover bd-doctor-card">
    <div class="card-body">
        <div class="bd-doctor-card__top">
            <?= doctor_avatar($doc) ?>

            <div class="bd-doctor-card__profile">
                <a class="bd-doctor-card__name" href="<?= e($profileUrl) ?>">
                    <?= e($doc['name']) ?>
                </a>
                <span class="specialty-tag"><?= e($doc['specialty']) ?></span>
            </div>

            <div class="bd-doctor-card__fee">
                <span>Consultation fee</span>
                <strong><?= e(money($doc['fee'])) ?></strong>
            </div>
        </div>

        <div class="bd-doctor-card__details">
            <span class="meta-row"><?= icon('pin', 15) ?> Chamber: <?= e($doc['location'] ?: 'Location not set') ?></span>
            <span class="meta-row"><?= icon('stetho', 15) ?> <?= (int) $doc['experience'] ?> years experience</span>
            <span class="bd-doctor-card__rating"><?= star_html($avg, $total) ?></span>
        </div>

        <div class="bd-doctor-card__footer">
            <span class="bd-doctor-card__verified"><?= icon('shield', 15) ?> Verified doctor</span>
            <a class="btn btn-primary btn-sm" href="<?= e($profileUrl) ?>">
                View profile &amp; book <?= icon('arrow', 15) ?>
            </a>
        </div>
    </div>
</article>
