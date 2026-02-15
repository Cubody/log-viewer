# Log Viewer

A plugin for [Pelican Panel](https://pelican.dev) (Filament) that lets you browse and search server log files directly from the admin panel with syntax highlighting, level filtering, and real-time updates.

<img width="2560" height="1400" alt="изображение" src="https://github.com/user-attachments/assets/2999f4a4-9d68-4e3c-9baa-b8bcbc31c4ae" />

## Features

- View server log files (`.log`, `.log.gz`, `.log.1`, `.log.2`, `.log.3`)
- Level highlighting: **Error**, **Warn**, **Info**, **Debug**
- Date filtering
- Full-text search across log contents
- Virtualized scrolling for large files (up to 5 MB)
- Auto-refresh via polling every 5 seconds
- Stack trace detection with error level inheritance

## Installation

### Option 1: Direct URL (Pelican Panel)

Use this URL in the Pelican Panel plugin installer:

```
https://github.com/Cubody/log-viewer/releases/download/v1.0.0/log-viewer.zip
```

### Option 2: Download Release

1. Go to the [Releases](../../releases) page
2. Download `log-viewer.zip`
3. Extract the contents into your Pelican Panel plugins directory:
   ```
   /path/to/pelican/plugins/log-viewer/
   ```
4. The resulting structure should look like this:
   ```
   plugins/
   └── log-viewer/
       ├── plugin.json
       ├── src/
       │   ├── LogViewerPlugin.php
       │   └── Filament/Server/Pages/LogViewer.php
       └── resources/views/
           ├── log-viewer.blade.php
           └── log-viewer.js
   ```

### Option 3: Clone the Repository

```bash
cd /path/to/pelican/plugins/
git clone https://github.com/Cubody/log-viewer.git
```

### After Installation

The plugin will be automatically discovered by Pelican Panel. Navigate to a server management page — you will see a **Log Viewer** item in the sidebar navigation.

## Requirements

- Pelican Panel with plugin support (`panel_version >= 1.0.0-beta1`)
- PHP 8.1+
- Laravel with Filament

## License

MIT
