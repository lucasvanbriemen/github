# GitHub GUI - Building & Updating

## Initial Build (creates the installer .exe)

```bash
npm run dist
```

The installer will be output to `dist-electron/` (e.g. `GitHub GUI Setup 1.0.0.exe`).

## Publishing an Update

1. Bump the `version` in `package.json`
2. Build and publish to GitHub Releases:

```bash
npm run dist:publish
```

Requires a `GH_TOKEN` environment variable with a GitHub personal access token (repo scope).

## How Auto-Update Works

- On app startup, `electron-updater` checks the GitHub Releases for a newer version.
- If found, it downloads automatically and shows a notification.
- Clicking the notification restarts the app with the update applied.
- Updates also install automatically on next app quit.
