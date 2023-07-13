<?php
require_once 'config.php';

// Obtener todos los usuarios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuarios = [];
    $sql = "SELECT p.ID, p.NOMBRE, p.APELLIDO, p.TIPO_DOCUMENTO, p.DOCUMENTO, u.USUARIO, u.ROL, u.ESTADO
    FROM persona p
    INNER JOIN user u ON p.DOCUMENTO = u.DOCUMENTO";
    $stmt = $conect->prepare($sql);
    $stmt->execute();
    $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

// Crear un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postBody = file_get_contents("php://input");
    $datos = json_decode($postBody, true);
    if ((isset($datos['NOMBRE'])) && (isset($datos['APELLIDO'])) && (isset($datos['TIPO_DOCUMENTO'])) && (isset($datos['DOCUMENTO'])) && (isset($datos['USUARIO'])) && (isset($datos['CONTRASENA'])) && (isset($datos['ROL'])) && (isset($datos['ESTADO']))
    ) {
        $pepper = 'c1isvFdxMDdmjOlvxpecFw';
        $pwd = $datos['CONTRASENA'];
        $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
        $pwd_hashed = password_hash($pwd_peppered, PASSWORD_ARGON2ID);

        $nombre = $datos['NOMBRE'];
        $apellidos = $datos['APELLIDO'];
        $tipo_id = $datos['TIPO_DOCUMENTO'];
        $dni = $datos['DOCUMENTO'];
        $usuario = $datos['USUARIO'];
        $contrasena = $pwd_hashed;
        $rol = $datos['ROL'];
        $estado = $datos['ESTADO'];
        $fecha = date("Y-m-d H:i:s");
        if ($nombre != '' && $apellidos != '' && $tipo_id != '' && $dni != '' && $usuario != '' && $contrasena != '' && $rol != '' && $estado != '' && $fecha) {
            $sql = "SELECT ID FROM persona WHERE DOCUMENTO = '$dni'";
            $stmt = $conect->prepare($sql);
            $stmt->execute();
            $result =  $stmt->fetch();
            if (!$result) {
                $sql = ("INSERT INTO persona (NOMBRE, APELLIDO, TIPO_DOCUMENTO, DOCUMENTO, ESTADO, FECHA_CREACION) VALUES(?,?,?,?,?,?)");
                $arrData = array(
                    $nombre,
                    $apellidos,
                    $tipo_id,
                    $dni,
                    $estado,
                    $fecha
                );
                $sth = $conect->prepare($sql);
                $sth->execute($arrData);
                $resp = $conect->lastInsertId();
                if ($resp > 0) {
                    $sql = ("INSERT INTO user (ID, TIPO_DOCUMENTO, DOCUMENTO, USUARIO, CONTRASENA, ROL, FECHA_CREACION, ESTADO) VALUES(?,?,?,?,?,?,?,?)");
                    $arrData = array(
                        intval($resp),
                        $tipo_id,
                        intval($dni),
                        $usuario,
                        $contrasena,
                        $rol,
                        $fecha,
                        $estado
                    );
                    $sth = $conect->prepare($sql);
                    $sth->execute($arrData);
                    $resp2 = $conect->lastInsertId();
                    if ($resp2 > 0) {
                        $return = array("status" => true, "msg" => 'Registro insertado correctamente ', "id" => $resp2);
                    } else {
                        $return = array("status" => false, "msg" => 'No se pudo insertar el registro ');
                    }
                }
            } else {
                $return = array("status" => false, "msg" => 'Este usuario existe');
            }
        } else {
            $return = array("status" => false, "msg" => 'Todos los campos son requeridos.');
        }
    } else {
        $return = array("status" => false, "msg" => 'Todos los campos son requeridos');
    }
    echo json_encode($return);
}

// Actualizar un usuario existente
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $postBody = file_get_contents("php://input");
    $datos = json_decode($postBody, true);
    if (isset($datos['accion'])) {
        if ($datos['accion'] == 'inactivar') {
            $id = $datos['id'];
            $valor = $datos['valor'];
            $sql = "SELECT COUNT(*) AS cant FROM persona WHERE id='$id'";
            $stmt = $conect->prepare($sql);
            $stmt->execute();
            $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result[0]['cant'] > 0) {
                $sql = "UPDATE persona SET estado = '$valor' WHERE id='$id'";
                $stmt = $conect->prepare($sql);
                $stmt->execute();
                $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                $sql = "UPDATE user SET estado = '$valor' WHERE id='$id'";
                $stmt = $conect->prepare($sql);
                $stmt->execute();
                $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                $msg = 'Usuario fue ' . ($valor == 2 ? 'inhabilitado' : 'habilitado') . ' correctamente';
                $return = array("status" => true, "msg" => $msg);
            } else {
                $return = array("status" => false, "msg" => 'Este usuario no existe');
            }
        } else if ($datos['accion'] == 'actualizar') {
            $id = $datos['data']['ID'];
            $nombre = $datos['data']['NOMBRE'];
            $apellidos = $datos['data']['APELLIDO'];
            $tipo_id = $datos['data']['TIPO_DOCUMENTO'];
            $dni = $datos['data']['DOCUMENTO'];
            $usuario = $datos['data']['USUARIO'];
            $contrasena = isset($datos['data']['CONTRASENA']) ? $datos['data']['CONTRASENA'] : '';
            $rol = $datos['data']['ROL'];
            $estado = $datos['data']['ESTADO'];
            $sql = "SELECT COUNT(*) AS cant FROM persona WHERE id='$id'";
            $stmt = $conect->prepare($sql);
            $stmt->execute();
            $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result[0]['cant'] == 0) {
                $request = array("status" => false, "msg" => 'Este usuario no existe.');
            } else {
                $sql = "UPDATE persona SET NOMBRE = ?, APELLIDO = ?, TIPO_DOCUMENTO = ?, DOCUMENTO = ?, ESTADO = ? WHERE ID = $id";
                $arrData = array($nombre, $apellidos, $tipo_id, $dni, $estado);
                $stmt = $conect->prepare($sql);
                $request = $stmt->execute($arrData);
                if ($contrasena != '') {
                    $sql = "UPDATE user SET TIPO_DOCUMENTO = ?, DOCUMENTO = ?, USUARIO = ?, CONTRASENA = ?, ROL = ?, ESTADO = ? WHERE ID = $id";
                    $arrData = array($tipo_id, $dni, $usuario, $contrasena, $rol, $estado);
                    $stmt = $conect->prepare($sql);
                    $request = $stmt->execute($arrData);
                } else {
                    $sql = "UPDATE user SET TIPO_DOCUMENTO = ?, DOCUMENTO = ?, USUARIO = ?, ROL = ?, ESTADO = ? WHERE ID = $id";
                    $arrData = array($tipo_id, $dni, $usuario, $rol, $estado);
                    $stmt = $conect->prepare($sql);
                    $request = $stmt->execute($arrData);
                }
                $return = array("status" => true, "msg" => 'Usuario actualizado correctamente');
            }
        } else {
            $return = array("status" => false, "msg" => 'la propiedad accion incorrecta');
        }
    } else {
        $return = array("status" => false, "msg" => 'la propiedad accion es requerida');
    }
    echo json_encode($return);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "SELECT CONCAT(p.NOMBRE,' ',p.APELLIDO) AS fucionario, (SELECT COUNT(1) FROM incidencia i WHERE i.ID_RESPONSABLE = p.DOCUMENTO) AS incidencias
            FROM persona p
            INNER JOIN user u ON u.ID = p.ID
            WHERE u.ROL = 2";
    $stmt = $conect->prepare($sql);
    $stmt->execute();
    $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}
