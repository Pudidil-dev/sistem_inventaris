<?php
/**
 * Form tambah & edit barang (dipakai bersama tambah.php dan edit.php).
 * @var array  $barang
 * @var array  $errors
 * @var array  $kategoriList
 * @var array  $lokasiList
 * @var bool   $isEdit
 * @var string $action
 * @var string $pageTitle
 */
$messages = \App\Validators\BarangValidator::MESSAGES;
$feedback = static fn (string $field): string => $errors[$field] ?? $messages[$field];
$fotoMessage = \App\Services\UploadService::MSG_INVALID;
$fotoUrl = upload_url($barang['foto'] ?? null);
$backUrl = $isEdit ? url('barang/detail.php', ['id' => $barang['id']]) : url('barang/index.php');

partial('page-header', [
    'title'    => $pageTitle,
    'subtitle' => $isEdit ? 'Perbarui data inventaris' : 'Catat barang inventaris baru',
    'icon'     => $isEdit ? 'pencil-square' : 'plus-square',
    'actions'  => [['url' => $backUrl, 'label' => 'Kembali', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary']],
]);
partial('form-error-summary', ['errors' => $errors]);
?>
<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="js-validate" novalidate
      data-testid="form-barang">
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card inv-card inv-animate inv-delay-1">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold">Informasi Barang</span>
                    <?php if ($isEdit): ?>
                        <span class="badge text-bg-light border inv-kode" data-testid="kode-barang"><?= e($barang['kode_barang']) ?></span>
                    <?php else: ?>
                        <span class="small text-muted">Kode otomatis:
                            <span class="badge text-bg-warning inv-kode" data-kode-preview
                                  data-kode-url="<?= e(url('api/kode-barang.php')) ?>" aria-live="polite">Pilih kategori</span>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nama" class="form-label">Nama Barang <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control<?= invalid_class($errors, 'nama') ?>"
                                   value="<?= e($barang['nama']) ?>" required minlength="3" maxlength="150"
                                   aria-describedby="nama-feedback" data-msg="<?= e($messages['nama']) ?>">
                            <div class="invalid-feedback" id="nama-feedback"><?= e($feedback('nama')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="kategori_id" name="kategori_id" class="form-select<?= invalid_class($errors, 'kategori_id') ?>"
                                    required aria-describedby="kategori_id-feedback" data-msg="<?= e($messages['kategori_id']) ?>">
                                <option value="">— Pilih kategori —</option>
                                <?php foreach ($kategoriList as $kategori): ?>
                                    <option value="<?= (int) $kategori['id'] ?>"<?= selected($barang['kategori_id'] ?? '', $kategori['id']) ?>><?= e($kategori['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="kategori_id-feedback"><?= e($feedback('kategori_id')) ?></div>
                            <?php if ($isEdit): ?>
                                <div class="form-text">Mengganti kategori tidak mengubah kode barang.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="lokasi_id" class="form-label">Lokasi <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="lokasi_id" name="lokasi_id" class="form-select<?= invalid_class($errors, 'lokasi_id') ?>"
                                    required aria-describedby="lokasi_id-feedback" data-msg="<?= e($messages['lokasi_id']) ?>">
                                <option value="">— Pilih lokasi —</option>
                                <?php foreach ($lokasiList as $lokasi): ?>
                                    <option value="<?= (int) $lokasi['id'] ?>"<?= selected($barang['lokasi_id'] ?? '', $lokasi['id']) ?>><?= e($lokasi['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="lokasi_id-feedback"><?= e($feedback('lokasi_id')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="merk" class="form-label">Merk</label>
                            <input type="text" id="merk" name="merk" class="form-control<?= invalid_class($errors, 'merk') ?>"
                                   value="<?= e($barang['merk'] ?? '') ?>" maxlength="100"
                                   aria-describedby="merk-feedback" data-msg="<?= e($messages['merk']) ?>">
                            <div class="invalid-feedback" id="merk-feedback"><?= e($feedback('merk')) ?></div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="jumlah" class="form-label">Jumlah <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="number" id="jumlah" name="jumlah" class="form-control<?= invalid_class($errors, 'jumlah') ?>"
                                   value="<?= e($barang['jumlah']) ?>" required min="1" max="100000" step="1" inputmode="numeric"
                                   aria-describedby="jumlah-feedback" data-msg="<?= e($messages['jumlah']) ?>">
                            <div class="invalid-feedback" id="jumlah-feedback"><?= e($feedback('jumlah')) ?></div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="satuan" class="form-label">Satuan <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="satuan" name="satuan" class="form-select<?= invalid_class($errors, 'satuan') ?>"
                                    required aria-describedby="satuan-feedback" data-msg="<?= e($messages['satuan']) ?>">
                                <?php foreach (\App\Validators\BarangValidator::SATUAN as $satuan): ?>
                                    <option value="<?= e($satuan) ?>"<?= selected($barang['satuan'], $satuan) ?>><?= e(ucfirst($satuan)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="satuan-feedback"><?= e($feedback('satuan')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="kondisi" class="form-label">Kondisi <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="kondisi" name="kondisi" class="form-select<?= invalid_class($errors, 'kondisi') ?>"
                                    required aria-describedby="kondisi-feedback" data-msg="<?= e($messages['kondisi']) ?>">
                                <?php foreach (\App\Enums\Kondisi::options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= selected($barang['kondisi'], $value) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="kondisi-feedback"><?= e($feedback('kondisi')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="sumber_dana" class="form-label">Sumber Dana <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="sumber_dana" name="sumber_dana" class="form-select<?= invalid_class($errors, 'sumber_dana') ?>"
                                    required aria-describedby="sumber_dana-feedback" data-msg="<?= e($messages['sumber_dana']) ?>">
                                <?php foreach (\App\Enums\SumberDana::options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= selected($barang['sumber_dana'], $value) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="sumber_dana-feedback"><?= e($feedback('sumber_dana')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="tanggal_perolehan" class="form-label">Tanggal Perolehan <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="date" id="tanggal_perolehan" name="tanggal_perolehan"
                                   class="form-control<?= invalid_class($errors, 'tanggal_perolehan') ?>"
                                   value="<?= e($barang['tanggal_perolehan']) ?>" required
                                   min="<?= e(\App\Validators\BarangValidator::TANGGAL_MIN) ?>" max="<?= e(date('Y-m-d')) ?>"
                                   data-rule-not-future aria-describedby="tanggal_perolehan-feedback"
                                   data-msg="<?= e($messages['tanggal_perolehan']) ?>">
                            <div class="invalid-feedback" id="tanggal_perolehan-feedback"><?= e($feedback('tanggal_perolehan')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="harga_satuan" class="form-label">Harga Satuan</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="harga_satuan" name="harga_satuan"
                                       class="form-control<?= invalid_class($errors, 'harga_satuan') ?>"
                                       value="<?= e($barang['harga_satuan']) ?>" min="0" max="1000000000" step="0.01"
                                       inputmode="decimal" placeholder="0"
                                       aria-describedby="harga_satuan-feedback" data-msg="<?= e($messages['harga_satuan']) ?>">
                                <div class="invalid-feedback" id="harga_satuan-feedback"><?= e($feedback('harga_satuan')) ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000"
                                      class="form-control<?= invalid_class($errors, 'keterangan') ?>"
                                      data-char-counter="#keterangan-counter"
                                      aria-describedby="keterangan-feedback keterangan-counter"
                                      data-msg="<?= e($messages['keterangan']) ?>"><?= e($barang['keterangan'] ?? '') ?></textarea>
                            <div class="invalid-feedback" id="keterangan-feedback"><?= e($feedback('keterangan')) ?></div>
                            <div class="form-text text-end" id="keterangan-counter">0/1000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card inv-card inv-animate inv-delay-2">
                <div class="card-header bg-white py-3 fw-semibold">Foto Barang</div>
                <div class="card-body">
                    <div class="inv-photo-frame mb-3">
                        <img src="<?= e($fotoUrl) ?>" alt="Pratinjau foto barang" class="inv-photo inv-photo-preview"
                             data-foto-preview data-original="<?= e($fotoUrl) ?>"
                             data-placeholder="<?= e(asset('img/no-image.svg')) ?>">
                    </div>
                    <label for="foto" class="form-label">Pilih foto (opsional)</label>
                    <input type="file" id="foto" name="foto" class="form-control<?= invalid_class($errors, 'foto') ?>"
                           accept="image/jpeg,image/png,image/webp"
                           data-max-size="<?= (int) config('upload.max_bytes') ?>"
                           data-allowed-types="image/jpeg,image/png,image/webp"
                           aria-describedby="foto-help foto-feedback" data-msg="<?= e($fotoMessage) ?>">
                    <div class="form-text" id="foto-help">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                    <div class="invalid-feedback" id="foto-feedback"><?= e($errors['foto'] ?? $fotoMessage) ?></div>

                    <?php if ($isEdit && !empty($barang['foto'])): ?>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="hapus_foto" name="hapus_foto" value="1">
                            <label class="form-check-label" for="hapus_foto">Hapus foto saat ini</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1" aria-hidden="true"></i>Simpan
                </button>
                <a href="<?= e($backUrl) ?>" class="btn btn-light">Batal</a>
            </div>
        </div>
    </div>
</form>
