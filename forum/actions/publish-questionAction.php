<?php
require_once '../../assets/php/db_connect.php';

if (isset($_POST['validate'])) {
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['content'])) {
        echo "All fields are required.";
        exit();
    }
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content = $_POST['content'];
    $username = $_SESSION['username'];

    $sql = "INSERT INTO questions (title, description, content, username) VALUES ('$title', '$description', '$content', '$username')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: ../forum.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}