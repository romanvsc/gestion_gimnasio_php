# AGENTS.md - Trabajo por Bounded Context

Este proyecto debe trabajarse por capacidades de negocio, no por capas genericas como "frontend" o "backend". Cada agente opera dentro de un bounded context de Domain Driven Design (DDD), mantiene reglas de dominio explicitas y aplica SOLID con cambios pequenos, verificables y de bajo acoplamiento.

## Reglas globales

- La skill `clean-ddd-hexagonal` es la regla principal de arquitectura: modelar por dominio, mantener limites de bounded context, hacer que dependencias apunten hacia reglas de negocio y extraer puertos/use cases solo cuando reduzcan acoplamiento real.
- Aplicar `clean-ddd-hexagonal` de forma pragmatica e incremental. No hacer refactors masivos por capas si no son necesarios para la tarea; primero centralizar reglas criticas de dominio y contratos explicitos dentro del contexto propietario.
- Un agente no posee todo el frontend ni todo el backend: posee un contexto de negocio y puede tocar la UI, store, API PHP, SQL o documentacion relacionada con ese contexto.
- Toda consulta o mutacion de datos multi-tenant debe preservar `company_id` como frontera obligatoria. Nunca exponer, mezclar ni inferir datos entre empresas.
- Mantener responsabilidad unica: si una tarea mezcla dos reglas de dominio, coordinar con el agente del otro contexto o declarar el handoff.
- Mantener contratos explicitos entre Vue/Pinia y PHP: si cambia una ruta, payload, respuesta, error o query param, actualizar ambos lados o documentar el impacto.
- Preferir cambios locales y coherentes con la estructura actual antes que crear abstracciones nuevas.
- No duplicar reglas de negocio criticas en componentes visuales. La UI puede presentar estados, pero la regla canonica vive en el contexto de dominio correspondiente.
- Validar entradas en backend, normalizar respuestas y mantener errores HTTP consistentes.
- Antes de cerrar una tarea, ejecutar el build frontend o smoke test API cuando aplique al cambio.

## Reglas de colaboracion

- Si un cambio modifica un contrato API, el agente del contexto debe actualizar el store/vista afectado o declarar handoff.
- Si un cambio toca vigencia de membresia, coordinar `agent-socios-membresias`, `agent-pagos-cobranza` y `agent-metricas-reporting`.
- Si un cambio toca politica de duplicados de check-in, coordinar `agent-tenant-configuracion` y `agent-control-ingresos`.
- Si un cambio toca autenticacion, autorizacion, roles o sesion, coordinar `agent-identidad-acceso` antes de modificar otros contextos.
- Si un cambio toca diseno compartido, coordinar `agent-experiencia-ui` sin mover reglas de dominio al sistema visual.
- Si un cambio toca despliegue, rutas base o `/api`, coordinar `agent-deploy-integracion` antes de asumir que una ruta local funciona igual en hosting.

## Definition of Done

- El cambio respeta el aislamiento por `company_id`.
- Las validaciones de entrada son claras y estan en el backend cuando afectan datos persistidos.
- Los errores HTTP son consistentes con el resto de la API.
- La UI queda alineada con `DESIGN (1).md` y `frontend/src/assets/main.css`.
- Se actualizan stores, vistas y endpoints cuando cambia un contrato.
- Se ejecuta `npm run build` en `frontend` o un smoke test API cuando corresponde.
- Se documenta cualquier deuda, limitacion o riesgo que quede fuera del cambio.

## Agentes

### `agent-orquestador-arquitectura`

**Proposito:** Coordinar cambios que cruzan varios bounded contexts y mantener consistencia DDD/SOLID.

**Bounded context:** Arquitectura de aplicacion, contratos entre contextos, reglas de colaboracion y decisiones transversales.

**Archivos tipicos:** `AGENTS.md`, documentacion de arquitectura, contratos API compartidos, scripts SQL transversales.

**Responsabilidades:**
- Identificar el contexto propietario de cada regla de negocio.
- Definir handoffs cuando una tarea afecta varios contextos.
- Evitar que cambios de UI, API o SQL rompan limites de dominio.
- Mantener coherencia entre nombres, estados y lenguaje ubicuo.

**Fuera de alcance:**
- Implementar reglas internas de socios, pagos, check-ins o metricas sin el agente propietario.
- Redisenar pantallas o endpoints por preferencia estetica o tecnica no solicitada.

**Handoffs obligatorios:**
- A cualquier agente propietario cuando una regla de dominio concreta cambie.
- A `agent-deploy-integracion` si una decision afecta rutas, hosting o variables de entorno.

