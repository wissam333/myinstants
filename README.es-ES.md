

<p align="center"><img src="https://www.myinstants.com/media/apple-touch-icon-114x114.png" alt="MyInstants"></p>
<h1 align="center">MyInstants REST API</h1>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white" alt="Versión de PHP">
  <img src="https://img.shields.io/badge/Vercel-Deployed-000000?logo=vercel&logoColor=white" alt="Desplegado en Vercel">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="Licencia MIT">
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs Bienvenidas">
</p>

<p align="center">Una API RESTful para extraer y obtener datos de sonidos desde el sitio web <a href="https://www.myinstants.com" target="_blank">MyInstants</a>. Esta API proporciona endpoints para recuperar información sobre sonidos, incluyendo títulos, URLs, descripciones, etiquetas, favoritos, vistas y detalles del subidor.</p>

## ✨ Características

- ⚡ **Ultra Rápida**: Impulsada por Vercel Edge Caching (`s-maxage=3600`) para tiempos de respuesta de ~0ms en solicitudes con caché.
- 🚀 **Listo para Serverless**: Despliegue nativo en Vercel sin ajustes. Utiliza funciones serverless separadas para máxima eficiencia.
- 🌐 **CORS Habilitado**: Listo para ser consumido directamente desde aplicaciones web frontend (React, Vue, etc.) sin problemas de origen cruzado.
- 🎯 **Manejo de Errores Confiable**: Devuelve códigos de estado HTTP adecuados (por ejemplo, 404, 400) en lugar de solo 200 OK.

## Tabla de Contenidos

