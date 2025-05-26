<?php
// Configuración básica de rutas
$routes = [
    'events' => '/Asistencia/public/login.php'
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/Asistencia/public/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success p-3 ">
        <div class="container">
            <a class="navbar-brand"> Bienvenido a nuestra pagina de inicio</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="admin">
                <h4 class="ad">¿Eres administrador?</h4>
                <!-- From Uiverse.io by satyamchaudharydev --> 
                 <a href="<?php echo $routes['events']; ?>" class="button1 router-link">
                    <button class="button1">
                        <p>Inicia sesion</p>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="4"
                        >
                            <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"
                            ></path>
                        </svg>
                    </button>
                </a>
            
            </div>
        </div>
    </nav>

</body>    
