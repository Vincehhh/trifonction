<?php
require_once '../../assets/php/db_connect.php';

if (isset($_POST['validate'])) {
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['content'])) {
        echo "All fields are required.";
        exit();
    }

    $question_title = htmlspecialchars($_POST['title']);
    $question_description = htmlspecialchars($_POST['description']);
    $question_content = htmlspecialchars($_POST['content']);
    $question_date = date('Y-m-d H:i:s');
    $question_username = $_SESSION['username'];

    $sql = "INSERT INTO questions (title, description, content, date, username) VALUES ('$question_title', '$question_description', '$question_content', '$question_date', '$question_username')";

    if ($conn->query($sql) === TRUE) {
        header("Location: ../forum.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}