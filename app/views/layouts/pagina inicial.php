<?php
// Configuración básica de rutas
$routes = [
    'eventos' => '/Asistencia/public/eventos.php'
];


require_once __DIR__ . '/../layouts/header.php';



?>


    
    <div class="wave-background">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
        
    </div>
    <section class="seccioninfo">
            <div>
                <H1 class="">BIENVENIDO A NUESTRA PAGINA DE EVENTOS</H1>
                <!-- From Uiverse.io by cssbuttons-io --> 
                <a href="<?php echo $routes['eventos']; ?>" class="router-link">
                    <button class="learn-more">
                        <span class="circle" aria-hidden="true">
                        <span class="icon arrow"></span>
                        </span>
                        <span class="button-text">Ver eventos</span>
                    </button>
                </a>
                
            </div>
            
    </section>

  