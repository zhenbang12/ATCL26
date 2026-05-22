<?php
// QR code / code input for check-in, with camera scanner
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>QR Check-In</h2>
    <a href="/participants" class="btn btn-outline-primary btn-sm">Dashboard</a>
</div>
<p class="text-muted small">Participants receive their group here (round-robin by preferred language) if group shells are saved on the Grouping Overview page.</p>

<div class="row mt-3">
    <div class="col-md-6 mb-4">
        <h5 class="fw-bold mb-3" style="color: var(--md-sys-color-on-surface);">Scan with camera</h5>
        <div id="qr-reader" style="width: 100%; max-width: 400px; border-radius: 16px; overflow: hidden; border: 1px solid var(--md-sys-color-outline-variant);"></div>
        <p class="text-muted mt-2 small">
            Allow camera access, then hold the participant's QR code in front of the camera.
        </p>
    </div>
    <div class="col-md-6 mb-4">
        <h5 class="fw-bold mb-3" style="color: var(--md-sys-color-on-surface);">Or enter code/ID manually</h5>
        
        <!-- Live Status Container for Check-in Results -->
        <div id="checkin-status-container"></div>
        
        <form id="checkin-form" method="post" action="/participants/checkin" class="mt-2">
            <div class="mb-3">
                <label class="form-label" for="qr_code_input">Student ID or QR Code Hash</label>
                <input type="text" id="qr_code_input" name="qr_code" class="form-control" placeholder="e.g. 25WMR09999 or hash..." required autocomplete="off">
                <small class="text-muted mt-1 d-block">You can enter either the participant's Student ID (e.g. 25WMR09999) or their unique QR code hash.</small>
            </div>
            <button type="submit" id="checkin-btn" class="btn btn-primary w-100 py-2">Check in</button>
        </form>

        <div class="card p-4 border-0 mt-4" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
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

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const qrRegionId = "qr-reader";
        const input = document.getElementById("qr_code_input");
        const form = document.getElementById("checkin-form");
        const submitBtn = document.getElementById("checkin-btn");
        const statusContainer = document.getElementById("checkin-status-container");
        const recentList = document.getElementById("recent-checkins-list");
        const noCheckinsMsg = document.getElementById("no-checkins-msg");
        let html5QrCode = null;
        let resumeTimeout = null;
        let resumeInterval = null;

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // AJAX Check-In Handler
        async function performCheckin(codeValue) {
            submitBtn.disabled = true;
            submitBtn.innerText = "Checking in...";
            
            // Clear any active resume timers
            if (resumeTimeout) clearTimeout(resumeTimeout);
            if (resumeInterval) clearInterval(resumeInterval);
            
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
                
                statusContainer.innerHTML = ""; // Clear loader/previous status

                if (data.success) {
                    // 1. Success Notification Card
                    let groupInfo = data.participant.group_code !== 'Not assigned' 
                        ? `<span class="badge bg-primary fs-6" style="padding: 4px 8px !important;">Group ${escapeHtml(data.participant.group_code)}</span>` 
                        : `<span class="text-muted">Not assigned</span>`;

                    let successHtml = `
                        <div class="card p-3 border-0 mb-3" style="background-color: var(--md-sys-color-success-container) !important; border: 1px solid rgba(0,200,0,0.15) !important; border-radius: 16px !important; color: var(--md-sys-color-on-success-container) !important;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 24px;">check_circle</span>
                                <strong class="fs-6">Check-In Successful</strong>
                            </div>
                            <div class="small">
                                <div class="mb-1"><strong>Name:</strong> ${escapeHtml(data.participant.full_name)}</div>
                                <div class="mb-1"><strong>Student ID:</strong> ${escapeHtml(data.participant.student_id)}</div>
                                <div class="mb-1"><strong>Group:</strong> ${groupInfo}</div>
                            </div>
                        </div>
                    `;
                    statusContainer.innerHTML += successHtml;

                    // 2. Warning Card for Medical/Dietary Info
                    if (data.participant.medical_notes || data.participant.dietary_notes) {
                        let medicalNotes = data.participant.medical_notes 
                            ? `<div><strong>Medical:</strong> ${escapeHtml(data.participant.medical_notes).replace(/\n/g, '<br>')}</div>` 
                            : '';
                        let dietaryNotes = data.participant.dietary_notes 
                            ? `<div><strong>Dietary:</strong> ${escapeHtml(data.participant.dietary_notes).replace(/\n/g, '<br>')}</div>` 
                            : '';
                            
                        let warningHtml = `
                            <div class="card p-3 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border: 1px solid rgba(255,0,0,0.1) !important; border-radius: 16px !important; color: var(--md-sys-color-on-error-container) !important;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-danger" style="font-size: 24px;">warning</span>
                                    <strong class="fs-6">Important Safety / Medical Info</strong>
                                </div>
                                <div class="small">
                                    ${medicalNotes}
                                    ${dietaryNotes}
                                </div>
                            </div>
                        `;
                        statusContainer.innerHTML += warningHtml;
                    }

                    // 3. Dynamic Warning if Group assignment had warning details
                    if (data.notice) {
                        let noticeHtml = `
                            <div class="alert alert-warning py-2 px-3 mb-3 small" style="border-radius: 12px;">
                                ${escapeHtml(data.notice)}
                            </div>
                        `;
                        statusContainer.innerHTML += noticeHtml;
                    }

                    // 4. Prepend to Recent Check-ins
                    if (noCheckinsMsg) {
                        noCheckinsMsg.style.display = 'none';
                    }
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                    const newItem = document.createElement("div");
                    newItem.className = "list-group-item d-flex justify-content-between align-items-center py-2 px-3";
                    newItem.style.backgroundColor = "var(--md-sys-color-primary-container)";
                    newItem.style.transition = "background-color 1s ease";
                    
                    let grpBadge = data.participant.group_code !== 'Not assigned'
                        ? `<span class="badge bg-primary" style="font-size: 11px; padding: 4px 8px !important;">Group ${escapeHtml(data.participant.group_code)}</span>`
                        : `<span class="badge bg-dark" style="font-size: 11px; padding: 4px 8px !important;">No Group</span>`;

                    newItem.innerHTML = `
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
                    `;
                    recentList.insertBefore(newItem, recentList.firstChild);
                    
                    // Fade out highlight container background
                    setTimeout(() => {
                        newItem.style.backgroundColor = "";
                    }, 1500);

                    // Maintain max 5 items in recent list
                    while (recentList.children.length > 5) {
                        recentList.removeChild(recentList.lastChild);
                    }
                } else {
                    // Show Error Notification Card
                    statusContainer.innerHTML = `
                        <div class="card p-3 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border: 1px solid rgba(255,0,0,0.15) !important; border-radius: 16px !important; color: var(--md-sys-color-on-error-container) !important;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined text-danger" style="font-size: 24px;">error</span>
                                <strong class="fs-6">${escapeHtml(data.message)}</strong>
                            </div>
                        </div>
                    `;
                }

            } catch (err) {
                console.error("Check-in error:", err);
                statusContainer.innerHTML = `
                    <div class="card p-3 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 16px !important; color: var(--md-sys-color-on-error-container) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined text-danger">report_problem</span>
                            <strong class="fs-6">System Error occurred during check-in.</strong>
                        </div>
                    </div>
                `;
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = "Check in";
                input.value = "";
                input.focus();

                // Setup auto-resume for the camera scanner with countdown
                if (html5QrCode && html5QrCode.getState() === 3) {
                    let secondsLeft = 3;
                    const timerDiv = document.createElement("div");
                    timerDiv.id = "scanner-timer";
                    timerDiv.className = "text-center text-primary mt-2 small fw-semibold";
                    timerDiv.innerText = `Resuming scanner in ${secondsLeft}s...`;
                    statusContainer.appendChild(timerDiv);

                    resumeInterval = setInterval(() => {
                        secondsLeft--;
                        if (timerDiv) {
                            timerDiv.innerText = `Resuming scanner in ${secondsLeft}s...`;
                        }
                    }, 1000);

                    resumeTimeout = setTimeout(() => {
                        clearInterval(resumeInterval);
                        if (timerDiv) timerDiv.remove();
                        
                        if (html5QrCode && html5QrCode.getState() === 3) {
                            try {
                                html5QrCode.resume();
                            } catch (e) {
                                console.error("Failed to resume scanner", e);
                            }
                        }
                    }, 3000);
                }
            }
        }

        // Intercept Manual Input Submission
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const val = input.value.trim();
            if (val) {
                performCheckin(val);
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
                performCheckin(decodedText);
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
