# GESTOR DE COBRANZAS — DOCUMENTO MAESTRO DEL PROYECTO

## 1. Objetivo del proyecto

Construir una aplicación web sencilla, moderna y responsive para asistir en la gestión diaria de cobranzas de una financiera.

La aplicación NO reemplazará el sistema oficial de la financiera ni modificará sus datos.

El sistema oficial de la empresa seguirá siendo la fuente de información principal.

La nueva aplicación será una herramienta complementaria y personal para:

- Organizar la cartera mensual.
- Priorizar clientes.
- Registrar gestiones.
- Registrar promesas de pago.
- Hacer seguimiento de promesas.
- Registrar pagos.
- Diferenciar importe original, punitorios y monto efectivamente cobrado.
- Registrar punitorios perdonados.
- Gestionar visitas de cobrador.
- Detectar clientes sin respuesta.
- Generar una agenda diaria de trabajo.
- Mostrar estadísticas y porcentaje de cobranza.
- Facilitar el uso de mensajes de cobranza.
- Mantener un historial de las gestiones realizadas.

La aplicación debe estar diseñada para poder crecer progresivamente sin necesidad de rehacer la arquitectura.

---

# 2. Stack tecnológico

Utilizar:

### Backend
- PHP 8.3+
- Laravel
- Eloquent ORM
- MySQL

### Frontend
- Blade
- Livewire
- Tailwind CSS
- Alpine.js únicamente cuando sea necesario para interacciones pequeñas.

### Importación de archivos
- CSV
- XLSX
- PhpSpreadsheet para archivos Excel.

### Desarrollo
- Git
- GitHub
- Desarrollo incremental mediante commits pequeños y claros.
- Tests automatizados para las reglas de negocio importantes.

No utilizar React, Vue o un frontend SPA separado salvo que exista una razón técnica concreta en una etapa posterior.

La primera versión debe mantenerse simple y monolítica dentro de Laravel.

---

# 3. Forma de trabajo

El proyecto se construirá progresivamente mediante prompts independientes.

IMPORTANTE:

NO implementar toda la aplicación de una sola vez.

Cada etapa deberá:

1. Analizar el estado actual del repositorio.
2. Implementar únicamente la funcionalidad solicitada.
3. Mantener compatibilidad con lo construido anteriormente.
4. Ejecutar tests.
5. Corregir errores.
6. Evitar modificaciones innecesarias.
7. Crear un commit descriptivo.

No modificar funcionalidades existentes sin una razón concreta.

Antes de cada nueva etapa, revisar la arquitectura existente.

---

# 4. Concepto fundamental de la cartera

La financiera genera una planilla de vencimientos.

El listado inicial se obtiene el día 1 del mes y contiene todos los vencimientos correspondientes a ese mes.

Ejemplo:

El 1 de agosto la planilla puede contener:

- Cliente A — vence 01/08
- Cliente B — vence 05/08
- Cliente C — vence 10/08
- Cliente D — vence 20/08

La aplicación importa esa cartera inicial.

NO es necesario volver a importar el listado todos los días.

La aplicación será utilizada diariamente para gestionar esa cartera.

---

# 5. Importaciones posteriores

Puede ser necesario volver a exportar e importar el listado oficial posteriormente.

Esto ocurrirá principalmente cuando sea necesario:

- Actualizar punitorios.
- Actualizar importes.
- Comprobar qué operaciones ya no aparecen porque fueron pagadas.
- Detectar cambios provenientes del sistema oficial.

Por lo tanto, la aplicación debe soportar:

### Importación inicial

Crear la cartera mensual.

### Importaciones posteriores

Sincronizar la información proveniente del sistema oficial.

NO duplicar operaciones.

---

# 6. Regla fundamental de sincronización

La aplicación debe entender que el listado oficial representa la cartera pendiente actual.

Ejemplo:

Importación inicial:

- Juan
- María
- Pedro
- Laura

