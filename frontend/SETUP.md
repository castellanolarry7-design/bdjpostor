# JPStore Frontend — Setup

## Requisitos
- Node.js 20+
- npm 10+

## Instalación

```bash
cd frontend

# 1. Instalar dependencias
npm install

# 2. Variables de entorno (ya configuradas para desarrollo)
# El archivo .env.development apunta a http://localhost:8000

# 3. Levantar en modo desarrollo
npm run dev
# → http://localhost:5173

# 4. Build de producción
npm run build
# → Genera la carpeta dist/

# 5. Tests
npm test
```

## Estructura de vistas
- /login              → Inicio de sesión
- /app/dashboard      → Dashboard de inventario (usuarios de empresa)
- /app/products       → Gestión de productos
- /app/movements      → Movimientos de inventario
- /superadmin/tenants → Panel de super admin (empresas)
