<?php
// Page view for advisor/committee to view registration and check-in insights.
/** @var string $sessionName */
/** @var array<string, int> $summary */
/** @var array<array{reg_date: string, count: string|int}> $regOverTime */
/** @var array<array{reg_date: string, reg_hour: string|int, count: string|int}> $hourlyRegData */
/** @var array<array{checkin_date: string, checkin_hour: string|int, count: string|int}> $hourlyCheckinData */
/** @var array<array{faculty: string, count: string|int}> $facultyDistribution */
/** @var array<array{preferred_language: string, count: string|int}> $languageDistribution */
/** @var array<array{gender: string, count: string|int}> $genderDistribution */
/** @var array<array{group_code: string, count: string|int}> $groupSizes */

$attendanceRate = $summary['total_active'] > 0 
    ? round(($summary['checked_in'] / $summary['total_active']) * 100, 1) 
    : 0;
$dropoutCount = max(0, $summary['total_active'] - $summary['checked_in']);
$dropoutRate = $summary['total_active'] > 0 
    ? round(($dropoutCount / $summary['total_active']) * 100, 1) 
    : 0;
?>

<style>
    .insights-container {
        margin-top: -1rem;
    }
    
    .insights-header {
        background: linear-gradient(135deg, var(--md-sys-color-primary-container) 0%, var(--md-sys-color-surface-container-high) 100%) !important;
        color: var(--md-sys-color-on-primary-container) !important;
        padding: 2.5rem 2rem;
        border-radius: 28px;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--md-sys-color-outline-variant);
    }
    
    .insights-header::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, var(--md-sys-color-primary) 0%, transparent 70%);
        opacity: 0.15;
        pointer-events: none;
    }

    .insight-stat-card {
        border: 1px solid var(--md-sys-color-outline-variant) !important;
        border-radius: 20px !important;
        background-color: var(--md-sys-color-surface-container-low) !important;
        transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .insight-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background-color: var(--card-accent-color, var(--md-sys-color-primary));
    }

    .insight-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px var(--md-sys-color-shadow) !important;
    }

    .chart-card {
        background-color: var(--md-sys-color-surface-container-lowest) !important;
        border: 1px solid var(--md-sys-color-outline-variant) !important;
        border-radius: 24px !important;
        padding: 1.5rem !important;
        height: 100%;
        transition: box-shadow 0.3s ease;
    }
    
    .chart-card:hover {
        box-shadow: 0 6px 14px var(--md-sys-color-shadow) !important;
    }

    .chart-title-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }

    .chart-icon-box {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background-color: var(--md-sys-color-primary-container);
        color: var(--md-sys-color-on-primary-container);
    }

    .progress-bar-container {
        height: 8px;
        border-radius: 4px;
        background-color: var(--md-sys-color-surface-container-highest);
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--md-sys-color-primary) 0%, var(--md-sys-color-tertiary) 100%);
        border-radius: 4px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .empty-state-card {
        text-align: center;
        padding: 4rem 2rem;
        border: 2px dashed var(--md-sys-color-outline-variant) !important;
        border-radius: 24px !important;
        background-color: var(--md-sys-color-surface-container-low) !important;
    }

    .chart-download-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 8px;
        background: var(--md-sys-color-surface-container-lowest);
        color: var(--md-sys-color-on-surface-variant);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .chart-download-btn:hover {
        background: var(--md-sys-color-primary-container);
        color: var(--md-sys-color-on-primary-container);
        border-color: var(--md-sys-color-primary);
    }
    .chart-download-btn .material-symbols-outlined {
        font-size: 18px;
    }

    .empty-state-icon {
        font-size: 64px;
        color: var(--md-sys-color-outline);
        margin-bottom: 1.5rem;
        display: inline-block;
        animation: pulse 2s infinite ease-in-out;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.8; }
        50% { transform: scale(1.05); opacity: 0.5; }
    }
</style>

