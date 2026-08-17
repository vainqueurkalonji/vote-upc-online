#!/bin/sh
set -eu

PORT="${PORT:-10000}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf

if [ "${VOTE_UPC_SKIP_MIGRATIONS:-0}" != "1" ] && [ -n "${DATABASE_URL:-}" ]; then
    echo "Preparation de la base de donnees..."
    tentative=1

    while [ "$tentative" -le 20 ]; do
        if php scripts/migrer_render.php; then
            break
        fi

        if [ "$tentative" -eq 20 ]; then
            echo "Impossible de preparer la base de donnees." >&2
            exit 1
        fi

        echo "Base indisponible, nouvel essai dans 3s (${tentative}/20)..."
        tentative=$((tentative + 1))
        sleep 3
    done
fi

exec "$@"
