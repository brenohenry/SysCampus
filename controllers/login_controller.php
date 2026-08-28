<?php

require_once "../config/database.php";
require_once "../classes/usuario.php";

session_start();

$database = new Database();

$pdo = $database->conectar();

$usuarioModel = new Usuario($pdo);

$matricula = trim(
    $_POST["matricula"] ?? ""
);

$senha = $_POST["senha"] ?? "";


if (
    $matricula === "" ||
    $senha === ""
) {

    header(
        "Location: ../public/login.php?erro=1"
    );

    exit;
}


$usuario = $usuarioModel
    ->buscarPorMatricula($matricula);


if (
    $usuario &&
    password_verify(
        $senha,
        $usuario["senha"]
    )
) {

    $_SESSION["usuario"] = [

        "id_usuario" =>
            $usuario["id_usuario"],

        "matricula" =>
            $usuario["matricula"],

        "nome" =>
            $usuario["nome"],

        "tipo" =>
            $usuario["tipo"]

    ];


    if (
        $usuario["tipo"] === "ADMIN"
    ) {

        header(
            "Location: ../admin/dashboard.php"
        );

    } else {

        header(
            "Location: ../usuario/dashboard.php"
        );
    }

    exit;
}


header(
    "Location: ../public/login.php?erro=1"
);

exit;
