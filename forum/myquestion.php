<!DOCTYPE html>
<html lang="en">
    <?php 
    include 'header.php';
    require_once 'actions/questions/myQuestionAction.php'; 
   ?>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>My Questions</title>
</head>

<body>
    
<?php


while($question = $getAllMyQuestions->fetch()) {
    echo '<div class="question-item">';
    echo '<h3>' . htmlspecialchars($question['titre']) . '</h3>';
    echo '<a href="question.php?id=' . $question['id'] . '">View Question</a>';
    echo '</div>';
}
?>

</body>
