#!/usr/bin/env bash
# Foundation §7 verification. Run from the api/ directory on a machine with PHP 8.3
# and the docker-compose stack (db + minio) up. Exits non-zero on any failure.
set -euo pipefail

cd "$(dirname "$0")"

echo "==> [1/4] composer install (sanctum + flysystem-s3)"
composer install --no-interaction

echo "==> [2/4] fresh migrate + seed (all tables build; §5.7 fixture loads)"
php artisan migrate:fresh --seed --force

echo "==> [3/4] tinker data-layer checks"
php artisan tinker --execute='
use App\Enums\RequestStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Models\Request;
use App\Models\UserAccount;

$fail = 0;
$ok = function (string $name, bool $cond) use (&$fail) {
    echo ($cond ? "  PASS  " : "  FAIL  ") . $name . PHP_EOL;
    if (! $cond) { $fail = 1; }
};

// enum cast + label() returns the spec label verbatim
$u = UserAccount::where("email", "admin@permitflow.test")->first();
$ok("enum cast resolves", $u->role instanceof App\Enums\Role);
$ok("label() = spec label", $u->role->label() === "Administrator");

// a representative relation loads
$r = Request::where("status", RequestStatus::Decided)->whereHas("decision")->first();
$ok("request->owner loads", $r->owner instanceof UserAccount);
$ok("request->responsibleStaff loads", $r->responsibleStaff instanceof UserAccount);
$ok("request->decision loads", $r->decision !== null);
$ok("request->historyEntries load", $r->historyEntries->count() >= 0);

// legal transition sets status (in memory); illegal one raises the guard exception
$sub = Request::where("status", RequestStatus::Submitted)->first();
$sub->transitionTo(RequestStatus::InReview);
$ok("legal submitted->in_review", $sub->status === RequestStatus::InReview);

$threw = false;
$draft = Request::where("status", RequestStatus::Draft)->first();
try { $draft->transitionTo(RequestStatus::Decided); }
catch (IllegalStatusTransitionException $e) { $threw = true; }
$ok("illegal draft->decided throws", $threw);

// restrictOnDelete blocks deleting a referenced user_account
$owner = $r->owner;
$blocked = false;
try { $owner->delete(); }
catch (\Illuminate\Database\QueryException $e) { $blocked = true; }
$ok("restrictOnDelete blocks delete", $blocked);

if ($fail) { echo "TINKER CHECKS: FAIL" . PHP_EOL; exit(1); }
echo "TINKER CHECKS: PASS" . PHP_EOL;
'

echo "==> [4/4] curl smoke test (Sanctum auth wired, fail closed)"
php artisan serve --port=8123 >/tmp/pf_serve.log 2>&1 &
SERVE_PID=$!
trap 'kill $SERVE_PID 2>/dev/null || true' EXIT
sleep 3

UNAUTH=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8123/api/user)
echo "  unauthenticated GET /api/user -> $UNAUTH (expect 401)"
[ "$UNAUTH" = "401" ] || { echo "SMOKE TEST: FAIL (expected 401)"; exit 1; }

COOKIES=$(mktemp)
curl -s -c "$COOKIES" http://127.0.0.1:8123/sanctum/csrf-cookie >/dev/null
XSRF=$(awk '/XSRF-TOKEN/ {print $7}' "$COOKIES" | sed 's/%3D/=/g')
curl -s -b "$COOKIES" -c "$COOKIES" \
  -H "X-XSRF-TOKEN: ${XSRF}" \
  -H "Accept: application/json" -H "Referer: http://localhost" \
  -d "email=admin@permitflow.test&password=password" \
  http://127.0.0.1:8123/api/login >/dev/null
AUTH=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIES" -H "Accept: application/json" http://127.0.0.1:8123/api/user)
echo "  authenticated GET /api/user -> $AUTH (expect 200)"
[ "$AUTH" = "200" ] || { echo "SMOKE TEST: FAIL (expected 200)"; exit 1; }

echo "SMOKE TEST: PASS"
echo "==> FOUNDATION VERIFICATION: GREEN"
