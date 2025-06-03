## ✏️ Actualización: Gestión de Asistencia con Envío de Correo Electrónico

### ✅ Cambios Realizados

Se ha actualizado el sistema de **Gestión de Asistencia** para incluir el envío automático de correos electrónicos de confirmación utilizando **PHPMailer**.

### 📬 Envío de Correos con Mailtrap

* Se ha integrado **Mailtrap** como herramienta de testing para los correos electrónicos.
* El archivo `AsistenciaController.php` contiene la lógica para:

  * Capturar los datos del usuario al registrar asistencia.
  * Configurar los parámetros de PHPMailer.
  * Enviar el correo electrónico al usuario registrado a través de los servidores SMTP de Mailtrap.

### 📌 Notas Técnicas

* Asegúrate de tener configurado correctamente tu cuenta de Mailtrap y haber ingresado tus credenciales SMTP en el controlador (`AsistenciaController.php`).
* Esta funcionalidad es útil para validar el envío de correos en ambientes de desarrollo sin utilizar un servidor de correo real.

### 🛠️ Archivos Relevantes

* `controllers/AsistenciaController.php`: lógica principal para registro y envío de correos.
* `config/email.php` (si aplica): configuración de credenciales y parámetros de Mailtrap.


