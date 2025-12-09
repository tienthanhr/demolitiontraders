Railway / Container runtime setup and troubleshooting

This project expects to be able to write to a couple of runtime directories:

- `backend/config/logs` — PHP error logs (optional; fallback to stderr when not writable)
- `uploads` — user-uploaded files (images etc.)

When running in a container platform (Railway, Docker), prefer logging to stdout/stderr instead of relying on files. The project includes a safe fallback in `backend/config/error-handler.php` to log to `php://stderr` when `backend/config/logs` can't be created or is not writable.

Files added
- `docker-entrypoint.sh` — entrypoint helper that creates `backend/config/logs` and `uploads`, attempts to `chown` them, then runs the container's CMD.

Recommended container setup (Docker)

- Add the entrypoint to your Dockerfile and set it as the entrypoint. Example Dockerfile snippet:

  FROM php:8.2-apache
  COPY . /var/www/html
  COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
  RUN chmod +x /usr/local/bin/docker-entrypoint.sh
  # ensure web root ownership (adjust as needed)
  RUN chown -R www-data:www-data /var/www/html
  ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
  CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]

- You can pass APP_USER/APP_GROUP env vars from Railway if your container uses a different user.

Railway / Production checklist

1) Prefer environment variables instead of a `.env` file. The logs show "Warning: .env file not found" — set required config in Railway Project → Variables.

2) If you require persistent uploads, configure a persistent volume or an external object store (S3 / Backblaze / Cloud Storage). Do NOT rely on container filesystem for long-term storage.

3) Use the Railway console to view logs — after the `error-handler` fallback we've made, logs will appear in Railway logs (stderr/stdout).

Commands to run on a Linux server/container to prepare dirs (if you have shell access)

# create dirs and give webserver ownership (run as root or use sudo)
mkdir -p /var/www/html/backend/config/logs
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/backend/config/logs /var/www/html/uploads
chmod -R 755 /var/www/html/backend/config/logs /var/www/html/uploads

If you use a different PHP/Apache user, replace `www-data:www-data` with the correct `user:group`.

Windows (XAMPP) quick local dev steps (PowerShell run as Admin):

New-Item -ItemType Directory -Path "C:\xampp\htdocs\demolitiontraders\backend\config\logs" -Force
New-Item -ItemType Directory -Path "C:\xampp\htdocs\demolitiontraders\uploads" -Force

Notes on common issues
- If you still see `mkdir(): Permission denied` in logs after following these steps, it means the webserver process user cannot write to the parent directory. Ensure the parent directories are writable by the webserver user or use the entrypoint to attempt creation at container start.

- Many 404s for images (`/demolitiontraders/uploads/...`) mean files were not uploaded or path is wrong. Verify the `uploads` directory content and the code that writes files.

Want me to:
- (A) add a small Dockerfile example into the repo and wire the entrypoint (I can create `Dockerfile.example`),
- (B) create an `entrypoint` invocation in a sample `docker-compose.yml` for local development,
- (C) or just leave the entrypoint and guide as-is?

Pick A, B, or C and I'll implement it.