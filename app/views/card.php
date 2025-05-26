<?php
require_once '../app/views/layouts/header2.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos Disponibles</title>
    <link rel="stylesheet" href="/Asistencia/public/css/cards.css">
    <style>
        .events-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(252px, 1fr));
            gap: 30px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .register-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .register-btn:hover {
            background-color: #45a049;
        }
        
        .event-date {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
        
        .event-duration {
            font-size: 13px;
            color: #555;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center; margin: 20px 0;">Próximos Eventos</h1>
    
    <div class="events-container" id="events-container">
        <!-- Las cards se cargarán aquí con JavaScript -->
    </div>

    <script src="/Asistencia/public/js/cards.js"></script>
</body>
</html>