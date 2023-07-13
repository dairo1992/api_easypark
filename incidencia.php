<?php
require_once 'config.php';

// Obtener todos los usuarios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['RESPONSABLE_ID'])) {
        $RESPONSABLE_ID = $_GET['RESPONSABLE_ID'];
        $sql = "SELECT I.ID, A.DESCRIPCION AS ASUNTO, I.DESCRIPCION, I.FECHA_CREACION, I.ESTADO
        FROM incidencia I
        INNER JOIN persona P ON P.DOCUMENTO = I.ID_RESPONSABLE
        INNER JOIN asunto A ON A.ID = I.ID_ASUNTO
        WHERE I.ID_RESPONSABLE = '$RESPONSABLE_ID' AND I.ESTADO = 1";
        $stmt = $conect->prepare($sql);
        $stmt->execute();
        $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = [];
    }
    echo json_encode($result);
}

// Crear un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postBody = file_get_contents("php://input");
    $datos = json_decode($postBody, true);
    if ((isset($datos['ID_ASUNTO'])) && (isset($datos['DESCRIPCION'])) && (isset($datos['FUNCIONARIO_SOLICITA']))
    ) {

        $ID_ASUNTO = $datos['ID_ASUNTO'];
        $DESCRIPCION = $datos['DESCRIPCION'];
        $FUNCIONARIO_SOLICITA = $datos['FUNCIONARIO_SOLICITA'];
        $ESTADO = 1;
        $FECHA_CREACION = date("Y-m-d H:i:s");
        if ($ID_ASUNTO != '' && $DESCRIPCION != '' && $FUNCIONARIO_SOLICITA != '' && $ESTADO != '') {
            $sql = "SELECT T2.DOCUMENTO, COUNT(*) AS CANT_INCIDENCIAS FROM (SELECT * FROM (SELECT U.DOCUMENTO, COUNT(*) FROM user U WHERE U.ROL = 2 GROUP BY U.DOCUMENTO) AS T1 LEFT JOIN incidencia I ON I.ID_RESPONSABLE = T1.DOCUMENTO WHERE I.ESTADO = 1) AS T2 GROUP BY T2.DOCUMENTO ORDER BY CANT_INCIDENCIAS ASC LIMIT 1";
            $stmt = $conect->prepare($sql);
            $stmt->execute();
            $result =  $stmt->fetch(PDO::FETCH_ASSOC);
            $RESPONSABLE_ID = $result['DOCUMENTO'];
            $sql = "SELECT P.DOCUMENTO, COUNT(*) AS total_incidencias
            FROM incidencia I
            INNER JOIN persona P ON I.ID_RESPONSABLE = P.DOCUMENTO
            INNER JOIN user U ON P.DOCUMENTO = U.DOCUMENTO
            WHERE U.ROL = 2 AND I.ESTADO = 1
            GROUP BY P.ID ORDER BY total_incidencias ASC LIMIT 1";
            $stmt = $conect->prepare($sql);
            $stmt->execute();
            $result =  $stmt->fetch(PDO::FETCH_ASSOC);
            $RESPONSABLE_ID = $result['DOCUMENTO'];
            $sql = ("INSERT INTO incidencia (ID_ASUNTO, DESCRIPCION, ID_RESPONSABLE, FECHA_CREACION, FUNCIONARIO_SOLICITA, ESTADO) VALUES(?,?,?,?,?,?)");
            $arrData = array(
                $ID_ASUNTO,
                $DESCRIPCION,
                $RESPONSABLE_ID,
                $FECHA_CREACION,
                $FUNCIONARIO_SOLICITA,
                $ESTADO
            );
            $sth = $conect->prepare($sql);
            $sth->execute($arrData);
            $resp = $conect->lastInsertId();
            if ($resp > 0) {
                $return = array("status" => true, "msg" => 'Incidencia insertado correctamente ', "id" => $resp);
            } else {
                $return = array("status" => false, "msg" => 'No se pudo insertar la incidencia');
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
    if ((isset($datos['ID'])) && (isset($datos['OBSERVACION']))) {
        $id = $datos['ID'];
        $observacion = $datos['OBSERVACION'];
        $fechaGestion = date("Y-m-d H:i:s");
        $sql = "SELECT COUNT(*) AS cant FROM incidencia WHERE id='$id'";
        $stmt = $conect->prepare($sql);
        $stmt->execute();
        $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($result[0]['cant'] > 0) {
            $sql = "UPDATE incidencia SET OBSERVACION = '$observacion', FECHA_GESTION = '$fechaGestion', ESTADO = 2 WHERE id='$id'";
            $stmt = $conect->prepare($sql);
            $stmt->execute();
            $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
            $return = array("status" => true, "msg" => "Incidencia gestionada exitosamente");
        } else {
            $return = array("status" => false, "msg" => 'Esta incidencia no existe');
        }
    } else {
        $return = array("status" => false, "msg" => 'Todos los campos son requeridos');
    }
    echo json_encode($return);
}
