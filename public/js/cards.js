document.addEventListener('DOMContentLoaded', function() {
    const API_URL = window.location.origin + '/Asistencia/app/controllers/EventosController.php';
    const eventsContainer = document.getElementById('events-container');

    async function loadEvents() {
        try {
            const response = await fetch(API_URL);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const events = await response.json();
            renderEvents(events);
        } catch (error) {
            console.error('Error al cargar eventos:', error);
            eventsContainer.innerHTML = `<p class="error">Error al cargar los eventos: ${error.message}</p>`;
        }
    }

    function renderEvents(events) {
        if (events.length === 0) {
            eventsContainer.innerHTML = '<p>No hay eventos programados actualmente.</p>';
            return;
        }

        eventsContainer.innerHTML = events.map(event => `
            <div class="card" data-id="${event.id}">
                <div class="img" style="background-image: url('/Asistencia/public/uploads/${event.imagen}'); background-size: cover;">
                    <!-- Imagen del evento -->
                </div>

                <div class="text">
                    <p class="h3">${event.nombre}</p>
                    <p class="p">${event.descripcion}</p>
                    <p class="event-date">${formatDate(event.fecha)} a las ${event.hora}</p>
                    <p class="event-duration">Duración: ${event.duracion} horas</p>
                    
                    <button class="register-btn" data-id="${event.id}">
                        Registrar Asistencia
                    </button>
                </div>
            </div>
        `).join('');

        // Agregar eventos a los botones
        document.querySelectorAll('.register-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const eventId = btn.getAttribute('data-id');
                showRegistrationForm(eventId);
            });
        });

        // Opcional: Click en la card para ver detalles
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('click', () => {
                const eventId = card.getAttribute('data-id');
                // Aquí puedes implementar ver detalles del evento
                console.log('Ver detalles evento:', eventId);
            });
        });
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    }

    function showRegistrationForm(eventId) {
    const eventName = document.querySelector(`.card[data-id="${eventId}"] .h3`).textContent;
    
    const formHTML = `
        <div class="modal" id="registro-modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Registro para: ${eventName}</h2>
                <form id="registro-form">
                    <input type="hidden" name="evento_id" value="${eventId}">
                    <div class="form-group">
                        <label for="nombre">Nombre completo:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="submit">Registrarse</button>
                </form>
                <div id="form-message" style="margin-top: 15px;"></div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', formHTML);
    
    const modal = document.getElementById('registro-modal');
    const closeBtn = modal.querySelector('.close');
    const messageDiv = document.getElementById('form-message');
    
    closeBtn.addEventListener('click', () => modal.remove());
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
    
    document.getElementById('registro-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const form = e.target;
        const formData = {
            evento_id: form.evento_id.value,
            nombre: form.nombre.value,
            email: form.email.value
        };

        try {
            const response = await fetch('/Asistencia/app/controllers/AsistenciasController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                const errorMsg = errorData?.message || `Error HTTP: ${response.status}`;
                throw new Error(errorMsg);
            }

            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Respuesta inesperada: ${text.substring(0, 100)}`);
            }
            
            const result = await response.json();
            
            
            
            if (result.success) {
                messageDiv.textContent = result.message;
                messageDiv.style.color = 'green';
                setTimeout(() => {
                    modal.remove();
                    loadEvents();
                }, 3000);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error completo:', error);
            messageDiv.textContent = `Error: ${error.message}`;
            messageDiv.style.color = 'red';
            
            // Mostrar más detalles en consola para depuración
            if (error.response) {
                console.error('Respuesta del servidor:', error.response);
            }
        }
    }); 
    }

    // Iniciar carga de eventos
    loadEvents();
});