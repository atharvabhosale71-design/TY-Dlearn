<?php
require_once "db_connect.php";

// Default credentials to seed
$accounts = [
    [
        'name'     => 'DLearn Admin',
        'email'    => 'admin@dlearn.com',
        'password' => 'Admin@123',
        'role'     => 'admin'
    ],
    [
        'name'     => 'DLearn Teacher',
        'email'    => 'teacher@dlearn.com',
        'password' => 'Teacher@123',
        'role'     => 'teacher'
    ]
];

echo "<h2>DLearn Account Setup</h2>";

foreach ($accounts as $acc) {
    // Check if email already exists
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $acc['email']);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        echo "<p style='color:orange;'>User <strong>{$acc['email']}</strong> already exists. Skipped.</p>";
    } else {
        $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);
        $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert, "ssss", $acc['name'], $acc['email'], $hashed, $acc['role']);

        if (mysqli_stmt_execute($insert)) {
            echo "<p style='color:green;'>Created <strong>{$acc['role']}</strong> account: <strong>{$acc['email']}</strong> (Password: <code>{$acc['password']}</code>)</p>";
        } else {
            echo "<p style='color:red;'>Error creating {$acc['email']}: " . mysqli_error($conn) . "</p>";
        }
        mysqli_stmt_close($insert);
    }
    mysqli_stmt_close($check);
}

echo "<hr><p><strong>Done!</strong> Test login at: <a href='login.php'>login.php</a></p>";
echo "<p style='color:red;'><em>Remember to delete setup_accounts.php after running this.</em></p>";
?>