Posteriormente Juan paga.

El siguiente archivo oficial contiene:

- María
- Pedro
- Laura

La aplicación debe interpretar que Juan ya no forma parte de la cartera pendiente.

Sin embargo, Juan NO debe ser eliminado de la base de datos.

Debe conservarse su historial.

Por ejemplo:

Juan Pérez
- Cuota: $100.000
- Fecha de pago: 02/08
- Estado: Pagada
- Historial de gestiones: disponible

La cartera activa disminuye, pero el historial permanece.

---

# 7. Identificación de una operación

No crear registros duplicados cuando se importen nuevamente los archivos.

La identificación de una operación debe basarse principalmente en:

- Número de solicitud.
- Número de cuota.

Estos valores deberán utilizarse como identificador lógico de la operación.

El sistema debe poder reconocer:

"Solicitud 12345 + cuota 3"

como la misma operación aunque aparezca en diferentes importaciones.

---

# 8. Datos provenientes del sistema oficial

La planilla de la financiera contiene muchos datos.

Los más importantes inicialmente son:

- Día de cobro.
- Número de solicitud.
- Nombre y apellido.
- Número de cuota.
- Importe.
- Punitorios.
- Cuenta.

El sistema debe permitir mapear estas columnas durante la importación.

No asumir que los nombres exactos de las columnas serán siempre idénticos.

Diseñar el importador de forma que posteriormente pueda adaptarse al formato real de la planilla.

---

# 9. Datos propios de la aplicación

La aplicación debe poder almacenar información que NO proviene del sistema oficial.

Por ejemplo:

- Teléfono.
- Domicilio.
- Fecha de último contacto.
- Cantidad de intentos de contacto.
- Resultado de cada gestión.
- Fecha de promesa.
- Hora de promesa, si corresponde.
- Estado de promesa.
- Próxima acción.
- Observaciones.
- Solicitud de cobrador.
- Cobrador asignado.
- Resultado de visita.
- Fecha de pago registrada.
- Medio de pago.
- Monto efectivamente cobrado.
- Punitorios perdonados.

Estos datos no deben perderse durante una nueva importación.

---

# 10. Importe y punitorios

Es fundamental separar estos conceptos.

Una operación puede tener:

### Importe original
Ejemplo:
$100.000

### Punitorios actuales
Ejemplo:
$2.000

### Total actualizado
$102.000

Pero la empresa puede permitir que el cobrador/empleado perdone los punitorios.

Ejemplo:

El cliente debe oficialmente:

$100.000 + $2.000 = $102.000

Pero se decide cobrar:

$100.000

La aplicación debe registrar:

- Importe original: $100.000
- Punitorios: $2.000
- Total actualizado: $102.000
- Monto efectivamente cobrado: $100.000
- Punitorios perdonados: $2.000

Por lo tanto:

IMPORTANTE:

"Total actualizado" NO significa necesariamente "monto cobrado".

---

# 11. Registro de pagos

Cuando se registre un pago, guardar:

- Operación.
- Fecha de pago.
- Importe original.
- Punitorios existentes.
- Total actualizado.
- Monto efectivamente cobrado.
- Punitorios perdonados.
- Medio de pago.
- Observación.
- Usuario/empleado que registró la operación, si posteriormente se implementa autenticación multiusuario.

Medios de pago iniciales:

- Efectivo.
- Transferencia.

La estructura debe permitir agregar otros medios posteriormente.

---

# 12. Estados de una operación

Diseñar un sistema de estados claro.

Estados posibles iniciales:

- Próximo vencimiento.
- Vence hoy.
- Vencida.
- Promesa de pago.
- Promesa incumplida.
- Sin respuesta.
- Pago realizado.
- Gestión domiciliaria pendiente.
- Visita realizada.
- No localizada.
- Cancelada/archivada, si corresponde.

No hacer depender toda la lógica de un único campo "estado".

