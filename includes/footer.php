    <div class="modal-backdrop" id="deleteModal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
            <div class="modal-header">
                <h2 class="modal-title" id="deleteModalTitle">Confirm Delete</h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Close dialog">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-body-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p>Are you sure you want to delete <strong id="modalItemName">this record</strong>?</p>
                    <p class="field-hint">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-modal-close>Cancel</button>
                <button type="button" class="btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
    <script src="<?= htmlspecialchars(app_url('assets/js/main.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(app_url('assets/js/validation.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
<?php
if (function_exists('rewrite_app_output') && ob_get_level()) {
    echo rewrite_app_output((string)ob_get_clean());
}
