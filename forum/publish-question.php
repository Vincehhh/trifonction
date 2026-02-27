<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: connexion.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Publish Question</title>
</head>

<body>
 <form action="assets/php/register.php" method="POST" >
        <label for="title">Titre de la question</label>
        <textarea id="title" name="title" placeholder="Titre de la question" required></textarea>

        <label for="description">Description de la question</label>
        <textarea id="description" name="description" placeholder="Description de la question" required></textarea>

        <label for="content">Contenu de la question</label>
        <textarea id="content" name="content" placeholder="Contenu de la question" required></textarea>
        
        <button type="submit" class="btn-signup">Publier la question</button>
      </form>

</body>
</html>