Cuando sea necesario, utilizar eventos/historiales de gestión.

---

# 13. Sistema de gestiones

Cada contacto debe poder registrarse como una gestión.

Ejemplos:

- WhatsApp enviado.
- WhatsApp respondido.
- Llamada realizada.
- No atendió.
- Cliente respondió.
- Cliente prometió pagar.
- Cliente solicitó link de pago.
- Cliente solicitó cobrador.
- Cobrador asignado.
- Visita realizada.
- Pago recibido.
- Promesa incumplida.
- Cliente sin respuesta.

Cada gestión debería guardar:

- Fecha.
- Hora.
- Tipo de gestión.
- Resultado.
- Observación.
- Próxima acción, si corresponde.

Debe existir un historial cronológico.

---

# 14. Promesas de pago

Esta es una funcionalidad fundamental.

Ejemplo:

Cliente:

Juan Pérez

Día de cobro:

01/08

La cuota vence el 01/08.

El empleado contacta al cliente.

El cliente responde:

"El 10 puedo pasar a pagar."

Registrar:

Fecha de promesa:
10/08

Estado:

Pendiente de verificación.

La aplicación debe generar automáticamente una tarea/alerta para el día 10.

---

# 15. Agenda de promesas

El dashboard debe mostrar:

### PROMESAS PARA HOY

Ejemplo:

- Juan Pérez — $100.000 — Prometió pagar hoy.
- María López — $80.000 — Prometió pagar hoy.
- Pedro Gómez — $120.000 — Prometió pagar hoy.

Acciones:

### Si pagó

Marcar:

"PAGO REALIZADO"

### Si no pagó

Marcar:

"PROMESA INCUMPLIDA"

Al marcar promesa incumplida, aumentar automáticamente la prioridad de gestión.

---

# 16. Próxima acción

Cada cliente/operación puede tener una próxima acción.

Ejemplos:

- Contactar hoy.
- Verificar pago mañana.
- Enviar link.
- Esperar promesa.
- Ofrecer cobrador.
- Asignar cobrador.
- Volver a llamar.
- Revisar punitorios.
- Registrar pago.

La aplicación debe poder mostrar las próximas acciones del día.

---

# 17. Clientes sin respuesta

Existe un caso especial:

El cliente no responde.

Puede ocurrir que:

- No responda WhatsApp.
- No atienda llamadas.
- No devuelva mensajes.
- No confirme fecha de pago.

Después de una cantidad configurable de intentos, el estado puede pasar a:

### SIN RESPUESTA

Estos clientes deben recibir una prioridad elevada.

La aplicación debe sugerir:

### Priorizar visita de cobrador.

IMPORTANTE:

No asumir que el cliente no quiere pagar.

Simplemente registrar que no fue posible establecer contacto.

---

# 18. Cobrador a domicilio

El cobrador NO debe estar asociado únicamente a clientes morosos.

Puede utilizarse como una alternativa de comodidad.

Ejemplo:

Cliente al día:

"No puedo acercarme a la oficina."

Respuesta:

"Podemos coordinar para que nuestro cobrador pase por su domicilio."

La aplicación debe permitir crear una solicitud de cobranza domiciliaria aunque la cuota esté al día.

---

# 19. Cobrador como escalamiento de cobranza

En clientes con mayor riesgo:

- Sin respuesta.
- Promesa incumplida.
- Mora prolongada.
- Múltiples intentos.
- Falta de contacto.

La aplicación puede recomendar:

### "Priorizar visita del cobrador."

Debe poder registrarse:

- Cliente.
- Operación.
- Domicilio.
- Importe.
- Fecha de visita.
- Cobrador asignado.
- Resultado.
- Importe cobrado.
- Observaciones.

Resultados posibles:

- Cobrado.
- Cobrado parcialmente.
- No estaba.
- No se pudo contactar.
- Reprogramar.
- Se negó a pagar.
- Domicilio incorrecto.
- Otro.

