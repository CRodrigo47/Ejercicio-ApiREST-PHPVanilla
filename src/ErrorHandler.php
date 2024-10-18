<?php

class ErrorHandler {
    public static function handleException( Throwable $exception) :void{
        http_response_code(500);
        // internale error
        echo json_encode(["code" => $exception->getCode(),
                         "message" => $exception->getMessage(),
                         "file" => $exception->getFile(),
                          "line" => $exception->getLine()]);
    }

    public static function errorHandler(int $errno, string $errmsg, string $errfile, int $errline){
        throw new ErrorException($errmsg, 0, $errno, $errfile, $errline);
    }
}