- [Características](#-features)
- [Primeros Pasos](#-getting-started)
  - [Requisitos](#requirements)
  - [Instalación](#installation)
- [Referencia](#%EF%B8%8F-reference)
  - [Endpoints](#endpoints)
  - [Parámetros de Solicitud](#request-parameters)
  - [Ejemplo de Respuesta](#response-example)
- [Manejo de Errores](#-error-handling)
- [Ejemplos](#-examples)
- [Contribuir](#-contributing)
- [Soporte](#-support)
- [Créditos](#-créditos)
- [Licencia y Descargo de Responsabilidad](#%EF%B8%8F-license)

## 🚀 Primeros Pasos

### Requisitos

- PHP 7.4 o superior
- Biblioteca [simple_html_dom.php](https://simplehtmldom.sourceforge.io/) para análisis HTML
- Extensión `curl` habilitada en `php.ini`

### Instalación

1. Clona el repositorio en tu servidor:

    ```bash
    git clone https://github.com/wissam333/myinstants.git
    cd myinstants
    ```

2. Descarga y coloca `simple_html_dom.php` en el directorio `lib/`.

3. **Desarrollo Local (No se requiere Apache/Nginx)**:
   Puedes ejecutar la API localmente utilizando el servidor web integrado de PHP. Este proyecto incluye un archivo `router.php` que simula perfectamente el entorno de enrutamiento serverless de Vercel, permitiéndote acceder a los endpoints sin la extensión `.php`.

   ```bash
   php -S localhost:8000 router.php
   ```

   Ahora puedes acceder a la API localmente (por ejemplo, `http://localhost:8000/best?q=id`).

4. **Desplegar en Vercel**:
   Desplegar es sencillo. Haz clic en el botón de abajo para desplegar este repositorio directamente en tu cuenta de Vercel.<br>
    [![Desplegar con Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fwissam333%2Fmyinstants%2F&redirect-url=https%3A%2F%2Fgithub.com%2Fwissam333%2Fmyinstants%2F)

## ❇️ Referencia

### Endpoints

URL Base: https://myinstants-api.vercel.app

| Petición          | Respuesta                 | Parámetro  |
| :--------------- | :----------------------- | :--------: |
| `GET /trending`  | Tendencias por región    | `q`, `page` |
| `GET /search`    | Buscar un sonido         | `q`, `page` |
| `GET /detail`    | Detalles del sonido      |    `id`    |
| `GET /recent`    | Sonidos subidos recientemente |   `page`   |
| `GET /best`      | Mejores sonidos de todos los tiempos | `q`, `page` |
| `GET /uploaded`  | Sonidos subidos por el usuario | `username`, `page` |
| `GET /favorites` | Sonidos favoritos del usuario | `username`, `page` |
| `GET /category`  | Sonidos por categoría | `category`, `q`, `page` |
| `GET /memesoundboard` | Sonidos de MemeSoundboard.io | `q`, `page` |
| `GET /101soundboards` | Sonidos de 101Soundboards.com | `q`, `page` |
| `GET /freesound` | Sonidos CC de Freesound.org (requiere API key) | `q`, `page` |
| `GET /search_all` | Búsqueda combinada en todas las fuentes | `q`, `page` |

_Todos los endpoints de lista (`/trending`, `/search`, `/recent`, `/best`, `/uploaded`, `/favorites`, `/category`, `/memesoundboard`, `/101soundboards`, `/freesound`, `/search_all`) admiten paginación con `?page=N` (por defecto `1`) y filtrado por duración con `?with_duration=1&min_duration=2&max_duration=6`._

_Cada elemento lleva un campo `source` (`myinstants`, `memesoundboard`, `101soundboards`, `freesound`). `/search_all` intercala todas las fuentes configuradas (round-robin) e informa el estado por fuente en `sources`. Los elementos de 101Soundboards/Freesound incluyen `thumbnail` y `duration` gratuita (sin análisis necesario); los de Freesound además traen su `license` (CC0/BY/BY-NC — atribuye a los autores y excluye NonCommercial en apps comerciales)._

> **Configurar Freesound:** clave gratuita sin espera — regístrate en https://freesound.org, solicítala en https://freesound.org/apiv2/apply y define `FREESOUND_API_KEY` (Vercel Dashboard → Settings → Environment Variables). Sin ella, `/freesound` devuelve `503` y `/search_all` omite esa fuente. El filtrado por duración se traduce a la consulta propia `duration:[min TO max]`, con `?page`/`page_size=30` y totales en `count`._

### Parámetros de Solicitud

|   Parámetro    | Descripción                                              |
| :------------: | :------------------------------------------------------- |
|      `q`       | Consulta de búsqueda o región                            |
|   `username`   | Nombre de usuario                                        |
|      `id`      | ID Único del sonido                                      |
|     `page`     | Número de página (>= 1, por defecto `1`). Ejemplo: `?page=4` |
|   `category`   | Nombre de categoría (obligatorio para `/category`, insensible a mayúsculas). Una de: `anime & manga`, `games`, `memes`, `movies`, `music`, `politics`, `pranks`, `reactions`, `sound effects`, `sports`, `television`, `tiktok trends`, `viral`, `whatsapp audios` |
| `with_duration`| `1` para incluir la `duration` (segundos) del MP3        |
| `min_duration` | Solo sonidos de >= N segundos (implica `with_duration`)  |
| `max_duration` | Solo sonidos de <= N segundos (implica `with_duration`)  |

_Nota: `myinstants.com` no expone duraciones en su HTML, por lo que `with_duration` / `min_duration` / `max_duration` analizan cada MP3 (cabeceras de tramas MPEG). Las listas son rápidas por defecto; solo son más lentas (descarga en paralelo, mejor esfuerzo, `duration: null` si no se detecta) cuando se usan estos parámetros._

### Ejemplo de Respuesta

Una respuesta exitosa típica (HTTP 200) devolverá un objeto JSON como este:

```json
{
  "status": 200,
  "author": "wissam333",
  "page": 4,
  "count": 30,
  "total_pages": 50,
  "has_next": true,
  "data": [
    {
      "id": "vine-boom-sound-70972",
      "title": "VINE BOOM SOUND",
      "url": "https://www.myinstants.com/en/instant/vine-boom-sound-70972/",
      "mp3": "https://www.myinstants.com/media/sounds/vine-boom.mp3",
      "duration": 1.42
    }
  ]
}
```

_`page` / `count` / `total_pages` / `has_next` los devuelven todos los endpoints de lista (`total_pages` se extrae del título original `Page X of Y`, `null` si no se detecta). `duration` (segundos) solo aparece con `with_duration=1` o `min_duration` / `max_duration`. `/category` además devuelve `category` y `region` (`null` en el listado global)._

_`source` es `"live"` normalmente, `"proxy"` si se sirvió vía tu proxy de scraping, o `"archive"` cuando `myinstants.com` bloqueó la petición y los datos vienen de la última instantánea de Wayback Machine (pueden ser más antiguos; los enlaces `mp3` siguen apuntando al sitio en vivo, y estas respuestas se cachean 24h). Si ninguna fuente funciona, la API devuelve HTTP `502` — reintenta más tarde._

> **Nota anti-bots:** `myinstants.com` usa protección Cloudflare que a veces bloquea IPs de centros de datos (Vercel) con HTTP 403. Solo con cabeceras de navegador no se puede pasar, y los proxies gratuitos simples (corsproxy.io, allorigins, codetabs…) tampoco — reciben la misma página de desafío. Para un scraping fiable, define la variable de entorno `UPSTREAM_PROXY_TEMPLATES` (Vercel Dashboard → Settings → Environment Variables) con una lista **separada por comas** de APIs de scraping que usen navegadores reales, usando `{url}` como marcador. Se prueban en orden hasta que una devuelva HTML válido, así puedes encadenar niveles gratuitos (p. ej. ScraperAPI ~5.000 créditos la primera semana + ~1.000/mes, ScrapingBee ~1.000 créditos, ZenRows ~1.000 básicos + 40 protegidos):
>
> ```
> UPSTREAM_PROXY_TEMPLATES=https://api.scraperapi.com?api_key=KEY1&url={url},https://app.scrapingbee.com/api/v1/?api_key=KEY2&url={url},https://api.zenrows.com/v1/?apikey=KEY3&url={url}
> ```
>
> (La antigua `UPSTREAM_PROXY_TEMPLATE` singular sigue funcionando para una sola entrada.)
>
> Sin proxies, la API recurre a instantáneas de Wayback cuando la bloquean.
>
> **Caché de un mes:** los TTL de caché edge se configuran por entorno (segundos; `2592000` ≈ 30 días). Los aciertos de caché no gastan créditos del proxy, así que una sola petición con proxy puede servir una URL durante un mes:
>
> ```
> CACHE_SMAXAGE_LIVE=2592000
> CACHE_SMAXAGE_PROXY=2592000
> CACHE_SMAXAGE_ARCHIVE=2592000
> ```
>
> (Valores por defecto: live/proxy `3600`, archive `86400`. Nota: la caché edge de Vercel es best-effort — puede expulsar entradas bajo presión y cada redeploy la purga, provocando una nueva ronda de peticiones. La clave de caché incluye toda la query string, así que cada combinación `page`/filtro se cachea por separado.)

_Nota: Para el endpoint `/detail`, el objeto `data` contendrá campos adicionales como `description`, `tags`, `favorites`, `views` y `uploader` (más `duration` con `?with_duration=1`)._

## 💥 Manejo de Errores

Todos los errores devuelven objetos JSON con un código de estado HTTP apropiado (por ejemplo, 404, 400) y un `message` que explica el problema.

- **Error 404**:
  - Cuando no se encuentra la página o se accede a un endpoint inválido.
  ```json
  {
    "status": 404,
    "author": "wissam333",
    "message": "Endpoint not found"
  }
  ```

- **Error 502**:
  - Cuando `myinstants.com` rechaza la petición (protección anti-bots HTTP 403/429). Reintenta más tarde — las respuestas en caché siguen funcionando.
  ```json
  {
    "status": 502,
    "author": "wissam333",
    "message": "Upstream myinstants.com refused this request (HTTP 403, anti-bot protection). Please retry later."
  }
  ```

## 🌐 Ejemplos

### Ejemplo 1: Obtener sonidos en tendencia por región

```http
GET https://myinstants-api.vercel.app/trending?q=id
```

### Ejemplo 2: Buscar sonidos por consulta

```http
GET https://myinstants-api.vercel.app/search?q=laugh
```

### Ejemplo 3: Obtener detalles de un sonido por ID

```http
GET https://myinstants-api.vercel.app/detail?id=akh-26815
```

### Ejemplo 4: Obtener sonidos subidos recientemente

```http
GET https://myinstants-api.vercel.app/recent
```

### Ejemplo 5: Obtener los mejores sonidos de todos los tiempos

Obtén una lista de los sonidos más populares de todos los tiempos basada en una región especificada:

```http
GET https://myinstants-api.vercel.app/best?q=id
```

### Ejemplo 6: Obtener sonidos subidos por un usuario

```http
GET https://myinstants-api.vercel.app/uploaded?username=hellmouz
```

### Ejemplo 7: Obtener sonidos favoritos de un usuario

```http
GET https://myinstants-api.vercel.app/favorites?username=hellmouz
```

### Ejemplo 8: Paginar cualquier lista (p. ej. tendencias Siria, página 4)

Refleja https://www.myinstants.com/en/index/sy/?page=4 — la página 1 es el valor por defecto:

```http
GET https://myinstants-api.vercel.app/trending?q=sy&page=4
GET https://myinstants-api.vercel.app/search?q=laugh&page=2
GET https://myinstants-api.vercel.app/recent?page=2
```

### Ejemplo 9: Solo sonidos de 2–6 segundos

```http
GET https://myinstants-api.vercel.app/search?q=laugh&min_duration=2&max_duration=6
GET https://myinstants-api.vercel.app/trending?q=sy&page=4&min_duration=2&max_duration=6
GET https://myinstants-api.vercel.app/search?q=laugh&with_duration=1
```

### Ejemplo 10: Sonidos por categoría (con región opcional y paginación)

Refleja https://www.myinstants.com/en/categories/anime%20&%20manga/sy/?page=9 — `q` (región) es opcional; sin él obtienes el listado global:

```http
GET https://myinstants-api.vercel.app/category?category=music
GET https://myinstants-api.vercel.app/category?category=anime%20%26%20manga&q=sy&page=9
GET https://myinstants-api.vercel.app/category?category=memes&q=sy&page=2&min_duration=2&max_duration=6
```

### Ejemplo 11: Buscar en otros sitios de sonidos (combinado o por fuente)

```http
GET https://myinstants-api.vercel.app/search_all?q=bruh&page=1
GET https://myinstants-api.vercel.app/memesoundboard?q=bruh&page=2
GET https://myinstants-api.vercel.app/101soundboards?q=bruh&page=2&min_duration=2&max_duration=6
GET https://myinstants-api.vercel.app/freesound?q=airhorn&min_duration=2&max_duration=6
```

## 🌱 Contribuir

¡Las contribuciones son bienvenidas! Para contribuir:

1. Haz un fork del repositorio.
2. Crea una rama de características: `git checkout -b feature-name`.
3. Confirma tus cambios: `git commit -m 'Agregar característica'`.
4. Publica en la rama: `git push origin feature-name`.
5. Envía una pull request.

## ✨ Soporte

Si te gusta este proyecto, por favor dale una estrella en este repositorio, gracias ⭐

## 🙏 Créditos

- Proyecto original por [abdipr](https://github.com/abdipr/myinstants-api).
- Actualizado y mantenido por [wissam333](https://github.com/wissam333/myinstants).

## ⚖️ Licencia

Este proyecto está licenciado bajo la `MIT License`. Consulta el archivo [LICENSE](https://github.com/wissam333/myinstants/blob/main/LICENSE) para más información.

## ⚠️ Descargo de Responsabilidad

Los sonidos contenidos en esta API se obtienen del sitio web original [MyInstants](https://www.myinstants.com) mediante web scraping. Los desarrolladores que utilicen esta API deben cumplir con las normativas aplicables mencionando este proyecto o al propietario oficial en sus proyectos, y está prohibido abusar de esta API para beneficios personales.

[⬆️ Volver al Inicio](#myinstants-rest-api)
