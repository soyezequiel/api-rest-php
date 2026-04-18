# Problemas Comunes y Soluciones

Durante el desarrollo con Docker, es normal enfrentarse a ciertos errores de configuración o de estado de la red. A continuación, se detallan los problemas más frecuentes y cómo solucionarlos.

### 1. Error `permission denied` al detener contenedores

**Síntoma:** Error `cannot stop container: ... permission denied` al ejecutar `docker compose down`.

**Causa:** Conflicto de permisos entre AppArmor y la versión de Docker instalada mediante `snap` (muy común en Ubuntu).

**Solución:** Reemplazar la versión `snap` por la oficial de `apt`. Ejecuta los siguientes comandos:

```bash
# 1. Eliminar la versión conflictiva de Snap
sudo snap remove docker

# 2. Instalar la versión oficial mediante APT
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
rm get-docker.sh

# 3. Configurar tu usuario (para evitar usar 'sudo' con docker)
sudo usermod -aG docker $USER
```

*Nota: Una vez finalizado, reinicia la computadora y abre una nueva para que los cambios de permisos surtan efecto.*
