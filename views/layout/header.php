<?php
// Shared page header
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'ATCL Management System') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">ATCL MS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (\App\Core\Auth::check()): ?>
                    <li class="nav-item"><a class="nav-link" href="/participants">Participants</a></li>
                    <li class="nav-item"><a class="nav-link" href="/finance">Finance</a></li>
                    <li class="nav-item"><a class="nav-link" href="/operations">Operations</a></li>
                    <li class="nav-item"><a class="nav-link" href="/governance">Governance</a></li>
                    <li class="nav-item"><a class="nav-link" href="/forms">Forms</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logistics">Logistics</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/participants/create">Participant Registration</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (\App\Core\Auth::check()): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-2">
                            <?= htmlspecialchars(\App\Core\Auth::user()['username'] ?? '') ?>
                            (<?= htmlspecialchars(\App\Core\Auth::role() ?? '') ?>)
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/logout">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login">Advisor / Committee Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mb-4">