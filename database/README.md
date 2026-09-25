# Base de producción

Importar `sistema_minutos_db.sql` en una base vacía seleccionada desde phpMyAdmin o con `mysql -u USUARIO -p BASE < database/sistema_minutos_db.sql`.

Requiere MySQL 8.4, permisos para crear tablas, triggers y eventos. Activar `event_scheduler=ON` para el cierre automático de turnos. El archivo incluye pagos manuales, pagos incompletos y cancelación/rehabilitación de turnos; no se necesitan migraciones adicionales. El archivo incluye tablas, índices, relaciones, reglas de códigos únicos y cierre de turnos, junto con los usuarios y buses actuales y sus roles, estados, asignaciones y permisos. Conserva las contraseñas almacenadas de los usuarios. No incluye pagos, obligaciones ni turnos. Se importa completo en una base vacía, sin scripts adicionales de configuración.

El script no borra tablas existentes: es exclusivamente para una instalación nueva. Configurar la conexión del servidor en `Config/conexion.php`.

Para limpiar una base existente, el script separado `scripts/limpiar_datos.sql` elimina pagos, obligaciones, turnos e intentos. Conserva usuarios, buses, sus asignaciones, permisos, roles y estados. No se ejecuta al instalar. No elimina comprobantes del disco ni `data/valores_diarios.xlsx` (fuente de importación de valores); gestionar esos archivos por separado si también se desea retirarlos.

## Contraseñas

El SQL de instalación incluye `usuario.password_hash`. Los usuarios nuevos ingresan con cédula/cédula; pueden elegir una contraseña desde Perfil. Una contraseña personalizada se guarda con `password_hash` y se verifica con `password_verify`. El administrador puede restablecerla desde Usuarios (botón de llave); el cambio posterior es opcional.

Para actualizar una instalación existente sin reinstalar ni borrar datos: ejecutar `php scripts/actualizar_credenciales.php` desde el proyecto (en Docker: `docker exec sistema_minutos_php php /var/www/html/scripts/actualizar_credenciales.php`). Es idempotente y no restablece contraseñas ya cambiadas.

El perfil independiente está en `App/conductor/perfil.php`. Para activar el aviso de cumpleaños de 5 segundos una sola vez al día por usuario en una base existente: `docker exec sistema_minutos_php php /var/www/html/scripts/actualizar_cumpleanos.php`. La instalación nueva ya incluye esta columna.

Para actualizar una instalación existente con las frases editables de pagos, ejecutar `database/frases_pago.sql` una sola vez antes de abrir el módulo. La migración conserva las frases existentes si se vuelve a ejecutar.

## Historial de Valores Diarios

Antes de usar esta versión en una base existente, ejecutar `php scripts/actualizar_historial_valores.php` (en Docker: `docker compose exec -T web php scripts/actualizar_historial_valores.php`). La migración es idempotente y conserva los registros y sus pagos. No importar el SQL de instalación para actualizar una base existente.

Borrar deshabilita el registro sin eliminarlo. La opción «Modificar y volver a guardar» crea una nueva versión no pagada, vinculada al registro anterior; este conserva su valor y sus pagos. Solo puede existir una versión activa por disco y fecha. El filtro «Ver registros» permite consultar activos, deshabilitados o todos. Al deshabilitar los registros de un Excel también se conserva el archivo original.

«Exportar Excel» respeta los filtros, incluye todas las páginas y ordena por fecha de más antigua a más reciente. Cada versión modificada ocupa su propia fila, con su estado de pago y si sigue activa o deshabilitada. La descarga del Excel original desde Gestión Archivos no se modifica.

Pruebas: `docker compose exec -T web php tests/valores_historial.php`. Crean y eliminan únicamente una base temporal con nombre aleatorio; no modifican los datos de la aplicación.
