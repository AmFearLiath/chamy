<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backend Registration</title>
</head>
<body>
    <h1>Backend Registration</h1>
    <form action="<?php echo $backendPath; ?>/register" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        
        <button type="submit">Register</button>
    </form>
</body>
</html>
