# Pilot Academy — Guía de administración

Guía para crear cursos, gestionar el examen final y emitir certificados.

## 1. Iniciar sesión y orientarse

Abre la dirección del sitio y añade `/admin`. Inicia sesión con tu correo y contraseña de administrador.

Entrarás al **Panel**, donde ves estudiantes, progreso, cursos publicados y certificados emitidos. El menú izquierdo lleva a cada parte de la academia.

Cambia tu nombre, correo o contraseña desde el menú de cuenta, arriba a la derecha, en **Perfil**.

## 2. El menú de un vistazo

| Opción | Para qué sirve |
|---|---|
| **Panel** | Números generales, progreso por empresa y estudiantes inactivos. |
| **Contenido → Cursos** | Crear cursos, activar examen final y certificado. |
| **Contenido → Lecciones** | Añadir video, texto y preguntas. |
| **Contenido → Productos** | Productos o módulos de formación y sus responsables. |
| **Contenido → Archivos multimedia** | Biblioteca compartida de imágenes. |
| **Personas → Usuarios** | Cuentas, roles, progreso y certificados. |
| **Personas → Empresas** | Empresas asociadas y sus estudiantes. |
| **Resultados → Certificados** | Descargar, reenviar, revocar o restaurar certificados. |
| **Resultados → Calidad del examen final** | Ver si los exámenes son demasiado fáciles o difíciles. |
| **Documentación → Guía** | Esta guía. |
| **Documentación → Novedades** | Cambios recientes y notas de versión. |

Los estudiantes tienen su propia guía en **Ayuda**, el botón **?** de la barra superior.

## 3. Inicio rápido: de curso vacío a certificado

El flujo es siempre: **crear curso → añadir lecciones → activar examen final → publicar → el estudiante aprueba → recibe certificado**.

### Paso 1 · Crear un curso

En **Cursos**, pulsa **Nuevo curso**. Completa título, descripción corta, duración opcional, nivel y producto/módulo si corresponde. Los cursos nuevos empiezan como **Borrador**.

### Paso 2 · Añadir lecciones

En **Lecciones**, crea una lección, elige el curso, añade video, texto, duración, transcripción y preguntas. La lección debe tener una verificación de conocimiento para que el estudiante pueda completarla.

Ordena las lecciones desde la pestaña **Lecciones** dentro del curso. Mover una lección existente la saca de su curso anterior; para copiar material, usa **Duplicar** en el curso.

### Paso 3 · Activar el examen final

En el curso, activa **Examen final y certificado**, define la nota mínima, preguntas por intento y máximo de intentos. Después abre **Preguntas finales** y añade preguntas de las lecciones o preguntas exclusivas del examen.

### Paso 4 · Publicar el curso

En la lista de cursos, usa **Publicar**. Un curso necesita al menos una lección publicada. **Despublicar** lo oculta sin borrar contenido, progreso ni certificados.

### Paso 5 · Probar como estudiante

Abre el sitio público, completa las lecciones y verifica que el examen final se desbloquee.

### Paso 6 · El certificado

Al aprobar, el certificado se genera automáticamente como PDF con nombre, curso, fecha, número único y QR.

## 4. Cómo configurar cosas

Edita preguntas del examen final desde el curso, pestaña **Preguntas finales**. Una pregunta tomada de una lección es la misma pregunta; editarla cambia también la lección.

Sube un fondo de certificado desde **Examen final y certificado → Fondo del certificado**. Usa una imagen A4 horizontal o deja vacío para el diseño integrado.

Duplica cursos desde la fila del curso cuando quieras reutilizar una estructura. El duplicado es un borrador independiente y no copia progreso ni certificados.

Para que un responsable de producto cree formación, asígnale el rol **Creator** y marca sus productos en **Usuarios**. Solo verá cursos y lecciones de esos productos.

Para añadir una empresa y estudiante: crea la empresa en **Empresas**, luego crea el usuario y asígnale empresa y rol.

## 5. Cómo revisar resultados

Usa **Usuarios** para filtrar por rol y abrir el progreso de un estudiante.

El **Panel** muestra contenido con problemas, números generales, progreso por empresa, estudiantes inactivos, lecciones difíciles, actividad y cursos más abiertos.

En **Comentarios de estudiantes**, dentro del curso, revisa si el curso fue útil y lee comentarios privados.

En **Estudiantes inactivos**, usa **Enviar recordatorio**. Nadie recibe más de un recordatorio dentro de 7 días.

Usa **Ctrl+K** o **⌘K** para buscar cursos, lecciones y personas rápidamente.

En **Salud del examen final**, revisa la tasa de aprobación del primer intento y los días hasta el certificado.

En **Novedades**, consulta cambios por versión o descarga PDF.

En **Certificados**, filtra por curso, empresa o estado. La página pública `/certificates/{number}` verifica un certificado.

## 6. Qué hacer si…

| Situación | Qué hacer |
|---|---|
| El estudiante no recibió el e-mail | Reenvíalo desde **Certificados**. |
| Se emitió por error | Usa **Revocar**. |
| El PDF está vacío o falla | Usa **Regenerar PDF** y descarga de nuevo. |
| Necesitas un informe | Exporta CSV desde **Certificados** o **Usuarios**. |
| No quedan intentos | Aumenta o limpia el máximo de intentos del curso. |
| No encuentran un curso | Verifica que el curso esté **Publicado**. |
| Falta una lección | Verifica que la lección y el curso estén publicados. |
| Un creator no ve un curso | Revisa el **Producto / módulo** del curso. |
| Debes ocultar contenido | Usa **Despublicar**. |

## 7. Preguntas frecuentes

**¿Los certificados vencen?** No.

**¿Se puede repetir el examen final después de aprobar?** No.

**¿De dónde sale el nombre del certificado?** Del nombre escrito por el estudiante al iniciar el examen final.

**¿Qué pasa si el e-mail no está configurado?** El certificado se crea igual y puede descargarse.

**¿Puedo cambiar la nota mínima?** Sí, por curso.

**¿Cuántos certificados puede tener un estudiante por curso?** Uno válido.

**¿Puedo probar el examen sin terminar las lecciones?** Sí, los administradores pueden previsualizarlo y luego revocar el certificado de prueba.

## 8. Términos usados aquí

| Término | Significado |
|---|---|
| Examen final | Prueba del curso completo. |
| Banco de preguntas | Preguntas disponibles para el examen final. |
| Nota mínima | Porcentaje necesario para aprobar. |
| Intento | Una ejecución del examen. |
| Certificado | PDF que prueba que se aprobó el curso. |
| Revocar | Invalidar un certificado. |
| Empresa asociada | Empresa a la que pertenecen los estudiantes. |
| Admin | Gestiona toda la plataforma. |
| Creator | Responsable que crea formación para sus productos. |
| Learner | Estudiante que toma cursos. |
| Borrador | No visible para estudiantes. |
| Publicado | Visible en el sitio del estudiante. |
| Archivado | Retirado, pero conservado. |
