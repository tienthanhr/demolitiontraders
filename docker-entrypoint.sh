#!/bin/sh
set -e

# Entry-point for containers: create runtime dirs and set permissive ownership
# Usage: provide APP_USER and APP_GROUP env vars where appropriate (defaults to www-data)

APP_USER=${APP_USER:-www-data}
APP_GROUP=${APP_GROUP:-www-data}

# Paths relative to repo root (adjust if your image uses different workdir)
ROOT_DIR=${ROOT_DIR:-/var/www/html}
LOGS_DIR="$ROOT_DIR/backend/config/logs"
UPLOADS_DIR="$ROOT_DIR/uploads"

# Create directories if possible
if [ ! -d "$LOGS_DIR" ]; then
  if ! mkdir -p "$LOGS_DIR" 2>/dev/null; then
    echo "Could not create $LOGS_DIR (permission denied) — continuing and logs will go to stderr"
  else
    echo "Created $LOGS_DIR"
  fi
fi

if [ ! -d "$UPLOADS_DIR" ]; then
  if ! mkdir -p "$UPLOADS_DIR" 2>/dev/null; then
    echo "Could not create $UPLOADS_DIR (permission denied) — user uploads may fail"
  else
    echo "Created $UPLOADS_DIR"
  fi
fi

# Try to chown; ignore failures
if command -v chown >/dev/null 2>&1; then
  if chown -R "$APP_USER:$APP_GROUP" "$LOGS_DIR" "$UPLOADS_DIR" 2>/dev/null; then
    echo "Set ownership of $LOGS_DIR and $UPLOADS_DIR to $APP_USER:$APP_GROUP"
  else
    echo "Warning: failed to chown $LOGS_DIR or $UPLOADS_DIR — continuing"
  fi
fi

# Ensure writable permissions where we could create
if [ -d "$LOGS_DIR" ]; then
  chmod 755 "$LOGS_DIR" || true
fi
if [ -d "$UPLOADS_DIR" ]; then
  chmod 755 "$UPLOADS_DIR" || true
fi

# Execute the container's main process (passed as CMD)
exec "$@"
