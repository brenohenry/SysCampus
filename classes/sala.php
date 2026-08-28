<?php

class Sala
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarDisponiveis()
    {
        $sql = "
            SELECT *
            FROM salas
            WHERE status = 'DISPONIVEL'
            ORDER BY numero
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $sql = "
            SELECT *
            FROM salas
            WHERE id_sala = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function cadastrar(
        $numero,
        $capacidade,
        $localizacao,
        $recursos
    ) {

        $sql = "
            INSERT INTO salas
            (
                numero,
                capacidade,
                localizacao,
                recursos
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $numero,
            $capacidade,
            $localizacao,
            $recursos
        ]);
    }

    public function atualizar(
        $id,
        $numero,
        $capacidade,
        $localizacao,
        $recursos
    ) {

        $sql = "
            UPDATE salas
            SET
                numero = ?,
                capacidade = ?,
                localizacao = ?,
                recursos = ?
            WHERE id_sala = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $numero,
            $capacidade,
            $localizacao,
            $recursos,
            $id
        ]);
    }

    public function inativar($id)
    {
        $sql = "
            UPDATE salas
            SET status = 'INATIVA'
            WHERE id_sala = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }
}
