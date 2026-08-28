<?php

class Usuario
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorMatricula($matricula)
    {
        $sql = "
            SELECT *
            FROM usuarios
            WHERE matricula = ?
            AND status = 'ATIVO'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $matricula
        ]);

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

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch();
    }

    public function cadastrar(
        $matricula,
        $nome,
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
                matricula,
                nome,
                senha,
                tipo
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $matricula,
            $nome,
            $senhaHash,
            $tipo
        ]);
    }

    public function listar()
    {
        $sql = "
            SELECT
                id_usuario,
                matricula,
                nome,
                tipo,
                status,
                criado_em
            FROM usuarios
            ORDER BY nome
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    public function atualizar(
        $id,
        $matricula,
        $nome,
        $tipo,
        $status
    ) {

        $sql = "
            UPDATE usuarios
            SET
                matricula = ?,
                nome = ?,
                tipo = ?,
                status = ?
            WHERE id_usuario = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $matricula,
            $nome,
            $tipo,
            $status,
            $id
        ]);
    }

    public function alterarSenha(
        $id,
        $senha
    ) {

        $senhaHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        $sql = "
            UPDATE usuarios
            SET senha = ?
            WHERE id_usuario = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $senhaHash,
            $id
        ]);
    }
}
