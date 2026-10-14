# MeshCentral link — GLPI plugin

Turns MeshCentral remote IDs stored on assets into clickable links, and adds a
**Connect** button to tickets.

GLPI ≥ 11.0 · PHP ≥ 8.1 · GPLv3+

## Why

GLPI core stores remote-management identifiers (TeamViewer, AnyDesk, MeshCentral,
…) on the **Remote management** tab of a computer. Core only renders a clickable
link for TeamViewer / AnyDesk / SupRemo / RustDesk — for **MeshCentral** the ID is
printed as plain text (`Item_RemoteManagement::getRemoteLink()` has no case for it).
There is no plugin needed to *collect* the ID (the inventory does it); this plugin
makes it *usable*.

## Features

- **Clickable remote IDs** — the MeshCentral ID on the computer's
  _Remote management_ tab becomes a link (configurable URL template).
- **Connect button on tickets** — a button appears in the ticket info panel for the
  computer(s) whose main user is the ticket requester, so an operator can jump
  straight into a remote session.
- **Configurable** — URL template with `{id}`, selectable connection types,
  open-in-new-tab.
- **Profile right** `plugin_meshlinking_connect` — controls who sees the ticket button.
- No GLPI core files are modified.
- English + Russian.

## Requirements

- GLPI ≥ 11.0
- PHP ≥ 8.1
- The computer's **User** field must be filled (the ticket button resolves the
  requester's computers through `glpi_computers.users_id`).

## Installation

1. Clone into `plugins/meshlinking`:

```bash
git clone https://github.com/pbolkhovitin/glpi-meshlinking.git plugins/meshlinking
```

2. Install and activate:

```bash
php bin/console plugin:install meshlinking
php bin/console plugin:activate meshlinking
```

3. Open _Setup → Plugins → MeshCentral link → gear icon_ and set the
   **URL template**, e.g.:

```
https://meshcentral.example.com/?viewmode=11&gotonode={id}&hide=16
```

## Configuration

| Setting | Meaning |
|---|---|
| **URL template** | `{id}` is replaced by the remote ID as-is (no URL encoding). |
| **Connection types** | Which remote-management types become links (default: `meshcentral`). |
| **Open links in a new tab** | Adds `target="_blank"`. |

### MeshCentral `viewmode` / `hide`

- `viewmode=10` — device page
- `viewmode=11` — remote desktop (auto connect)
- `viewmode=12` — terminal
- `viewmode=13` — files
- `hide=16` — hide the device list (bitmask: 1 masthead, 2 topbar, 4 footer, 8 title, 16 device list)

## Usage

- **Assets:** _Computer → Remote management_ tab — the MeshCentral ID is now a link.
- **Tickets:** the info panel shows **Connect to &lt;computer&gt;** for the requester's computer.
  Users need the `plugin_meshlinking_connect` right (granted on install to profiles
  that can update tickets; manage it under _Administration → Profiles → &lt;profile&gt; → MeshCentral link_).

## Technical notes

- Hook `Glpi\Plugin\Hooks::POST_ITIL_INFO_SECTION` renders the ticket button.
- Hook `Glpi\Plugin\Hooks::ADD_JAVASCRIPT` loads `js/meshlinking.js`, which rewrites
  the MeshCentral cell in the remote-management datatable (core has no template hook there).
- Configuration is passed to JS through `<meta name="meshlinking-config">`
  (`Hooks::ADD_HEADER_TAG`).
- Configuration is stored with `Config::setConfigurationValues('meshlinking', …)`.
- Admin page is a Symfony route (`#[Route("/config")]` → `/plugins/meshlinking/config`).

## License

GNU General Public License v3 or later (GPLv3+).

---

# MeshCentral link — плагин для GLPI

Превращает remote ID MeshCentral в кликабельные ссылки и добавляет кнопку
**Подключиться** в заявках.

Ядро GLPI показывает ссылку только для TeamViewer / AnyDesk / SupRemo / RustDesk;
для **MeshCentral** ID выводится простым текстом. Сам ID собирает инвентаризация —
плагин делает его пригодным для использования.

## Установка

```bash
git clone https://github.com/pbolkhovitin/glpi-meshlinking.git plugins/meshlinking
php bin/console plugin:install meshlinking
php bin/console plugin:activate meshlinking
```

Настройка — _Настройки → Плагины → MeshCentral link → шестерёнка_. Пример шаблона URL:

```
https://meshcentral.example.com/?viewmode=11&gotonode={id}&hide=16
```

## Возможности

- Кликабельные remote ID на вкладке _Удалённое управление_ компьютера.
- Кнопка **Подключиться к &lt;компьютер>** в панели заявки — для компьютеров
  заявителя (по полю «Пользователь» компьютера).
- Настраиваемый шаблон URL, выбор типов, открытие в новой вкладке.
- Право профиля `plugin_meshlinking_connect`.
- Ядро GLPI не изменяется. Русский и английский языки.
