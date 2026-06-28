# 🌐 Guía Rápida para Compartir el Proyecto en Internet

Este proyecto incluye una herramienta automatizada para exponer tu servidor XAMPP local a internet, de modo que desarrolladores externos o clientes puedan interactuar con el sistema sin necesidad de subirlo a un hosting.

---

## 🚀 Instrucciones de Uso

### Paso 1: Encender XAMPP
Asegúrate de que los módulos **Apache** y **MySQL** estén activos (en verde) en tu XAMPP Control Panel.

### Paso 2: Ejecutar el Script
Haz doble clic sobre el archivo ejecutable:
👉 **[compartir.bat](file:///C:/xampp/htdocs/Cycsa/compartir.bat)**

### Paso 3: Iniciar Localtunnel
1. En el menú que aparece en pantalla, selecciona la **Opción 1** (Localtunnel).
2. El script detectará tu **IP Pública** automáticamente y la mostrará en la pantalla.
3. Se generará un enlace público similar a este:  
   `your url is: https://xxxx.loca.lt`

### Paso 4: Compartir el Enlace
Copia la URL generada y añade el subdirectorio de acceso al final del enlace. Por ejemplo:
🔗 **`https://xxxx.loca.lt/Cycsa/publico/`**

> [!IMPORTANT]
> **Contraseña de Acceso (Tunnel Password)**:  
> La primera vez que alguien abra el enlace desde su navegador, Localtunnel le pedirá una IP pública como medida de seguridad. Debe ingresar la **IP Pública** que te mostró el archivo `.bat` al abrirse.

---

## 🔒 Cerrar el Túnel
Cuando termines de mostrar tus cambios, simplemente **cierra la ventana de la consola** (la pantalla negra). El acceso externo se cancelará de inmediato y tu PC dejará de estar expuesta a internet.

---

## ⚙️ Métodos Alternativos Disponibles en el Menú
Si Localtunnel presenta fallas o lentitud, el archivo `compartir.bat` también incluye otras opciones de respaldo:
* **Opción 2 - Cloudflare Tunnel**: No requiere contraseñas de acceso ni registros, es 100% estable.
* **Opción 3 - SSH (Pinggy)**: No requiere instalar ningún programa en tu computadora.
* **Opción 4 - Ngrok**: Método clásico (requiere registro de cuenta gratuita).
