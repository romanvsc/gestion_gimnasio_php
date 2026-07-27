# Entorno local y subida a Ferozo

## Local

El backend local queda configurado para usar esta base:

```env
DB_HOST=localhost
DB_NAME=c2650268_gym
DB_USER=root
DB_PASS=
APP_ENV=development
FRONTEND_URL=http://127.0.0.1:5174
```

Para trabajar localmente:

```bash
cd backend
php -S 127.0.0.1:8004 -t . index.php
```

En otra terminal:

```bash
cd frontend
npm run dev
```

El frontend local corre en `http://127.0.0.1:5174` y Vite manda las llamadas `/api` al backend local `http://127.0.0.1:8004`.

## Que subir a Ferozo

Antes de subir cambios del frontend, generar el build:

```bash
cd frontend
npm run build
```

Subir al servidor:

- El contenido de `frontend/dist/` a la carpeta publica donde vive la web, por ejemplo `public_html/gimnasio/` si la app queda en `/gimnasio/`.
- La carpeta `frontend/dist/assets/`.
- El archivo `frontend/dist/index.html`.
- El archivo `frontend/dist/favicon.ico`, si corresponde.
- La carpeta `backend/` como API, manteniendo su estructura interna: `api/`, `config/`, `helpers/`, `middleware/`, `index.php` y `.htaccess`.
- La carpeta `backend/uploads/` debe existir en el servidor y tener permisos de escritura para PHP. Ahi se guardan logos cargados desde Sistema.

No subir a Ferozo:

- `frontend/node_modules/`.
- `frontend/src/`.
- `frontend/package-lock.json` y `frontend/package.json`, salvo que tambien vayas a compilar en el servidor.
- `backend/.env` local.
- `backend/uploads/` local, salvo que estes restaurando logos manualmente. En produccion no borrar esta carpeta al actualizar la app.
- Archivos de prueba locales como `test_api.php`, `test_db.php`, `test_hash.php`.
- `sql_example.sql`, salvo que necesites importar o actualizar la base manualmente.

## Importante sobre backend/.env en Ferozo

En Ferozo debe existir un `backend/.env` propio con los datos reales del hosting:

```env
DB_HOST=...
DB_NAME=...
DB_USER=...
DB_PASS=...
FRONTEND_URL=https://tu-dominio.com
APP_ENV=production
JWT_SECRET=una_clave_larga_distinta_a_la_local
```

No reemplazar ese archivo con el `.env` local, porque apuntaria la app del servidor a una base que no existe en Ferozo.
