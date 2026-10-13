# S2J Webinar - 実装状況

最終更新: 2026-10-14

索引は [specs.md](./specs.md) です。計算規則の正本は [Webinar Service の `docs/`](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/specs.md) です。

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [plugin_spec.md](./plugin_spec.md) — 統合見取り図
* [sync_execution_spec.md](./sync_execution_spec.md) / [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) / [admin_ui_spec.md](./admin_ui_spec.md)
* [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md) / [webhook_spec.md](./webhook_spec.md) / [survey_handoff_spec.md](./survey_handoff_spec.md)
* [governance/documentation_governance.md](./governance/documentation_governance.md) — ドキュメント運用
* [archive/README.md](./archive/README.md) — 完了イニシアチブ (まだなし)

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
| テスト・CI (段階1–3) | 一部 | 83 | [§品質・スモーク](#status-quality) (10/12) |
| 運用素材 | 未着手 | 0 | [§運用素材](#status-operational) (0/3) |

## 100% 完了条件チェックリスト

各§は **プラグイン実装 → Test (PHPUnit / smoke) → Test (wp-env)** の順。Zoom / 手動は [§外部結合ゲート](#status-external)。

### 仕様分割 {#status-spec-docs}

* [x] 正本が `docs/`、大きな改訂案は `docs_mod/` ([governance/documentation_governance.md](./governance/documentation_governance.md))
* [x] CI: `docs-lint` ([`.github/workflows/docs-lint.yml`](../.github/workflows/docs-lint.yml))

### Composer・配布 {#status-composer-dist}

* [x] `composer.json` — Packagist 名のみで `s2j/webinar-service`
* [x] `s2j-webinar.php` — `vendor/autoload.php`
* [x] Test: [`ServiceBridgeTest.php`](../tests/ServiceBridgeTest.php)
* [x] 配布 zip — `vendor/` + `dist/`
* [x] CI: [`.github/workflows/dist-zip.yml`](../.github/workflows/dist-zip.yml)
* [x] Survey メタ除外 — `uninstall.php`

### OAuth・設定 {#status-oauth}

正本: [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)

#### プラグイン実装

* [x] **設定 → S2J Webinar** — GatherPress 依存状態、GatherPress 内 Webinar タブ表示 ON/OFF、Webinar provider ([`SettingsPage.php`](../includes/Admin/SettingsPage.php))
* [x] **GatherPress 設定 → Webinar** — クライアント ID/秘密鍵、リダイレクト URI、Webhook 秘密 / URL、接続状態、Save / Connect %s ([`SettingsTab.php`](../includes/GatherPress/SettingsTab.php))
* [x] タブ OFF 時 — GP から Webinar タブ非表示 (ON で復帰) — `s2j_webinar_show_gatherpress_settings_tab`
* [x] プロバイダー表示名 — [`WebinarProvider.php`](../includes/Provider/WebinarProvider.php)（初版 `zoom` のみ。管理・通知・ガイドは `%s` msgid）
* [x] connect / callback / disconnect ([`OAuthFlow.php`](../includes/OAuth/OAuthFlow.php), [`TokenStore.php`](../includes/OAuth/TokenStore.php))
* [x] Webinar REST — Bearer ([`ZoomHttpClient.php`](../includes/Http/ZoomHttpClient.php))
* [x] 期限前 refresh — `ensure_access_token()` ([`ZoomHttpClient.php`](../includes/Http/ZoomHttpClient.php))
* [x] OAuth token / refresh — Basic + form body (材料 URL 前置なし)
* [x] 接続アカウント email — 保存・表示 ([`TokenStore::persist_token_response`](../includes/OAuth/TokenStore.php))

#### Test (PHPUnit / smoke)

* [x] Test: [`ErrorRedactionTest.php`](../tests/ErrorRedactionTest.php) — トークン様文字列を `last_error` 経路で除去
* [x] Test: [`ZoomHttpClientOAuthTest.php`](../tests/ZoomHttpClientOAuthTest.php) — refresh Header / Content-Type (モック `wp_remote_request`)

### 管理 UI {#status-admin-ui}

正本: [admin_ui_spec.md](./admin_ui_spec.md)

#### プラグイン実装

* [x] Slot Fill **Webinar** ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] 未接続時の誘導 — `%s is not connected…` + `providerLabel` ([`AssetLoader.php`](../includes/Admin/AssetLoader.php))
* [x] 録画 / 参加登録 / 登壇者 — `useEntityProp` + REST メタ
* [x] **同期** / **Open in %s** / **Delete from %s** (確認 `Remove this webinar from %s?`)
* [x] 登壇者0人 — Sync 無効
* [x] REST — sync / open / delete ([`SyncController.php`](../includes/Rest/SyncController.php), [`EventActionsController.php`](../includes/Rest/EventActionsController.php))
* [x] 設定画面分割 — [admin_ui_spec.md](./admin_ui_spec.md)（GP タブに GatherPress 状態を出さない）

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
* [x] 不足 — i18n + プロバイダー名 (`%s`) ([`DeficiencyMessages.php`](../includes/Messages/DeficiencyMessages.php))
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

### フロント・lint {#status-frontend}

* [x] Vite 4 エントリ / 本番 `dist/`
* [x] `npm run lint` — TS + SCSS
* [x] CI: [`.github/workflows/js-lint.yml`](../.github/workflows/js-lint.yml)
* [x] 設定 UI — PHP のみ (GP Webinar タブ + **設定 → S2J Webinar**。admin JS フッター二重化なし)
* [x] Gutenberg — メタ REST 双方向 ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] `@wordpress/core-data` — `useEntityProp`
* [x] TS shims — [`src/types/wordpress-shims.d.ts`](../src/types/wordpress-shims.d.ts)
* [x] i18n — `load_plugin_textdomain`、`languages/s2j-webinar-ja.mo`、`wp_set_script_translations`、`npm run makepot`（PHP は `'s2j-webinar'` リテラルで POT 抽出）

### アンインストール {#status-uninstall}

* [x] 明示メタ / option 削除
* [x] Zoom 上 Webinar は削除しない

### 品質・スモーク {#status-quality}

* [x] 段階3a — `composer test` + CI PHPUnit
* [x] 段階1 — [`tests/smoke-local.sh`](../tests/smoke-local.sh)
* [x] 段階2 — [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json)
* [x] `.wp-env.json` + [`phpunit.wp-env.xml.dist`](../phpunit.wp-env.xml.dist)
* [x] CI — js-lint / docs-lint / dist-zip / phpunit
* [x] Test: 純 PHPUnit 18 件 ([`tests/`](../tests/)、`Integration` 除外)
* [x] 手動 — 日本語ロケールで管理 UI / Gutenberg 翻訳表示（2026-10-13 確認）
* [ ] 段階3b — `npm run test:unit:php` green (wp-env)
* [ ] 段階1–2 — [test-results.md](./test-results.md) Pass 記録（段階1・2 自動 smoke: 2026-10-14 PASS。手動 checklist 未）
* [ ] Service [§プラグイン](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/status.md#status-plugin) — 本プラグイン 100% 到達後に Service 側を [x]

### 運用素材 {#status-operational}

* [ ] Source QR / tracking REST
* [ ] CoverArt / フライヤー
* [ ] 配配メール (ラクス) — 別途

## 外部結合ゲート {#status-external}

**100% 分母外。** Pass は [test-results.md](./test-results.md) に記録。

| # | 内容 |
| --- | --- |
| E1 | ステージング Zoom — OAuth connect |
| E2 | create / update / get / delete 実 API |
| E3 | Webhook — Zoom ダッシュボードから started / ended |
| E4 | Survey プラグイン `ready` → attach_survey 実 Zoom |
| E5 | フロント — 参加 URL 目視 |
| E6 | v0.0.2 運用素材 |

## 補足

* **実装％** はプラグイン検証可能項のみ (上記)。Zoom ステージングは Pass 記録があっても % に含めない。
* 初版プロバイダー slug は `zoom` のみ ([`WebinarProvider`](../includes/Provider/WebinarProvider.php))。UI 文言は `%s` / `providerLabel` で差し替え。
* HTTP Adapter は本プラグイン、計算は Service ([plugin_spec.md](./plugin_spec.md))。

## 実装ロードマップ (到達順)

1. ~~フロント・lint~~ → ~~メタ・レコード~~ → ~~同期~~ → ~~OAuth~~ → ~~get/delete~~ → ~~Survey~~ → ~~管理 UI (status / last_error)~~ → ~~公開リンク / Webhook テスト~~ → **品質 (wp-env 緑)** → **外部結合ゲート (test-results)**
