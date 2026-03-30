# 📚 Documentación Completa de API - GeoAcademico Alumnos

## 🔧 **Configuración Base para Postman**

### **Variables de Entorno**
```
base_url = http://geoacademicoalumnos.test
institution_id = 34
family_id = 11834
family_email = padre@ejemplo.com
student_id = 413
```

### **Headers Globales**
```
Accept: application/json
X-Institution-ID: {{institution_id}}
X-Family-ID: {{family_id}}
X-Family-Email: {{family_email}}
```

---

## 🌐 **Rutas Públicas (Sin Headers)**

### **1. Hello World**
```http
GET {{base_url}}/hello
```
**Response:**
```json
{
  "message": "Hello World",
  "status": "success",
  "project": "geoAcademicoAlumnos"
}
```

### **2. Listar Instituciones**
```http
GET {{base_url}}/institutions
```

### **3. Detalle de Institución**
```http
GET {{base_url}}/institutions/{{institution_id}}
```

---

## 👨‍👩‍👧‍👦 **App Familias (Requieren Headers)**

### **🏠 Dashboard Principal**

#### **Alumnos Vinculados**
```http
GET {{base_url}}/api/app-familias/linked-students
```

#### **Dashboard del Alumno**
```http
GET {{base_url}}/api/app-familias/dashboard/student/{{student_id}}
```

#### **Agenda del Alumno**
```http
GET {{base_url}}/api/app-familias/agenda/student/{{student_id}}
```

---

### **📚 Tareas y Académicos**

#### **Tareas de Intensificación**
```http
GET {{base_url}}/api/app-familias/intensification/student/{{student_id}}
```

#### **Libro de Calificaciones**
```http
GET {{base_url}}/api/app-familias/grades/student/{{student_id}}
```

#### **Inasistencias**
```http
GET {{base_url}}/api/app-familias/attendance/student/{{student_id}}
```

#### **Informes Pedagógicos**
```http
GET {{base_url}}/api/app-familias/reports/student/{{student_id}}
```

---

### **📰 Comunicaciones y Novedades**

#### **Muros de Novedades**
```http
GET {{base_url}}/api/app-familias/walls/student/{{student_id}}
```

#### **Comunicaciones (Listado)**
```http
GET {{base_url}}/api/app-familias/announcements/student/{{student_id}}
```

#### **Comunicaciones (Detalle)**
```http
GET {{base_url}}/api/app-familias/announcements/{tipo}/{code}/student/{{student_id}}
```

#### **Publicaciones (Listado)**
```http
GET {{base_url}}/api/app-familias/posts/student/{{student_id}}
```

#### **Publicaciones (Detalle)**
```http
GET {{base_url}}/api/app-familias/posts/{postId}/student/{{student_id}}
```

---

### **🔐 Autorizaciones de Retiro**

#### **Listado de Autorizaciones**
```http
GET {{base_url}}/api/app-familias/authorizations/student/{{student_id}}
```

#### **Agregar Persona Autorizada**
```http
POST {{base_url}}/api/app-familias/authorizations/person/student/{{student_id}}
Content-Type: application/json

{
  "nombre": "Juan Pérez",
  "dni": "12345678",
  "parentesco": "Tío",
  "telefono": "3511234567"
}
```

#### **Eliminar Persona Autorizada**
```http
DELETE {{base_url}}/api/app-familias/authorizations/person/{id}
```

#### **Agregar Aviso de Retiro**
```http
POST {{base_url}}/api/app-familias/authorizations/notice/student/{{student_id}}
Content-Type: application/json

{
  "fecha": "2026-03-20",
  "hora": "14:30",
  "motivo": "Consulta médica",
  "retira": "María González"
}
```

#### **Eliminar Aviso de Retiro**
```http
DELETE {{base_url}}/api/app-familias/authorizations/notice/{id}
```

---

### **📝 Mesas de Examen**
```http
GET {{base_url}}/api/app-familias/exam-boards/student/{{student_id}}
```

---

### **👤 Perfil del Alumno**

#### **Ver Perfil**
```http
GET {{base_url}}/api/app-familias/profile/student/{{student_id}}
```

#### **Actualizar Datos Personales**
```http
POST {{base_url}}/api/app-familias/profile/student/{{student_id}}/update
Content-Type: application/json

{
  "direccion": "Nueva Dirección 456",
  "telefono": "3517654321",
  "password": "nueva",
  "password_confirmation": "nueva"
}
```

#### **Actualizar Foto de Perfil**
```http
POST {{base_url}}/api/app-familias/profile/student/{{student_id}}/photo
Content-Type: multipart/form-data

file: [archivo de imagen]
```

---

### **💬 Mensajería Bidireccional**

#### **Listado de Conversaciones**
```http
GET {{base_url}}/api/app-familias/messaging/student/{{student_id}}
```

#### **Ver Chat Específico**
```http
GET {{base_url}}/api/app-familias/messaging/chat/{code}
```

#### **Destinatarios Disponibles**
```http
GET {{base_url}}/api/app-familias/messaging/recipients/student/{{student_id}}
```

#### **Enviar Mensaje**
```http
POST {{base_url}}/api/app-familias/messaging/send
Content-Type: application/json

{
  "mensaje": "Hola profesor, necesito consultar sobre la tarea",
  "id_destinatario": 89,
  "id_alumno": {{student_id}},
  "id_curso": 15,
  "id_nivel": 3,
  "codigo": "ABC123XYZ9"
}
```

---

### **📝 Auto-inscripciones a Materias Grupales**

#### **Listar Materias Disponibles**
```http
GET {{base_url}}/api/app-familias/enrollments/student/{{student_id}}
```

