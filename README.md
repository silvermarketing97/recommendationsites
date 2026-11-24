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
| `FTP_DIR` | *(opcional)* `./public_html/` — si la cuenta FTP ya apunta a public_html, pon `./` |

> Nunca compartas estas contraseñas en un chat. Solo van en los secretos de GitHub.

### 4. Activar el panel de administración
1. En GitHub: foto de perfil → **Settings → Developer settings → Personal access tokens → Fine-grained tokens → Generate new token**.
2. Nombre: `admin-recommendationsites`, expiración: la más larga, **Repository access → Only select repositories →** `recommendationsites`.
3. **Permissions → Repository permissions → Contents: Read and write**.
4. Copia el token.
5. Entra a **https://recommendationsites.com/admin/** → **Sign in with Token** → pega el token.

Desde ahí puedes crear, editar o borrar artículos, subir imágenes, cambiar páginas, el link de referido, el bono y toda la configuración. Cada cambio se publica solo en 1–2 minutos.

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
