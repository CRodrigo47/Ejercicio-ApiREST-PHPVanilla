<?php

class ReservasGateway{
    private PDO $con;

    public function __construct(Database $database)
    {
        $this->con = $database->getConnection();
    }

    public function getAll():Array {
        $sql = "SELECT * FROM reserva";
        $stmt = $this->con->query($sql);
        $data=[];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $row["iluminar"]=(bool) $row["iluminar"];
            $data[]=$row;
        }
        return $data;
    }

    public function get(string $id):array | false{
        $sql = "SELECT * FROM reserva WHERE id= :id";
        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data=$stmt->fetch(PDO::FETCH_ASSOC);

        if($data!==false){
            $data["iluminar"] = (bool) $data["iluminar"];
        }
        return $data;
    }

    public function create(array $data){
        $sql = "INSERT INTO reserva (socio, pista, fecha, hora, iluminar) VALUES (:socio, :pista, :fecha, :hora, :iluminar)";

        $stmt = $this->con->prepare($sql);

        $stmt->bindValue(":socio", $data["socio"], PDO::PARAM_INT);
        $stmt->bindValue(":pista", $data["pista"], PDO::PARAM_INT);
        $stmt->bindValue(":fecha", $data["fecha"], PDO::PARAM_STR);
        $stmt->bindValue(":hora", $data["hora"], PDO::PARAM_INT);
        $stmt->bindValue(":iluminar", (bool)($data["iluminar"] ?? false), PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->con->lastInsertId();
    }

    public function delete(string $id):int {
        $sql = "DELETE FROM reserva WHERE id= :id";
        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function update(array $current, array $new): int {
        $sql = "UPDATE reserva
                SET socio = :socio, pista = :pista, fecha = :fecha, hora = :hora, iluminar = :iluminar WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        $stmt->bindValue(":socio", $new["socio"] ?? $current["socio"], PDO::PARAM_INT);
        $stmt->bindValue(":pista", $new["pista"] ?? $current["pista"], PDO::PARAM_INT);
        $stmt->bindValue(":fecha", $new["fecha"] ?? $current["fecha"], PDO::PARAM_STR);
        $stmt->bindValue(":hora", $new["hora"] ?? $current["hora"], PDO::PARAM_INT);
        $stmt->bindValue(":iluminar", $new["iluminar"] ?? $current["iluminar"], PDO::PARAM_BOOL);

        $stmt->bindValue(":id", $current["id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}