<div class="insights-container">
    <!-- Header Block -->
    <header class="insights-header mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
                        <li class="breadcrumb-item"><a href="/dashboard" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item active text-primary" aria-current="page">Insights & Graphs</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-1 fw-bold" style="color: var(--md-sys-color-on-primary-container);">Event Insights & Graphs</h1>
                <p class="mb-0 text-secondary" style="color: var(--md-sys-color-on-primary-container); opacity: 0.85;">
                    Visual statistics and registration analysis for session: <strong class="text-primary fw-semibold"><?= htmlspecialchars($sessionName) ?></strong>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button onclick="window.print()" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
                    Print Report
                </button>
            </div>
        </div>
    </header>

    <?php if ($summary['total_active'] === 0): ?>
        <!-- Empty State View -->
        <div class="card empty-state-card mb-4">
            <span class="material-symbols-outlined empty-state-icon">bar_chart</span>
            <h2 class="h4 fw-bold mb-2">No Registrations in this Session</h2>
            <p class="text-muted mb-4 max-w-md mx-auto" style="max-width: 500px; margin: 0 auto 1.5rem;">
                There is currently no participant data registered for the <strong><?= htmlspecialchars($sessionName) ?></strong> session. Once registrations are received, interactive charts and insights will populate here automatically.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/participants/create" class="btn btn-primary">Pre-register Participant</a>
                <a href="/participants/create-walkin" class="btn btn-outline-primary">Add Walk-in</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Summary Cards Grid -->
        <div class="row g-3 mb-4">
            <!-- Total Active -->
            <div class="col-lg col-md-4 col-sm-6">
                <div class="insight-stat-card card p-3 h-100" style="--card-accent-color: var(--md-sys-color-primary);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Active Registered</span>
                        <span class="material-symbols-outlined text-primary" style="font-size: 20px;">groups</span>
                    </div>
                    <div class="fs-2 fw-bold text-primary mb-1"><?= number_format($summary['total_active']) ?></div>
                    <div class="small text-muted">Excluding resolved duplicates</div>
                </div>
            </div>

            <!-- Checked In -->
            <div class="col-lg col-md-4 col-sm-6">
                <div class="insight-stat-card card p-3 h-100" style="--card-accent-color: var(--md-sys-color-success);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Attendance Rate</span>
                        <span class="material-symbols-outlined text-success" style="font-size: 20px;">assignment_turned_in</span>
                    </div>
                    <div class="fs-2 fw-bold text-success mb-1"><?= $attendanceRate ?>%</div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="progress-bar-container flex-grow-1">
                            <div class="progress-bar-fill" style="width: <?= $attendanceRate ?>%; background: var(--md-sys-color-success);"></div>
                        </div>
                    </div>
                    <div class="small text-muted"><?= number_format($summary['checked_in']) ?> of <?= number_format($summary['total_active']) ?> checked in</div>
                </div>
            </div>

            <!-- Dropout Rate -->
            <div class="col-lg col-md-4 col-sm-6">
                <div class="insight-stat-card card p-3 h-100" style="--card-accent-color: #E28413;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Dropout Rate</span>
                        <span class="material-symbols-outlined text-warning" style="font-size: 20px; color: #E28413 !important;">cancel_presentation</span>
                    </div>
                    <div class="fs-2 fw-bold mb-1" style="color: #E28413;"><?= $dropoutRate ?>%</div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="progress-bar-container flex-grow-1">
                            <div class="progress-bar-fill" style="width: <?= $dropoutRate ?>%; background: #E28413;"></div>
                        </div>
                    </div>
                    <div class="small text-muted mb-2"><?= number_format($dropoutCount) ?> of <?= number_format($summary['total_active']) ?> no-shows</div>
                    <div class="border-top pt-2 mt-2" style="font-size: 0.75rem;">
                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span>Pre-register:</span>
                            <span class="fw-semibold" style="color: var(--md-sys-color-on-surface);"><?= $summary['pre_register_dropout_rate'] ?>% <span class="fw-normal text-muted">(<?= number_format($summary['pre_register_dropout']) ?>)</span></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span>Walk-in:</span>
                            <span class="fw-semibold" style="color: var(--md-sys-color-on-surface);"><?= $summary['walk_in_dropout_rate'] ?>% <span class="fw-normal text-muted">(<?= number_format($summary['walk_in_dropout']) ?>)</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pre-registered vs Walk-in -->
            <div class="col-lg col-md-6 col-sm-6">
                <div class="insight-stat-card card p-3 h-100" style="--card-accent-color: var(--md-sys-color-tertiary);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Registration Types</span>
                        <span class="material-symbols-outlined text-info" style="font-size: 20px;">join_inner</span>
                    </div>
                    <div class="fs-2 fw-bold mb-1" style="color: var(--md-sys-color-tertiary);"><?= number_format($summary['pre_register']) ?></div>
                    <div class="small text-muted mb-2">
                        Pre-registered vs <strong><?= number_format($summary['walk_in']) ?></strong> Walk-ins
                    </div>
                    <div class="border-top pt-2 mt-2" style="font-size: 0.75rem;">
                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span>Pre-reg Turnout:</span>
                            <span class="fw-semibold" style="color: var(--md-sys-color-on-surface);"><?= number_format($summary['pre_register_checked_in']) ?> / <?= number_format($summary['pre_register']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span>Walk-in Turnout:</span>
                            <span class="fw-semibold" style="color: var(--md-sys-color-on-surface);"><?= number_format($summary['walk_in_checked_in']) ?> / <?= number_format($summary['walk_in']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Duplicate Flagged -->
            <div class="col-lg col-md-6 col-sm-6">
                <div class="insight-stat-card card p-3 h-100" style="--card-accent-color: var(--md-sys-color-error);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Flagged Duplicates</span>
                        <span class="material-symbols-outlined text-danger" style="font-size: 20px;">content_copy</span>
                    </div>
                    <div class="fs-2 fw-bold text-danger mb-1"><?= number_format($summary['duplicates']) ?></div>
                    <div class="small text-muted">
                        <?php if ($summary['duplicates'] > 0): ?>
                            <a href="/participants/duplicates" class="text-decoration-none text-danger fw-semibold d-inline-flex align-items-center gap-1">
                                Resolve Duplicates
                                <span class="material-symbols-outlined" style="font-size: 14px;">arrow_forward</span>
                            </a>
                        <?php else: ?>
                            Zero duplicate accounts found
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Registration Flow Trend -->
            <div class="col-12">
                <div class="chart-card card">
                    <div class="chart-title-container">
                        <div>
                            <h2 class="h5 mb-1 fw-bold">Registration Trend</h2>
                            <p class="text-muted small mb-0">Timeline showing daily registration frequency and cumulative growth</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="chart-download-btn" onclick="downloadChart('registrationTrendChart', 'registration-trend', 'Registration Trend')" title="Download chart">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                            <div class="chart-icon-box">
                                <span class="material-symbols-outlined">show_chart</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 320px; position: relative;">
                        <canvas id="registrationTrendChart"></canvas>
                    </div>
                </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Registration Peak Hours -->
            <div class="col-md-6">
                <div class="chart-card card">
                    <div class="chart-title-container">
                        <div>
                            <h2 class="h5 mb-1 fw-bold">Registration Peak Hours</h2>
                            <p class="text-muted small mb-0">Hourly volume of online pre-registrations</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <select id="rushHourDateSelector" class="form-select form-select-sm" style="font-size: 0.8rem; padding: 0.25rem 1.75rem 0.25rem 0.75rem;" aria-label="Select Date for Registration Peak Hours">
                                <option value="all">All Dates</option>
                            </select>
                            <button class="chart-download-btn" onclick="downloadChart('registrationHoursChart', 'registration-peak-hours', 'Registration Peak Hours')" title="Download chart">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                            <div class="chart-icon-box">
                                <span class="material-symbols-outlined">how_to_reg</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="registrationHoursChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Check-in Peak Hours -->
            <div class="col-md-6">
                <div class="chart-card card">
                    <div class="chart-title-container">
                        <div>
                            <h2 class="h5 mb-1 fw-bold">Check-in Peak Hours</h2>
                            <p class="text-muted small mb-0">Hourly arrival traffic at registration desks</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="chart-download-btn" onclick="downloadChart('checkinHoursChart', 'checkin-peak-hours', 'Check-in Peak Hours')" title="Download chart">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                            <div class="chart-icon-box">
                                <span class="material-symbols-outlined">login</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="checkinHoursChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Turnout & Dropout Analysis by Registration Type -->
        <div class="row g-4 mb-4">
            <!-- Grouped Bar Chart -->
            <div class="col-md-6">
                <div class="chart-card card">
                    <div class="chart-title-container">
                        <div>
                            <h2 class="h5 mb-1 fw-bold">Turnout vs. Dropout Comparison</h2>
                            <p class="text-muted small mb-0">Turnout (Checked-in) vs. Dropout (No-show) for each registration mode</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="chart-download-btn" onclick="downloadChart('regTypeBreakdownChart', 'turnout-dropout-comparison', 'Turnout vs. Dropout Comparison')" title="Download chart">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                            <div class="chart-icon-box">
                                <span class="material-symbols-outlined">compare_arrows</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="regTypeBreakdownChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats Table -->
            <div class="col-md-6">
                <div class="chart-card card d-flex flex-column justify-content-between" id="turnoutDetailsCard">
                    <div>
                        <div class="chart-title-container">
                            <div>
                                <h2 class="h5 mb-1 fw-bold">Registration Mode Turnout Details</h2>
                                <p class="text-muted small mb-0">Turnout and no-show stats with percentage rates</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="chart-download-btn" onclick="downloadTable('turnoutDetailsCard', 'registration-mode-turnout-details')" title="Download table">
                                    <span class="material-symbols-outlined">download</span>
                                </button>
                                <div class="chart-icon-box" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container);">
                                    <span class="material-symbols-outlined">table_chart</span>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-muted fw-semibold">Reg. Mode</th>
                                        <th scope="col" class="text-muted fw-semibold text-center">Total</th>
                                        <th scope="col" class="text-muted fw-semibold text-center">Attended</th>
                                        <th scope="col" class="text-muted fw-semibold text-center">No-Show</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">Pre-registered</td>
                                        <td class="text-center"><?= number_format($summary['pre_register']) ?></td>
                                        <td class="text-center">
                                            <span class="badge text-success-emphasis bg-success-subtle border border-success-subtle px-2 py-1 rounded-pill">
                                                <?= number_format($summary['pre_register_checked_in']) ?> (<?= 100 - $summary['pre_register_dropout_rate'] ?>%)
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge text-warning-emphasis bg-warning-subtle border border-warning-subtle px-2 py-1 rounded-pill">
                                                <?= number_format($summary['pre_register_dropout']) ?> (<?= $summary['pre_register_dropout_rate'] ?>%)
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Walk-in</td>
                                        <td class="text-center"><?= number_format($summary['walk_in']) ?></td>
                                        <td class="text-center">
                                            <span class="badge text-success-emphasis bg-success-subtle border border-success-subtle px-2 py-1 rounded-pill">
                                                <?= number_format($summary['walk_in_checked_in']) ?> (<?= 100 - $summary['walk_in_dropout_rate'] ?>%)
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge text-warning-emphasis bg-warning-subtle border border-warning-subtle px-2 py-1 rounded-pill">
                                                <?= number_format($summary['walk_in_dropout']) ?> (<?= $summary['walk_in_dropout_rate'] ?>%)
                                            </span>
                                        </td>
                                    </tr>
                                    <tr style="border-top: 2px solid var(--md-sys-color-outline-variant);">
                                        <td class="fw-bold">Total Active</td>
                                        <td class="text-center fw-bold"><?= number_format($summary['total_active']) ?></td>
                                        <td class="text-center fw-bold">
                                            <span class="badge bg-success text-white px-2 py-1 rounded-pill">
                                                <?= number_format($summary['checked_in']) ?> (<?= $attendanceRate ?>%)
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <span class="badge text-white px-2 py-1 rounded-pill" style="background-color: #E28413 !important;">
                                                <?= number_format($dropoutCount) ?> (<?= $dropoutRate ?>%)
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Faculty Distribution - Full Width -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="chart-card card">
                    <div class="chart-title-container">
                        <div>
                            <h2 class="h5 mb-1 fw-bold">Faculty Distribution</h2>
                            <p class="text-muted small mb-0">Number of participants enrolled by faculty</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="chart-download-btn" onclick="downloadChart('facultyDistributionChart', 'faculty-distribution', 'Faculty Distribution')" title="Download chart">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                            <div class="chart-icon-box">
                                <span class="material-symbols-outlined">school</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="facultyDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Group Sizes - Full Width -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="chart-card card">
                    <div class="chart-title-container">
                        <div>
                            <h2 class="h5 mb-1 fw-bold">Group Sizes (Balancing)</h2>
                            <p class="text-muted small mb-0">Headcount in active groups for event slots</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="chart-download-btn" onclick="downloadChart('groupSizesChart', 'group-sizes', 'Group Sizes')" title="Download chart">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                            <div class="chart-icon-box">
                                <span class="material-symbols-outlined">group_work</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="groupSizesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demographics & Attendance Doughnuts Grid -->
        <div class="card p-4 mb-4">
            <h2 class="h5 mb-1 fw-bold">Participant Demographics & Attendance</h2>
            <p class="text-muted small mb-4">Insights based on registration type, preferred language, gender, and attendance status</p>
            <div class="row g-4">
                <!-- Reg Type Doughnut -->
                <div class="col-lg-3 col-sm-6 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <h3 class="h6 mb-0 fw-semibold">Registration Mode</h3>
                        <button class="chart-download-btn" onclick="downloadChart('regTypeDoughnut', 'registration-mode', 'Registration Mode')" title="Download chart" style="width:26px;height:26px;">
                            <span class="material-symbols-outlined" style="font-size:15px;">download</span>
                        </button>
                    </div>
                    <div style="height: 220px; position: relative; margin: 0 auto;" class="d-flex justify-content-center">
                        <canvas id="regTypeDoughnut" style="max-width: 220px; max-height: 220px;"></canvas>
                    </div>
                </div>
                <!-- Language Doughnut -->
                <div class="col-lg-3 col-sm-6 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <h3 class="h6 mb-0 fw-semibold">Preferred Language</h3>
                        <button class="chart-download-btn" onclick="downloadChart('languageDoughnut', 'preferred-language', 'Preferred Language')" title="Download chart" style="width:26px;height:26px;">
                            <span class="material-symbols-outlined" style="font-size:15px;">download</span>
                        </button>
                    </div>
                    <div style="height: 220px; position: relative; margin: 0 auto;" class="d-flex justify-content-center">
                        <canvas id="languageDoughnut" style="max-width: 220px; max-height: 220px;"></canvas>
                    </div>
                </div>
                <!-- Gender Doughnut -->
                <div class="col-lg-3 col-sm-6 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <h3 class="h6 mb-0 fw-semibold">Gender Breakdown</h3>
                        <button class="chart-download-btn" onclick="downloadChart('genderDoughnut', 'gender-breakdown', 'Gender Breakdown')" title="Download chart" style="width:26px;height:26px;">
                            <span class="material-symbols-outlined" style="font-size:15px;">download</span>
                        </button>
                    </div>
                    <div style="height: 220px; position: relative; margin: 0 auto;" class="d-flex justify-content-center">
                        <canvas id="genderDoughnut" style="max-width: 220px; max-height: 220px;"></canvas>
                    </div>
                </div>
                <!-- Attendance/Dropout Doughnut -->
                <div class="col-lg-3 col-sm-6 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <h3 class="h6 mb-0 fw-semibold">Attendance vs. Dropout</h3>
                        <button class="chart-download-btn" onclick="downloadChart('attendanceDropoutDoughnut', 'attendance-dropout', 'Attendance vs. Dropout')" title="Download chart" style="width:26px;height:26px;">
                            <span class="material-symbols-outlined" style="font-size:15px;">download</span>
                        </button>
                    </div>
                    <div style="height: 220px; position: relative; margin: 0 auto;" class="d-flex justify-content-center">
                        <canvas id="attendanceDropoutDoughnut" style="max-width: 220px; max-height: 220px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Load Chart.js CDN + Datalabels Plugin + html2canvas for table download -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
// --- Download helper (global, available immediately) ---
// --- Download table card as PNG using html2canvas ---
function downloadTable(elementId, filename) {
    var el = document.getElementById(elementId);
    if (!el || typeof html2canvas === 'undefined') return;
    html2canvas(el, {
        backgroundColor: '#ffffff',
        scale: 2,
        useCORS: true
    }).then(function(canvas) {
        var link = document.createElement('a');
        link.download = filename + '.png';
        link.href = canvas.toDataURL('image/png', 1.0);
        link.click();
    });
}

function downloadChart(canvasId, filename, title) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    var dpr = window.devicePixelRatio || 1;
    // Title area: 50px in CSS, scaled by DPR for pixel accuracy
    var titleCssHeight = title ? 50 : 0;
    var titlePx = titleCssHeight * dpr;
    // Create a new canvas with white background + title space
    var newCanvas = document.createElement('canvas');
    newCanvas.width = canvas.width;
    newCanvas.height = canvas.height + titlePx;
    var ctx = newCanvas.getContext('2d');
    // Fill entire canvas white
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);
    // Draw title if provided
    if (title) {
        ctx.save();
        ctx.scale(dpr, dpr);
        ctx.fillStyle = '#1C1B1F';
        ctx.font = 'bold 22px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(title, (newCanvas.width / dpr) / 2, titleCssHeight / 2);
        ctx.restore();
    }
    // Draw chart below title
    ctx.drawImage(canvas, 0, titlePx);
    var link = document.createElement('a');
    link.download = filename + '.png';
    link.href = newCanvas.toDataURL('image/png', 1.0);
    link.click();
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($summary['total_active'] > 0): ?>
        // --- 1. Fetch data from PHP variables ---
        const regOverTimeData = <?= json_encode($regOverTime) ?>;
        const hourlyRegData = <?= json_encode($hourlyRegData) ?>;
        const hourlyCheckinData = <?= json_encode($hourlyCheckinData) ?>;
        const facultyData = <?= json_encode($facultyDistribution) ?>;
        const languageData = <?= json_encode($languageDistribution) ?>;
        const genderData = <?= json_encode($genderDistribution) ?>;
        const groupSizesData = <?= json_encode($groupSizes) ?>;
        
        // --- Register Datalabels plugin globally, disable by default ---
        Chart.register(ChartDataLabels);
        Chart.defaults.plugins.datalabels = { display: false };

        // --- 2. Chart Styling Helpers (Material Design Harmonious Color Palettes) ---
        // Fetch variables from document body/style computed tokens if needed, else use beautiful modern hues.
        const computedStyle = getComputedStyle(document.body);
        const primaryColor = computedStyle.getPropertyValue('--md-sys-color-primary').trim() || '#6750A4';
        const primaryContainer = computedStyle.getPropertyValue('--md-sys-color-primary-container').trim() || '#EADDFF';
        const successColor = '#1B6B38';
        const tertiaryColor = '#7D5260';
        const errorColor = '#B3261E';
        
        // Palette choices for distributions
        const colorsPalette = [
            '#6750A4', // Violet
            '#006C51', // Teal/Green
            '#9C403C', // Terracotta
            '#7D5260', // Rose
            '#A2396C', // Pink
            '#2D5C8F', // Steel Blue
            '#6F5B3E', // Sand
            '#51624F', // Sage
            '#4F616E'  // Slate
        ];
        
        Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif";
        Chart.defaults.color = computedStyle.getPropertyValue('--md-sys-color-on-surface-variant').trim() || '#49454F';
        Chart.defaults.font.size = 12;

        // --- 3. Registration Trend (Timeline Chart) ---
        const dates = regOverTimeData.map(d => d.reg_date);
        const dailyCounts = regOverTimeData.map(d => parseInt(d.count));
        
        // Cumulative count computation for gradient fill line chart
        let cumulativeSum = 0;
        const cumulativeCounts = dailyCounts.map(count => cumulativeSum += count);

        const ctxTrend = document.getElementById('registrationTrendChart').getContext('2d');
        const gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 300);
        gradientTrend.addColorStop(0, primaryColor + '40'); // 25% opacity
        gradientTrend.addColorStop(1, primaryColor + '00'); // Transparent

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Total Registered (Cumulative)',
                        data: cumulativeCounts,
                        borderColor: primaryColor,
                        borderWidth: 3,
                        backgroundColor: gradientTrend,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#FFFFFF',
                        pointHoverRadius: 7,
                        pointRadius: 4,
                        yAxisID: 'yCumulative',
                        datalabels: {
                            display: true,
                            anchor: 'end',
                            align: 'top',
                            offset: 8,
                            color: '#1C1B1F',
                            font: { weight: 'bold', size: 12, family: "'Plus Jakarta Sans', system-ui, sans-serif" },
                            textStrokeColor: '#ffffff',
                            textStrokeWidth: 3,
                            formatter: function(value) { return value; }
                        }
                    },
                    {
                        type: 'bar',
                        label: 'New Daily Registrations',
                        data: dailyCounts,
                        backgroundColor: '#6750A425',
                        borderColor: '#6750A460',
                        borderWidth: 1,
                        borderRadius: 4,
                        yAxisID: 'yDaily',
                        datalabels: {
                            display: true,
                            anchor: 'center',
                            align: 'inside',
                            color: '#ffffff',
                            font: { weight: 'bold', size: 11, family: "'Plus Jakarta Sans', system-ui, sans-serif" },
                            textStrokeColor: primaryColor,
                            textStrokeWidth: 3,
                            formatter: function(value) { return value > 0 ? value : ''; }
                        }
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 16, usePointStyle: true, pointStyle: 'circle' }
                    },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 12,
                        backgroundColor: 'rgba(29, 27, 32, 0.95)',
                        titleFont: { weight: 'bold' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 45, minRotation: 0 }
                    },
                    yCumulative: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Cumulative Registered' },
                        grid: { color: '#E6E1E5' }
                    },
                    yDaily: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Daily Increment' },
                        grid: { display: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // --- 4. Interactive Date-Filter Peak Activity Hours Chart Logic ---
        // Helper to compile a 24h array (0-23) from the grouped data
        function get24hArray(dataList, filterDate) {
            const arr = Array(24).fill(0);
            dataList.forEach(item => {
                const dateVal = item.reg_date !== undefined ? item.reg_date : item.checkin_date;
                if (filterDate === 'all' || dateVal === filterDate) {
                    const hr = parseInt(item.reg_hour !== undefined ? item.reg_hour : item.checkin_hour);
                    const cnt = parseInt(item.count);
                    if (!isNaN(hr) && hr >= 0 && hr < 24) {
                        arr[hr] += cnt;
                    }
                }
            });
            return arr;
        }

        // Collect unique dates only from registration datasets
        const uniqueDates = new Set();
        hourlyRegData.forEach(item => { if (item.reg_date) uniqueDates.add(item.reg_date); });
        
        // Sort dates chronologically
        const sortedDates = Array.from(uniqueDates).sort();

        // Populate the dropdown selector
        const dateSelector = document.getElementById('rushHourDateSelector');
        if (dateSelector) {
            sortedDates.forEach(dateStr => {
                const option = document.createElement('option');
                option.value = dateStr;
                try {
                    // Split date to avoid timezone offset issues when formatting
                    const parts = dateStr.split('-');
                    const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    option.textContent = d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
                } catch(e) {
                    option.textContent = dateStr;
                }
                dateSelector.appendChild(option);
            });
        }

        // Initialize 24-hour datasets (defaults to 'all' dates)
        const initialRegData = get24hArray(hourlyRegData, 'all');
        const initialCheckinData = get24hArray(hourlyCheckinData, 'all');

        const hoursLabels = Array.from({length: 24}, (_, i) => `${i.toString().padStart(2, '0')}:00`);

        // --- Common bar datalabels config ---
        const barDatalabels = {
            display: true,
            anchor: 'end',
            align: 'end',
            offset: 4,
            color: '#1C1B1F',
            font: { weight: 'bold', size: 11, family: "'Plus Jakarta Sans', system-ui, sans-serif" },
            textStrokeColor: '#ffffff',
            textStrokeWidth: 3,
            formatter: function(value) { return value > 0 ? value : ''; }
        };
        // Common layout padding to prevent top bar labels from clipping
        const barLayoutPadding = { padding: { top: 24 } };

        // --- 4.1 Registration Peak Hours Chart ---
        const ctxRegHours = document.getElementById('registrationHoursChart').getContext('2d');
        const regHoursChart = new Chart(ctxRegHours, {
            type: 'bar',
            data: {
                labels: hoursLabels,
                datasets: [{
                    label: 'Registrations',
                    data: initialRegData,
                    backgroundColor: primaryColor + 'CC', // 80% opacity
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: barLayoutPadding,
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 10, cornerRadius: 8 },
                    datalabels: barDatalabels
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            callback: function(val, index) {
                                return index % 3 === 0 ? hoursLabels[index] : '';
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#E6E1E5' }
                    }
                }
            }
        });

        // --- 4.2 Check-in Peak Hours Chart ---
        const ctxCheckinHours = document.getElementById('checkinHoursChart').getContext('2d');
        const checkinHoursChart = new Chart(ctxCheckinHours, {
            type: 'bar',
            data: {
                labels: hoursLabels,
                datasets: [{
                    label: 'Check-ins',
                    data: initialCheckinData,
                    backgroundColor: successColor + 'CC',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: barLayoutPadding,
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 10, cornerRadius: 8 },
                    datalabels: barDatalabels
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            callback: function(val, index) {
                                return index % 3 === 0 ? hoursLabels[index] : '';
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#E6E1E5' }
                    }
                }
            }
        });

        // Handle dropdown selection change (Registration only)
        if (dateSelector) {
            dateSelector.addEventListener('change', function() {
                const targetDate = this.value;
                const newReg = get24hArray(hourlyRegData, targetDate);

                regHoursChart.data.datasets[0].data = newReg;
                regHoursChart.update();
            });
        }

        // --- 5. Faculty Distribution (Horizontal Bar Chart) ---
        const faculties = facultyData.map(f => f.faculty || 'Not Specified');
        const facultyCounts = facultyData.map(f => parseInt(f.count));
        const ctxFaculty = document.getElementById('facultyDistributionChart').getContext('2d');
        new Chart(ctxFaculty, {
            type: 'bar',
            data: {
                labels: faculties,
                datasets: [{
                    label: 'Participants',
                    data: facultyCounts,
                    backgroundColor: colorsPalette,
                    borderRadius: 8,
                    barThickness: 16
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { right: 40 } },
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 10, cornerRadius: 8 },
                    datalabels: {
                        display: true,
                        anchor: 'end',
                        align: 'right',
                        clamp: true,
                        clip: false,
                        color: computedStyle.getPropertyValue('--md-sys-color-on-surface').trim() || '#1C1B1F',
                        font: { weight: 'bold', size: 12 },
                        textStrokeColor: '#ffffff',
                        textStrokeWidth: 3,
                        formatter: function(value) { return value; }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 2 },
                        grid: { color: '#E6E1E5' }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });

        // --- 6. Group Sizes Chart (Vertical Bar) ---
        const groupCodes = groupSizesData.map(g => `Group ${g.group_code}`);
        const groupCounts = groupSizesData.map(g => parseInt(g.count));
        const ctxGroup = document.getElementById('groupSizesChart').getContext('2d');
        
        // Show empty message placeholder in groupSizes canvas if empty
        if (groupSizesData.length === 0) {
            ctxGroup.font = "14px 'Plus Jakarta Sans'";
            ctxGroup.fillStyle = "#79747E";
            ctxGroup.textAlign = "center";
            ctxGroup.fillText("No groups assigned yet", ctxGroup.canvas.width/2, ctxGroup.canvas.height/2);
        } else {
            new Chart(ctxGroup, {
                type: 'bar',
                data: {
                    labels: groupCodes,
                    datasets: [{
                        label: 'Headcount',
                        data: groupCounts,
                        backgroundColor: '#7D5260CC',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: barLayoutPadding,
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 10, cornerRadius: 8 },
                        datalabels: barDatalabels
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: { color: '#E6E1E5' }
                        }
                    }
                }
            });
        }

        // --- 6.1. Turnout & Dropout Breakdown Chart ---
        const preRegCheckedInCount = <?= (int)($summary['pre_register_checked_in'] ?? 0) ?>;
        const preRegDropoutCount = <?= (int)($summary['pre_register_dropout'] ?? 0) ?>;
        const walkInCheckedInCount = <?= (int)($summary['walk_in_checked_in'] ?? 0) ?>;
        const walkInDropoutCount = <?= (int)($summary['walk_in_dropout'] ?? 0) ?>;

        const ctxRegBreakdown = document.getElementById('regTypeBreakdownChart').getContext('2d');
        new Chart(ctxRegBreakdown, {
            type: 'bar',
            data: {
                labels: ['Pre-registered', 'Walk-in'],
                datasets: [
                    {
                        label: 'Attended (Checked In)',
                        data: [preRegCheckedInCount, walkInCheckedInCount],
                        backgroundColor: successColor + 'CC', // 80% opacity
                        borderRadius: 6
                    },
                    {
                        label: 'Dropout (No-show)',
                        data: [preRegDropoutCount, walkInDropoutCount],
                        backgroundColor: '#E28413CC', // 80% opacity
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: barLayoutPadding,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 16, usePointStyle: true, pointStyle: 'circle' }
                    },
                    datalabels: barDatalabels,
                    tooltip: {
                        padding: 12,
                        cornerRadius: 12,
                        backgroundColor: 'rgba(29, 27, 32, 0.95)',
                        titleFont: { weight: 'bold' },
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const label = context.dataset.label;
                                const index = context.dataIndex;
                                const total = index === 0 ? (preRegCheckedInCount + preRegDropoutCount) : (walkInCheckedInCount + walkInDropoutCount);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 5 },
                        grid: { color: '#E6E1E5' }
                    }
                }
            }
        });

        // --- Doughnut datalabels config (high readability with stroke) ---
        const doughnutDatalabels = {
            display: true,
            color: '#ffffff',
            font: { weight: 'bold', size: 14, family: "'Plus Jakarta Sans', system-ui, sans-serif" },
            textStrokeColor: 'rgba(0,0,0,0.85)',
            textStrokeWidth: 3,
            textShadowBlur: 0,
            textShadowColor: 'transparent',
            formatter: function(value, ctx) {
                const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const pct = sum > 0 ? ((value / sum) * 100).toFixed(0) : 0;
                return value + '\n(' + pct + '%)';
            },
            textAlign: 'center'
        };

        // --- 7. Demographics - Registration Mode (Doughnut) ---
        const preRegCount = <?= (int)($summary['pre_register'] ?? 0) ?>;
        const walkInCount = <?= (int)($summary['walk_in'] ?? 0) ?>;
        new Chart(document.getElementById('regTypeDoughnut'), {
            type: 'doughnut',
            data: {
                labels: ['Pre-registered', 'Walk-in'],
                datasets: [{
                    data: [preRegCount, walkInCount],
                    backgroundColor: [primaryColor, '#FF8A80'],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    datalabels: doughnutDatalabels
                },
                cutout: '65%'
            }
        });

        // --- 8. Demographics - Preferred Language (Doughnut) ---
        const langLabels = languageData.map(l => l.preferred_language || 'Not Specified');
        const langCounts = languageData.map(l => parseInt(l.count));
        new Chart(document.getElementById('languageDoughnut'), {
            type: 'doughnut',
            data: {
                labels: langLabels,
                datasets: [{
                    data: langCounts,
                    backgroundColor: colorsPalette.slice(1, 1 + langLabels.length),
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    datalabels: doughnutDatalabels
                },
                cutout: '65%'
            }
        });

        // --- 9. Demographics - Gender (Doughnut) ---
        const genderLabels = genderData.map(g => g.gender || 'Not Specified');
        const genderCounts = genderData.map(g => parseInt(g.count));
        new Chart(document.getElementById('genderDoughnut'), {
            type: 'doughnut',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderCounts,
                    backgroundColor: ['#2D5C8F', '#A2396C', '#79747E'], // Blue, Pink, Grey
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    datalabels: doughnutDatalabels
                },
                cutout: '65%'
            }
        });

        // --- 10. Attendance vs. Dropout (Doughnut) ---
        const checkedInCount = <?= (int)($summary['checked_in'] ?? 0) ?>;
        const dropoutCount = <?= $dropoutCount ?>;
        new Chart(document.getElementById('attendanceDropoutDoughnut'), {
            type: 'doughnut',
            data: {
                labels: ['Attended', 'Dropout (No-show)'],
                datasets: [{
                    data: [checkedInCount, dropoutCount],
                    backgroundColor: [successColor, '#E28413'],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    datalabels: doughnutDatalabels
                },
                cutout: '65%'
            }
        });
    <?php endif; ?>
});
</script>
