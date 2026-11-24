<?php
/** Penutup halaman: menutup kerangka, modal konfirmasi, lightbox foto, dan JavaScript. */
$layout = $layout ?? 'app';
?>
<?php if ($layout === 'auth'): ?>
        </div>
    </section>
</div>
<?php else: ?>
        </main>
        <footer class="app-footer">Fakultas Matematika dan Ilmu Pengetahuan Alam, Universitas Cenderawasih</footer>
    </div>
</div>
<?php endif; ?>

<div class="modal" id="modal-konfirmasi" hidden role="dialog" aria-modal="true" aria-labelledby="modal-judul">
    <div class="modal-box">
        <h3 id="modal-judul" data-modal-title>Konfirmasi</h3>
        <p data-modal-text></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" data-modal-cancel>Batal</button>
            <button type="button" class="btn btn-primary" data-modal-confirm>Lanjutkan</button>
        </div>
    </div>
</div>

<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Foto kerusakan diperbesar">
    <button type="button" class="lightbox-close" aria-label="Tutup foto"><?= icon('x', 24) ?></button>
    <img src="" alt="Foto kerusakan diperbesar">
</div>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
