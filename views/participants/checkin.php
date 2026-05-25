<?php
// QR code / code input for check-in, with camera scanner
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">QR Check-In</h2>
    <a href="/participants" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">dashboard</span> Dashboard
    </a>
</div>
<p class="text-muted small">Participants receive their group here (round-robin by preferred language) if group shells are saved on the Grouping Overview page.</p>

<!-- Scan View: Camera + Manual Input -->
<div id="scan-view">
    <div class="row mt-3">
        <div class="col-12 col-md-6 mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                <span class="material-symbols-outlined" style="font-size: 22px;">qr_code_scanner</span>
                Scan with camera
            </h5>
            <div id="qr-reader" style="width: 100%; max-width: 400px; border-radius: 16px; overflow: hidden; border: 1px solid var(--md-sys-color-outline-variant);"></div>
            <p class="text-muted mt-2 small">
                Allow camera access, then hold the participant's QR code in front of the camera.
            </p>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                <span class="material-symbols-outlined" style="font-size: 22px;">edit</span>
                Or enter code/ID manually
            </h5>
            
            <!-- Live Status Container for Check-in Results (manual input only) -->
            <div id="checkin-status-container"></div>
            
            <form id="checkin-form" method="post" action="/participants/checkin" class="mt-2">
                <div class="mb-3">
                    <label class="form-label" for="qr_code_input">Student ID or QR Code Hash</label>
                    <input type="text" id="qr_code_input" name="qr_code" class="form-control" placeholder="e.g. 25WMR09999 or hash..." required autocomplete="off">
                    <small class="text-muted mt-1 d-block">You can enter either the participant's Student ID (e.g. 25WMR09999) or their unique QR code hash.</small>
                </div>
                <button type="submit" id="checkin-btn" class="btn btn-primary w-100 py-2">Check in</button>
            </form>

            <div class="card p-4 border-0 mt-4" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 1.1rem; color: var(--md-sys-color-on-surface);">
                    <span class="material-symbols-outlined text-primary" style="font-size: 22px;">history</span>
                    Recent Check-Ins
                </h5>
                <div id="recent-checkins-list" class="list-group">
                    <?php if (!empty($recentCheckins)): ?>
                        <?php foreach ($recentCheckins as $chk): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="overflow-hidden me-2">
                                    <div class="fw-semibold small text-truncate" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($chk['full_name']) ?></div>
                                    <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($chk['student_id'] ?? '') ?> • 
                                        <span class="badge bg-secondary" style="font-size: 9px; padding: 2px 6px !important;"><?= htmlspecialchars($chk['preferred_language'] ?? '') ?></span> •
                                        <?= date('g:i A', strtotime($chk['checked_in_at'])) ?>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php if (!empty($chk['group_code'])): ?>
                                        <span class="badge bg-primary" style="font-size: 11px; padding: 4px 8px !important;">Group <?= htmlspecialchars($chk['group_code']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-dark" style="font-size: 11px; padding: 4px 8px !important;">No Group</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (empty($recentCheckins)): ?>
                    <p id="no-checkins-msg" class="text-muted small mb-0">No check-ins yet for this session.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Result View: Full-screen participant info after scan (hidden by default) -->
<div id="result-view" style="display: none;">
    <div id="result-content"></div>
    <div class="text-center mt-4 mb-3">
        <button type="button" id="scan-next-btn" class="btn btn-primary btn-lg px-5 py-3" style="font-size: 1.1rem;">
            <span class="material-symbols-outlined" style="font-size: 22px; vertical-align: text-bottom;">qr_code_scanner</span>
            Scan Next Participant
        </button>
    </div>
    <div class="card p-4 border-0 mt-3" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 1.1rem; color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined text-primary" style="font-size: 22px;">history</span>
            Recent Check-Ins
        </h5>
        <div id="recent-checkins-list-result" class="list-group">
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const qrRegionId = "qr-reader";
        const input = document.getElementById("qr_code_input");
        const form = document.getElementById("checkin-form");
        const submitBtn = document.getElementById("checkin-btn");
        const statusContainer = document.getElementById("checkin-status-container");
        const recentList = document.getElementById("recent-checkins-list");
        const recentListResult = document.getElementById("recent-checkins-list-result");
        const noCheckinsMsg = document.getElementById("no-checkins-msg");
        const scanView = document.getElementById("scan-view");
        const resultView = document.getElementById("result-view");
        const resultContent = document.getElementById("result-content");
        const scanNextBtn = document.getElementById("scan-next-btn");
        let html5QrCode = null;
        let lastScannedFromCamera = false;

        function escapeHtml(str) {
            if (!str) return '';
            const map = { '&': '\u0026amp;', '<': '\u0026lt;', '>': '\u0026gt;', '"': '\u0026quot;', "'": '\u0026#039;' };
            return str.replace(/[&<>"']/g, c => map[c]);
        }

        // Switch to result view (hide camera, show big result)
        function showResultView(html) {
            resultContent.innerHTML = html;
            resultView.style.display = 'block';
            scanView.style.display = 'none';
            // Sync recent checkins to the result view list
            recentListResult.innerHTML = recentList.innerHTML;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Switch back to scan view (show camera, hide result)
        function showScanView() {
            resultView.style.display = 'none';
            scanView.style.display = 'block';
            statusContainer.innerHTML = "";
            input.value = "";
            // Resume scanner if available
            if (html5QrCode && html5QrCode.getState() === 3) {
                try {
                    html5QrCode.resume();
                } catch (e) {
                    console.error("Failed to resume scanner", e);
                }
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // "Scan Next" button
        scanNextBtn.addEventListener('click', showScanView);

        // Prepend a new item to both recent lists
        function prependRecentItem(data) {
            if (noCheckinsMsg) {
                noCheckinsMsg.style.display = 'none';
            }
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

            let grpBadge = data.participant.group_code !== 'Not assigned'
                ? `<span class="badge bg-primary" style="font-size: 11px; padding: 4px 8px !important;">Group ${escapeHtml(data.participant.group_code)}</span>`
                : `<span class="badge bg-dark" style="font-size: 11px; padding: 4px 8px !important;">No Group</span>`;

            const newItemHtml = `
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="overflow-hidden me-2">
                        <div class="fw-semibold small text-truncate" style="color: var(--md-sys-color-on-surface);">${escapeHtml(data.participant.full_name)}</div>
                        <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                            ${escapeHtml(data.participant.student_id)} • 
                            <span class="badge bg-success" style="font-size: 9px; padding: 2px 6px !important;">Checked in</span> •
                            ${timeStr}
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        ${grpBadge}
                    </div>
                </div>
            `;

            // Add to scan-view list
            const item1 = document.createElement("div");
            item1.innerHTML = newItemHtml;
            item1.firstElementChild.style.backgroundColor = "var(--md-sys-color-primary-container)";
            item1.firstElementChild.style.transition = "background-color 1s ease";
            recentList.insertBefore(item1.firstElementChild, recentList.firstChild);
            setTimeout(() => { if (recentList.firstChild) recentList.firstChild.style.backgroundColor = ""; }, 1500);
            while (recentList.children.length > 5) { recentList.removeChild(recentList.lastChild); }
        }

        // AJAX Check-In Handler
        async function performCheckin(codeValue, fromCamera) {
            lastScannedFromCamera = !!fromCamera;
            submitBtn.disabled = true;
            submitBtn.innerText = "Checking in...";
            
            // Pause scanner immediately to prevent duplicate scans
            if (html5QrCode && html5QrCode.getState() === 2) {
                try {
                    html5QrCode.pause(true);
                } catch (e) {
                    console.error("Failed to pause scanner", e);
                }
            }

            try {
                const response = await fetch('/participants/checkin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({ qr_code: codeValue })
                });

                const data = await response.json();

                if (data.success) {
                    // Build large result card
                    let groupInfo = data.participant.group_code !== 'Not assigned' 
                        ? `<span class="badge bg-primary fs-5" style="padding: 6px 14px !important;">Group ${escapeHtml(data.participant.group_code)}</span>` 
                        : `<span class="badge bg-dark fs-6" style="padding: 4px 10px !important;">Not assigned</span>`;

                    let resultHtml = `
                        <div class="card p-4 p-md-5 border-0 mb-3" style="background-color: var(--md-sys-color-success-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-success-container) !important;">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="material-symbols-outlined" style="font-size: 40px;">check_circle</span>
                                <div>
                                    <div class="fw-bold" style="font-size: 1.3rem;">Check-In Successful</div>
                                </div>
                            </div>
                            <div style="font-size: 1.05rem; line-height: 1.8;">
                                <div class="mb-1"><strong>Name:</strong> ${escapeHtml(data.participant.full_name)}</div>
                                <div class="mb-1"><strong>Student ID:</strong> ${escapeHtml(data.participant.student_id)}</div>
                                <div class="mb-1"><strong>Email:</strong> ${escapeHtml(data.participant.student_email || 'N/A')}</div>
                                <div class="mb-1"><strong>Language:</strong> ${escapeHtml(data.participant.preferred_language || 'N/A')}</div>
                                <div class="mt-2"><strong>Group:</strong> ${groupInfo}</div>
                            </div>
                        </div>
                    `;

                    // Warning Card for Medical/Dietary Info
                    if (data.participant.medical_notes || data.participant.dietary_notes) {
                        let medicalNotes = data.participant.medical_notes 
                            ? `<div class="mb-2"><strong>Medical:</strong> ${escapeHtml(data.participant.medical_notes).replace(/\n/g, '<br>')}</div>` 
                            : '';
                        let dietaryNotes = data.participant.dietary_notes 
                            ? `<div class="mb-2"><strong>Dietary:</strong> ${escapeHtml(data.participant.dietary_notes).replace(/\n/g, '<br>')}</div>` 
                            : '';
                            
                        resultHtml += `
                            <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined" style="font-size: 28px;">warning</span>
                                    <strong style="font-size: 1.1rem;">Important Safety / Medical Info</strong>
                                </div>
                                <div style="font-size: 1rem; line-height: 1.6;">
                                    ${medicalNotes}
                                    ${dietaryNotes}
                                </div>
                            </div>
                        `;
                    }

                    // Dynamic notice
                    if (data.notice) {
                        resultHtml += `
                            <div class="alert alert-warning py-3 px-4 mb-3" style="border-radius: 16px; font-size: 1rem;">
                                ${escapeHtml(data.notice)}
                            </div>
                        `;
                    }

                    // Add to recent list then show result view
                    prependRecentItem(data);
                    showResultView(resultHtml);

                } else {
                    // Show error — if from camera, switch to result view with error; otherwise inline
                    let errorHtml = `
                        <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="material-symbols-outlined" style="font-size: 36px;">error</span>
                                <div style="font-size: 1.1rem; font-weight: 600;">${escapeHtml(data.message)}</div>
                            </div>
                        </div>
                    `;
                    if (fromCamera) {
                        showResultView(errorHtml);
                    } else {
                        statusContainer.innerHTML = errorHtml;
                    }
                }

            } catch (err) {
                console.error("Check-in error:", err);
                let errorHtml = `
                    <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
                        <div class="d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined" style="font-size: 36px;">report_problem</span>
                            <div style="font-size: 1.1rem; font-weight: 600;">System Error occurred during check-in.</div>
                        </div>
                    </div>
                `;
                if (fromCamera) {
                    showResultView(errorHtml);
                } else {
                    statusContainer.innerHTML = errorHtml;
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = "Check in";
                input.value = "";
            }
        }

        // Intercept Manual Input Submission
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const val = input.value.trim();
            if (val) {
                performCheckin(val, false);
            }
        });

        // Initialize QR Scanner
        function initScanner() {
            if (!window.Html5Qrcode) {
                console.error("html5-qrcode library not loaded");
                document.getElementById(qrRegionId).innerHTML = '<div class="alert alert-warning">QR scanner library failed to load. Please refresh the page.</div>';
                return;
            }

            html5QrCode = new Html5Qrcode(qrRegionId);

            function onScanSuccess(decodedText, decodedResult) {
                console.log("QR Code detected:", decodedText);
                performCheckin(decodedText, true);
            }

            function onScanFailure(error) {
                // Ignore scanning cycle errors (normal behaviour)
            }

            Html5Qrcode.getCameras().then(function (devices) {
                if (!devices || devices.length === 0) {
                    document.getElementById(qrRegionId).innerHTML = '<div class="alert alert-warning">No camera found. Please use manual code entry.</div>';
                    return;
                }
                
                let cameraId = devices.find(d => d.label.toLowerCase().includes('back'))?.id || devices[0].id;
                
                html5QrCode.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: {width: 250, height: 250},
                        aspectRatio: 1.0
                    },
                    onScanSuccess,
                    onScanFailure
                ).catch(function (err) {
                    console.error("Unable to start camera:", err);
                    document.getElementById(qrRegionId).innerHTML = 
                        '<div class="alert alert-danger">Camera access denied or unavailable. Please allow camera access or use manual code entry.</div>';
                });
            }).catch(function (err) {
                console.error("Error getting cameras:", err);
                document.getElementById(qrRegionId).innerHTML = 
                    '<div class="alert alert-danger">Unable to access camera. Please use manual code entry.</div>';
            });
        }

        if (window.Html5Qrcode) {
            initScanner();
        } else {
            setTimeout(function() {
                if (window.Html5Qrcode) {
                    initScanner();
                } else {
                    console.error("html5-qrcode library did not load");
                    document.getElementById(qrRegionId).innerHTML = 
                        '<div class="alert alert-warning">QR scanner library is loading... If this persists, please refresh the page.</div>';
                }
            }, 500);
        }
    });
</script>