---

# 20. Sistema de prioridades

La aplicación debe ordenar automáticamente la cartera.

Prioridad crítica:

- Cliente sin respuesta.
- Promesa incumplida.
- Mora prolongada.
- Múltiples intentos sin resultado.

Prioridad alta:

- Cuota vencida.
- Cliente contactado pero sin fecha concreta.
- Cliente solicitó cobrador.
- Promesa para hoy.

Prioridad media:

- Próximo vencimiento.
- Cliente que normalmente paga con demora.

Prioridad baja:

- Cliente al día.
- Cliente que ya confirmó cómo y cuándo pagará.

La lógica debe ser configurable posteriormente.

---

# 21. Día de cobro

El "día de cobro" es importante.

Representa el día en que el cliente cobra su sueldo y fue registrado al momento de otorgar el crédito.

Ejemplo:

Día de cobro: 1

Esto NO significa necesariamente que la cuota se pagará exactamente ese día, pero sirve como información para priorizar las gestiones.

El dashboard puede mostrar:

"Clientes cuyo día de cobro es hoy."

Esto ayuda a planificar la jornada de cobranza.

---

# 22. Dashboard principal

Al abrir la aplicación debe existir un dashboard sencillo.

Mostrar como mínimo:

### Objetivo mensual

Ejemplo:

$10.000.000

### Cobrado

$8.000.000

### Pendiente

$2.000.000

### Porcentaje de cobranza

80%

Además:

- Vencen hoy.
- Vencidos.
- Promesas para hoy.
- Promesas incumplidas.
- Clientes sin respuesta.
- Visitas de cobrador pendientes.
- Gestiones pendientes.

---

# 23. Cartera restante

La aplicación debe mostrar claramente:

Cartera inicial:
$10.000.000

Cobrado:
$7.800.000

Pendiente:
$2.200.000

Porcentaje:
78%

La cartera pendiente debe disminuir a medida que las operaciones se pagan.

No acumular importaciones como si fueran nuevas deudas.

---

# 24. Historial mensual

Cada mes debe poder conservarse.

Ejemplo:

### Agosto 2026

- Cartera inicial.
- Total cobrado.
- Total pendiente.
- Porcentaje de cobranza.
- Total de punitorios perdonados.
- Cantidad de operaciones cobradas.
- Cantidad de promesas.
- Promesas cumplidas.
- Promesas incumplidas.
- Visitas de cobrador.
- Cobros realizados mediante cobrador.

Cuando comience septiembre, se genera una nueva cartera mensual sin perder agosto.

---

# 25. Mensajes de cobranza

La aplicación debe contemplar plantillas.

Categorías:

### Próximo vencimiento

Mensaje cordial para recordar la próxima fecha.

### Vence hoy

Recordatorio del vencimiento.

### Cuota vencida

Informar que la cuota está pendiente y que pueden generarse recargos.

### Link de pago

Ofrecer enviar el enlace de pago.

### Promesa de pago

Confirmar la fecha prometida.

### Seguimiento de promesa

Recordar el compromiso.

### Cobrador

Informar que existe la posibilidad de realizar el cobro a domicilio.

### Sin respuesta

Mensaje final de contacto antes de escalar la gestión.

Los mensajes deben permitir variables:

- Nombre.
- Importe.
- Fecha.
- Número de cuota.
- Link de pago.

No implementar envío automático de mensajes sin confirmación del usuario en la primera versión.

---

# 26. Ficha del cliente

Cada cliente debe tener una ficha clara.

Ejemplo conceptual:

Juan Pérez

Teléfono:
3755XXXXXX

Domicilio:
[domicilio]

Solicitud:
12345

Cuota:
3

Día de cobro:
10

Importe:
$100.000

Punitorios:
$2.000

Total actualizado:
$102.000

Estado:
Vencida

---

### Historial

