<?php
/**
 * @var array  $kategori
 * @var array  $errors
 * @var string $action
 * @var string $pageTitle
 */
$isEdit = isset($kategori['id']);
partial('page-header', [
    'title'    => $pageTitle,
    'subtitle' => $isEdit ? 'Perbarui data kategori' : 'Tambahkan kategori barang baru',
    'icon'     => 'tags',
    'actions'  => [[
        'url'   => url('kategori/index.php'),
        'label' => 'Kembali',
        'icon'  => 'arrow-left',
        'class' => 'btn-outline-secondary',
    ]],
]);
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php partial('form-error-summary', ['errors' => $errors]); ?>

        <div class="card inv-card inv-animate inv-delay-1">
            <div class="card-body p-4">
                <form action="<?= e($action) ?>" method="post" class="js-validate" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="kode" class="form-label">Kode <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="kode" name="kode"
                                   class="form-control text-uppercase<?= invalid_class($errors, 'kode') ?>"
                                   value="<?= e($kategori['kode']) ?>" required minlength="3" maxlength="3"
                                   pattern="[A-Za-z]{3}" autocomplete="off"
                                   aria-describedby="kode-help kode-feedback" data-msg="Kode kategori harus 3 huruf.">
                            <div class="form-text" id="kode-help">
                                3 huruf, contoh ELK.<?= $isEdit ? ' Mengubah kode tidak mengubah kode barang lama.' : '' ?>
                            </div>
                            <div class="invalid-feedback" id="kode-feedback">
                                <?= e($errors['kode'] ?? 'Kode kategori harus 3 huruf.') ?>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label for="nama" class="form-label">Nama Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control<?= invalid_class($errors, 'nama') ?>"
                                   value="<?= e($kategori['nama']) ?>" required minlength="3" maxlength="50"
                                   aria-describedby="nama-feedback" data-msg="Nama kategori wajib diisi 3–50 karakter.">
                            <div class="invalid-feedback" id="nama-feedback">
                                <?= e($errors['nama'] ?? 'Nama kategori wajib diisi 3–50 karakter.') ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="255"
                                      class="form-control<?= invalid_class($errors, 'deskripsi') ?>"
                                      data-char-counter="#deskripsi-counter"
                                      aria-describedby="deskripsi-feedback deskripsi-counter"><?= e($kategori['deskripsi'] ?? '') ?></textarea>
                            <div class="invalid-feedback" id="deskripsi-feedback">
                                <?= e($errors['deskripsi'] ?? 'Deskripsi maksimal 255 karakter.') ?>
                            </div>
                            <div class="form-text text-end" id="deskripsi-counter">0/255</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= e(url('kategori/index.php')) ?>" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1" aria-hidden="true"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
