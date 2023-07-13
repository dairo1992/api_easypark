<?php
require_once 'config.php';

// Obtener todos los usuarios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuarios = [];
    $sql = "SELECT a.ID, a.DESCRIPCION FROM asunto a WHERE a.ESTADO = 1";
    $stmt = $conect->prepare($sql);
    $stmt->execute();
    $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}
