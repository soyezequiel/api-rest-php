# Seminario PHP - API REST de Inversiones (UNLP)

## Requisitos
* Docker Desktop

## Configuración Inicial
1. Crear archivo `.env` a partir de la plantilla:
   `cp .env.dist .env` (y configurar las claves reales).
2. Crear volumen para la base de datos:
   `docker volume create seminariophp`
3. Iniciar servicios:
   `docker-compose up -d`
4. Instalar dependencias (desde la carpeta /slim):
   `docker run --rm -v ${PWD}:/app composer install`
5. Importar base de datos:
   Importar `db/schema.sql` en phpMyAdmin (`localhost:8080`).

## Comandos Útiles
* **Bajar servicios:** `docker-compose down -v`
* **Limpiar DB:** `docker volume rm seminariophp`

## Tecnologías y Librerías
* **Slim 4**: Framework base.
* **Firebase PHP-JWT**: Autenticación con tokens.
* **PHP-Dotenv**: Variables de entorno.
