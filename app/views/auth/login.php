<?php 
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Iniciar Sesión</h4>
            </div>
            <div class="card-body">
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const originalText = submitBtn.innerHTML;
    
    // Mostrar spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Verificando...
    `;

    // Validación básica
    if(email === '' || password === '') {
        showAlert('Todos los campos son obligatorios', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        return;
    }
    
    // Simulación de verificación de credenciales
    if(email === 'admin1@gmail.com' && password === 'A1234567'){
        // Redirección después de 1 segundo (simulando tiempo de verificación)
        setTimeout(() => {
            window.location.href = '/Asistencia/public/gestion.php'; // Ruta corregida
        }, 1000);
    } else {
        showAlert('Credenciales incorrectas', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

function showAlert(message, type) {
    // Eliminar alertas anteriores
    const oldAlert = document.querySelector('.alert');
    if (oldAlert) oldAlert.remove();
    
    // Crear nueva alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} mt-3`;
    alertDiv.innerHTML = `
        <i class="bi ${type === 'danger' ? 'bi-exclamation-triangle' : 'bi-check-circle'}"></i>
        ${message}
    `;
    
    // Insertar después del formulario
    const form = document.getElementById('loginForm');
    form.parentNode.insertBefore(alertDiv, form.nextSibling);
    
    // Eliminar después de 5 segundos
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>