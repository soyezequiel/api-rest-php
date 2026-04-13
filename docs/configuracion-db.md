# Guía de Configuración de Base de Datos (Docker + MySQL)

Esta guía detalla los pasos necesarios para configurar correctamente el entorno de base de datos para el proyecto API REST PHP, evitando los problemas comunes de permisos y configuración de usuarios.

## 1. Configuración del Entorno (`.env`)

El archivo `.env` en la raíz del proyecto controla cómo se crea la base de datos. 

> [!IMPORTANT]
> **Conflicto de Usuario Root**: En el archivo `docker-compose.yml`, la variable `MYSQL_USER` se usa para crear un usuario *adicional*. 
> **NO uses `root` como valor de `DB_USER`**, ya que el usuario root ya existe y esto causará que el contenedor falle al iniciar.

**Configuración recomendada:**
```ini
DB_NAME=seminariophp
DB_USER=ezequiel      # O cualquier nombre que NO sea root
DB_PASS=tu_clave      # Elige una clave segura
```

## 2. Preparación de Docker

Antes de levantar los servicios, asegúrate de tener el volumen local creado:

```bash
sudo docker volume create seminariophp
```

## 3. Estructura de Volúmenes en `docker-compose.yml`

Para que la aplicación PHP (Slim) pueda conectarse a la base de datos, necesita leer las credenciales del archivo `.env`. Dado que la aplicación corre dentro de un contenedor, debemos "mapear" el archivo `.env` de la raíz dentro del contenedor de Slim.

Asegúrate de que tu `docker-compose.yml` tenga esta configuración en el servicio `slim`:

```yaml
    slim:
        volumes:
            - ./slim:/var/www/html
            - ./.env:/var/www/html/.env  # Esto permite que PHP lea la configuración
```

## 4. Inicialización de la Base de Datos (`schema.sql`)

El proyecto está configurado para que cualquier archivo `.sql` dentro de la carpeta `/db` del proyecto se ejecute automáticamente la **primera vez** que se crea la base de datos.

1.  Crea el archivo `db/schema.sql`.
2.  Pega tu estructura de tablas (`CREATE TABLE...`) e inserciones iniciales.

### Importación Manual (Si el contenedor ya existe)
Si el contenedor ya está creado y quieres volver a importar el esquema, usa este comando:

```bash
sudo docker exec -i seminariophp-db-1 mysql -u root -proot < db/schema.sql
```
*(Nota: El nombre del contenedor puede variar, usa `docker ps` para verificarlo).*

## 5. Verificación

Puedes verificar que todo esté funcionando correctamente de dos maneras:

1.  **phpMyAdmin**: Entra a [http://localhost:8080](http://localhost:8080) con usuario `root` y clave `root`.
2.  **Endpoint de Test**: La API tiene un endpoint de prueba. Visita [http://localhost/test-env](http://localhost/test-env) en tu navegador. Deberías ver un JSON con el estado `success`.

---
*Guía generada por Antigravity para el Seminario PHP (UNLP).*
