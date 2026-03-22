#!/usr/bin/env sh
set -eu

run_compose() {
  echo
  echo "==> docker compose $*"
  docker compose "$@"
}

wait_for_e2e_base_url() {
  run_compose exec -T e2e sh -lc 'attempt=0; until curl -fsS http://creditall-app:8000/ >/dev/null; do attempt=$((attempt+1)); if [ "$attempt" -ge 30 ]; then exit 1; fi; sleep 2; done'
}

run_compose up -d app e2e
run_compose run --rm app php artisan test
run_compose exec -T e2e npm ci
wait_for_e2e_base_url
run_compose exec -T e2e npm run e2e:docker

echo
echo "All PHP and E2E tests finished successfully."