01/08 — WhatsApp enviado
02/08 — Cliente respondió
02/08 — Promesa de pago para 10/08
10/08 — Promesa incumplida
11/08 — No respondió
12/08 — Visita de cobrador recomendada

---

### Acciones

- WhatsApp
- Registrar gestión
- Registrar promesa
- Enviar link
- Ofrecer cobrador
- Asignar cobrador
- Registrar pago

---

# 27. Importador

Crear un módulo:

### IMPORTAR CARTERA

Debe permitir:

- Seleccionar CSV/XLSX.
- Previsualizar datos.
- Validar columnas.
- Mostrar errores.
- Confirmar importación.

Después de importar mostrar:

- Registros procesados.
- Operaciones nuevas.
- Operaciones actualizadas.
- Operaciones que ya no aparecen.
- Errores.

Nunca eliminar físicamente información histórica solamente porque una operación dejó de aparecer en el archivo.

---

# 28. Historial de importaciones

Crear una entidad para registrar cada importación.

Guardar:

- Fecha.
- Hora.
- Archivo.
- Usuario, si corresponde.
- Cantidad de registros.
- Nuevos.
- Actualizados.
- Ausentes.
- Errores.

Esto permitirá auditar qué ocurrió durante cada sincronización.

---

# 29. Regla de actualización

Una importación posterior NO debe sobrescribir datos de gestión propios.

Por ejemplo:

La aplicación tiene:

Promesa:
10/08

Observación:
"Cliente dijo que cobra el viernes."

Se importa un nuevo archivo.

El sistema puede actualizar:

- Punitorios.
- Importe.
- Situación oficial.

Pero NO debe eliminar:

- Promesa.
- Observación.
- Historial.
- Gestiones.
- Próxima acción.

---

# 30. Seguridad y privacidad

La aplicación manejará información potencialmente sensible de clientes.

Por eso:

- No almacenar datos innecesarios.
- Utilizar autenticación.
- Proteger rutas.
- Validar inputs.
- Utilizar CSRF.
- No almacenar contraseñas en texto plano.
- No exponer información mediante endpoints públicos.
- Utilizar variables de entorno para credenciales.
- No subir archivos reales de clientes al repositorio GitHub.
- Agregar archivos reales de la financiera al `.gitignore`.
- No utilizar datos reales en fixtures/tests.
- No enviar datos de clientes a servicios externos sin autorización.

Antes de utilizar datos reales, confirmar que la empresa permite utilizar esta herramienta.

---

# 31. GitHub

El repositorio debe contener únicamente:

- Código.
- Migraciones.
- Seeders sin datos reales.
- Tests.
- Configuración de desarrollo.
- Documentación.

NO subir:

- Planillas reales.
- Datos personales reales.
- Contraseñas.
- Tokens.
- API keys.
- Archivos `.env`.

Crear `.env.example`.

---

# 32. Arquitectura de base de datos

La arquitectura debe separar claramente:

### Clientes

Información de la persona.

### Operaciones

Solicitud/crédito.

### Cuotas

Cada cuota asociada a una operación.

### Importaciones

Cada archivo importado.

### Gestiones

Historial de contactos.

### Promesas

Compromisos de pago.

### Pagos

Pagos efectivamente realizados.

### Visitas de cobrador

Gestiones domiciliarias.

### Mensajes

Plantillas de comunicación.

### Usuarios

Preparar la aplicación para autenticación.

No mezclar todos estos conceptos en una única tabla.

---

# 33. Relaciones conceptuales

Un cliente puede tener:

- Una o varias operaciones.

Una operación puede tener:

- Varias cuotas.

Una cuota puede tener:

- Muchas gestiones.
- Una o varias promesas a lo largo del tiempo.
- Uno o varios intentos de cobranza.
- Un pago final.
- Una o varias visitas de cobrador.

Una importación contiene:

- Muchas operaciones/cuotas.

