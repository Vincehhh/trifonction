<?php
require_once 'db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $password_hash]);

        header("Location: ../../connexion.html?success=1");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Erreur : Ce pseudo ou cet email est déjà utilisé.";
        } else {
            echo "Erreur technique : " . $e->getMessage();
        }
    }
} else {
    header("Location: ../../inscription.html");
    exit();
}
?>
