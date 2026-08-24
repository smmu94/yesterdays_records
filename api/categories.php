<?php
    include(__DIR__."/../config/database.php");
    $sql_categories = "SELECT * FROM categories";
    $res = $con->query($sql_categories);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>