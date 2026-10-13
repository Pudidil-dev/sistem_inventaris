<?php
/**
 * @var string $title
 * @var string $subtitle
 * @var string $icon     nama Bootstrap Icons tanpa awalan "bi-"
 * @var array  $actions  [['url' => ..., 'label' => ..., 'icon' => ..., 'class' => 'btn-primary']]
 */
$subtitle = $subtitle ?? '';
$icon = $icon ?? 'box';
$actions = $actions ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 inv-page-header inv-animate">
    <div class="d-flex align-items-center gap-3">
        <span class="inv-icon-box" aria-hidden="true"><i class="bi bi-<?= e($icon) ?>"></i></span>
        <div>
            <h1 class="h4 fw-bold mb-0"><?= e($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="text-muted small mb-0"><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($actions !== []): ?>
        <div class="d-flex flex-wrap gap-2 no-print">
            <?php foreach ($actions as $action): ?>
                <a href="<?= e($action['url']) ?>" class="btn btn-sm <?= e($action['class'] ?? 'btn-primary') ?>">
                    <?php if (!empty($action['icon'])): ?>
                        <i class="bi bi-<?= e($action['icon']) ?> me-1" aria-hidden="true"></i>
                    <?php endif; ?>
                    <?= e($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
