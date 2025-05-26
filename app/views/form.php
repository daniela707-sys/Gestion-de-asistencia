<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Eventos</title>
    <link rel="stylesheet" href="css/form.css">
   
</head>
<body>
    <h1 class="Bienvenido">Bienvenido de nuevo!</h1>
    <!-- Formulario para agregar/editar productos -->
    <div class="form-container">
            <h2 id="form-title">Agregar Evento</h2>
            <form id="producto-form" enctype="multipart/form-data">
                <input type="hidden" id="producto-id" name="id">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required></textarea>
                </div>
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" required>
                </div>
                <div class="form-group">
                    <label for="hora">Hora:</label>
                    <input type="time" id="hora" name="hora" required>
                </div>
                <div class="form-group">
                    <label for="duracion">Duracion(h):</label>
                    <input type="number" id="duracion" name="duracion" required>
                </div>
                <div class="form-group">
                    <label for="imagen">Imagen del producto:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                </div>
                <button type="submit" id="submit-btn">Guardar</button>
                <button type="button" id="cancel-btn" style="display: none;">Cancelar</button>
            </form>
        </div>
        
        <!-- Tabla de productos -->
        <div class="table-container">
            <h2>Lista de eventos</h2>
            <table id="productos-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Duracion(h)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="eventos-body">
                    <!-- Los productos se cargarán aquí con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    <script src="js/app.js"></script>

</body>
</html>