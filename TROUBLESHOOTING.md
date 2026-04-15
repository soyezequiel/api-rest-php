# Problemas Comunes y Soluciones

Durante el desarrollo con Docker, es normal enfrentarse a ciertos errores de configuración o de estado de la red. A continuación, se detallan los problemas más frecuentes y cómo solucionarlos.

## Reiniciar Docker cuando queda un puerto o proxy trabado

**El problema:**
A veces Docker se queda con estado interno viejo después de un `up`, un `down` o un cierre inesperado. En ese caso puede dejar un `docker-proxy` colgado, reservar un puerto que parece libre o mantener una red en un estado inconsistente.

**La solución:**
Reiniciar el servicio de Docker limpia ese estado temporal y vuelve a levantar el daemon desde cero. Eso suele liberar puertos que quedaron enganchados y hace que Docker reconstruya sus redes y proxies correctamente.

```bash
sudo systemctl restart docker
```

Después de eso, volvé a levantar el proyecto:

```bash
sudo docker compose up -d
```

## Error de Red: `iptables failed: Chain 'DOCKER-ISOLATION-STAGE-2' does not exist`

**El problema:** 
Este error ocurre cuando las reglas de red que Docker maneja internamente en tu sistema (`iptables`/`nftables`) se han corrompido, borrado o entrado en conflicto. Suele pasar tras actualizaciones del sistema, configuraciones de firewalls (como `ufw`) o limpiezas manuales.

**La solución:**
Debes detener Docker, hacer una limpieza manual y profunda de las tablas del firewall y de las redes de Docker, y luego volver a iniciarlo. 

Ejecuta los siguientes comandos uno por uno en tu terminal:

1. **Detener el servicio de Docker:**
   ```bash
   sudo systemctl stop docker
   sudo systemctl stop docker.socket
   ```

2. **Limpiar absolutamente todas las reglas del firewall:**
   ```bash
   sudo iptables --flush
   sudo iptables -t nat --flush
   sudo iptables -t filter --flush
   sudo iptables -X
   ```

3. **Restablecer las políticas por defecto del firewall:**
   ```bash
   sudo iptables -P INPUT ACCEPT
   sudo iptables -P FORWARD ACCEPT
   sudo iptables -P OUTPUT ACCEPT
   ```

4. **Borrar los puentes de red locales de Docker que quedaron corruptos:**
   *(Si alguno de estos comandos indica que `docker0` no existe, no te preocupes, continúa con el paso 5)*
   ```bash
   sudo ip link set docker0 down
   sudo ip link delete docker0
   ```

5. **Iniciar Docker nuevamente:**
   ```bash
   sudo systemctl start docker
   ```

6. **Volver a levantar tu proyecto:**
   ```bash
   sudo docker compose up -d
   ```

## Error: Puerto ya en uso (`bind: address already in use`)

**El problema:**
Ocurre cuando intentas levantar los contenedores pero uno de los puertos (por ejemplo, el `80` o el `3306` de la base de datos) ya está siendo ocupado por otro programa en tu computadora (como Apache local, un MySQL local u otro contenedor huérfano).

**La solución:**
1. Identifica qué servicio está ocupando el puerto:
   ```bash
   sudo lsof -i :PUERTO_CONFLICTIVO
   ```
2. Puedes detener ese servicio local, o bien, si tu intención es que corran a la vez, edita tu archivo `.env` y cambia las variables de los puertos (`SLIM_PORT` o `DBADMIN_PORT`) por otros que estén libres (ej: `8080` a `8081`).
3. Vuelve a ejecutar `sudo docker compose up -d`.
