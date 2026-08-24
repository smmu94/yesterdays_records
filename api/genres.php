<?php
    include(__DIR__."/../config/database.php");
    $sql_genres = "SELECT * FROM genres";
    $res = $con->query($sql_genres);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>