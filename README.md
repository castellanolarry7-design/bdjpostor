# JPStore — Instrucciones para Windows

## Requisitos
- PHP 8.1+ (descargado de https://windows.php.net/download/)
- Composer (https://getcomposer.org/Composer-Setup.exe)
- Node.js 20+ (https://nodejs.org/)

## Instalación (3 pasos)

### Paso 1 — Instalar
Haz doble clic en **instalar.bat**

O en una terminal:
```
cd jpstore
instalar.bat
```

### Paso 2 — Correr el backend
Abre una terminal en la carpeta `backend` y ejecuta:
```
php artisan serve
```
Debe mostrar: `Server running on [http://127.0.0.1:8000]`

### Paso 3 — Correr el frontend
Abre OTRA terminal en la carpeta `frontend` y ejecuta:
```
npm run dev
```
Debe mostrar: `Local: http://localhost:5173/`

## Acceder al sistema
Abre tu navegador en: **http://localhost:5173**

**Super Admin:**
- Email: admin@jpstore.com
- Password: JPStore2024!

## Base de datos
Por defecto usa **SQLite** (no necesitas instalar nada).
El archivo de la base de datos se crea automáticamente en:
`backend/database/database.sqlite`

## Si algo falla
Si `composer install` falla por extensiones de PHP:
```
composer install --ignore-platform-reqs
```

Si necesitas reiniciar la base de datos:
```
cd backend
php artisan migrate:fresh --seed
```
