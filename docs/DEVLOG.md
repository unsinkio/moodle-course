# DEVLOG — moodle-course

Registro cronológico de evolución técnica.

> Generado desde `git log`
> **Última actualización:** `2026-05-06T17:56:36Z`  
> **Commits totales:** 59

---

## Modelo de autoridad

Este DEVLOG separa explícitamente dos roles que **no son equivalentes**:

| Columna | Pregunta que responde | Quién |
|---------|----------------------|-------|
| **Decisión** | ¿Quién definió qué construir, cuándo y por qué? | 🏛️ Arquitecto — siempre |
| **Código** | ¿Quién escribió e hizo commit del código? | 🤖 AI Dev (o 🏛️ Architect directamente) |

> El hecho de que `aunexus-lab` aparezca como autor en git **no significa**
> que tomó la decisión. Significa que fue el implementador. La dirección,
> los criterios de aceptación y la aprobación final siempre pertenecen al Arquitecto.

---

## Leyenda

| Símbolo | Rol | Descripción |
|---------|-----|-------------|
| 🏛️ **Architect** | Decisión + (a veces) Código | Define, dirige y aprueba todo |
| 🤖 **AI Dev** | Código únicamente | Implementa bajo dirección del Arquitecto |
| 🔧 **System/CI** | N/A | Scaffolding y automatizaciones |

---

## Resumen

| Métrica | Valor |
|---------|-------|
| Commits totales | 59 |
| Decisiones del Arquitecto | 59 |
| Implementados por AI Dev | 59 |
| Sistema/CI | 0 |

**Desglose de implementadores:**

| Commits | Implementador (git author) | Decisión |
|---------|--------------------------|----------|
| 47 | 🤖 `aunexus-lab` (AI Dev) | 🏛️ Architect |
| 11 | 🏛️ `saezinty` (Architect) | 🏛️ Architect |
| 1 | 👤 `Inty Saez` (Inty Saez) | 🏛️ Architect |

---

## Historial de commits

Cada fila muestra: **qué** se hizo, **quién tomó la decisión** (siempre el Arquitecto)
y **quién escribió el código** (AI Dev o el Arquitecto directamente).


### 2026-03-24

| Hash | Decisión | Código | Tipo | Descripción |
|------|----------|--------|------|-------------|
| `3d0ce73` | 🏛️ Architect | 🤖 AI Dev | `chore` | chore: migrate settings under AU Nexus category |

### 2026-03-12

| Hash | Decisión | Código | Tipo | Descripción |
|------|----------|--------|------|-------------|
| `a46fc55` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement inline video playback for resources in the Resources tab. |
| `f8b4b8d` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Display Moodle 'label' module content directly within the course format, conditionally rendering links or inline content with new styling. |

### 2026-03-11

| Hash | Decisión | Código | Tipo | Descripción |
|------|----------|--------|------|-------------|
| `7eda254` | 🏛️ Architect | 🤖 AI Dev | `fix` | fix: action box ribbon overlap |
| `9a99db2` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: Remove custom activity move buttons and styling, simplifying activity controls, and add z-index fix for dropdown menus. |
| `9541454` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement reordering controls for course modules in editing mode. |
| `8ef88a1` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement and style Moodle course module controls for videoclass activities. |

### 2026-03-10

| Hash | Decisión | Código | Tipo | Descripción |
|------|----------|--------|------|-------------|
| `f59bfaf` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Update chat conversation deletion to use integer `deleteforall` parameter, implement soft-delete, and restrict 'delete for everyone' to owners. |
| `958f827` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement 'delete for me' and 'delete for everyone' options for chat conversations, including recipient notifications and soft deletion. |
| `79e9299` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Allow retrieving chat history for shared conversations by verifying user ownership or recipient status. |
| `b0d6bf9` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Add markdown rendering for AI assistant messages in the chat interface and apply corresponding styles. |
| `d3ae56a` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: add styles for videoclass activity items including hover effects and icons. |
| `aae3ff9` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: Reimplement course section activity list generation using modinfo directly and reorder tab HTML elements. |
| `f914fff` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: use `modinfo` for module type classification instead of `$cmitem->module` and remove redundant `RESOURCE_MODULES` check. |
| `5568169` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Expand section label regex to include 'assignment' and 'assignments' for assessment classification. |
| `31a2058` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement chat conversation sharing, unsharing, and renaming functionality with new external APIs, database tables, and UI elements. |
| `950f720` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Add mobile-responsive slide-over sidebar for AI Tutor conversations and general mobile styling improvements. |
| `6aca112` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Introduce AI tutor chat conversation management with new API endpoints and UI. |

### 2026-03-09

