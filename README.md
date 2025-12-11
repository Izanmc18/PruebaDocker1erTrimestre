# Despliegue Docker Compose: Nginx Proxy + Encuesta + Chistes

## ¿Qué es esto?

Pues basicamente he hecho un proyecto con Docker Compose que tenia que meter varios contenedores (Nginx, PHP, MySQL) para que funcione una encuesta y un servicio de chistes. El proxy inverso de Nginx es el que dirige las peticiones a cada servicio segun el dominio que uses.

## La estructura que cree

Así quedó todo organizado:

```
EncuestaChiste/
├── docker-compose.yml
├── nginx/
│   ├── Dockerfile
│   └── nginx.conf
├── encuesta/
│   ├── Dockerfile
│   └── index.php
├── chiste/
│   ├── Dockerfile
│   └── index.php
├── bd/
│   └── init.sql
└── README.md
```

Nada complicado, cada servicio en su carpeta y el docker-compose en la raiz.

## Lo que pedia la prueba

- Un Nginx que actue como proxy inverso y que reciba dos dominios distintos.
- El dominio de la encuesta redirige a 3 contenedores PHP (load balancing con pesos).
- El dominio de chistes va a un contenedor PHP que devuelve un chiste aleatorio.
- Todo en PHP y con MySQL para guardar los datos.

## Cómo hice el proyecto

### Paso 1: crear las carpetas y archivos

Primero lo que hago es crear la carpeta `EncuestaChiste` en Windows 11. Abro VS Code, creo las carpetas (nginx, encuesta, chiste, bd) y dentro pongo los archivos vacíos. Nada del otro mundo.

### Paso 2: el docker-compose.yml

Aqui es donde digo que servicios tengo y como se conectan. Creo:

- **nginx**: la imagen oficial, puerto 80, y le monto el nginx.conf.
- **encuesta1, encuesta2, encuesta3**: tres contenedores iguales que corren PHP.
- **chiste**: otro contenedor PHP para los chistes.
- **mysql**: la base de datos, con las tablas que necesito.

Cada servicio PHP tiene variables de entorno para conectarse a MySQL (host, usuario, contraseña, etc).

### Paso 3: Dockerfile de PHP

Tanto para la encuesta como para los chistes, uso `php:8.2-apache`. Necesito instalar el driver de MySQL:

```dockerfile
FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY index.php /var/www/html/index.php

EXPOSE 80
```

Sin esto no me conectaba a la BD y me daba el error "could not find driver".

### Paso 4: la configuración de Nginx

Ahora lo que hago es crear el `nginx.conf`. Aca defino un `upstream` con las 3 encuestas, dandole mas peso a una:

```nginx
events {}

http {
    upstream encuesta_app {
        server encuesta1 weight=3;
        server encuesta2 weight=1;
        server encuesta3 weight=1;
    }

    server {
        listen 80;
        server_name www.freedomforLinares.com;

        location / {
            proxy_pass http://encuesta_app;
        }
    }

    server {
        listen 80;
        server_name www.chiquito.com;

        location / {
            proxy_pass http://chiste;
        }
    }
}
```

Cuando entro en www.freedomforLinares.com, Nginx distribuye las peticiones entre las 3 encuestas. Y cuando voy a www.chiquito.com me lleva al servicio de chistes.

### Paso 5: la base de datos

Creo el `bd/init.sql` con las tablas:

```sql
CREATE DATABASE IF NOT EXISTS survey;

USE survey;

CREATE TABLE IF NOT EXISTS votos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  si INT DEFAULT 0,
  no INT DEFAULT 0
);

INSERT INTO votos (id, si, no)
VALUES (1, 0, 0)
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS chistes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  texto VARCHAR(255) NOT NULL
);

INSERT INTO chistes (texto) VALUES
  ('¡Paparr, llévame al circorr! No, no, el que quiera verte que venga a casa'),
  ('¡Feliz año nuevo a todos, señores! ¿Cómo que Feliz Año nuevo a todos si estamos en agosto? Uy qué bronca me va a echar mi mujer, nunca me he retrasado tanto..'),
  ('Mamarr, mamarr, ha venido papá borracho y se ha caído en el water. ¡Quitale la cartera y tira de la cisterna!'),
  ('Le dice un padre a un niño: Dime una mentira, hijo. ¡¡¡Paparr, paparr, paparr!!!');
```

