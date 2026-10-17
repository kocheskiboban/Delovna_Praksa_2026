
cd "$(dirname "$0")" || exit 1

say() { printf "\n  %s\n" "$1"; }
port_open() { nc -z 127.0.0.1 "$1" >/dev/null 2>&1; }
pause_exit() { printf "\n  Притиснете Enter за да го затворите прозорецот."; read -r; exit 1; }

# 1. PHP -------------------------------------------------------------
PHP=""
for p in $(ls -d /Applications/MAMP/bin/php/php8*/bin/php 2>/dev/null | sort -t. -k2,2nr -k3,3nr) \
         /Applications/XAMPP/xamppfiles/bin/php /opt/homebrew/bin/php /usr/local/bin/php "$(command -v php)"; do
  if [ -n "$p" ] && [ -x "$p" ]; then PHP="$p"; break; fi
done
if [ -z "$PHP" ]; then
  say "Не е пронајден PHP. Инсталирајте MAMP од https://www.mamp.info (бесплатно), па кликнете повторно двапати на start.command."
  open "https://www.mamp.info/en/downloads/"
  pause_exit
fi

# 2. MySQL -----------------------------------------------------------
if ! port_open 8889 && ! port_open 3306; then
  say "Ја стартувам базата на податоци..."
  if [ -x /Applications/MAMP/bin/startMysql.sh ]; then
    /Applications/MAMP/bin/startMysql.sh >/dev/null 2>&1 &
  elif [ -x /Applications/XAMPP/xamppfiles/xampp ]; then
    sudo /Applications/XAMPP/xamppfiles/xampp startmysql
  fi
  for _ in $(seq 1 20); do port_open 8889 || port_open 3306 && break; sleep 1; done
fi
if ! port_open 8889 && ! port_open 3306; then
  say "Базата не стартува сама. Ќе се отвори MAMP — кликнете Start во него."
  open -a MAMP 2>/dev/null || open -a "MAMP" 2>/dev/null
  for _ in $(seq 1 120); do port_open 8889 || port_open 3306 && break; sleep 1; done
fi
if ! port_open 8889 && ! port_open 3306; then
  say "Базата на податоци (MySQL) не работи. Отворете MAMP, кликнете Start и пробајте повторно."
  pause_exit
fi

# 2b. База: увоз / ажурирање ако е постара верзија ---------------------
"$PHP" tools/update-db.php || pause_exit

# 3. Веб сервер ------------------------------------------------------
PORT=8000
while port_open "$PORT"; do PORT=$((PORT + 1)); done
URL="http://127.0.0.1:$PORT/"
LOG="${TMPDIR:-/tmp}/ohrid-summer-server.log"

PHP_CLI_SERVER_WORKERS=4 "$PHP" -d display_errors=0 -S "127.0.0.1:$PORT" router.php >"$LOG" 2>&1 &
SERVER=$!
for _ in $(seq 1 20); do port_open "$PORT" && break; kill -0 "$SERVER" 2>/dev/null || break; sleep 0.5; done
if ! port_open "$PORT"; then
  say "Веб серверот не стартува. Порака од PHP:"
  cat "$LOG"
  pause_exit
fi

clear
cat <<EOF2

  ==============================================
   Охридско лето работи на:  $URL
  ==============================================
   Админ:     admin@ohridsummer.test / Admin123!
   Корисник:  user@ohridsummer.test  / User123!
   Картичка:  4242 4242 4242 4242, 12/29, 123

   Не го затворајте овој прозорец додека ја
   користите апликацијата. За крај: затворете го.
  ==============================================

EOF2
open "$URL"
trap 'kill $SERVER 2>/dev/null' EXIT
wait $SERVER
