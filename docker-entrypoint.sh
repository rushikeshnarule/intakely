#!/bin/sh
set -e

# Wait for database if DB_HOST is set
if [ -n "$DB_HOST" ]; then
  echo "Waiting for PostgreSQL ($DB_HOST:${DB_PORT:-5432})..."
  until php -r '
    $host = getenv("DB_HOST");
    $port = getenv("DB_PORT") ?: 5432;
    $db   = getenv("DB_NAME") ?: "intakely";
    $user = getenv("DB_USER") ?: "intakely_owner";
    $pass = getenv("DB_PASS") ?: "";
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
  '; do
    echo "PostgreSQL is not ready yet. Retrying in 2 seconds..."
    sleep 2
  done
  echo "PostgreSQL connected successfully!"

  # Run migrations automatically
  echo "Running database schema migrations..."
  php bin/migrate.php || echo "Migration notice: check schema state"

  # Optional auto-seed if seed is requested
  if [ "$AUTO_SEED" = "true" ]; then
    echo "Auto-seeding initial chambers and legal templates..."
    php bin/seed.php || echo "Seed notice: initial records already present"
  fi
fi

exec "$@"