Este script se ejecuta cuando arranca MySQL por primera vez, asi que las tablas estan listas.

### Paso 6: el PHP de la encuesta

En `encuesta/index.php` hago:

1. Me conecto a MySQL con las variables de entorno.
2. Si recibo un POST (alguien votó), actualizo la tabla votos.
3. Despues hago un redirect a sí mismo para no repetir el voto si recargo.
4. Muestro la pregunta, el formulario y los resultados.
5. También muestro el nombre del contenedor para ver que el balanceo funciona.

El truco del redirect es importante porque sino cada recarga suma un voto.

### Paso 7: el PHP de chistes

Para los chistes es más fácil, en `chiste/index.php`:

1. Me conecto a BD.
2. Hago un SELECT aleatorio: `SELECT texto FROM chistes ORDER BY RAND() LIMIT 1`.
3. Muestro el chiste en HTML.

Cada vez que recargo, aparece otro distinto. Nginx va alternando entre contenedores, aunque solo haya uno.

### Paso 8: mapear los dominios en hosts

Para que los dominios funcionen localmente, tengo que editar el archivo `hosts` de Windows como administrador:

```
C:\Windows\System32\drivers\etc\hosts
```

Añado al final:

```
127.0.0.1   www.freedomforLinares.com
127.0.0.1   www.chiquito.com
```

Así cuando escribo esos dominios en el navegador, van a mi máquina.

### Paso 9: levantar todo

En la terminal hago:

```bash
docker-compose up --build
```

El primero construye las imágenes, el segundo arranca todo. Veo en la terminal como se inicializa todo, MySQL arranca, Apache también, y Nginx empieza a escuchar.

### Paso 10: probar

Abro el navegador:

- Voy a **http://www.freedomforLinares.com** y voto varias veces. Refresco sin votar y veo como cambia el contenedor.
- Voy a **http://www.chiquito.com** y refresco varias veces para ver chistes distintos.

## Cómo sé que el balanceo funciona

Lo importante es ver que Nginx está distribuyendo el tráfico correctamente. Yo hago esto:

1. Entro en la encuesta y refresco muchas veces sin votar.
2. Me fijo en el "Contenedor" que aparece al final de la página.
3. Como encuesta1 tiene weight=3, debería aparecer el triple de veces que encuesta2 o encuesta3.

Si refresco 20 veces sin votar:
- encuesta1 debería salir mas o menos 10 veces.
- encuesta2 debería salir unos 5 veces.
- encuesta3 debería salir unos 5 veces.

Eso significa que el balanceo está funcionando bien.

## Que necesitas para esto

- Windows 11 (o Linux/Mac).
- Docker Desktop instalado.
- VS Code.
- Git.
- GitHub Desktop (opcional).

## Los errores que tuve

### Error "could not find driver"

Me pasó cuando intenté entrar a la encuesta. El error era que PHP no tenia instalado el driver de MySQL. Lo fixeé añadiendo `RUN docker-php-ext-install pdo pdo_mysql` en los Dockerfile.

### Los votos se repetían al recargar

Cada vez que pulsaba F5, se sumaba otro voto. Esto pasaba porque el navegador reenviaba el POST automático. Lo arreglé con `header("Location: /")` despues de guardar el voto, así la pagina se recarga con GET y no repite el voto.

### Conflicto de puerto MySQL

Tenia otro MySQL corriendo en el 3306, así que cambié el puerto a 3307 en docker-compose. Los contenedores PHP se conectan internamente a mysql:3306 así que no hubo que cambiar nada en el PHP.

## Lo que aprendí

Basicamente aprendí:

- Usar Docker Compose para meter varios servicios.
- Configurar Nginx como proxy inverso.
- Como funciona el load balancing con pesos en Nginx.
- Conectar PHP a MySQL con variables de entorno.
- Mapear dominios locales en Windows.
- Como evitar duplicar datos (POST-Redirect-GET).

