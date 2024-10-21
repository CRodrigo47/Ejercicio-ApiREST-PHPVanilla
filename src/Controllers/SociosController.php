<?php

require_once("src/Gateways/SociosGateway.php");
require_once("src/Gateways/ReservasGateway.php");

class SociosController {
    private SociosGateway $gateway;
    private ReservasGateway $reservaGateway;
    public function __construct(private Database $database)
    {
        $this->reservaGateway = new ReservasGateway($database);
        $this->gateway = new SociosGateway($database);
    }

    public function processRequest(string $method, ?string $id): void{
        if($id!=null){
            $this->processResourceRequest($method, $id);
        }else{
            $this->processCollectionRequest($method);
        }
    }

    public function processResourceRequest(string $method, string $id){
        $socio = $this->gateway->get($id);

        if(!$socio){
            http_response_code(404);
            echo json_encode(["message"=> "No existe ningun socio con el ID: {$id}"]);
            return;
        }

        switch($method){
            case "GET":
                echo json_encode($socio);
                break;
            case "PATCH":
                $new_socio = (array) json_decode(file_get_contents("php://input"), true);
                $errors = $this->getValidationErrors($new_socio, false);
                if(!empty($errors)){
                    http_response_code(422);
                    echo json_encode(["errors"=>$errors]);
                    break;
                }
                $rows = $this->gateway->update($socio, $new_socio);

                http_response_code(206);
                echo json_encode(
                    [
                        "message" => "Socio con ID: $id actualizado.",
                        "rows" => $rows
                    ]
                    );
                break;
            case "DELETE":
                $errors = $this->getDeleteErrors($socio, false);
                if(!empty($errors)){
                    http_response_code(422);
                    echo json_encode(["errors"=>$errors]);
                    break;
                }
                $rows = $this->gateway->delete($id);
                echo json_encode(
                    [
                    "message"=>"El socio con ID: {$id} ha sido eliminado.",
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
                $socio = (array) json_decode( file_get_contents("php://input", true));
                $errors=$this->getValidationErrors($socio);
                if (!empty($errors)){
                    http_response_code(422);
                    echo json_encode($errors);
                    break;
                }
                $id = $this->gateway->create($socio);

                http_response_code(201);
                echo json_encode([
                    "message"=>"Socio creado",
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
       

        if ($is_new) {
            if(!isset($data["nombre"]) || empty($data["nombre"])){
                $errors[]="Se requiere nombre del socio";
            }elseif (!is_string($data["nombre"])){
                $errors[] = "El socio debe tener un nombre válido.";
            }
            if(!isset($data["telefono"]) || empty($data["telefono"])){
                $errors[]="Se requiere el telefono del socio";
            }elseif (!is_string($data["telefono"])){
                $errors[] = "El telefono del socio debe ser un dato valido";
            }
            if(!isset($data["edad"]) || empty($data["edad"])){
                $errors[]="Se requiere la edad del socio";
            }elseif (filter_var($data["edad"],FILTER_VALIDATE_INT)===false){
                $errors[] = "La edad del socio debe ser un numero entero";
            }
            if(isset($data["penalizado"]) && !is_bool($data["penalizado"])){
                $errors[]="El campo penalizado debe se ser true o false";
            }
        } else {
            if(array_key_exists("nombre", $data)){
                if (!is_string($data["nombre"])){
                    $errors[] = "El socio debe tener un nombre válido.";
                }
            }
            if(array_key_exists("telefono", $data)){
                if (!is_string($data["telefono"])){
                    $errors[] = "El telefono del socio debe ser un dato valido (Texto)";
                }
            }
            if(array_key_exists("edad", $data)){
                if (filter_var($data["edad"],FILTER_VALIDATE_INT)===false){
                    $errors[] = "La edad del socio debe ser un número entero.";
                }
            }
            if(array_key_exists("penalizado", $data)){
                if (!is_bool($data["penalizado"])){
                    $errors[] = "La socio debe estar penalizado (true) o no (false)";
                }
            }
        }

        return $errors;
    }

    private function getDeleteErrors(array $data){
        $errors=[];
        $reservaExistente = array_filter($this->reservaGateway->getAll(), fn($reserva) => $reserva["socio"]===$data["id"]);

        if($reservaExistente){
            $errors[] = "No se puede eliminar un socio si existen reservas a su nombre. Elimina las reservas primero.";
        }
        return $errors;
    }
}