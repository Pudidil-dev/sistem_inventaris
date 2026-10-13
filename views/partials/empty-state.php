<?php
/**
 * @var string      $title
 * @var string      $message
 * @var string|null $actionUrl
 * @var string|null $actionLabel
 */
$actionUrl = $actionUrl ?? null;
$actionLabel = $actionLabel ?? null;
?>
<div class="text-center py-5 px-3" data-testid="empty-state">
    <img src="<?= e(asset('img/empty-state.svg')) ?>" alt="" width="180" height="135" class="mb-3 inv-float">
    <h2 class="h6 fw-bold"><?= e($title) ?></h2>
    <p class="text-muted small mb-3"><?= e($message) ?></p>
    <?php if ($actionUrl !== null && $actionLabel !== null): ?>
        <a href="<?= e($actionUrl) ?>" class="btn btn-sm btn-outline-primary"><?= e($actionLabel) ?></a>
    <?php endif; ?>
</div>
