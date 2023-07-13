<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postBody = file_get_contents("php://input");
    $datos = json_decode($postBody, true);
    if ((isset($datos['USUARIO'])) && (isset($datos['CONTRASENA']))) {
        $contrasena = $datos['CONTRASENA'];
        $usuario = $datos['USUARIO'];
        $pepper = 'c1isvFdxMDdmjOlvxpecFw';
        $pwd_peppered = hash_hmac("sha256", $contrasena, $pepper);
        $sql = "SELECT CONTRASENA, ID FROM user WHERE USUARIO = '$usuario'";
        $stmt = $conect->prepare($sql);
        $stmt->execute();
        $result =  $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result != '') {
            $id = $result['ID'];
            $pwd_hashed = $result['CONTRASENA']; ///clave de base de datos
            if (password_verify($pwd_peppered, $pwd_hashed)) {
                $sql = "SELECT p.ID, p.NOMBRE, p.APELLIDO, p.TIPO_DOCUMENTO, p.DOCUMENTO, u.USUARIO, u.ROL, u.ESTADO
                        FROM persona p
                        INNER JOIN user u ON p.DOCUMENTO = u.DOCUMENTO
                        WHERE p.ID = '$id'";
                $stmt = $conect->prepare($sql);
                $stmt->execute();
                $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                $return = array("status" => true, "msg" => 'Inicio de session exitoso', "data" => $result[0]);
            } else {
                $return = array("status" => false, "msg" => 'Usuario o contraseña incorrecta');
            }
        } else {
            $return = array("status" => false, "msg" => 'Este usuario no existe');
        }
    } else {
        $return = array("status" => false, "msg" => 'Todos los campos son requeridos');
    }
    echo json_encode($return);
}
