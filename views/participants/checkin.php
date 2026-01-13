<?php
// QR code / code input for check-in, with camera scanner
?>
<h2>QR Check-In</h2>

<div class="row mt-3">
    <div class="col-md-6 mb-4">
        <h5>Scan with camera</h5>
        <div id="qr-reader" style="width: 100%; max-width: 400px;"></div>
        <p class="text-muted mt-2">
            Allow camera access, then hold the participant's QR code in front of the camera.
        </p>
    </div>
    <div class="col-md-6 mb-4">
        <h5>Or enter code manually</h5>
        <form id="checkin-form" method="post" action="/participants/checkin" class="mt-2">
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" id="qr_code_input" name="qr_code" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Check in</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const qrRegionId = "qr-reader";
        const input = document.getElementById("qr_code_input");
        const form = document.getElementById("checkin-form");
        let html5QrCode = null;

        function initScanner() {
            if (!window.Html5Qrcode) {
                console.error("html5-qrcode library not loaded");
                document.getElementById(qrRegionId).innerHTML = '<div class="alert alert-warning">QR scanner library failed to load. Please refresh the page.</div>';
                return;
            }

            html5QrCode = new Html5Qrcode(qrRegionId);

            function onScanSuccess(decodedText, decodedResult) {
                console.log("QR Code detected:", decodedText);
                html5QrCode.stop().then(() => {
                    input.value = decodedText;
                    form.submit();
                }).catch(err => {
                    console.error("Error stopping scanner:", err);
                    input.value = decodedText;
                    form.submit();
                });
            }

            function onScanFailure(error) {
                // Ignore continuous scan errors - this is normal while scanning
            }

            // Request camera access and start scanning
            Html5Qrcode.getCameras().then(function (devices) {
                if (!devices || devices.length === 0) {
                    document.getElementById(qrRegionId).innerHTML = '<div class="alert alert-warning">No camera found. Please use manual code entry.</div>';
                    return;
                }
                
                // Prefer back camera (environment), fallback to first available
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

        // Wait for library to load
        if (window.Html5Qrcode) {
            initScanner();
        } else {
            // Wait a bit for the script to load
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
