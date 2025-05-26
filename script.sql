CREATE DATABASE eventos;

USE eventos;

CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    duracion INT NOT NULL,
    imagen VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    nombre_participante VARCHAR(100) NOT NULL,
    email_participante VARCHAR(100) NOT NULL,
    codigo_asistencia VARCHAR(20) NOT NULL,
    qr_path VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    UNIQUE KEY (email_participante, evento_id)
);

-- Optional: Add sample data
INSERT INTO eventos (nombre, descripcion, fecha, hora, duracion, imagen) VALUES
('Conferencia de Tecnología', 'Una conferencia sobre las últimas tendencias tecnológicas', '2025-06-15', '10:00:00', 3, 'tech_conf.jpg'),
('Taller de Programación', 'Aprende a programar desde cero', '2025-06-20', '14:00:00', 4, 'coding_workshop.jpg');
