<?php
session_start(); 
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT id, username, password_hash, role FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: ../../profil.php");
            exit();

        } else {
            header("Location: ../../connexion.html?error=invalid_credentials");
            exit();
        }

    } catch (PDOException $e) {
        echo "Erreur technique : " . $e->getMessage();
    }
} else {
    header("Location: ../../connexion.html");
    exit();
}
?>