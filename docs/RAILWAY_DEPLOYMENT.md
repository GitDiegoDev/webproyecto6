# Guía de Despliegue en Railway - Gestor de Cobranzas

Esta guía explica paso a paso cómo preparar, desplegar y mantener el sistema **Gestor de Cobranzas** en la plataforma de nube **Railway**, utilizando una base de datos MySQL provista por la misma plataforma.

---

## Requisitos Previos

1. Una cuenta activa en [Railway](https://railway.com/).
2. Un repositorio en GitHub con el código del proyecto (rama `main`).
3. El cliente Git y/o la aplicación de GitHub en su dispositivo (puede realizarse completamente desde un celular o navegador web).

---

## 1. Estructura de Despliegue e Infraestructura

El despliegue en Railway consta de:
* **Servicio Web (Laravel):** Compilado y servido de forma automática usando **Nixpacks** (el constructor inteligente de Railway), que detecta un proyecto de Laravel, compila los assets de frontend y ejecuta el servidor web configurando el puerto dinámico de escucha.
* **Servicio de Base de Datos (MySQL):** Un contenedor de MySQL provisto por Railway para persistir los datos de manera aislada y segura.

---

## 2. Variables de Entorno Requeridas

Debe ingresar las siguientes variables de entorno en la pestaña **Variables** del servicio de Laravel en Railway:

### Configuración de la Aplicación
| Variable | Valor Recomendado | Descripción |
|---|---|---|
| `APP_ENV` | `production` | Establece el entorno en modo producción. |
| `APP_DEBUG` | `false` | Desactiva el modo depuración para evitar fugas de información. |
| `APP_KEY` | `base64:...` | Clave de cifrado de la aplicación. *(Ver sección de Generación de APP_KEY)* |
| `APP_URL` | `https://tu-dominio.up.railway.app` | La URL asignada por Railway o tu dominio propio. |

### Configuración de Base de Datos (MySQL)
No coloque credenciales fijas en el código. Railway permite enlazar servicios automáticamente. Puede utilizar las siguientes variables que se autocompletarán utilizando las referencias cruzadas de Railway o cargando las variables generadas por el servicio MySQL:

| Variable | Valor en Railway |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` o el host provisto por Railway |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` o el puerto provisto (generalmente `3306`) |
| `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` o el nombre de la base de datos |
| `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` o el usuario provisto |
| `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` o la contraseña provista |

### Configuración del Servidor y Caché
* El puerto de escucha de la aplicación se configura automáticamente mediante la variable de entorno `$PORT` que Railway inyecta en el contenedor en cada despliegue. No configure un puerto fijo en su código.
* Se recomienda utilizar drivers estables y compatibles con contenedores efímeros:
  * `SESSION_DRIVER=file` (o `database` si desea persistencia de sesiones ante reinicios)
  * `CACHE_STORE=file` (o `database` / `redis` si aprovisiona Redis)

---

## 3. Configuración y Optimización en Producción (`railway.toml`)

El proyecto incluye un archivo `railway.toml` en la raíz para automatizar las tareas del ciclo de vida del despliegue:

```toml
[build]
builder = "nixpacks"

[deploy]
preDeployCommand = "php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache"
restartPolicyType = "on-failure"
```

### Explicación de los Comandos de Lanzamiento:
1. `php artisan migrate --force`: Corre las migraciones de forma segura en la base de datos MySQL en producción. **Nunca** utiliza comandos destructivos como `migrate:fresh` o `db:wipe` en producción.
2. `php artisan config:cache`: Serializa y optimiza los archivos de configuración para máxima velocidad.
3. `php artisan route:cache`: Optimiza el mapeo de rutas.
4. `php artisan view:cache`: Precompila las plantillas Blade para reducir tiempos de respuesta.

---

## 4. Almacenamiento Persistente (`storage/`)

Las instancias de contenedores en Railway son efímeras (cualquier archivo creado en el sistema de archivos del contenedor se perderá cuando este se reinicie o redespliegue).

Si su aplicación requiere almacenar archivos subidos por los usuarios (como comprobantes de pago o archivos importados de carteras en producción), tiene dos opciones recomendadas:
1. **Volumen de Railway (Recomendado para inicio rápido):**
   * En la configuración del servicio Laravel en Railway, añada un **Volume** y monte la ruta `/app/storage/app` o `/app/storage`. Esto persistirá los archivos localmente a través de reinicios.
2. **Servicio Cloud Externo (S3 o compatible - Altamente recomendado para escalabilidad):**
   * Configure un proveedor como AWS S3, MinIO o Cloudflare R2 utilizando las variables estándar de Laravel en `config/filesystems.php`.

---

## 5. Instrucciones Paso a Paso de Despliegue (Desde el Celular o Navegador)

Siga estos pasos exactos para realizar el despliegue de forma rápida y sencilla:

### Paso 1: Crear un Nuevo Proyecto en Railway
1. Inicie sesión en [Railway](https://railway.com/).
2. Presione el botón **+ New Project**.
3. Seleccione la opción **Empty Project** (o elija directamente **Deploy from GitHub repo** si prefiere conectar de inmediato).

### Paso 2: Aprovisionar la Base de Datos MySQL
1. Dentro del proyecto vacío creado, presione **+ Add** (o **New**).
2. Seleccione **Database** y luego **MySQL**.
3. Railway creará y aprovisionará una base de datos MySQL en segundos.

### Paso 3: Agregar el Servicio Laravel (Desde GitHub)
1. Presione **+ Add** (o **New**).
2. Seleccione **GitHub Repo**.
3. Conecte su cuenta de GitHub si no lo ha hecho y elija el repositorio de su **Gestor de Cobranzas**.
4. Seleccione la rama principal (`main`).

### Paso 4: Configurar las Variables de Entorno en Laravel
1. Haga clic en el servicio web de su repositorio de GitHub recientemente agregado.
2. Navegue a la pestaña **Variables**.
3. Presione el botón **Raw Editor** para pegar rápidamente múltiples variables o añádalas una por una:
   * Genere un `APP_KEY` seguro (puede generarlo localmente ejecutando `php artisan key:generate --show` y copiando la cadena).
   * Ingrese las variables de configuración de la base de datos referenciando al servicio MySQL (Railway facilita esto con autocompletado en la interfaz).

### Paso 5: Despliegue Automático
Una vez guardadas las variables de entorno, Railway detectará el cambio y disparará un nuevo Build y Deploy de forma automática.
* El proceso de construcción ejecutará `composer install` y compilará los assets de producción mediante Vite (`npm install && npm run build`).
* Antes de que el nuevo contenedor empiece a recibir tráfico web, se ejecutará el comando definido en `preDeployCommand` (migraciones y caches).

### Paso 6: Generar un Dominio de Acceso
1. En el servicio de Laravel, vaya a la pestaña **Settings**.
2. Bajo la sección **Environment / Networking**, busque **Domains**.
3. Presione **Generate Domain** para obtener una dirección pública gratuita de tipo `*.up.railway.app` o configure un dominio personalizado propio apuntando el registro CNAME.
4. Actualice su variable de entorno `APP_URL` con la URL generada (asegúrese de incluir `https://`).

---

## 6. Configuración de Datos Iniciales y Semillas (Seeders)

Para cargar los datos iniciales obligatorios del sistema (como las plantillas de mensajes preconfiguradas):

1. En el dashboard de Railway, haga clic en el servicio de Laravel.
2. Vaya a la pestaña **Logs** o abra la **Terminal** interactiva en Railway (disponible en la esquina superior derecha del panel del servicio o usando el CLI de Railway desde su consola).
3. Ejecute el siguiente comando para sembrar las plantillas:
   ```bash
   php artisan db:seed --class=PlantillasMensajesSeeder --force
   ```
   *Nota: El parámetro `--force` es requerido para ejecutar seeders en un entorno configurado como `production`.*

---

## 7. Creación del Primer Usuario Administrador de Forma Segura

Por razones de seguridad informática, **nunca** incluya credenciales fijas de administrador en el código fuente de los seeders del repositorio público.

Siga una de las siguientes opciones seguras para crear el primer usuario administrativo:

### Opción A: Usar la Terminal Interactiva de Railway (Recomendada)
1. Inicie la terminal en la consola de Railway de su servicio web.
2. Ejecute la herramienta interactiva de Laravel (Tinker):
   ```bash
   php artisan tinker
   ```
3. Ejecute los comandos PHP para crear el usuario administrador:
   ```php
   $user = new \App\Models\User();
   $user->name = 'Administrador Principal';
   $user->email = 'admin@tudominio.com';
   $user->password = Hash::make('TuContraseñaSeguraYPrivada123!');
   $user->role = 'admin'; // El rol debe ser 'admin' para acceso completo
   $user->save();
   ```
4. Escriba `exit` para cerrar la sesión de Tinker.

### Opción B: Crear un Comando Artisan de Creación
Si prefiere automatizar esto en el futuro, puede crear un comando Artisan personalizado en Laravel para la creación de usuarios administrativos iniciales que tome la información de variables de entorno seguras (como `ADMIN_EMAIL` y `ADMIN_PASSWORD`), garantizando que no existan secretos en el código fuente.

---

## 8. Resolución de Problemas Comunes (Troubleshooting)

### Error: `No application encryption key has been specified`
* **Causa:** Falta configurar la variable de entorno `APP_KEY`.
* **Solución:** Genere un hash de 32 bytes ejecutando `php artisan key:generate --show` en su computadora y asígnelo como variable de entorno `APP_KEY` en Railway.

### Error: `Vite manifest not found` o problemas de carga de CSS/JS
* **Causa:** El build de frontend falló o no se ejecutó en Railway.
* **Solución:** Verifique que el archivo `package.json` tenga el script `"build": "vite build"`. Nixpacks lo detecta y compila automáticamente durante el despliegue. Revise la pestaña de Logs de Construcción en Railway para asegurarse de que no haya errores de dependencias de node.

### Error: `SQLSTATE[HY000] [2002] Connection refused`
* **Causa:** Laravel está intentando conectarse a un host de base de datos incorrecto o inexistente.
* **Solución:** Asegúrese de que las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, y `DB_PASSWORD` coincidan perfectamente con los datos de acceso provistos por el servicio de MySQL de Railway.

### Error de Permisos en `storage` o `bootstrap/cache`
* **Causa:** Fallo al intentar escribir caches o subir archivos.
* **Solución:** Nixpacks maneja los permisos automáticamente asignando permisos de escritura al servidor web en estas carpetas. Si el error persiste, asegúrese de no haber incluido archivos temporales en su repositorio Git removiéndolos mediante su archivo `.gitignore`.
