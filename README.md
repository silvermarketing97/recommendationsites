# Recommendation Sites — guía de instalación

Blog estático (Eleventy) con panel de administración en `/admin`, publicación automática diaria y despliegue automático a HostGator.

## Cómo funciona

```
Artículo nuevo (Claude cada día)  ─┐
Edición desde /admin (tú)          ├─► GitHub (silvermarketing97/recommendationsites)
Cambios de diseño (Claude)        ─┘          │
                                              ▼
                                GitHub Actions construye el sitio
                                              │  (FTP)
                                              ▼
                              HostGator → recommendationsites.com
```

Todo queda guardado en GitHub con historial: subir cambios nunca borra artículos.

## Configuración (una sola vez)

### 1. Crear el repositorio en GitHub
1. Entra a github.com con tu cuenta **silvermarketing97**.
2. Toca **New repository** → nombre: `recommendationsites` → **Private** o Public → **sin** README → **Create repository**.

### 2. Datos FTP de HostGator
En cPanel de HostGator → **Cuentas FTP**, usa tu cuenta principal o crea una nueva con directorio `public_html`.

### 3. Guardar los datos FTP en GitHub (secretos)
En el repositorio → **Settings → Secrets and variables → Actions → New repository secret**, crea:

| Nombre | Valor |
|---|---|
| `FTP_SERVER` | `ftp.recommendationsites.com` (o el servidor que muestra HostGator) |
| `FTP_USERNAME` | tu usuario FTP |
| `FTP_PASSWORD` | tu contraseña FTP |
| `FTP_DIR` | *(opcional)* por defecto `./` (cuenta FTP que abre en public_html). Si tu cuenta FTP abre en la carpeta principal, pon `./public_html/` |

> Nunca compartas estas contraseñas en un chat. Solo van en los secretos de GitHub.

### 4. Activar el panel de administración (usuario y contraseña)
Se hace **una sola vez**. Después entras a **https://recommendationsites.com/admin/** solo con tu usuario (o correo) y contraseña.

1. Crea un token de GitHub: foto de perfil → **Settings → Developer settings → Personal access tokens → Fine-grained tokens → Generate new token**. Repositorio: solo `recommendationsites`. Permiso **Contents: Read and write**. Expiración: la más larga.
2. En el repositorio → **Settings → Secrets and variables → Actions**, crea estos secretos:

| Nombre | Valor |
|---|---|
| `ADMIN_USERNAME` | el usuario que quieras para entrar |
| `ADMIN_EMAIL` | tu correo (también sirve para entrar) |
| `ADMIN_PASSWORD` | tu contraseña |
| `ADMIN_GITHUB_TOKEN` | el token del paso 1 (nunca se te volverá a pedir) |
| `ADMIN2_USERNAME`, `ADMIN2_EMAIL`, `ADMIN2_PASSWORD` | *(opcional)* un segundo usuario |

3. **Actions → Build & deploy to HostGator → Run workflow.**

Para cambiar la contraseña: edita el secreto `ADMIN_PASSWORD` y vuelve a correr el workflow.
La contraseña se guarda cifrada en tu hosting (carpeta protegida `admin/_private`), nunca en el código.
Tras 5 intentos fallidos, el acceso se bloquea 15 minutos.

## Qué puedes editar desde /admin

| Sección del panel | Qué haces ahí |
|---|---|
| **Articles** | Crear, editar, borrar artículos. Además del texto, puedes agregar **bloques arrastrables** (texto, imagen, video, botón, recuadro de Swagbucks, tip/aviso, preguntas, gadget HTML, anuncio, espacio) y reordenarlos arrastrando ☰ |
| **🏠 Home page** | La página de inicio completa por secciones: arrástralas para cambiar el orden, agrega o quita secciones |
| **📢 Ads & HTML widgets** | Espacios de publicidad: banner bajo el menú, arriba del artículo, **dentro del artículo** (automático cada N secciones), final del artículo, barra lateral, páginas de listado y antes del pie. Formatos horizontal, rectangular, cuadrado y vertical. También código en `<head>`, `ads.txt` y gadgets HTML ilimitados |
| **🎨 Design & menu** | Colores, tipografías, bordes, barra superior, botón fijo en celular y el menú (arrastrable) |
| **Pages** | About, Contact, privacidad, términos, aviso de afiliados |
| **Site settings** | Link de referido, monto del bono, Google Analytics, Search Console |

### Ejemplo: activar Google AdSense
1. Pega el script de AdSense en **Ads & HTML widgets → Code inside head**.
2. Pega la línea que te da AdSense en **ads.txt content**.
3. En cada espacio (por ejemplo *Inside articles*), marca **Enabled** y pega el código del bloque de anuncio.

## Archivos importantes

| Archivo | Qué controla |
|---|---|
| `src/_data/site.json` | Nombre, link de referido de Swagbucks, monto del bono, Google Analytics, Search Console |
| `src/posts/*.md` | Artículos |
| `src/pages/*.md` | Páginas (About, Contact, privacidad, etc.) |
| `src/assets/css/style.css` | Colores, tipografías y diseño (variables al inicio) |
| `AUTOMATION.md` | Reglas del escritor automático (cantidad diaria, temas, estilo) |
| `content-plan.md` | Lista de próximos temas |

## Después de publicar

1. Registra el sitio en **Google Search Console** y envía `https://recommendationsites.com/sitemap.xml`.
2. Pega el código de verificación en *Site settings* del panel (`googleSiteVerification`).
3. Haz lo mismo en **Bing Webmaster Tools** (cubre Bing, Yahoo y DuckDuckGo).
