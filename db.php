
<?php
$host = '127.0.0.1:3307';
$user = 'root';
$pass = '';  // your MySQL password if any
$db = 'pg_expenses';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>