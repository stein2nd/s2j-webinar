# S2J Webinar - 実装状況

最終更新: 2026-10-17

索引は [specs.md](./specs.md) です。計算規則の正本は [Webinar Service の `docs/`](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/specs.md) です。

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [plugin_spec.md](./plugin_spec.md) — 統合見取り図
* [sync_execution_spec.md](./sync_execution_spec.md) / [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) / [admin_ui_spec.md](./admin_ui_spec.md)
* [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md) / [webhook_spec.md](./webhook_spec.md) / [survey_handoff_spec.md](./survey_handoff_spec.md)
* [governance/documentation_governance.md](./governance/documentation_governance.md) — ドキュメント運用
* [archive/README.md](./archive/README.md) — 完了イニシアチブ (まだなし)
* [test-results.md](./test-results.md) — 段階1–3、外部結合の記録
* コンパニオン [S2J Webinar Source QR — status.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md) / [test-results.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md) — P2 / 定義 REST 連携 (本 repo 実装待ち)

## 機能一覧

**完了条件列:** 100% 到達用チェックリスト。`[x]` = 満たし済み、`[ ]` = 未。正本 spec は各§の見出しリンク。

**実装％** は、各§の **プラグイン検証可能** 項 (`[x]` / その全数) のみ。Zoom ステージング等は [§外部結合ゲート](#status-external) に分離し、Pass 記録があっても % には入れない。

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (100%) |
| --- | --- | --- | --- |
| 仕様 (`docs/`) | 確定 | — | [§仕様分割](#status-spec-docs) |
| Composer + 配布 | 実装済み | 100 | [§ Composer・配布](#status-composer-dist) (6/6) |
| サイト設定 + OAuth | 実装済み | 100 | [§ OAuth・設定](#status-oauth) (11/11) |
| イベントパネル (Gutenberg) | 実装済み | 100 | [§管理 UI](#status-admin-ui) (11/11) |
| メタ ↔ WebinarRecord + dirty | 実装済み | 100 | [§メタ・レコード](#status-meta-record) (9/9) |
| 同期 (create / update / 列) | 実装済み | 100 | [§同期実行](#status-sync) (10/10) |
| 削除・「Zoom で開く」(`get`) | 実装済み | 100 | [§ get / delete](#status-get-delete) (7/7) |
| 公開リンク (GatherPress) | 実装済み | 100 | [§公開リンク](#status-public-link) (4/4) |
| Webhook (`started` / `ended`) | 実装済み | 100 | [§ Webhook](#status-webhook) (5/5) |
| Survey `ready` → `attach_survey` | 一部 | 100 | [§ Survey 引き渡し](#status-survey) (3/3) |
| フロント資産 (Vite / lint / i18n) | 実装済み | 100 | [§フロント・lint](#status-frontend) (8/8) |
| アンインストール | 実装済み | 100 | [§アンインストール](#status-uninstall) (2/2) |
| テスト・ CI (段階1–3) | 一部 | 92 | [§品質・スモーク](#status-quality) (11/12) |
| 運用素材 | 未着手 | 0 | [§運用素材](#status-operational) (0/3) |

## 100% 完了条件チェックリスト

各§は **プラグイン実装 → Test (PHPUnit / smoke) → Test (wp-env)** の順。Zoom / 手動は [§外部結合ゲート](#status-external)。

**Test 見出しの環境タグ** ([S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md) と同型):

| タグ | 意味 |
| --- | --- |
| **Local 可のみ** | Local / Studio / CI (`test:unit:php:unit-only`、`test:unit:php:local`、smoke、`lint:php`)。HTTPS 不要 |
| **Local 可 (E*)** | localhost で可だが [§外部結合](#status-external) の **E*** 前提 (例: Source QR 定義 REST **E7**) |
| **要 HTTPS** | 公衆 HTTPS (ステージング・トンネル等)。Zoom OAuth / 実 API / Webhook |

### 推奨テスト実施順 (localhost 自動優先) {#status-test-order}

[Source QR status §推奨テスト実施順](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md#status-test-order) と同型。**段階1/2/3** × **localhost / HTTPS** で、先に localhost の **自動** を完了する。

| 順 | 段階 | 環境 | やること |
| --- | --- | --- | --- |
| 0 | 3a | Local 可のみ | `npm run test:unit:php:unit-only` (**22**)、`npm run lint` / **`npm run lint:php`** (`prelint:php` → Composer dev 依存) |
| 1 | 1 | Local 可のみ (CLI) | Local Site Shell / Studio — `npm run test:smoke:local` |
| 2 | 3b | Local 可 (MySQL) | `npm run test:unit:php:local` — **22 + 6** (`.env.tests`、任意 `WP_TESTS_GATHERPRESS_DIR`) |
| 3 | 2 | Local 可のみ | Studio Blueprint + 手動 checklist [#1–8](./test-results.md#第1段階-local-結合スモーク) (OAuth #6 SKIP 可) |
| 4 | 1–3 | Local 可 (E5/E7) | 参加 URL 目視 (E5)。**P2a** 定義 REST 後 Source QR `catalogReady` (E7 — [Source QR 順4 E2](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#順4-オプション-local-可-e2e4)) |
| 5 | 2–3 | 要 HTTPS | OAuth (E1)、実 Zoom sync (E2)、Webhook (E3)、Survey attach (E4) — Pass は [test-results.md](./test-results.md) |

詳細コマンドは [test-results.md §推奨実施順](./test-results.md#推奨実施順-source-qr-同型localhost-自動テスト優先)。

### 仕様分割 {#status-spec-docs}

* [x] 正本が `docs/`、大きな改訂案は `docs_mod/` ([governance/documentation_governance.md](./governance/documentation_governance.md))
* [x] CI: `docs-lint` ([`.github/workflows/docs-lint.yml`](../.github/workflows/docs-lint.yml))

### Composer ・配布 {#status-composer-dist}

* [x] `composer.json` — Packagist 名のみで `s2j/webinar-service`
* [x] `s2j-webinar.php` — `vendor/autoload.php`
* [x] Test: [`ServiceBridgeTest.php`](../tests/ServiceBridgeTest.php)
* [x] 配布 zip — `vendor/` + `dist/`
* [x] CI: [`.github/workflows/dist-zip.yml`](../.github/workflows/dist-zip.yml)
* [x] Survey メタ除外 — `uninstall.php`

### OAuth ・設定 {#status-oauth}

正本: [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)

#### プラグイン実装

* [x] **設定 → S2J Webinar** — GatherPress 依存状態、GatherPress 内 Webinar タブ表示 オン／オフ、Webinar provider ([`SettingsPage.php`](../includes/Admin/SettingsPage.php))
* [x] **GatherPress 設定 → Webinar** — クライアント ID/秘密鍵、リダイレクト URI、Webhook 秘密 / URL、接続状態、Save / Connect %s ([`SettingsTab.php`](../includes/GatherPress/SettingsTab.php))
* [x] タブ OFF 時 — GP から Webinar タブ非表示 (ON で復帰) — `s2j_webinar_show_gatherpress_settings_tab`
* [x] プロバイダ表示名 — [`WebinarProvider.php`](../includes/Provider/WebinarProvider.php) (初版 `zoom` のみ。管理・通知・ガイドは `%s` msgid)
* [x] connect / callback / disconnect ([`OAuthFlow.php`](../includes/OAuth/OAuthFlow.php), [`TokenStore.php`](../includes/OAuth/TokenStore.php))
* [x] Webinar REST — Bearer ([`ZoomHttpClient.php`](../includes/Http/ZoomHttpClient.php))
* [x] 期限前 refresh — `ensure_access_token()` ([`ZoomHttpClient.php`](../includes/Http/ZoomHttpClient.php))
* [x] OAuth token / refresh — Basic + form body (材料 URL 前置なし)
* [x] 接続アカウント メール — 保存・表示 ([`TokenStore::persist_token_response`](../includes/OAuth/TokenStore.php))

#### Test (PHPUnit / smoke)

* [x] Test: [`ErrorRedactionTest.php`](../tests/ErrorRedactionTest.php) — トークン様文字列を `last_error` 経路で除去
* [x] Test: [`ZoomHttpClientOAuthTest.php`](../tests/ZoomHttpClientOAuthTest.php) — refresh Header / Content-Type (モック `wp_remote_request`)

### 管理 UI {#status-admin-ui}

正本: [admin_ui_spec.md](./admin_ui_spec.md)

#### プラグイン実装

* [x] Document Settings パネル **S2J Webinar** (`PluginDocumentSettingPanel`) ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] 未接続時の誘導 — `%s is not connected…` + `providerLabel` ([`AssetLoader.php`](../includes/Admin/AssetLoader.php))
* [x] 録画 / 参加登録 / 登壇者 — `useEntityProp` + REST メタ
* [x] **同期** / **Open in %s** / **Delete from %s** (確認 `Remove this webinar from %s?`)
* [x] 登壇者0人 — Sync 無効
* [x] REST — sync / open / delete ([`SyncController.php`](../includes/Rest/SyncController.php), [`EventActionsController.php`](../includes/Rest/EventActionsController.php))
* [x] 設定画面分割 — [admin_ui_spec.md](./admin_ui_spec.md) (GP タブに GatherPress 状態を出さない)

#### Test (PHPUnit / smoke)

* [x] Smoke: [`tests/smoke-local.sh`](../tests/smoke-local.sh) — REST ルート登録
* [x] Test: wp-env — パネル編集キー REST 往復 ([`PanelMetaRestTest.php`](../tests/Integration/PanelMetaRestTest.php))

* [x] status — `_s2j_webinar_status` の i18n ラベル表示 (REST read + [`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] `last_error` — パネル常時表示 (REST read + 同期後 `invalidateResolution`)

### メタ・レコード {#status-meta-record}

正本: [data_dictionary.md](./data_dictionary.md)

#### プラグイン実装

* [x] `register_post_meta` / `save_post` ([`PostMetaRegistrar.php`](../includes/Meta/PostMetaRegistrar.php), [`PanelMetaSaver.php`](../includes/Meta/PanelMetaSaver.php))
* [x] `RecordAssembler` / `StatusCalculator`
* [x] GatherPress 日時 — `Event` / `gatherpress_datetime` + filter ([`EventSchedule.php`](../includes/GatherPress/EventSchedule.php))
* [x] 同期 context — `previous_panelists` ([`SyncContextBuilder.php`](../includes/Sync/SyncContextBuilder.php))

#### Test (PHPUnit / smoke)

* [x] Test: [`StatusCalculatorTest.php`](../tests/StatusCalculatorTest.php)
* [x] Test: [`SyncContextBuilderTest.php`](../tests/SyncContextBuilderTest.php)
* [x] Test: [`EventScheduleFilterTest.php`](../tests/EventScheduleFilterTest.php)
* [x] Test: wp-env — GatherPress イベント post で `start_at` 非空 ([`EventScheduleIntegrationTest.php`](../tests/Integration/EventScheduleIntegrationTest.php))

### 同期実行 {#status-sync}

正本: [sync_execution_spec.md](./sync_execution_spec.md)

#### プラグイン実装

* [x] validate → plan → HTTP → map ([`SyncRunner.php`](../includes/Sync/SyncRunner.php))
* [x] context — retry / panelists / survey / `webinar_title` ([`SyncContextBuilder.php`](../includes/Sync/SyncContextBuilder.php))
* [x] `last_sent` / `last_panelists` 永続化 ([`RecordMetaWriter.php`](../includes/Record/RecordMetaWriter.php))
* [x] `join_url` / `uuid` — 空で既存を消さない ([`RecordMetaWriter.php`](../includes/Record/RecordMetaWriter.php))
* [x] 不足 — i18n + プロバイダ名 (`%s`) ([`DeficiencyMessages.php`](../includes/Messages/DeficiencyMessages.php))
* [x] HTTP 差し替え — [`WebinarHttpExecutor.php`](../includes/Http/WebinarHttpExecutor.php)

#### Test (PHPUnit / smoke)

* [x] Test: [`DeficiencyMessagesTest.php`](../tests/DeficiencyMessagesTest.php)
* [x] Test: [`SyncRunnerTest.php`](../tests/SyncRunnerTest.php) — モック HTTP + Service 応答 JSON
* [x] Test: 列中断後も `last_sent` 保持 ([`SyncRunnerTest.php`](../tests/SyncRunnerTest.php))

### get / delete {#status-get-delete}

#### プラグイン実装

* [x] get 専用 — [`GetRunner.php`](../includes/Sync/GetRunner.php) (plan 非経由)
* [x] delete — [`DeleteRunner.php`](../includes/Sync/DeleteRunner.php) (`intend_delete`)
* [x] delete 成功 — メタ / 公開欄クリア ([`RecordMetaWriter::write_after_delete_success`](../includes/Record/RecordMetaWriter.php))
* [x] REST open / delete

#### Test (PHPUnit / smoke)

* [x] Smoke: open / delete / webhook ルート ([`tests/smoke-local.sh`](../tests/smoke-local.sh))
* [x] Test: [`GetRunnerTest.php`](../tests/GetRunnerTest.php) / [`DeleteRunnerTest.php`](../tests/DeleteRunnerTest.php) — モック HTTP

### 公開リンク {#status-public-link}

#### プラグイン実装

* [x] join_url 非空 — [`OnlineEventLinkWriter.php`](../includes/GatherPress/OnlineEventLinkWriter.php)
* [x] join_url 空 — 既存を消さない (Writer 側)
* [x] Webhook `ended` — 公開欄を空にしない ([`WebhookController.php`](../includes/Rest/WebhookController.php))

#### Test (wp-env)

* [x] Test: wp-env — `gatherpress_online_event_link` 更新 ([`OnlineEventLinkIntegrationTest.php`](../tests/Integration/OnlineEventLinkIntegrationTest.php))

### Webhook {#status-webhook}

#### プラグイン実装

* [x] REST `POST …/webhook/zoom` ([`WebhookController.php`](../includes/Rest/WebhookController.php))
* [x] URL validation challenge
* [x] `webinar.started` / `webinar.ended` — `_s2j_webinar_session.phase`
* [x] 設定画面 URL / 秘密 — [oauth 設定](#status-oauth)

#### Test (PHPUnit / smoke)

* [x] Test: 署名検証 — fixture payload ([`WebhookControllerTest.php`](../tests/WebhookControllerTest.php))

### Survey 引き渡し {#status-survey}

* [x] `ready` のみ `survey_document` ([`SyncContextBuilder.php`](../includes/Sync/SyncContextBuilder.php))
* [x] レコード本体に Survey を載せない ([`RecordAssembler.php`](../includes/Record/RecordAssembler.php))
* [x] 通常 sync に plan 任せ (専用ボタンなし)

### フロント・ lint {#status-frontend}

* [x] Vite4 エントリ / 本番 `dist/`
* [x] `npm run lint` — TS + SCSS
* [x] CI: [`.github/workflows/js-lint.yml`](../.github/workflows/js-lint.yml)
* [x] 設定 UI — PHP のみ (GP Webinar タブ + **設定 → S2J Webinar**。admin JS フッター二重化なし)
* [x] Gutenberg — メタ REST 双方向 ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] `@wordpress/core-data` — `useEntityProp`
* [x] TS shims — [`src/types/wordpress-shims.d.ts`](../src/types/wordpress-shims.d.ts)
* [x] i18n — `load_plugin_textdomain`、`languages/s2j-webinar-ja.mo`、`wp_set_script_translations`、`npm run makepot` (PHP は `'s2j-webinar'` リテラルで POT 抽出)

### アンインストール {#status-uninstall}

* [x] 明示メタ / option 削除
* [x] Zoom 上 Webinar は削除しない

### 品質・スモーク {#status-quality}

#### Test (自動・ smoke) — Local 可のみ

* [x] 段階3a — `composer test:unit` / CI [`phpunit.yml`](../.github/workflows/phpunit.yml) (`npm run test:unit:php:unit-only`、**22** tests)
* [x] 段階1— [`tests/smoke-local.sh`](../tests/smoke-local.sh) (Local / Studio `WP_PATH`)
* [x] 段階2— [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) + [`studio/README.md`](../studio/README.md)
* [x] `.wp-env.json` + [`phpunit.wp-env.xml.dist`](../phpunit.wp-env.xml.dist) (任意。日常3b は MySQL)
* [x] CI — js-lint / docs-lint / dist-zip / phpunit (3a only)
* [x] ローカル **`npm run lint:php`** — PHPStan + PHPCS + WPCS (`composer lint:php`、`prelint:php` で dev 依存)
* [x] 段階3b — `npm run test:unit:php:local` green (**2026-10-17** — unit **22** (`55` assertions) + integration **6** (`30` assertions)。`.env.tests` + GatherPress)

#### Test (記録) — Local 可のみ

* [x] [test-results.md](./test-results.md) — localhost 可能 / 不可能の表、[§推奨実施順](./test-results.md#推奨実施順-source-qr-同型localhost-自動テスト優先)
* [x] 段階1 PASS — Local + Studio smoke (2026-10-14: CLI、**2026-10-17** 手動 #1–8)
* [x] 段階3b ローカル — [test-results.md §最新結果](./test-results.md#最新結果) **2026-10-17** 行 (**22 + 6**)
* [x] 手動 — 日本語ロケールで管理 UI / Gutenberg 翻訳表示 (2026-10-13)
* [ ] Blueprint **ゼロから** Studio 再現の Pass 記録 ([test-results.md §最新結果](./test-results.md#最新結果) 未記録行 — Source QR 同型)

#### Test (外部結合・連携) — 要 HTTPS / Local 可 (E*)

* [ ] Service [§プラグイン](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/status.md#status-plugin) — 本プラグイン 100% 到達後に Service 側を [x]
* [ ] [E1–E4](#status-external) — Zoom OAuth / 実 API / Webhook / Survey attach (Pass は [test-results.md](./test-results.md))
* [ ] Source QR [§品質 E2](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md#status-quality) — [P2a](#status-operational) 定義 REST 実装後、localhost で `catalogReady` 連携 Pass

### 運用素材 {#status-operational}

正本: [operational_material_index.md](./operational_material_index.md) §実装優先順位

| 優先 | 項 | Marketplace / HTTPS | 理由 |
| --- | --- | --- | --- |
| **P1** | CoverArt / フライヤー | 不要 | 接続先 HTTP なし。パネル + メタのみ。Local で完結 |
| **P2a** | ソース定義 REST (`tracking-source-definitions`) | 不要 | 接続先 HTTP なし。Source QR 行の正本 |
| **P2b** | tracking 取り込み REST (`tracking-sources`) | **GET のみ** (作成はポータル) | [s2j/webinar-service](https://github.com/stein2nd/s2j-webinar-service) 写像は Packagist 済み。実 Zoom GET は E2 後 |
| **P3** | 配配メール (ラクス) | — | v0.0.2 **対象外** ([operational_material_index.md](./operational_material_index.md)) |

* [ ] **P1** — CoverArt / フライヤー ([coverart_flyer_spec.md](./coverart_flyer_spec.md))
* [ ] **P2** — Source QR / tracking REST (定義 → 取り込みの順)
* [ ] **P3** — 配配メール (ラクス) — 別途 (status 100% 外)

## 外部結合ゲート {#status-external}

**100% 分母外。** Pass は [test-results.md](./test-results.md) に記録。

**Local 可 / 要 HTTPS:** **Local** は Local / Studio の `http://localhost` または `http://127.0.0.1:<port>` (Zoom から到達不可) で **ゲートとして Pass できるか**。**要 HTTPS** は公衆 HTTPS (ステージング、ngrok / CloudFlare Tunnel 等) が必要。同一 PC 上の Local + トンネルは **要 HTTPS** 側で実施する ([test-results.md](./test-results.md))。

| # | 内容 | Local 可 / 要 HTTPS |
| --- | --- | --- |
| E1 | ステージング Zoom — OAuth connect | **要 HTTPS** (`127.0.0.1` / HTTP は Zoom `4700` エラー・ Allow list 不可。Client 保存・ Connect 試行まで Local 可) |
| E2 | create / update / get / delete 実 API | **要 HTTPS** (E1完了後。未接続時の REST 到達は段階1、実 API 成功は本ゲート) |
| E3 | Webhook — Zoom ダッシュボードから started / ended | **要 HTTPS** (Event Subscriptions。Local では未設定でよい — [webhook_spec.md](./webhook_spec.md)) |
| E4 | Survey プラグイン `ready` → attach_survey 実 Zoom | **要 HTTPS** (E1・ E2後。Survey 保存・ UI は Local 可) |
| E5 | フロント — 参加 URL 目視 | **Local 可** (`_s2j_webinar_join_url` / 公開欄が入っていること。E2またはテスト用メタ) |
| E6 | 運用素材 | **項目依存** (プラグイン UI / REST のみ Local 可。外部配信・本番ドメインは **要 HTTPS**) |
| E7 | Source QR — 定義 REST `200` + `sources.length >= 1` → `catalogReady` | **Local 可** ([P2a](#status-operational) 実装後。仕様 [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md)。Source QR 側 [E2](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md#status-external) / [順4 E1–E2](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/test-results.md#順4-オプション-local-可-e2e4)) |

## 補足

* **実装％** はプラグイン検証可能項のみ (上記)。Zoom ステージングは Pass 記録があっても % に含めない。
* 初版プロバイダ slug は `zoom` のみ ([`WebinarProvider`](../includes/Provider/WebinarProvider.php))。UI 文言は `%s` / `providerLabel` で差し替え。
* HTTP Adapter は本プラグイン、計算は Service ([plugin_spec.md](./plugin_spec.md))。

## 実装ロードマップ (到達順)

1. ~~フロント・ lint~~ → ~~メタ・レコード~~ → ~~同期~~ → ~~OAuth~~ → ~~get/delete~~ → ~~Survey~~ → ~~管理 UI~~ → ~~公開リンク / Webhook テスト~~ → [§推奨テスト実施順](#status-test-order) **順0–2** (3a **22**、`lint:php`、3b **6** — **2026-10-17**) → **順3** 手動 #1–8 PASS → **P1 / P2** (Source QR [status](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md)) → **E1–E4** (HTTPS) / **E7** (定義 REST)
