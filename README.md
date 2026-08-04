# Gym System

Sistema web para administrar la operación diaria de un gimnasio. Permite gestionar socios, planes, cuotas, accesos, reportes y configuración de la empresa desde una SPA responsive.

La aplicación fue desarrollada como proyecto académico de PHP y está publicada en:

**Demo:** [https://servinlgsm.com.ar/gimnasio/](https://servinlgsm.com.ar/gimnasio/)

## Funcionalidades

- Autenticación con JWT y roles `admin` y `staff`.
- Alta, consulta, edición y baja lógica de socios.
- Búsqueda de socios por nombre, apellido, DNI, email o teléfono.
- Administración de planes, precios y duración de membresías.
- Registro de cuotas y actualización automática de la vigencia.
- Control de accesos y política configurable para ingresos duplicados.
- Dashboard con socios activos, mora, ingresos y asistencia.
- Reportes operativos de los últimos meses.
- Configuración de datos, logo y reglas del gimnasio.
- Interfaz responsive para escritorio y dispositivos móviles.
- Aislamiento multiempresa mediante `company_id`.

## Tecnologías

### Frontend

- Vue 3
- Vite 8
- Pinia
- Vue Router
- Axios
- Tailwind CSS 3

### Backend

- PHP 8.1 o superior
- API REST sin framework
- PDO para MySQL/MariaDB
- Autenticación JWT con firma HS256
- Apache y reglas `.htaccess` para producción

### Base de datos

- MySQL o MariaDB
- Relaciones mediante claves foráneas
- Separación obligatoria de los datos por empresa

## Arquitectura

El proyecto se organiza por capacidades de negocio. Cada contexto incluye las vistas, stores y endpoints necesarios para resolver su responsabilidad sin trasladar reglas críticas a los componentes visuales.

| Contexto | Responsabilidad |
| --- | --- |
| Identidad y acceso | Login, JWT, roles y protección de rutas |
| Socios y membresías | Datos personales, estado y vigencia visible |
| Planes | Precio, duración y disponibilidad de planes |
| Pagos y cobranza | Registro de cuotas y extensión de membresía |
| Control de ingresos | Check-ins y tratamiento de duplicados |
| Métricas y reportes | Indicadores de socios, ingresos y asistencia |
| Configuración | Datos y políticas de cada gimnasio |

El frontend consume contratos HTTP explícitos. El backend valida las operaciones persistentes y todas las consultas de negocio utilizan el `company_id` obtenido del token autenticado.

## Estructura del proyecto

```text
gimnasio-php/
├── backend/
│   ├── api/             # Endpoints agrupados por contexto
│   ├── config/          # Base de datos y CORS
│   ├── helpers/         # JWT, respuestas y validaciones
│   ├── middleware/      # Autenticación y autorización
│   ├── uploads/         # Logos cargados por la aplicación
│   └── index.php        # Router principal de la API
├── frontend/
│   ├── public/          # Archivos públicos
│   └── src/
│       ├── components/  # Componentes reutilizables
│       ├── layouts/     # Estructura general de la SPA
│       ├── services/    # Cliente HTTP
│       ├── stores/      # Estado con Pinia
│       └── views/       # Pantallas por capacidad
├── deploy_filezilla/    # Artefactos preparados para Ferozo
├── sql_example.sql      # Esquema completo y datos demo
└── sql_sync_ferozo_runtime_schema.sql
```

## Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql` y `fileinfo`.
- MySQL o MariaDB.
- Node.js `20.19+` o `22.12+`.
- npm.
- XAMPP es opcional, pero simplifica el uso local de PHP y MySQL en Windows.

## Instalación local

### 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd gimnasio-php
```

### 2. Crear la base de datos

Crear una base llamada `c2650268_gym` desde phpMyAdmin e importar:

```text
sql_example.sql
```

El script crea las tablas y carga una empresa, planes, socios, pagos y accesos de demostración.

### 3. Configurar el backend

En PowerShell:

```powershell
Copy-Item .env.example backend/.env
```

Revisar `backend/.env` y completar los datos locales:

```env
DB_HOST=localhost
DB_NAME=c2650268_gym
DB_USER=root
DB_PASS=
JWT_SECRET=una_clave_larga_y_aleatoria
JWT_EXPIRATION=28800
FRONTEND_URL=http://127.0.0.1:5185
APP_ENV=development
```

Nunca se debe subir `backend/.env` al repositorio ni reemplazar con él las credenciales del hosting.

### 4. Iniciar la API PHP

```powershell
cd backend
php -S 127.0.0.1:8004 -t . index.php
```

La API quedará disponible en `http://127.0.0.1:8004/api`.

### 5. Iniciar el frontend

En otra terminal:

```powershell
cd frontend
npm install
npm run dev
```

Abrir `http://127.0.0.1:5185`.

## Usuario de demostración local

Después de importar `sql_example.sql`:

```text
Usuario:    admin@demogym.com
Contraseña: password
Rol:        admin
```

Estas credenciales son exclusivamente demostrativas y deben cambiarse antes de utilizar el sistema con información real.

## Rutas principales de la API

Todas las rutas privadas requieren el encabezado `Authorization: Bearer <token>`.

| Método | Ruta | Descripción |
| --- | --- | --- |
| `POST` | `/api/auth/login` | Iniciar sesión |
| `GET` | `/api/auth/me` | Obtener la sesión actual |
| `GET`, `POST` | `/api/members` | Listar o crear socios |
| `GET`, `PUT`, `DELETE` | `/api/members/{id}` | Consultar, editar o dar de baja un socio |
| `PATCH` | `/api/members/{id}/status` | Cambiar el estado de un socio |
| `GET`, `POST` | `/api/plans` | Listar o crear planes |
| `PUT`, `DELETE` | `/api/plans/{id}` | Editar o desactivar planes |
| `GET`, `POST` | `/api/payments` | Listar o registrar cuotas |
| `GET`, `POST` | `/api/checkins` | Listar o registrar accesos |
| `GET` | `/api/metrics` | Obtener métricas del dashboard |
| `GET`, `PUT` | `/api/settings` | Consultar o actualizar la empresa |
| `POST` | `/api/settings/logo` | Cargar el logo del gimnasio |

## Reglas de negocio destacadas

- Un socio y el plan que se le asigna deben pertenecer a la misma empresa.
- Una cuota extiende la membresía usando la duración del plan vigente.
- Si la membresía todavía está vigente, una cuota nueva extiende desde la fecha de vencimiento actual y no desde una fecha anterior.
- Un socio inactivo no puede registrar un ingreso.
- La política de check-in duplicado puede ser `allow`, `confirm` o `block`.
- Los endpoints filtran los datos por el `company_id` incluido en el JWT.

## Verificación

Compilar el frontend antes de entregar o desplegar:

```powershell
cd frontend
npm run build
```

Comprobar la sintaxis de los archivos PHP:

```powershell
Get-ChildItem ..\backend -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

También se recomienda probar como mínimo el login, el listado y alta de socios, una cuota y un check-in.

## Despliegue en Ferozo

El build utiliza `/gimnasio/` como ruta base de producción.

1. Ejecutar `npm run build` dentro de `frontend`.
2. Subir el contenido de `frontend/dist` a `/gimnasio`.
3. Subir el backend a `/gimnasio/api` conservando el `.env` remoto y `uploads`.
4. Si la base ya existía, importar `sql_sync_ferozo_runtime_schema.sql` desde phpMyAdmin.
5. Verificar que socios, planes, configuración y creación de registros respondan correctamente.

Las instrucciones completas se encuentran en [DEPLOY_FEROZO.md](DEPLOY_FEROZO.md).

## Seguridad

- Las contraseñas se almacenan con `password_hash` y se verifican con `password_verify`.
- Los tokens JWT tienen vencimiento y firma HMAC SHA-256.
- Los secretos se cargan desde variables de entorno.
- Las rutas administrativas validan el rol del usuario.
- El aislamiento por `company_id` evita mezclar información entre gimnasios.
- CORS limita los orígenes permitidos según el entorno.

## Licencia

Este proyecto se distribuye bajo la licencia MIT. Consulta [LICENSE.md](LICENSE.md) para conocer sus términos.

## Autor

**RomanVC**  
Proyecto académico de gestión de gimnasios desarrollado con Vue, PHP y MySQL.