#### **Realizar Inscripción**
```http
POST {{base_url}}/api/app-familias/enrollments/student/{{student_id}}
Content-Type: application/json

{
  "id_materia": 45
}
```

---

## 🔄 **Rutas Legacy (Antiguas)**

### **📋 Alumnos**
```http
GET {{base_url}}/api/alumnos
GET {{base_url}}/api/alumnos/{{student_id}}
```

### **📝 Tareas**
```http
GET {{base_url}}/api/tasks
GET {{base_url}}/api/tasks/stats
GET {{base_url}}/api/tasks/{{taskId}}
GET {{base_url}}/api/tasks/student/{{student_id}}
POST {{base_url}}/api/tasks/resolution
```

### **📊 Calificaciones**
```http
GET {{base_url}}/api/grades/student/{{student_id}}
GET {{base_url}}/api/grades/student/{{student_id}}/summary
```

### **📰 Noticias**
```http
GET {{base_url}}/api/news/student/{{student_id}}
GET {{base_url}}/api/news/student/{{student_id}}/summary
POST {{base_url}}/api/news/student/{{student_id}}/{{newsId}}/read
```

### **📅 Asistencia**
```http
GET {{base_url}}/api/attendance/student/{{student_id}}/summary
GET {{base_url}}/api/attendance/student/{{student_id}}/subjects
GET {{base_url}}/api/attendance/student/{{student_id}}/subject/{{subjectId}}
```

### **📝 Inscripciones**
```http
GET {{base_url}}/api/inscripciones/disponibles
POST {{base_url}}/api/inscripciones/inscribir
POST {{base_url}}/api/inscripciones/baja
```

### **📊 Dashboard**
```http
GET {{base_url}}/api/dashboard
```

### **📄 Documentación**
```http
GET {{base_url}}/api/documentation/student/{{student_id}}
```

### **🧪 Tests Virtuales**
```http
GET {{base_url}}/api/virtual-tests/student/{{student_id}}
GET {{base_url}}/api/virtual-tests/{{testId}}/student/{{student_id}}
```

### **📋 Correlatividades**
```http
GET {{base_url}}/api/correlativities/student/{{student_id}}
```

### **📝 Sesiones de Examen**
```http
GET {{base_url}}/api/exam-sessions/student/{{student_id}}
```

### **📜 Certificados**
```http
GET {{base_url}}/api/certificates/student/{{student_id}}
GET {{base_url}}/api/certificates/student/{{student_id}}/create
POST {{base_url}}/api/certificates/student/{{student_id}}
```

### **📝 Posts**
```http
GET {{base_url}}/api/posts/student/{{student_id}}
```

### **👤 Perfil (Legacy)**
```http
GET {{base_url}}/api/profile/student/{{student_id}}
PUT {{base_url}}/api/profile/student/{{student_id}}
```

### **💌 Mensajes (Legacy)**
```http
GET {{base_url}}/api/messages/student/{{student_id}}/recipients
GET {{base_url}}/api/messages/student/{{student_id}}/chat/{{codigo}}
POST {{base_url}}/api/messages/student/{{student_id}}
POST {{base_url}}/api/messages/student/{{student_id}}/chat/{{codigo}}
```

---

## 📋 **Resumen de Colecciones Postman**

### **🏠 App Familias (Principal)**
- Dashboard y agenda
- Calificaciones y asistencia
- Comunicaciones y publicaciones
- Autorizaciones de retiro
- Perfil y mensajería
- Auto-inscripciones

### **📚 Sistema Académico (Legacy)**
- Tareas y tests virtuales
- Calificaciones y noticias
- Asistencia y documentación
- Inscripciones y correlatividades

### **🌐 Instituciones**
- Listado y detalle de colegios

---

## ⚠️ **Notas Importantes**

1. **Headers Obligatorios**: Todas las rutas multitenant requieren `X-Institution-ID`
2. **Headers Familia**: Las rutas de app-familias requieren `X-Family-ID` y `X-Family-Email`
3. **Multipart**: Para subir archivos usar `Content-Type: multipart/form-data`
4. **JSON**: Para datos usar `Content-Type: application/json`
5. **Validación**: Los errores de validación retornan código 422
6. **No encontrado**: Recursos no existentes retornan código 404

---

## 🎯 **Ejemplos de Respuestas**

### **Respuesta Exitosa Típica**
```json
{
  "success": true,
  "data": [...],
  "message": "Datos cargados correctamente"
}
```

### **Respuesta de Error Típica**
```json
{
  "success": false,
  "message": "Error al cargar los datos",
  "debug": "Error detallado para desarrollo"
}
```

### **Error de Validación**
```json
{
  "success": false,
  "errors": {
    "campo": ["El campo es obligatorio"]
  }
}
```

---

## 📊 **Códigos de Estado HTTP**

- **200**: Éxito
- **201**: Creado
- **400**: Bad Request (error de validación)
- **401**: No autorizado
- **404**: No encontrado
- **422**: Error de validación
- **500**: Error del servidor

---

## 🔗 **Referencias Rápidas**

### **Variables Comunes**
- `{{student_id}}`: ID del alumno (ej: 413)
- `{{institution_id}}`: ID de la institución (ej: 34)
- `{{family_id}}`: ID de la familia (ej: 11834)
- `{code}`: Código de conversación de chat
- `{postId}`: ID de publicación
- `{taskId}`: ID de tarea
- `{testId}`: ID de test virtual

### **Tipos de Contenido**
- `application/json`: Para datos JSON
- `multipart/form-data`: Para archivos

---


