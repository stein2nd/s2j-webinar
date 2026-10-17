# S2J Webinar - テスト戦略と結果記録

最終更新: 2026-10-17

本プラグインの検証方針と実行記録です。環境の役割 (Local / Studio / PHPUnit) の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このプラグイン固有の手順と結果** です。コンパニオン [S2J Webinar Source QR — test-results.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md) は **同型の段階表・推奨順** を採用 (本 repo は OAuth / 同期 / Service パイプラインが中心)。

[status.md](./status.md) — 実装％・[§推奨テスト実施順](./status.md#status-test-order)・[§外部結合](./status.md#status-external) (E7 = Source QR 定義 REST)。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリの状態 |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (結合スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` + 手動チェックリスト |
| **2** | テストサイト構成の再現 (WP / PHP、**GatherPress 同梱**) | **WordPress Studio**、Blueprints | [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) (S2J Webinar は手動配置) |
| **3** | PHP 自動テスト、PR 時のゲート | PHPUnit、GitHub Actions | 3a: 純 PHPUnit (CI **`unit-only`**)。3b: MySQL + WP テスト lib (ローカル、`bin/phpunit-local.sh`)。任意: wp-env |

段階1と2は「実際の WordPress サイト」。段階 **3a** は WordPress を起動しない。段階 **3b** はテスト専用 DB のみ (Local 開発 DB を直接書き換えない)。

### Docker なしの開発環境

[S2J Webinar Source QR test-results §Docker なし](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#docker-なしの開発環境) と同型。

| 段階 | Docker なしでローカル実行 | 代替 |
| --- | --- | --- |
| **3a** | **可能** — `npm run test:unit:php:unit-only` (= `composer test:unit`、**22** tests) | CI [`.github/workflows/phpunit.yml`](../.github/workflows/phpunit.yml) と同一 |
| **3b** | **可能** — `npm run test:unit:php:local` (要 MySQL テスト DB、`.env.tests`、任意 `WP_TESTS_GATHERPRESS_DIR`) | **wp-env** — `npm run test:unit:php` (Docker 任意) |
| **1** | **可能** — Local / Studio + `npm run test:smoke:local` | `WP_PATH` を Site Shell または Studio ルートに |
| **2** | **可能** — Studio Blueprint + 手動 checklist | CLI は段階1と同じ smoke |

日常のループ例: **`npm run test:unit:php:unit-only`** → **`npm run lint:php`** → (統合を触ったら) **`npm run test:unit:php:local`** (**22 + 6**)。

## 推奨実施順 (Source QR 同型・ localhost 自動テスト優先) {#推奨実施順-source-qr-同型localhost-自動テスト優先}

**段階 (1/2/3)** と **環境 (localhost / HTTPS)** は別軸です。先に **localhost で完結する自動テスト** をそろえ、Zoom OAuth / 実 API は後回しにします ([status.md §推奨テスト実施順](./status.md#status-test-order))。

| 順 | 段階 | 環境 | 内容 | 本リポジトリ |
| --- | --- | --- | --- | --- |
| **0** | **3a** | **Local 可のみ** | 単体 PHPUnit ・ TS/SCSS lint ・ **PHPStan/PHPCS** | `npm run test:unit:php:unit-only` (**22**) / `npm run lint` / **`npm run lint:php`** |
| **1** | **1** | **Local 可のみ** (CLI) | smoke — REST ルート・Service 到達 | `npm run test:smoke:local` |
| **2** | **3b** | **Local 可** (MySQL) | 統合 PHPUnit — `tests/Integration/` (**6**、GP 読込時) | `npm run test:unit:php:local` |
| **3** | **2** | **Local 可のみ** | Blueprint + 手動 [#1–8](#第1段階-local-結合スモーク) (OAuth #6 SKIP 可) | [studio/](../studio/) |
| **4** | **1–3** | **Local 可 (E5/E7)** | 参加 URL 目視 (E5)。**P2a** 定義 REST 後 Source QR `catalogReady` ([Source QR 順4](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#順4-オプション-local-可-e2e4)) | [status §運用素材 P2a](./status.md#status-operational) |
| **5** | **2–3** | **要 HTTPS** | OAuth (E1) ・実 Zoom sync (E2) ・ Webhook (E3) ・ Survey attach (E4) | [status §外部結合](./status.md#status-external) |

**補足:** 段階1に HTTPS 版は通常なし。順 **0** は段階番号より先に回してよい。HTTPS 行は **CI 必須条件にしない**。

### localhost で可能 / 不可能 (Source QR 対照)

| 観点 | Local / Studio (`localhost`) | 備考 |
| --- | --- | --- |
| プラグイン有効化、REST (`sync` / open / delete / webhook ルート) | **可能** | 段階1 smoke |
| Document Settings **S2J Webinar** パネル | **可能** (GatherPress 必須) | `npm run build:production:gutenberg` 済み `dist/` |
| **同期** REST (`POST …/sync`) | **可能** | 未接続時 **422** も Pass (ルート到達)。Marketplace 不要の Service 検証は 3a/3b |
| Zoom OAuth Connect | **不可** (localhost) | #6 SKIP → [E1](./status.md#status-external) |
| Webhook — Zoom ダッシュボード登録 | **不可** (localhost) | 段階1–2は secret 未設定可 → [E3](./status.md#status-external) |
| Gutenberg **日本語 UI** | **可能** | サイト **ja** + `languages/s2j-webinar-ja.mo` / script translations |
| Source QR **定義 REST** → `catalogReady` | **条件付き** | 本 repo **[P2a 未実装](./status.md#status-operational)**。実装後 **Local 可** ([E7](./status.md#status-external)) |
| Source QR **取り込み** (`tracking-sources`) | **条件付き** | **P2b** + `_s2j_webinar_id` + OAuth — 実 Zoom は **要 HTTPS** ([Source QR E3](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md#status-external)) |

**依存:** 段階1・2では **[GatherPress](https://github.com/stein2nd/gatherpress)** (フォーク可) を **有効化必須** です。wp.org 最新系では **GatherPress alpha** も同時に有効化する必要があります (GatherPress 本体が要求)。本プラグイン単体ではイベント post type / Slot が成立しません。

**Webhook (段階1–2):** Zoom Event Subscriptions は **公衆 HTTPS** のエンドポイント URL が必須です。Local / Studio の `http://localhost` 等では Zoom 側で登録できません (*A secure URL using HTTPS is required*)。**段階1–2では Webhook URL / Webhook secret は未設定のまま** とし、OAuth ・同期・スモークを優先します。`webinar.started` / `webinar.ended` の検証は [status.md の外部結合ゲート E3](./status.md#status-external) (HTTPS ステージング) で行います。

**OAuth Connect (Local / Studio):** Zoom は **`localhost` ホスト名** を拒否します (4700)。本プラグインは **`127.0.0.1` に正規化** しますが、**`http://127.0.0.1:<port>` も Marketplace 側で4700になることがあります** (Allow list 不一致、または HTTP ループバック非対応)。

1. General app → **Development** の **OAuth Redirect URL for OAuth** (必須・1本) に callback の **完全 URL** を登録。**OAuth allow list** は Zoom UI が **HTTPS + FQDN** を要求し、`http://127.0.0.1:…` は **Wrong URL format** になる (ループバック / HTTP はフォーム段階で拒否)。Allow list には **ベースのみ** (例: `https://staging.example.com`) を入れ、Redirect URL 欄にパス付き callback を入れる運用が一般的 ([Zoom OAuth Information](https://developers.zoom.us/docs/build-flow/basic-info/oauth-info/))。
2. WordPress の Client ID / Secret は **Development** の資格情報と一致させる。
3. それでも4700→ **HTTPS の到達可能 URL** が必要 ([Zoom 開発者フォーラム](https://devforum.zoom.us/) では ngrok / ステージングを推奨)。例: `ngrok http 8884` → `https://….ngrok-free.app/wp-admin/admin-post.php?action=s2j_webinar_oauth_callback` を Zoom に登録し、必要なら `s2j_webinar_oauth_redirect_uri` フィルターで同 URL を指定 ([oauth_and_settings_spec.md](./oauth_and_settings_spec.md))。
4. **段階1–2で Connect を省略** し、OAuth 実 Connect は [外部結合 E1](./status.md#status-external) (HTTPS ステージング) に回す — Webhook 後回しと同様 — でもよい。

---

## 第1段階: Local (結合スモーク)

日常の **Local 等の開発サイト** の `wp-content/plugins/s2j-webinar` に置いた状態で確認します (サイト名・パスは環境依存のため、手順では固定しません)。

| # | 確認項目 | 期待 |
| --- | --- | --- |
| 1 | `composer install` + `npm run build:production` | `vendor/` と `dist/js/s2j-webinar-*.js` が存在 |
| 2 | GatherPress 有効化 | エラーなく有効 |
| 3 | S2J Webinar 有効化 | エラーなく有効 (`s2j/webinar-service` 同梱) |
| 4 | 管理メニュー **S2J Webinar** | 設定画面が開く。クライアント ID / 秘密鍵保存可 |
| 5 | GatherPress **イベント** 編集 (ブロックエディター) | Document Settings に **S2J Webinar** パネル（**S2J Slug Generater** と同列）。未接続時は警告 |
| 6 | Zoom 接続 (任意・テストアカウント) | OAuth コールバック後「connected」通知 |
| 7 | イベント保存 | パネルメタ (`_s2j_webinar_*`) が REST 経由で保存される |
| 8 | **同期** ボタン | REST `POST /wp-json/s2j-webinar/v1/events/{id}/sync` が422/200を返す (Zoom 未設定時は失敗も可。ルート到達を確認) |

**自動 (段階1の一部):** Local の **Site Shell** では、まず **本プラグインのディレクトリ** (`wp-content/plugins/s2j-webinar`) に `cd` してから実行します。Site Shell の初期カレントは多くの場合 WordPress の `public` だけで、そこには `test:smoke:local` がありません (`npm error Missing script`)。

```bash
cd wp-content/plugins/s2j-webinar
export WP_PATH="/path/to/your/site/app/public"   # wp-config.php があるディレクトリ
npm run test:smoke:local
# 同等: WP_PATH="$WP_PATH" bash tests/smoke-local.sh
```

GatherPress + 本プラグイン有効化、REST ルート、Service 関数到達を確認します。Gutenberg UI ・OAuth は手動項目として残します。

### 手動チェックリスト結果 (Pass 記録)

[第1段階](#第1段階-local-結合スモーク) の #1–8 を、Zoom Marketplace / HTTPS OAuth を **要求しない範囲** で確認した記録です。#6 OAuth Connect は [段階1–2で省略可](#段階的な導入-3段階) のため **SKIP (意図的)** とします。

| 環境 | 確認日 | #1–5 | #6 OAuth | #7–8 | 総合 |
| --- | --- | --- | --- | --- | --- |
| Local (`blockpluginbase`) | 2026-10-17 | PASS | SKIP | PASS | **PASS** |
| WordPress Studio (`webinar-smoke`) | 2026-10-17 | PASS | SKIP | PASS | **PASS** |

| # | 項目 | Local メモ |
| --- | --- | --- |
| 1 | build + vendor/dist | `composer install` / `npm run build:production` 済み |
| 2 | GatherPress 有効 | GP + alpha 有効 |
| 3 | S2J Webinar 有効 | `s2j/webinar-service` ^0.1 同梱 |
| 4 | 設定 → S2J Webinar | 画面・Client ID / Secret 保存可 |
| 5 | Gutenberg パネル | Document Settings に S2J Webinar (未接続警告含む) |
| 6 | OAuth Connect | 省略 (E1 / HTTPS ステージングに回す) |
| 7 | イベント保存 | `_s2j_webinar_*` REST 保存 |
| 8 | 同期 REST | `POST …/sync` 到達 (422/200) |

---

## 第2段階: WordPress Studio (再現可能な検証環境)

[WordPress Studio](https://developer.wordpress.com/docs/developer-tools/studio/) を、Local とは別系統の **検証用サイト工場** として使います。

* **Local:** いつもの開発・手動スモーク (段階1)
* **Studio:** 同じ手順で立ち上がる **テスト専用サイト** (WP / PHP 差、**GatherPress + 本プラグイン** の事前配置)

### 想定する使い方 (Blueprint 完成後)

1. Studio → Upload Blueprint → [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) ([Blueprints](https://developer.wordpress.com/docs/developer-tools/studio/blueprints/) 形式)。GatherPress は Blueprint 内で有効化。
2. 生成サイトに **`npm run build:production` 済み** + `composer install --no-dev` 済みの本プラグインを symlink / コピーし、`wp plugin activate s2j-webinar`。
3. 管理画面を開き mu-plugin シード (下書きイベント1件) を走らせる。
4. (GatherPress は Blueprint 済み。フォーク利用時は [studio/README.md](../studio/README.md) 参照)
5. 段階1チェックリスト + `WP_PATH='<WordPress ルート>' npm run test:smoke:local` (Studio のサイト表示名や OS 上のフォルダー名は手順に含めない)。
6. 結果を下記「最新結果」に記録 (環境列は「WordPress Studio (Blueprint)」など抽象名でよい)。

詳細メモ: [studio/README.md](../studio/README.md)

---

## 第3段階: PHPUnit

[S2J MediaLibrary Date Corrector](https://github.com/stein2nd/s2j-media-library-date-corrector) と同様、**ローカルデフォルトは wp-env / Docker 不要** です。開発サイト DB (`local` 等) とは **別名のテスト DB** を使います (`.env.tests.example`)。

### 3a. 純 PHPUnit (CI ・日常)

WordPress / MySQL なし。`s2j/webinar-service` 連携とアダプタの純ロジック (`tests/`、`Integration` 除外)。

**現在の件数 (2026-10-17):** **22** tests / **55** assertions（`composer test:unit` または `npm run test:unit:php:local` の前半）。

```bash
composer install
composer test
# または
npm run test:unit:php:unit-only
```

設定: `phpunit.unit.xml.dist`。GitHub Actions (`.github/workflows/phpunit.yml`) は `npm run test:unit:php:unit-only` を実行します。WordPress 統合 (3b) 向けに **PHPUnit 9.6** を使用します (WP テスト lib が PHPUnit 10 の `parseTestMethodAnnotations` 削除と非互換のため。MediaLibrary Date Corrector と同型)。

### 3b. WordPress 統合 PHPUnit (ローカル MySQL)

`WP_UnitTestCase` 利用の `tests/Integration/` (REST、GatherPress 境界など)。

**現在の件数 (2026-10-17、GP 読込時):** **6** tests / **30** assertions（`npm run test:unit:php:local` の後半。3a と合わせて **22 + 6**）。

1. `.env.tests.example` を `.env.tests` にコピーし、**テスト専用 DB** と Local の socket / 認証を設定
2. (任意) GatherPress 依存のケース向け `WP_TESTS_GATHERPRESS_DIR` にプラグインルートを指定
3. 初回のみ Subversion で WP PHPUnit ライブラリ取得 + MySQL — 以降はキャッシュ (`tests/.wordpress*`、gitignore)

```bash
npm run test:unit:php:local
# pretest で composer install。中身: bin/phpunit-local.sh (unit → integration)
```

**wp-env (任意):** Docker で GatherPress 同梱の統合のみ試す場合は `npm run test:unit:php` (`.wp-env.json` 参照)。Local 日常の3b には不要です。

**Zoom Marketplace 不要の Service 連携:** `WebinarServicePipelineTest`（validate / plan / `build_webinar_request`）と `SyncRestServiceIntegrationTest`（サイト token + `WebinarHttpExecutor` モックで REST sync 完走）で、Composer `s2j/webinar-service` とプラグイン配線を検証。OAuth Connect・実 Zoom API・Webhook 登録は含まない（[外部結合 E1–E4](./status.md#status-external)）。

**Pass の読み方:** ユニット **22** 件 OK のあと、統合 **6** 件（GP 読込時）が目安。GP 未読込時は `RestRoutesTest` のみ実行し、他は `markTestSkipped`（`SSS.` 等は **失敗ではなく成功**）。6 件すべて Pass にするには `.env.tests` で `WP_TESTS_GATHERPRESS_DIR` を設定 (必要なら alpha 同梱)。

**統合スイート (現行、GP 読込時):** `tests/Integration/` — **6** tests — `RestRoutesTest`、`PanelMetaRestTest`、`EventScheduleIntegrationTest`、`OnlineEventLinkIntegrationTest`、`SyncRestServiceIntegrationTest` 等。3a **22** + 3b **6** = 計 **28** tests (**55** + **30** assertions)。

---

## 最新結果

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-14 | 1 | Local (`blockpluginbase`) | PASS | `npm run test:smoke:local` (プラグインルート + 自動 `WP_PATH`)。手動 checklist は [第1段階](#第1段階-local-結合スモーク) 参照 |
| 2026-10-14 | 2 | WordPress Studio (`webinar-smoke`) | PASS | プラグイン `~/Studio/webinar-smoke/wp-content/plugins/s2j-webinar` から `npm run test:smoke:local` (`WP_PATH` → Studio サイトルート)。wp-cli v2.12の PHP Deprecated (react/promise) は出力のみ・判定に影響なし。手動 checklist は [第1段階](#第1段階-local-結合スモーク) 参照 |
| 2026-10-13 | 3 | CI / ローカル | PASS | `composer test` (ServiceBridge, StatusCalculator) |
| 2026-10-14 | 3a | ローカル | PASS | 純 PHPUnit: 18件 (OAuth refresh, SyncRunner, Get/Delete, Webhook 署名) |
| 2026-10-16 | 3b | Local (`blockpluginbase`) | PASS | `npm run test:unit:php:local` — 当時 unit 18 + integration 4 (→ 2026-10-17 に **22 + 6**) |
| 2026-10-17 | 3a+3b | Local (`blockpluginbase`) | PASS | `npm run test:unit:php:local` — **22** unit (55 assertions) + **6** integration (30 assertions)。`.env.tests` + GP。Service pipeline / mock sync 含む (Marketplace 不要) |
| 2026-10-14 | 3b | wp-env | (未実行) | 統合4 件 — `npm run test:unit:php` (Docker 要) |
| 2026-10-17 | 1 | Local (`blockpluginbase`) | PASS (manual) | 手動 checklist #1–8 — [記録表](#手動チェックリスト結果-pass-記録) |
| 2026-10-17 | 2 | WordPress Studio | PASS (manual) | 同上 (2026-10-14 自動 smoke 済み + 手動 checklist) |
| 2026-10-17 | 0 | Local (`blockpluginbase`) | PASS | **`npm run lint:php`** — PHPStan + PHPCS + WPCS (`prelint:php` → Composer dev 依存)。[Source QR 同型行](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#最新結果) |
| (未記録) | 2 | Studio | — | Blueprint **ゼロから** 再現 + 手動 #1–8 一括 ([Source QR 未記録行](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#最新結果) 同型) |
| (未記録) | 4 | Local or Studio | — | **E7** — P2a 定義 REST 実装後 Source QR `catalogReady` / Save draft **200** ([Source QR 順4 E1–E2](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#順4-オプション-local-可-e2e4)) |

実行後、日付、Pass/Fail、メモを追記してください。

### 外部結合ゲート (E1– E7)

Zoom 実 API ・OAuth 完了・ Webhook 等の Pass は [status.md §外部結合ゲート](./status.md#status-external) を参照。**Local (`http` localhost / 127.0.0.1) で Pass しにくい項目 (E1– E4等)** と **要 HTTPS (ステージング / ngrok)** の区別は同表の **Local 可 / 要 HTTPS** 列に記載。**E7** (Source QR 定義 REST) は **P2a 実装後 Local 可**。段階1–2の smoke Pass だけでは E1– E7は満たさない。
