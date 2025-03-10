<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISP Management System</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="/login" method="POST">
            <div>
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div>
                <button type="submit">Login</button>
            </div>
        </form>
        <p>Don't have an account? <a href="/register">Register here</a></p>
    </div>
</body>
</html>
