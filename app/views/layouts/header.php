<?php
$app_config = require_once __DIR__ . '/../../config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?><?php echo $app_config['name']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Box Icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Custom CSS -->
    <link href="/css/styles.css" rel="stylesheet">
    
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
