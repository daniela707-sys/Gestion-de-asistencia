document.addEventListener('DOMContentLoaded', function() {
    // 1. OBTENER ELEMENTOS DEL DOM
    const form = document.getElementById('producto-form');
    const idInput = document.getElementById('producto-id');
    const nombreInput = document.getElementById('nombre');
    const descripcionInput = document.getElementById('descripcion');
    const fechaInput = document.getElementById('fecha');
    const horaInput = document.getElementById('hora');
    const duracionInput = document.getElementById('duracion');
    const imagenInput = document.getElementById('imagen');
    const submitBtn = document.getElementById('submit-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const eventosBody = document.getElementById('eventos-body');


    // Verificar que todos los elementos existen
    if (!form || !idInput || !nombreInput || !descripcionInput || !fechaInput || 
        !horaInput || !duracionInput || !imagenInput || !submitBtn || !cancelBtn || 
        !eventosBody ) {
        console.error('Error: No se encontraron todos los elementos necesarios en el DOM');
        return;
    }

    // 2. CONFIGURACIÓN INICIAL
    const API_URL = window.location.origin + '/Asistencia/app/controllers/EventosController.php';
    let eventos = [];
    let editingId = null;

    // 3. FUNCIONES PRINCIPALES

    // Cargar eventos desde la API
    async function cargareventos() {
        try {
            const response = await fetch(API_URL);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`La respuesta no es JSON: ${text.substring(0, 100)}...`);
            }
            
            eventos = await response.json();
            renderTabla();
        } catch (error) {
            console.error('Error al cargar eventos:', error);
            alert('No se pudieron cargar los eventos. Verifica la consola para más detalles.');
        }
    }

    // Renderizar tabla de eventos
    function renderTabla() {
        eventosBody.innerHTML = '';
        
        if (eventos.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.textContent = 'No hay eventos registrados';
            row.appendChild(cell);
            eventosBody.appendChild(row);
            return;
        }

        eventos.forEach(evento => {
            const row = document.createElement('tr');
            
            // Create cells safely
            const cells = [
                { text: evento.id },
                { text: evento.nombre },
                { text: evento.descripcion },
                { text: evento.fecha },
                { text: evento.hora },
                { text: evento.duracion }
            ];

            cells.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell.text;
                row.appendChild(td);
            });

            // Create action buttons
            const actionsCell = document.createElement('td');
            const editBtn = document.createElement('button');
            editBtn.className = 'btn-editar';
            editBtn.textContent = 'Editar';
            editBtn.dataset.id = evento.id;
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-eliminar';
            deleteBtn.textContent = 'Eliminar';
            deleteBtn.dataset.id = evento.id;

            actionsCell.appendChild(editBtn);
            actionsCell.appendChild(deleteBtn);
            row.appendChild(actionsCell);
            
            eventosBody.appendChild(row);
        });

        // Add event listeners
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => editarEvento(btn.dataset.id));
        });

        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', () => eliminarEvento(btn.dataset.id));
        });
    }
            


    // 4. MANEJADORES DE EVENTOS

    // Manejador del formulario
