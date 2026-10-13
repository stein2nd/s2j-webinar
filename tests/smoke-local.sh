#!/usr/bin/env bash
#
# Stage-1 smoke test for Local (or any WP-CLI site). Run from Local Site Shell
# when possible so DB credentials match. Optional: export WP_PATH to WordPress root.
#
# Requires: GatherPress active, composer vendor present, production build recommended.
#
# Usage (from plugin root — not WordPress public alone):
#   cd wp-content/plugins/s2j-webinar
#   export WP_PATH="/Users/you/Local Sites/yoursite/app/public"   # optional if layout is standard
#   ./tests/smoke-local.sh
#   # or: npm run test:smoke:local
#
set -euo pipefail

PLUGIN_SLUG="s2j-webinar"
GATHERPRESS_SLUG="gatherpress"
GATHERPRESS_ALPHA_SLUG="gatherpress-alpha"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
GUTENBERG_JS="${PLUGIN_ROOT}/dist/js/s2j-webinar-gutenberg.js"
VENDOR_AUTOLOAD="${PLUGIN_ROOT}/vendor/autoload.php"

if [[ -z "${WP_PATH:-}" ]]; then
	_wp_candidate="$(cd "${PLUGIN_ROOT}/../../.." && pwd)"
	if [[ -f "${_wp_candidate}/wp-config.php" ]]; then
		WP_PATH="${_wp_candidate}"
	fi
	unset _wp_candidate
fi

WP_ARGS=()
if [[ -n "${WP_PATH:-}" ]]; then
	WP_ARGS+=(--path="${WP_PATH}")
fi

log() {
	echo "smoke: $*"
}

if ! command -v wp >/dev/null 2>&1; then
	echo "FAIL: wp (WP-CLI) not found. Use Local → Site Shell." >&2
	exit 1
fi

log "WordPress path: ${WP_PATH:-<default wp cwd>}"
log "checking WordPress…"
if ! wp "${WP_ARGS[@]}" core is-installed >/dev/null 2>&1; then
	echo "FAIL: WordPress not reachable. Set WP_PATH to the site public directory (wp-config.php)." >&2
	exit 1
fi

log "checking GatherPress…"
if ! wp "${WP_ARGS[@]}" plugin is-installed "${GATHERPRESS_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: GatherPress (${GATHERPRESS_SLUG}) is not installed. It is required for S2J Webinar." >&2
	exit 1
fi

wp "${WP_ARGS[@]}" plugin activate "${GATHERPRESS_SLUG}" >/dev/null 2>&1 || true

if ! wp "${WP_ARGS[@]}" plugin is-active "${GATHERPRESS_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: GatherPress could not be activated." >&2
	exit 1
fi

if wp "${WP_ARGS[@]}" plugin is-installed "${GATHERPRESS_ALPHA_SLUG}" >/dev/null 2>&1; then
	wp "${WP_ARGS[@]}" plugin activate "${GATHERPRESS_ALPHA_SLUG}" >/dev/null 2>&1 || true
	if ! wp "${WP_ARGS[@]}" plugin is-active "${GATHERPRESS_ALPHA_SLUG}" >/dev/null 2>&1; then
		echo "WARN: gatherpress-alpha is installed but not active. Current GatherPress may require it." >&2
	fi
fi

log "checking ${PLUGIN_SLUG}…"
if ! wp "${WP_ARGS[@]}" plugin is-installed "${PLUGIN_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: Plugin ${PLUGIN_SLUG} is not installed under wp-content/plugins." >&2
	exit 1
fi

if [[ ! -f "${VENDOR_AUTOLOAD}" ]]; then
	echo "FAIL: ${VENDOR_AUTOLOAD} missing. Run composer install in the plugin directory." >&2
	exit 1
fi

wp "${WP_ARGS[@]}" plugin activate "${PLUGIN_SLUG}" >/dev/null 2>&1 || true

if ! wp "${WP_ARGS[@]}" plugin is-active "${PLUGIN_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: ${PLUGIN_SLUG} could not be activated." >&2
	exit 1
fi

if [[ ! -f "${GUTENBERG_JS}" ]]; then
	echo "WARN: ${GUTENBERG_JS} missing. Run npm run build:production for the event panel UI." >&2
fi

log "checking service + REST routes…"
_eval_status=0
wp "${WP_ARGS[@]}" eval "
if ( ! class_exists( 'S2J\\\\Webinar\\\\ServiceBridge' ) ) {
	exit( 1 );
}
if ( ! function_exists( 'S2J\\\\WebinarService\\\\validate_webinar_record' ) ) {
	exit( 2 );
}
if ( ! did_action( 'rest_api_init' ) ) {
	do_action( 'rest_api_init' );
}
\$routes = rest_get_server()->get_routes();
\$need = array(
	array( 's2j-webinar/v1/events', '/sync' ),
	array( 's2j-webinar/v1/events', '/open' ),
	array( 's2j-webinar/v1/events', '/delete' ),
	array( 's2j-webinar/v1/webhook/zoom', '' ),
);
foreach ( \$need as \$pair ) {
	\$found = false;
	foreach ( array_keys( \$routes ) as \$route ) {
		if ( strpos( \$route, \$pair[0] ) === false ) {
			continue;
		}
		if ( \$pair[1] !== '' && strpos( \$route, \$pair[1] ) === false ) {
			continue;
		}
		\$found = true;
		break;
	}
	if ( ! \$found ) {
		exit( 3 );
	}
}
" || _eval_status=$?

if [[ "${_eval_status}" -ne 0 ]]; then
	case "${_eval_status}" in
		1) echo "FAIL: S2J\\\\Webinar\\\\ServiceBridge not loaded (composer install / activate plugin)." >&2 ;;
		2) echo "FAIL: s2j/webinar-service not available (validate_webinar_record missing)." >&2 ;;
		3)
			echo "FAIL: One or more s2j-webinar REST routes are not registered." >&2
			echo "Hint: wp ${WP_ARGS[*]} eval 'do_action(\\\"rest_api_init\\\"); foreach ( array_keys( rest_get_server()->get_routes() ) as \\\$r ) { if ( str_contains( \\\$r, \\\"s2j-webinar\\\" ) ) { echo \\\$r, PHP_EOL; } }'" >&2
			;;
		*) echo "FAIL: wp eval check failed (exit ${_eval_status})." >&2 ;;
	esac
	exit 1
fi

echo "PASS: GatherPress + ${PLUGIN_SLUG} active; service + sync REST route present."
