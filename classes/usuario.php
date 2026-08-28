<?php

class Usuario
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorEmail($email)
    {
        $sql = "
            SELECT *
            FROM usuarios
            WHERE email = ?
            AND status = 'ATIVO'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function buscarPorId($id)
    {
        $sql = "
            SELECT *
            FROM usuarios
            WHERE id_usuario = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function cadastrar(
        $nome,
        $email,
        $senha,
        $tipo = "PROFESSOR"
    ) {

        $senhaHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        $sql = "
            INSERT INTO usuarios
            (
                nome,
                email,
                senha,
                tipo
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $nome,
            $email,
            $senhaHash,
            $tipo
        ]);
    }

    public function listar()
    {
        $sql = "
            SELECT
                id_usuario,
                nome,
                email,
                tipo,
                status,
                criado_em
            FROM usuarios
            ORDER BY nome
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }
}
