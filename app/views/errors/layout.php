<?php
$app_config = require_once __DIR__ . '/../../config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Error'; ?> - <?php echo $app_config['name']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Box Icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        
        .error-container {
            text-align: center;
            color: #fff;
            padding: 2rem;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .error-description {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-light {
            padding: 0.75rem 2rem;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php echo $content ?? ''; ?>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
