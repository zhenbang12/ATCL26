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
            // Wait for the 250ms CSS slide-out transition, then reflow layouts
            setTimeout(function() {
                window.dispatchEvent(new Event('resize'));
            }, 260);
        });
    }

    if (sidebarExpandBtn) {
        sidebarExpandBtn.addEventListener('click', function() {
            document.documentElement.classList.remove('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', 'false');
            // Wait for the 250ms CSS slide-in transition, then reflow layouts
            setTimeout(function() {
                window.dispatchEvent(new Event('resize'));
            }, 260);
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
                
                // 3. Save to server via AJAX
                const formData = new FormData();
                formData.append('theme', selectedTheme);
                
                fetch('/settings/theme/save?format=json', {
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
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error saving theme settings:', error);
                    this.classList.remove('saving');
                });
            });
        });
    // Global Dark / Light Mode Toggle button
    const colorModeBtn = document.getElementById('globalColorModeToggle');
    if (colorModeBtn) {
        const icon = colorModeBtn.querySelector('.mode-toggle-icon');
        const updateIcon = () => {
            const isDark = document.documentElement.getAttribute('data-color-mode') === 'dark';
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            colorModeBtn.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        };
        updateIcon();
        colorModeBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-color-mode') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-color-mode', next);
            localStorage.setItem('color-mode', next);
            updateIcon();
        });
    }
});
</script>
</body>
</html>