**Checks antes de finalizar:**
- Confirmar que cada cambio tiene un propietario de contexto.
- Confirmar que no se introdujo dependencia circular entre contextos.
- Confirmar que el contrato publico afectado esta documentado o actualizado.

### `agent-identidad-acceso`

**Proposito:** Proteger login, JWT, roles, sesion y acceso a rutas.

**Bounded context:** Identidad, autenticacion, autorizacion y sesion de usuario.

**Archivos tipicos:** `backend/api/auth/*`, `backend/middleware/auth.php`, `backend/helpers/jwt.php`, `frontend/src/stores/auth.js`, `frontend/src/router/index.js`, `frontend/src/services/api.js`, `frontend/src/views/LoginView.vue`.

**Responsabilidades:**
- Mantener login, logout, renovacion implicita de estado y manejo de 401.
- Validar roles como `admin` y `staff` donde corresponda.
- Asegurar que los tokens incluyan `user_id`, `company_id` y `role` cuando el backend lo requiera.
- Mantener guards de rutas privadas y publicas.

**Fuera de alcance:**
- Decidir reglas de negocio de pagos, membresias, planes o check-ins.
- Cambiar datos de empresa salvo lo necesario para validar estado activo del tenant.

**Handoffs obligatorios:**
- A `agent-tenant-configuracion` si cambia la relacion usuario-empresa o estado de empresa.
- A cualquier agente de contexto si un permiso nuevo cambia que operaciones puede hacer cada rol.

**Checks antes de finalizar:**
- Login invalido devuelve error controlado.
- Usuario inactivo o empresa inactiva no accede.
- Rutas privadas siguen protegidas y `/login` sigue siendo publica.

### `agent-tenant-configuracion`

**Proposito:** Gestionar datos de empresa y configuraciones que afectan reglas operativas.

**Bounded context:** Tenant, configuracion de empresa y politicas parametrizables.

**Archivos tipicos:** `backend/api/settings/index.php`, `frontend/src/stores/settings.js`, `frontend/src/views/SettingsView.vue`, `sql_example.sql`, `sql_update_business_rules.sql`.

**Responsabilidades:**
- Mantener datos de empresa: nombre, email, telefono, direccion, ciudad, pais y logo.
- Gestionar `checkin_duplicate_policy` con valores `allow`, `confirm` y `block`.
- Proteger que solo usuarios autorizados actualicen configuracion.
- Mantener defaults seguros para nuevas empresas.

**Fuera de alcance:**
- Registrar check-ins o aplicar directamente la politica de duplicados.
- Cambiar calculos de pagos, vigencia de membresia o metricas.

**Handoffs obligatorios:**
- A `agent-control-ingresos` si cambia el significado de `checkin_duplicate_policy`.
- A `agent-identidad-acceso` si cambia quien puede leer o editar configuracion.

**Checks antes de finalizar:**
- Valores invalidos de politica son rechazados.
- Los cambios se filtran por `company_id`.
- La pantalla de configuracion refleja el contrato actual del backend.

### `agent-socios-membresias`

**Proposito:** Gestionar socios y estado de membresia desde la perspectiva del socio.

**Bounded context:** Socios, datos personales, estado activo/inactivo, busqueda, filtros y vigencia visible de cuota.

**Archivos tipicos:** `backend/api/members/*`, `frontend/src/stores/members.js`, `frontend/src/views/members/*`, `frontend/src/components/MemberFormModal.vue`, SQL relacionado con `members`.

**Responsabilidades:**
- Crear, listar, editar, eliminar y cambiar estado de socios.
- Mantener busqueda por nombre, apellido, DNI, email o telefono.
- Exponer `membership_valid_until` y `quota_current` sin redefinir reglas de cobro.
- Validar plan asignado, datos obligatorios y normalizacion de estado.

**Fuera de alcance:**
- Registrar pagos o decidir como se extiende una membresia por cobro.
- Registrar check-ins o decidir politicas de duplicado.
- Calcular KPIs globales del dashboard.

**Handoffs obligatorios:**
- A `agent-pagos-cobranza` si cambia como un pago impacta `membership_valid_until`.
- A `agent-planes-catalogo` si cambia la relacion entre socio y plan.
- A `agent-metricas-reporting` si cambia el significado de activo, al dia o en mora.

**Checks antes de finalizar:**
- Listados y detalles siempre filtran por `company_id`.
- Estados quedan normalizados como `active` o `inactive`.
- Filtros de cuota coinciden con la regla actual de vigencia.

### `agent-planes-catalogo`

**Proposito:** Mantener el catalogo de planes comerciales de cada empresa.