async function handleSubmit(e) {
    e.preventDefault();
    
    try {
        const requiredFields = {
            nombre: nombreInput,
            descripcion: descripcionInput,
            fecha: fechaInput,
            hora: horaInput,
            duracion: duracionInput
        };

        const emptyFields = Object.entries(requiredFields)
            .filter(([_, input]) => !input.value.trim())
            .map(([name]) => name);

        if (emptyFields.length > 0) {
            throw new Error(`Los siguientes campos son requeridos: ${emptyFields.join(', ')}`);
        }
        
        // Validar campos requeridos
        if (!nombreInput.value || !descripcionInput.value || !fechaInput.value || !horaInput.value) {
            throw new Error('Todos los campos son requeridos');
        }

        const formData = new FormData();
        formData.append('nombre', nombreInput.value);
        formData.append('descripcion', descripcionInput.value);
        formData.append('fecha', fechaInput.value);
        formData.append('hora', horaInput.value);
        formData.append('duracion', duracionInput.value);

        // Agregar imagen si existe
        if (imagenInput.files[0]) {
            formData.append('imagen', imagenInput.files[0]);
        }

        // Agregar ID si estamos editando
        if (editingId) {
            formData.append('id', editingId);
        }

        const response = await fetch(API_URL, {
            method: editingId ? 'PUT' : 'POST',
            body: editingId ? JSON.stringify(Object.fromEntries(formData)) : formData,
            headers: editingId ? { 'Content-Type': 'application/json' } : {}
        });

        // Verificar respuesta
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'Error en la respuesta del servidor');
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'La operación no fue exitosa');
        }

        alert(`Evento ${editingId ? 'actualizado' : 'creado'} correctamente!`);
        resetForm();
        cargareventos();
    } catch (error) {
        console.error('Error al guardar producto:', error);
        alert(`Error: ${error.message}`);
    }
}

    // Editar producto
    function editarEvento(id) {
        const producto = eventos.find(p => p.id == id);
        if (producto) {
            editingId = producto.id;
            idInput.value = producto.id;
            nombreInput.value = producto.nombre;
            descripcionInput.value = producto.descripcion;
            fechaInput.value = producto.fecha;
            horaInput.value = producto.hora;
            duracionInput.value = producto.duracion;

            
            submitBtn.textContent = 'Actualizar';
            cancelBtn.style.display = 'inline-block';
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Eliminar producto
    async function eliminarEvento(id) {
        if (!confirm('¿Estás seguro de eliminar este producto?')) return;
        
        try {
            const response = await fetch(API_URL, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });

            if (!response.ok) throw new Error('Error al eliminar');

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'No se pudo eliminar el producto');
            }

            alert('Producto eliminado correctamente');
            cargareventos();
        } catch (error) {
            console.error('Error al eliminar producto:', error);
            alert(`Error: ${error.message}`);
        }
    }

    // Resetear formulario
    function resetForm() {
        form.reset();
        editingId = null;
        idInput.value = '';
        submitBtn.textContent = 'Guardar';
        cancelBtn.style.display = 'none';
    }

    // 5. ASIGNAR EVENTOS

    // Evento del formulario
    form.addEventListener('submit', handleSubmit);

    // Evento del botón cancelar
    cancelBtn.addEventListener('click', resetForm);

   

   

    // 6. INICIAR APLICACIÓN
    cargareventos();
});

/*
// Función mejorada para manejar el formulario
async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const isEditing = idInput.value !== '';

    try {
        let response;
        
        if (isEditing) {
            // Para edición (PUT)
            const jsonData = {
                id: idInput.value,
                nombre: nombreInput.value,
                descripcion: descripcionInput.value,
                fecha: fechaInput.value,
                hora: horaInput.value
            };
            
            if (imagenInput.files[0]) {
                jsonData.imagen = await processImage(imagenInput.files[0]);
            }
            
            response = await fetch(API_URL, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(jsonData)
            });
        } else {
            // Para creación (POST)
            response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
        }

        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const errorText = await response.text();
            throw new Error(`El servidor respondió con: ${errorText.substring(0, 100)}...`);
        }

        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Error en la operación');
        }
        
        alert(`Producto ${isEditing ? 'actualizado' : 'creado'} correctamente`);
        resetForm();
        cargareventos();
    } catch (error) {
        console.error('Error en el formulario:', error);
        alert(`Error: ${error.message}`);
    }
}

// Función para procesar imágenes
async function processImage(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            // Aquí podrías comprimir o redimensionar la imagen si es necesario
            resolve(e.target.result);
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

// Asignar el evento corregido
form.removeEventListener('submit', handleSubmit); // Elimina el anterior si existe
form.addEventListener('submit', handleFormSubmit);*/