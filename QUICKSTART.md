# Guía Rápida

Esta es una versión alternativa, más directa, de la configuración inicial y comandos útiles para el proyecto (proveniente de la rama local).

## Lanzador en Windows
Ejecutar `lanzador.bat` con doble clic desde la raiz del proyecto.

Opciones principales:
* **Abrir proyecto:** inicia Docker Compose y abre la API/phpMyAdmin.
* **Cerrar proyecto:** detiene los servicios y conserva la base de datos.
* **Limpiar DB:** elimina el volumen de la base de datos despues de confirmar.

## Configuracion manual
1. Crear archivo `.env` a partir de la plantilla:
   `cp .env.dist .env` (y configurar las claves reales).
2. Crear volumen para la base de datos:
   `sudo docker volume create seminariophp`
3. Iniciar servicios:
   `docker-compose up -d`
4. Instalar dependencias (desde la carpeta /slim):
   `docker run --rm -v ${PWD}:/app composer install`
5. Importar base de datos:
   Importar `db/schema.sql` en phpMyAdmin (`localhost:8080`).

## Comandos Útiles
* **Bajar servicios:** `docker-compose down -v`
* **Limpiar DB:** `docker volume rm seminariophp`
* **Ver contenedores:** `docker ps`
