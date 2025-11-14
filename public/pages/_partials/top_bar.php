<?php
// Usage: set $title and optional $rightActionHtml before including this file
// Example:
//   $title = 'OrangeRoute';
//   $rightActionHtml = '<button ...>Action</button>';
//   include __DIR__ . '/_partials/top_bar.php';
?>
<div class="top-bar">
    <div style="display:flex;align-items:center;gap:.5rem;">
        <?php if (isset($backHref)): ?>
            <a href="<?= e($backHref) ?>" class="back-btn" aria-label="Go back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </a>
        <?php endif; ?>
        <div class="top-bar-title"><?= isset($title) ? e($title) : 'OrangeRoute' ?></div>
    </div>
    <div class="top-bar-actions">
        <?= isset($rightActionHtml) ? $rightActionHtml : '' ?>
    </div>
    <?php // Clean up variables to avoid leaking into later includes ?>
    <?php unset($title, $rightActionHtml, $backHref); ?>
</div>
<script>document.body.classList.add('has-top-bar');</script>
