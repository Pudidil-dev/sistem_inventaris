<?php /** @var array $errors pesan error per field */ ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex gap-2 inv-animate" role="alert" data-testid="form-errors">
        <i class="bi bi-exclamation-octagon-fill fs-5" aria-hidden="true"></i>
        <div>
            <strong class="d-block">Periksa kembali isian berikut:</strong>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $message): ?>
                    <li><?= e($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