| Hash | Decisión | Código | Tipo | Descripción |
|------|----------|--------|------|-------------|
| `a6fc9ea` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Enable linking chat messages to personal notes by adding a `noteid` field, a new `link_chat_note` service, and UI integration. |
| `285d9c3` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Dispatch a note deletion event and re-enable the corresponding "Save to Notes" button. |
| `d552e5d` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement auto-resizing textarea for the tutor input field, including dynamic height adjustment and corresponding styling. |
| `be77653` | 🏛️ Architect | 🤖 AI Dev | `—` | style: Update atlas icon. |
| `e67569f` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: Replace tutor icon `<img>` with Moodle `pix` helper and update corresponding CSS selector. |
| `82a4c7d` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Replace robot emoji with an ATLAS icon in the videoclass tutor toggle, adding the image asset and its associated styling. |
| `255b347` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Dynamically update the "My Notes" section when a note is saved from the AI Tutor chat. |
| `310ec0f` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Update plugin version to 2026030906 for AI Tutor chat services and settings page. |
| `df692ab` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Add AI tutor settings for CampusMCP integration, including configurable API endpoint, authentication, and system prompt. |
| `389d745` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Introduce AI Tutor chat interface with message sending, chat history, and the ability to save assistant responses. |
| `b4840f0` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: add an edit button to video sections when editing is enabled |
| `87bdc55` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement a fixed-width sidebar navigation in the video layout and reposition tabs below the main content. |
| `ee4fb59` | 🏛️ Architect | 🤖 AI Dev | `chore` | chore: add temporary debug logging for enrolled user names and emails. |
| `42b08b1` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Enhance student search by adapting client-side parsing for Moodle external function responses and relaxing server-side enrollment filters. |
| `6d256de` | 🏛️ Architect | 🤖 AI Dev | `chore` | chore: Add detailed console logging to the video class recipient search functionality and increment the plugin version. |
| `3ab2018` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Pre-render renderable properties and Moodle URLs to strings in section data for template output. |
| `a24f4d6` | 🏛️ Architect | 🤖 AI Dev | `—` | Fix: Object of class stdClass could not be converted to string |
| `1e8fab9` | 🏛️ Architect | 🤖 AI Dev | `—` | Refactor section content layout, update CSS grid properties, and revamp tab navigation and activity list rendering. |
| `9421ef9` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: Replace complex tabbed section content with a simplified video embed using summary text, an edit button, and updated availability rendering. |
| `f4cbc8f` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: Remove sidebar and personal notes, update video embedding, and streamline activity display within the section content template. |
| `0035738` | 🏛️ Architect | 🤖 AI Dev | `—` | Fix: Correct `core_text::strtolower` namespace and improve student search results parsing in the UI. |
| `5bd3248` | 🏛️ Architect | 🤖 AI Dev | `refactor` | refactor: replace Moodle string placeholders with direct English text and remove redundant JavaScript comments. |
| `2643d11` | 🏛️ Architect | 🤖 AI Dev | `fix` | fix: update 'no notes' message to be more concise and instructional |
| `e8995fb` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Add timeshared column to format_videoclass_note_recipients table. |
| `81b4024` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Refactor note sharing to consolidate personal and shared notes into a single system, introducing "shared with me" functionality. |
| `07e18c1` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement personal notes functionality and enhance shared notes with recipient management, broadcast options, and updated display. |
| `8d91684` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Implement shared notes functionality including database schema, external APIs for creation, retrieval, and deletion, and UI integration. |
| `06819ce` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: Add a collapsible announcement banner with state persistence to the `content.mustache` template and relocate the file. |
| `29ac730` | 🏛️ Architect | 🤖 AI Dev | `feat` | feat: add pluginname_help string for VideoClass format. |

### 2026-02-02

| Hash | Decisión | Código | Tipo | Descripción |
|------|----------|--------|------|-------------|
| `3f13fe4` | 🏛️ Architect | 🏛️ Architect (directo) | `feat` | feat: Implement section title display and an "Edit this topic" button with associated styling. |
| `61119af` | 🏛️ Architect | 🏛️ Architect (directo) | `—` | Refactor: explicitly construct section URLs with `moodle_url`. |
| `fccf989` | 🏛️ Architect | 🏛️ Architect (directo) | `fix` | fix: Adjust videoclass layout to remove left drawer spacing and ensure full width for main content and header. |
| `619bf63` | 🏛️ Architect | 🏛️ Architect (directo) | `—` | style: Expand content area and main region to full width when left drawer is closed and add page content padding. |
| `dfcde68` | 🏛️ Architect | 🏛️ Architect (directo) | `fix` | fix: Fully hide Moodle course index drawer and prevent layout shifts for the videoclass format. |
| `978c539` | 🏛️ Architect | 🏛️ Architect (directo) | `feat` | feat: Implement custom course section navigation in the sidebar, replacing the previous lesson list, and hide Moodle's default course index. |
| `09f9589` | 🏛️ Architect | 🏛️ Architect (directo) | `fix` | fix: improve video embed responsiveness and display by adding max-width and display properties. |
| `3a56fe0` | 🏛️ Architect | 🏛️ Architect (directo) | `feat` | feat: Improve video embed responsiveness and layout by setting a fixed column width, increasing wrapper min-height, and enhancing embedded content styling. |
| `173c833` | 🏛️ Architect | 🏛️ Architect (directo) | `refactor` | refactor: improve code style and explicitly cast cmname to string before stripping tags. |
| `2e75338` | 🏛️ Architect | 🏛️ Architect (directo) | `feat` | feat: Default to section 0 when `singlesection` is null in `get_sections_to_display` and apply minor formatting adjustments. |
| `a982ca5` | 🏛️ Architect | 🏛️ Architect (directo) | `feat` | feat: Introduce the 'videoclass' Moodle course format with a video-centric layout and dedicated styling. |
| `292f7c5` | 🏛️ Architect | 👤 Inty Saez | `—` | Initial commit |

---

*Generado por `scripts/update-devlog.sh` — 2026-05-06T17:56:36Z*
