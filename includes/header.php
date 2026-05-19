<?php
// =============================================================================
// includes/header.php - HTML <head> + opening <body>
// Expects $page_title to be set by the including file.
// =============================================================================

// Fallback so the tag is never empty if a page forgets to set it.
$page_title = $page_title ?? 'InvenTrack';

if (!ob_get_level()) {
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> - InvenTrack</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(app_url('assets/images/material-management.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/style.css?v=8'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/responsive.css?v=8'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="layout">
