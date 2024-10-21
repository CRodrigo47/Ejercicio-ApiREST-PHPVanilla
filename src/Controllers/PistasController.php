<?php

require_once("src/Gateways/PistasGateway.php");
require_once("src/Gateways/ReservasGateway.php");

class PistasController {
    private PistasGateway $gateway;
    private ReservasGateway $reservaGateway;
    public function __construct(private Database $database)
    {
        $this->reservaGateway = new ReservasGateway($database);
        $this->gateway = new PistasGateway($database);
    }

    public function processRequest(string $method, ?string $id): void{
        if($id!=null){
            $this->processResourceRequest($method, $id);
        }else{
            $this->processCollectionRequest($method);
        }
    }

    public function processResourceRequest(string $method, string $id){
        $pista = $this->gateway->get($id);

        if(!$pista){
            http_response_code(404);
            echo json_encode(["message"=> "No existe ninguna pista con el ID: {$id}"]);
            return;
        }

        switch($method){
            case "GET":
                echo json_encode($pista);
                break;
            case "PATCH":
                $new_pista = (array) json_decode(file_get_contents("php://input"), true);
                $errors = $this->getValidationErrors($new_pista, false);
                if(!empty($errors)){
                    http_response_code(422);
                    echo json_encode(["errors"=>$errors]);
                    break;
                }
                $rows = $this->gateway->update($pista, $new_pista);

                http_response_code(206);
                echo json_encode(
                    [
                        "message" => "Pista con ID: $id actualizada.",
                        "rows" => $rows
                    ]
                    );
                break;
            case "DELETE":
                $errors = $this->getDeleteErrors($pista, false);
                if(!empty($errors)){
                    http_response_code(422);
                    echo json_encode(["errors"=>$errors]);
                    break;
                }
                $rows = $this->gateway->delete($id);
                echo json_encode(
                    [
                    "message"=>"La pista con ID: {$id} ha sido eliminada.",
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
                $pista = (array) json_decode( file_get_contents("php://input", true));
                $errors=$this->getValidationErrors($pista);
                if (!empty($errors)){
                    http_response_code(422);
                    echo json_encode($errors);
                    break;
                }
                $id = $this->gateway->create($pista);

                http_response_code(201);
                echo json_encode([
                    "message"=>"Pista creada",
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
                $errors[]="Se requiere nombre de la pista";   
            }elseif (!is_string($data["nombre"])){
                $errors[] = "La pista debe tener un nombre válido.";
            }
            if(!isset($data["tipo"]) || empty($data["tipo"])){
                $errors[]="Se requiere tipo de pista";
            }elseif (!is_string($data["tipo"])){
                $errors[] = "El tipo de pista debe tener un nombre válido.";
            }
            if(isset($data["max_jugadores"]) || !empty($data["max_jugadores"])){
                if (filter_var($data["max_jugadores"],FILTER_VALIDATE_INT)===false){
                    $errors[] = "La cantidad de jugadores debe ser un número entero.";
                }
                if( $data["max_jugadores"]<2)
                $errors[]="Para poder reservar una pista, necesitas al menos 2 personas.";
            }
            if(isset($data["disponible"]) && !is_bool($data["disponible"])){
                $errors[]="El campo Disponible debe se ser true o false";
            }
        } else {
            if(array_key_exists("nombre", $data)){
                if (!is_string($data["nombre"])){
                    $errors[] = "La pista debe tener un nombre válido.";
                }
            }
            if(array_key_exists("tipo", $data)){
                if (!is_string($data["tipo"])){
                    $errors[] = "El tipo de pista debe tener un nombre válido.";
                }
            }
            if(array_key_exists("max_jugadores", $data)){
                if (filter_var($data["max_jugadores"],FILTER_VALIDATE_INT)===false){
                    $errors[] = "La cantidad de jugadores debe ser un número entero.";
                }
            }
            if(array_key_exists("disponible", $data)){
                if (!is_bool($data["disponible"])){
                    $errors[] = "La pista debe estar disponible (true) o no (false)";
                }
            }
        }

        return $errors;
    }

    private function getDeleteErrors(array $data){
        $errors=[];
        $reservaExistente = array_filter($this->reservaGateway->getAll(), fn($reserva) => $reserva["pista"]===$data["id"]);

        if($reservaExistente){
            $errors[] = "No se puede eliminar una pista si existen reservas a su nombre. Elimina las reservas primero.";
        }
        return $errors;
    }
}