</div> <!-- /container-fluid -->
<?php if (\App\Core\Auth::check() && !isset($forcePublic)): ?>
    </div> <!-- /m3-main-content -->
</div> <!-- /m3-layout-container -->
<?php else: ?>
</div> <!-- /m3-full-width-container -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const m3Sidebar = document.getElementById('m3Sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && m3Sidebar) {
        sidebarToggle.addEventListener('click', function() {
            m3Sidebar.classList.add('open');
        });
    }

    if (sidebarOverlay && m3Sidebar) {
        sidebarOverlay.addEventListener('click', function() {
            m3Sidebar.classList.remove('open');
        });
    }

    // Desktop Collapse / Expand Event Listeners
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const sidebarExpandBtn = document.getElementById('sidebarExpandBtn');

    if (sidebarCollapseBtn) {
        sidebarCollapseBtn.addEventListener('click', function() {
            document.documentElement.classList.add('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', 'true');
        });
    }

    if (sidebarExpandBtn) {
        sidebarExpandBtn.addEventListener('click', function() {
            document.documentElement.classList.remove('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', 'false');
        });
    }

    // Sidebar Theme Switcher click handler
    const sidebarThemeSwitcher = document.getElementById('sidebarThemeSwitcher');
    if (sidebarThemeSwitcher) {
        const themeSwatches = sidebarThemeSwitcher.querySelectorAll('.theme-swatch-btn');
        themeSwatches.forEach(swatch => {
            swatch.addEventListener('click', function() {
                const selectedTheme = this.getAttribute('data-theme-val');
                
                // 1. Instantly set theme attribute on html
                document.documentElement.setAttribute('data-theme', selectedTheme);
                
                // 2. Update active class on swatches
                themeSwatches.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                this.classList.add('saving');
                
                // 3. Sync to any dashboard theme inputs if they exist on the page
                const regFormThemeInput = document.getElementById('regFormThemeVal');
                if (regFormThemeInput) {
                    regFormThemeInput.value = selectedTheme;
                }
                
                // 4. Save to server via AJAX using the global window.currentRegMode
                const activeRegMode = window.currentRegMode || 'pre_reg';
                const formData = new FormData();
                formData.append('reg_mode', activeRegMode);
                formData.append('theme', selectedTheme);
                
                fetch('/settings/registration/save?format=json', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    this.classList.remove('saving');
                })
                .catch(error => {
                    console.error('Error saving theme settings:', error);
                    this.classList.remove('saving');
                });
            });
        });
    }
});
</script>
</body>
</html>