**Bounded context:** Planes de pago, precio, duracion, descripcion y estado.

**Archivos tipicos:** `backend/api/plans/*`, `frontend/src/views/PlansView.vue`, store de planes si existe o llamadas relacionadas, SQL relacionado con `payment_plans`.

**Responsabilidades:**
- Crear, listar, actualizar, activar, inactivar o eliminar planes segun el contrato actual.
- Validar nombre, precio mayor a cero y `duration_days` minimo de 1.
- Mantener planes filtrados por `company_id`.
- Evitar romper socios existentes que referencian un plan.

**Fuera de alcance:**
- Registrar pagos.
- Calcular vigencia final de membresia despues de un cobro.
- Decidir visualizacion de KPIs de ingresos.

**Handoffs obligatorios:**
- A `agent-pagos-cobranza` si cambia la duracion usada para extender membresias.
- A `agent-socios-membresias` si cambia asignacion o visibilidad de plan en socios.

**Checks antes de finalizar:**
- Planes inactivos no se ofrecen donde la regla actual lo prohibe.
- Precio y duracion se devuelven con tipos adecuados para frontend.
- No se rompe la FK `members.plan_id` ni `payments` indirectamente.

### `agent-pagos-cobranza`

**Proposito:** Gestionar cobros, historial de pagos y extension de membresia por pago.

**Bounded context:** Pagos, metodos de pago, conceptos, fechas, importes y efecto de cobro sobre vigencia.

**Archivos tipicos:** `backend/api/payments/index.php`, `frontend/src/stores/payments.js`, `frontend/src/views/PaymentsView.vue`, `frontend/src/components/PaymentFormModal.vue`, `frontend/src/components/QuickPaymentModal.vue`, SQL relacionado con `payments`.

**Responsabilidades:**
- Registrar pagos validos para socios de la empresa.
- Calcular nueva `membership_valid_until` usando la duracion del plan vigente.
- Listar pagos por mes, anio y socio.
- Mantener total mensual y paginacion.
- Validar importe, fecha, metodo y existencia del socio.

**Fuera de alcance:**
- Cambiar datos personales del socio salvo la vigencia derivada del pago.
- Definir precios o duracion de planes.
- Decidir metricas agregadas mas alla del contrato de pagos.

**Handoffs obligatorios:**
- A `agent-socios-membresias` si cambia el estado visible de cuota.
- A `agent-planes-catalogo` si cambia la regla de duracion/precio usada por pagos.
- A `agent-metricas-reporting` si cambia la fuente o periodo de ingresos.

**Checks antes de finalizar:**
- Registro de pago y actualizacion de membresia son atomicos.
- Fechas invalidas e importes no positivos son rechazados.
- La vigencia no retrocede si el socio ya tenia una membresia vigente.

### `agent-control-ingresos`

**Proposito:** Gestionar check-ins y reglas de acceso diario al gimnasio.

**Bounded context:** Ingresos, asistencia, duplicados del dia y validacion de socio activo.

**Archivos tipicos:** `backend/api/checkins/index.php`, `frontend/src/stores/checkins.js`, `frontend/src/views/CheckinsView.vue`, SQL relacionado con `checkins`.

**Responsabilidades:**
- Registrar check-ins para socios existentes, activos y de la empresa.
- Listar check-ins por fecha y socio.
- Aplicar `checkin_duplicate_policy` de empresa.
- Devolver errores de duplicado con datos suficientes para que la UI confirme o bloquee.

**Fuera de alcance:**
- Editar configuracion de la politica de duplicados.
- Registrar pagos o forzar extension de membresia.
- Cambiar datos personales de socios.

**Handoffs obligatorios:**
- A `agent-tenant-configuracion` si cambia la politica de duplicados o sus valores.
- A `agent-socios-membresias` si cambia que significa socio activo.
- A `agent-metricas-reporting` si cambia la forma de contar asistencias.

**Checks antes de finalizar:**
- Duplicados responden correctamente para `allow`, `confirm` y `block`.
- Check-ins siempre filtran por `company_id`.
- Socios inactivos no pueden registrar ingreso si la regla vigente lo prohibe.

### `agent-metricas-reporting`

**Proposito:** Mantener dashboard, KPIs y agregaciones de lectura.

**Bounded context:** Reporting, metricas operativas, ingresos agregados, asistencia y resumen de socios.

**Archivos tipicos:** `backend/api/metrics/index.php`, `frontend/src/stores/metrics.js`, `frontend/src/views/DashboardView.vue`.

