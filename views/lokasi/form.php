<?php
/**
 * @var array  $lokasi
 * @var array  $errors
 * @var string $action
 * @var string $pageTitle
 */
$isEdit = isset($lokasi['id']);
$kodeMessage = \App\Validators\LokasiValidator::MSG_KODE;
partial('page-header', [
    'title'    => $pageTitle,
    'subtitle' => $isEdit ? 'Perbarui data lokasi' : 'Tambahkan ruangan atau tempat penyimpanan baru',
    'icon'     => 'geo-alt',
    'actions'  => [[
        'url'   => url('lokasi/index.php'),
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
                                   value="<?= e($lokasi['kode']) ?>" required minlength="2" maxlength="10"
                                   pattern="[A-Za-z0-9\-]{2,10}" autocomplete="off"
                                   aria-describedby="kode-help kode-feedback" data-msg="<?= e($kodeMessage) ?>">
                            <div class="form-text" id="kode-help">Contoh: LAB-KOM, R-7A.</div>
                            <div class="invalid-feedback" id="kode-feedback"><?= e($errors['kode'] ?? $kodeMessage) ?></div>
                        </div>

                        <div class="col-md-8">
                            <label for="nama" class="form-label">Nama Lokasi <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control<?= invalid_class($errors, 'nama') ?>"
                                   value="<?= e($lokasi['nama']) ?>" required minlength="3" maxlength="100"
                                   aria-describedby="nama-feedback" data-msg="Nama lokasi wajib diisi 3–100 karakter.">
                            <div class="invalid-feedback" id="nama-feedback">
                                <?= e($errors['nama'] ?? 'Nama lokasi wajib diisi 3–100 karakter.') ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                            <input type="text" id="penanggung_jawab" name="penanggung_jawab"
                                   class="form-control<?= invalid_class($errors, 'penanggung_jawab') ?>"
                                   value="<?= e($lokasi['penanggung_jawab'] ?? '') ?>" maxlength="100"
                                   aria-describedby="penanggung_jawab-feedback">
                            <div class="invalid-feedback" id="penanggung_jawab-feedback">
                                <?= e($errors['penanggung_jawab'] ?? 'Penanggung jawab maksimal 100 karakter.') ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= e(url('lokasi/index.php')) ?>" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1" aria-hidden="true"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
