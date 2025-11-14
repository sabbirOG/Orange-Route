<?php
// Usage: set $title and optional $rightActionHtml before including this file
// Example:
//   $title = 'OrangeRoute';
//   $rightActionHtml = '<button ...>Action</button>';
//   include __DIR__ . '/_partials/top_bar.php';
?>
<div class="top-bar">
    <div class="logo"><?= isset($title) ? $title : 'OrangeRoute' ?></div>
    <div>
        <?= isset($rightActionHtml) ? $rightActionHtml : '' ?>
    </div>
    <?php // Clean up variables to avoid leaking into later includes ?>
    <?php unset($title, $rightActionHtml); ?>
</div>
