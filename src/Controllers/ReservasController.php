<?php

require_once("src/Gateways/ReservasGateway.php");
require_once("src/Gateways/PistasGateway.php");
require_once("src/Gateways/SociosGateway.php");

class ReservasController {
    private ReservasGateway $gateway;
    private PistasGateway $pistaGateway;
    private SociosGateway $socioGateway;
    public function __construct(private Database $database)
    {
        $this->gateway = new ReservasGateway($database);
        $this->pistaGateway = new PistasGateway(($database));
        $this->socioGateway = new SociosGateway(($database));
    }

    public function processRequest(string $method, ?string $id): void{
        if($id!=null){
            $this->processResourceRequest($method, $id);
        }else{
            $this->processCollectionRequest($method);
        }
    }

    public function processResourceRequest(string $method, string $id){
        $reserva = $this->gateway->get($id);

        if(!$reserva){
            http_response_code(404);
            echo json_encode(["message"=> "No existe ninguna reserva con el ID: {$id}"]);
            return;
        }

        switch($method){
            case "GET":
                echo json_encode($reserva);
                break;
            case "PATCH":
                $new_reserva = (array) json_decode(file_get_contents("php://input"), true);
                $errors = $this->getValidationErrors($new_reserva, false);
                if(!empty($errors)){
                    http_response_code(422);
                    echo json_encode(["errors"=>$errors]);
                    break;
                }
                $rows = $this->gateway->update($reserva, $new_reserva);

                http_response_code(206);
                echo json_encode(
                    [
                        "message" => "Reserva con ID: $id actualizado.",
                        "rows" => $rows
                    ]
                    );
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(
                    [
                    "message"=>"La reserva con ID: {$id} ha sido eliminado.",
                    "rows"=>$rows
                    ]
                    );
                break;
            default:
                http_response_code(405);
                header("Allow: GET, PATCH, DELETE");
                break;
        }
    }

    public function processCollectionRequest(string $method){
        switch($method){
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            case "POST":
                $reserva = (array) json_decode( file_get_contents("php://input", true));
                $errors=$this->getValidationErrors($reserva);
                if (!empty($errors)){
                    http_response_code(422);
                    echo json_encode($errors);
                    break;
                }
                $id = $this->gateway->create($reserva);

                http_response_code(201);
                echo json_encode([
                    "message"=>"Reserva creada",
                    "id" => $id
                ]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, PATCH, DELETE");
                break;
        }
    }

    private function getValidationErrors(array $data, bool $is_new=true){
        $errors=[];
        $pista = $this->pistaGateway->get($data["pista"]);
        $socio = $this->socioGateway->get($data["socio"]);

        if ($is_new) {
            if(!isset($data["socio"]) || empty($data["socio"])){
                $errors[]="Se requiere ID del socio que reserva";
            }elseif(!$socio){
                $errors[]="No existe ningun socio con el ID indicado.";
            }
            if(!isset($data["pista"]) || empty($data["pista"])){
                $errors[]="Se requiere el ID de la pista a reservar";
            }elseif(!$pista){
                $errors[]="No existe ninguna pista con el ID indicado.";
            }
            if(!isset($data["fecha"]) || empty($data["fecha"])){
                $errors[]="Se requiere la fecha de la reserva";
            }elseif (!is_string($data["fecha"])){
                $errors[] = "La fecha de la reserva debe ser un dato valido (Texto formato dd/mm/aaaa)";
            }
            if(!isset($data["hora"]) || empty($data["hora"])){
                $errors[]="Se requiere la hora de la reserva";
            }elseif (filter_var($data["hora"],FILTER_VALIDATE_INT)===false){
                $errors[] = "La hora de la reserva debe ser un dato valido (Num entero de la hora en punto)";
            }
            if(isset($data["penalizado"]) && !is_bool($data["penalizado"])){
                $errors[]="El campo penalizado debe se ser true o false";
            }
        } else {
            if(array_key_exists("socio", $data)){
                if(!isset($data["socio"]) || empty($data["socio"])){
                    $errors[]="Se requiere ID del socio que reserva";
                }elseif(!$socio){
                    $errors[]="No existe ningun socio con el ID indicado.";
                }
            }
            if(array_key_exists("pista", $data)){
                if(!isset($data["pista"]) || empty($data["pista"])){
                    $errors[]="Se requiere el ID de la pista a reservar";
                }elseif(!$pista){
                    $errors[]="No existe ninguna pista con el ID indicado.";
                }
            }
            if(array_key_exists("fecha", $data)){
                if (!is_string($data["fecha"])){
                    $errors[] = "La fecha de la reserva debe ser un dato valido (Texto formato dd/mm/aaaa)";
                }
            }
            if(array_key_exists("hora", $data)){
                if (filter_var($data["hora"],FILTER_VALIDATE_INT)===false){
                    $errors[] = "La hora de la reserva debe ser un dato valido (Num entero de la hora en punto)";
                }
            }
            if(array_key_exists("penalizado", $data)){
                if (!is_bool($data["penalizado"])){
                    $errors[] = "La reserva debe estar penalizado (true) o no (false)";
                }
            }
        }

        return $errors;
    }
}