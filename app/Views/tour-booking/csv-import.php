<?php
$pageTitle = 'Import Tour Bookings — CSV Upload';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
?>
<style>
.import-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06); max-width:700px; }
.import-header { background:linear-gradient(135deg,#0d9488,#0f766e); padding:22px 28px; display:flex; align-items:center; gap:14px; }
.import-header-icon { width:48px; height:48px; background:rgba(255,255,255,.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; color:#fff; flex-shrink:0; }
.import-header h4 { color:#fff; margin:0; font-size:17px; font-weight:700; }
.import-header p  { color:rgba(255,255,255,.85); margin:2px 0 0; font-size:12px; }
.import-body { padding:28px; }
.drop-zone { border:2px dashed #cbd5e1; border-radius:12px; padding:40px 20px; text-align:center; cursor:pointer; transition:all .2s; background:#f8fafc; }
.drop-zone:hover, .drop-zone.drag-over { border-color:#0d9488; background:#f0fdfa; }
.drop-zone i { font-size:40px; color:#cbd5e1; display:block; margin-bottom:12px; }
.drop-zone.drag-over i { color:#0d9488; }
.drop-zone p { color:#64748b; margin:0; font-size:14px; }
.drop-zone small { color:#94a3b8; font-size:12px; }
.file-selected { display:none; background:#f0fdfa; border:1px solid #99f6e4; border-radius:8px; padding:12px 16px; margin-top:12px; font-size:13px; color:#0f766e; }
.col-spec { background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; overflow:hidden; margin-top:20px; }
.col-spec table { width:100%; border-collapse:collapse; font-size:13px; }
.col-spec th { background:#f1f5f9; padding:8px 12px; text-align:left; font-weight:600; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
.col-spec td { padding:8px 12px; border-top:1px solid #f1f5f9; color:#334155; }
.col-spec td code { background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:11px; }
.badge-req { background:#fef2f2; color:#dc2626; padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; }
.badge-opt { background:#f0f9ff; color:#0369a1; padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; }
</style>

<div class="container-fluid" style="padding:24px 20px;">
    <div class="page-header" style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h2 style="margin:0;"><i class="fa fa-upload" style="color:#0d9488;"></i> Import Tour Bookings</h2>
            <p class="text-muted" style="margin:4px 0 0;">Upload a CSV file to create multiple bookings at once.</p>
        </div>
        <a href="index.php?page=tour_booking" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Back to Bookings
        </a>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="max-width:700px;">
        <i class="fa fa-exclamation-triangle"></i> <?= $e($error) ?>
    </div>
    <?php endif; ?>

    <div class="import-card">
        <div class="import-header">
            <div class="import-header-icon"><i class="fa fa-file-text-o"></i></div>
            <div>
                <h4>CSV Upload</h4>
                <p>One booking per row — required columns: travel_date, pax_adult, total_amount</p>
            </div>
        </div>

        <div class="import-body">
            <!-- Template download -->
            <form method="POST" action="index.php?page=tour_booking_csv_import" style="margin-bottom:20px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="download_template">
                <button type="submit" class="btn btn-default btn-sm">
                    <i class="fa fa-download"></i> Download Template CSV
                </button>
                <span class="text-muted" style="font-size:12px; margin-left:8px;">
                    Start from the template to ensure correct column names.
                </span>
            </form>

            <!-- Upload form -->
            <form method="POST" action="index.php?page=tour_booking_csv_import"
                  enctype="multipart/form-data" id="uploadForm">
                <?= csrf_field() ?>

                <div class="drop-zone" id="dropZone" onclick="document.getElementById('csvFile').click()">
                    <i class="fa fa-cloud-upload" id="dropIcon"></i>
                    <p id="dropText">Click to choose a CSV file, or drag &amp; drop here</p>
                    <small>Accepted: .csv — max 5 MB</small>
                </div>
                <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" style="display:none;">
                <div class="file-selected" id="fileSelected">
                    <i class="fa fa-check-circle"></i> <span id="fileName"></span>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" id="btnUpload" disabled
                            style="border-radius:8px; padding:9px 22px;">
                        <i class="fa fa-search"></i> Preview Import
                    </button>
                </div>
            </form>

            <!-- Column spec -->
            <div class="col-spec">
                <table>
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Format / Example</th>
                            <th>Required?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>travel_date</code></td><td>YYYY-MM-DD or DD/MM/YYYY</td><td><span class="badge-req">Required</span></td></tr>
                        <tr><td><code>pax_adult</code></td><td>Integer e.g. <code>2</code></td><td><span class="badge-req">Required</span></td></tr>
                        <tr><td><code>total_amount</code></td><td>Decimal e.g. <code>5500.00</code></td><td><span class="badge-req">Required</span></td></tr>
                        <tr><td><code>booking_date</code></td><td>YYYY-MM-DD (defaults to today)</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>booking_by</code></td><td>Lead passenger name</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>pax_child</code></td><td>Integer (default 0)</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>pax_infant</code></td><td>Integer (default 0)</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>customer</code></td><td>Company name (must match existing)</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>agent</code></td><td>Agent company name (must match existing)</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>status</code></td><td><code>draft</code> / <code>confirmed</code> / <code>completed</code></td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>currency</code></td><td><code>THB</code> (default)</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>pickup_hotel</code></td><td>Hotel name</td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>pickup_time</code></td><td>HH:MM e.g. <code>07:30</code></td><td><span class="badge-opt">Optional</span></td></tr>
                        <tr><td><code>remark</code></td><td>Free text notes</td><td><span class="badge-opt">Optional</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var fileInput = document.getElementById('csvFile');
var dropZone  = document.getElementById('dropZone');
var btnUpload = document.getElementById('btnUpload');
var fileSelected = document.getElementById('fileSelected');

fileInput.addEventListener('change', function () {
    if (this.files.length) {
        document.getElementById('fileName').textContent = this.files[0].name + ' (' + Math.round(this.files[0].size / 1024) + ' KB)';
        fileSelected.style.display = 'block';
        btnUpload.disabled = false;
    }
});

dropZone.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', function ()  { this.classList.remove('drag-over'); });
dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    var files = e.dataTransfer.files;
    if (files.length) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});
</script>