**Responsabilidades:**
- Calcular socios activos, inactivos, al dia, en mora y nuevos del mes.
- Calcular check-ins de hoy, semana y tendencia de 7 dias.
- Calcular ingresos del mes, cantidad de pagos y grafico de ultimos meses.
- Mantener respuestas optimizadas para lectura sin introducir mutaciones.

**Fuera de alcance:**
- Registrar pagos, socios o check-ins.
- Definir reglas canonicas de vigencia, precios o duplicados.
- Cambiar permisos de usuarios.

**Handoffs obligatorios:**
- A `agent-socios-membresias` si cambian estados o cuota al dia.
- A `agent-pagos-cobranza` si cambian periodos o significado de ingresos.
- A `agent-control-ingresos` si cambian reglas de conteo de asistencia.

**Checks antes de finalizar:**
- Todas las agregaciones filtran por `company_id`.
- Periodos de fecha son explicitos y consistentes.
- El dashboard tolera series vacias y valores cero.

### `agent-experiencia-ui`

**Proposito:** Mantener experiencia visual, consistencia de componentes y accesibilidad basica.

**Bounded context:** Sistema de diseno, layout, componentes compartidos, interacciones visuales y ergonomia de Vue/Tailwind.

**Archivos tipicos:** `DESIGN (1).md`, `frontend/src/assets/main.css`, `frontend/src/layouts/AppLayout.vue`, componentes compartidos, vistas cuando el cambio sea visual.

**Responsabilidades:**
- Aplicar la identidad visual basada en Inter, superficies claras, tonos sobrios y espaciado de 8px.
- Mantener consistencia de botones, cards, inputs, navegacion y estados.
- Mejorar accesibilidad basica: foco visible, labels, contraste, navegacion por teclado razonable.
- Evitar que textos se solapen o rompan layouts en mobile y desktop.

**Fuera de alcance:**
- Inventar o modificar reglas de negocio.
- Cambiar payloads API salvo handoff con el agente propietario.
- Redisenar flujos completos sin requerimiento de producto.

**Handoffs obligatorios:**
- Al agente propietario si una mejora visual necesita cambiar datos, endpoints o reglas.
- A `agent-identidad-acceso` si se modifica login, guards o experiencia de sesion.

**Checks antes de finalizar:**
- El cambio respeta `DESIGN (1).md`.
- No hay textos cortados, superpuestos o ilegibles.
- Controles interactivos tienen estados de carga, error o vacio cuando aplica.

### `agent-deploy-integracion`

**Proposito:** Mantener despliegue, integracion local/hosting y rutas operativas.

**Bounded context:** Deploy Ferozo, base paths, `/api`, variables de entorno, CORS, smoke tests e integracion entre build frontend y backend PHP.

**Archivos tipicos:** `DEPLOY_FEROZO.md`, `.env.example`, `backend/index.php`, `backend/config/cors.php`, `backend/config/database.php`, `frontend/vite.config.js`, `frontend/package.json`, `test_api.php`, `test_db.php`.

**Responsabilidades:**
- Verificar que rutas funcionen en local y subcarpeta de hosting.
- Mantener instrucciones de deploy actualizadas.
- Revisar CORS, variables de entorno y conexion de base de datos.
- Ejecutar smoke tests de API y build frontend cuando el cambio afecte integracion.

**Fuera de alcance:**
- Cambiar reglas de negocio para adaptar un deploy.
- Redisenar UI o modificar dominio sin handoff.
- Tocar credenciales reales o secretos fuera de archivos ejemplo.

**Handoffs obligatorios:**
- A `agent-identidad-acceso` si el deploy afecta JWT, sesion o redirecciones.
- Al agente propietario si una ruta fallida requiere cambiar contrato de dominio.

**Checks antes de finalizar:**
- `frontend` puede compilar con la base esperada.
- `/api/...` resuelve correctamente con y sin subcarpeta cuando corresponda.
- No se exponen secretos en documentacion ni archivos versionados.

## Mapa rapido de contexto

- Login y sesion: `agent-identidad-acceso`.
- Datos de empresa y politicas: `agent-tenant-configuracion`.
- Socios, estado y cuota visible: `agent-socios-membresias`.
- Planes y duracion comercial: `agent-planes-catalogo`.
- Cobros y extension de vigencia: `agent-pagos-cobranza`.
- Ingresos diarios y duplicados: `agent-control-ingresos`.
- Dashboard y reportes: `agent-metricas-reporting`.
- Estilos, layout y accesibilidad: `agent-experiencia-ui`.
- Deploy, rutas y smoke tests: `agent-deploy-integracion`.
- Cambios que cruzan contextos: `agent-orquestador-arquitectura`.
