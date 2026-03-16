<?php 

require_once '../../assets/php/db_connect.php';

$getAllMyQuestions = $bdd ->prepare("SELECT id,titre FROM questions WHERE id_author = ?");
$getAllMyQuestions ->execute(array($_SESSION['id']));
