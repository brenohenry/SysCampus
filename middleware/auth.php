<?php

session_start();


function usuarioLogado()
{
    return isset(
        $_SESSION["usuario"]
    );
}


function exigirLogin()
{
    if (!usuarioLogado()) {

        header(
            "Location: ../public/login.php"
        );

        exit;
    }
}


function exigirAdmin()
{
    exigirLogin();

    if (
        $_SESSION["usuario"]["tipo"]
        !== "ADMIN"
    ) {

        http_response_code(403);

        die(
            "Acesso negado."
        );
    }
}
