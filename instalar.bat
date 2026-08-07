@echo off
chcp 65001 > nul
echo.
echo  ╔══════════════════════════════════════╗
echo  ║       JPStore — Instalacion          ║
echo  ╚══════════════════════════════════════╝
echo.

echo [1/5] Instalando dependencias del backend...
cd backend
call composer install --ignore-platform-reqs --no-interaction
if errorlevel 1 (
    echo ERROR: Fallo composer install
    pause
    exit /b 1
)

echo [2/5] Configurando variables de entorno...
if not exist .env copy .env.example .env

echo [3/5] Generando clave de aplicacion...
php artisan key:generate

echo [4/5] Creando base de datos y ejecutando migraciones...
php artisan migrate:fresh --seed

echo [5/5] Instalando dependencias del frontend...
cd ..\frontend
call npm install

echo.
echo  ╔══════════════════════════════════════════════════╗
echo  ║            JPStore listo!                        ║
echo  ╠══════════════════════════════════════════════════╣
echo  ║                                                  ║
echo  ║  Abre DOS terminales:                            ║
echo  ║                                                  ║
echo  ║  Terminal 1:  cd backend                        ║
echo  ║               php artisan serve                  ║
echo  ║               (backend en localhost:8000)        ║
echo  ║                                                  ║
echo  ║  Terminal 2:  cd frontend                       ║
echo  ║               npm run dev                        ║
echo  ║               (frontend en localhost:5173)       ║
echo  ║                                                  ║
echo  ║  Usuario: admin@jpstore.com                      ║
echo  ║  Password: JPStore2024!                          ║
echo  ║                                                  ║
echo  ╚══════════════════════════════════════════════════╝
echo.
pause
