<?php
$flash = get_flash();
if ($flash === null) {
    return;
}

$icons = [
    'success' => 'bi-check-circle-fill',
    'danger'  => 'bi-x-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info'    => 'bi-info-circle-fill',
];
$type = array_key_exists($flash['type'], $icons) ? $flash['type'] : 'info';
?>
<div class="alert alert-<?= e($type) ?> alert-dismissible fade show d-flex gap-2 align-items-start inv-animate js-auto-dismiss"
     role="alert" aria-live="polite" data-testid="flash">
    <i class="bi <?= e($icons[$type]) ?> fs-5" aria-hidden="true"></i>
    <div>
        <?php if (!empty($flash['title'])): ?>
            <strong class="d-block"><?= e($flash['title']) ?></strong>
        <?php endif; ?>
        <?= e($flash['message']) ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
