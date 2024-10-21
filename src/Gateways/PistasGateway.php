<?php

class PistasGateway{
    private PDO $con;

    public function __construct(Database $database)
    {
        $this->con = $database->getConnection();
    }

    public function getAll():Array {
        $sql = "SELECT * FROM pista";
        $stmt = $this->con->query($sql);
        $data=[];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $row["disponible"]=(bool) $row["disponible"];
            $data[]=$row;
        }
        return $data;
    }

    public function get(string $id):array | false{
        $sql = "SELECT * FROM pista WHERE id= :id";
        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data=$stmt->fetch(PDO::FETCH_ASSOC);

        if($data!==false){
            $data["disponible"] = (bool) $data["disponible"];
        }
        return $data;
    }

    public function create(array $data){
        $sql = "INSERT INTO pista (nombre, tipo, max_jugadores, disponible) VALUES (:nombre, :tipo, :max_jugadores, :disponible)";

        $stmt = $this->con->prepare($sql);

        $stmt->bindValue(":nombre", $data["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":tipo", $data["tipo"], PDO::PARAM_STR);
        $stmt->bindValue(":max_jugadores", $data["max_jugadores"] ?? 2, PDO::PARAM_INT);
        $stmt->bindValue(":disponible", (bool)($data["disponible"] ?? false), PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->con->lastInsertId();
    }

    public function delete(string $id):int {
        $sql = "DELETE FROM pista WHERE id= :id";
        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function update(array $current, array $new): int {
        $sql = "UPDATE pista
                SET nombre = :nombre, tipo = :tipo, max_jugadores = :max_jugadores, disponible = :disponible WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(":nombre", $new["nombre"] ?? $current["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":tipo", $new["tipo"] ?? $current["tipo"], PDO::PARAM_STR);
        $stmt->bindValue(":max_jugadores", $new["max_jugadores"] ?? $current["max_jugadores"], PDO::PARAM_INT);
        $stmt->bindValue(":disponible", $new["disponible"] ?? $current["disponible"], PDO::PARAM_BOOL);

        $stmt->bindValue(":id", $current["id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}