El historial debe mantenerse aunque una operación deje de aparecer en la cartera activa.

---

# 34. Reglas importantes

1. No duplicar operaciones.
2. No eliminar historial porque una operación ya no aparece.
3. No perder promesas durante una importación.
4. No perder gestiones durante una importación.
5. Separar importe original de punitorios.
6. Separar total actualizado de monto efectivamente cobrado.
7. Registrar punitorios perdonados.
8. La cartera activa debe disminuir cuando las operaciones dejan de estar pendientes.
9. La aplicación debe permitir actualizar datos provenientes del sistema oficial.
10. La aplicación debe mantener los datos propios de gestión.
11. El cobrador puede ser ofrecido tanto a clientes al día como a clientes morosos.
12. Los clientes sin respuesta deben recibir mayor prioridad para gestión domiciliaria.
13. Las promesas deben generar seguimiento automático.
14. Una promesa incumplida debe aumentar la prioridad.
15. El sistema oficial sigue siendo la fuente oficial de los datos financieros.
16. La aplicación es una herramienta de organización y gestión.

---

# 35. Desarrollo por etapas

No implementar todo inmediatamente.

Orden recomendado:

### ETAPA 1
Base Laravel + configuración + autenticación básica + estructura inicial.

### ETAPA 2
Modelo de datos y migraciones.

### ETAPA 3
Clientes, operaciones y cuotas.

### ETAPA 4
Importación CSV/XLSX.

### ETAPA 5
Sincronización de importaciones.

### ETAPA 6
Dashboard y cartera activa.

### ETAPA 7
Gestiones e historial.

### ETAPA 8
Promesas y agenda.

### ETAPA 9
Pagos, punitorios y condonaciones.

### ETAPA 10
Sistema de prioridades.

### ETAPA 11
Mensajes y plantillas.

### ETAPA 12
Módulo de cobrador.

### ETAPA 13
Estadísticas y objetivos.

### ETAPA 14
Mejoras de UX/UI.

### ETAPA 15
Tests, seguridad, validaciones y optimización.

---

# 36. Criterio general de UX

La aplicación está pensada para ser utilizada durante una jornada laboral de cobranza.

Por lo tanto:

- Debe ser rápida.
- Clara.
- Responsive.
- Fácil de usar desde PC y celular.
- Evitar formularios innecesariamente largos.
- Mostrar las acciones importantes rápidamente.
- Minimizar cantidad de clics.
- Priorizar información accionable.

El usuario debe poder abrir la aplicación y responder rápidamente:

1. ¿Cuánto llevo cobrado?
2. ¿Cuánto falta?
3. ¿A quién tengo que contactar hoy?
4. ¿Quién prometió pagar hoy?
5. ¿Quién no responde?
6. ¿Quién necesita cobrador?
7. ¿Qué gestiones tengo pendientes?

---

# 37. Principio de diseño

La aplicación no debe ser simplemente una base de datos de clientes.

Debe funcionar como un:

## "Asistente personal de cobranza"

La aplicación debe ayudar a decidir:

> "¿Qué debería hacer ahora?"

Por eso el dashboard y el sistema de prioridades son tan importantes como la gestión de clientes.

---

# 38. Importante para Jules

Este documento describe el contexto completo y las reglas de negocio del proyecto.

NO comenzar a desarrollar toda la aplicación después de leer este documento.

Primero analizar el repositorio actual y confirmar:

- Stack.
- Estructura.
- Dependencias.
- Estado de Git.
- Configuración.
- Posibles conflictos.

Después esperar instrucciones de implementación por etapas.

Cada nuevo prompt será una tarea específica.

No inventar reglas de negocio que no estén definidas.

Cuando exista una decisión que pueda afectar la estructura de datos o la integridad de la información, detenerse y explicarla antes de realizar un cambio destructivo.

La prioridad absoluta es construir una aplicación simple, mantenible, segura y extensible.
