# S2J Webinar - CHANGELOG

## unreleased

## 0.0.1 - 2026-10-30

### Changed

* npm (dev) — `rollup` / `sass` / `@typescript-eslint/*` / `@s2j/docs-linter` / `autoprefixer` / `@types/node` 等パッチ更新 ([`package.json`](package.json))
* [`composer.lock`](composer.lock) — 再生成 (Composer `published-time` メタデータ)

## 0.0.1 - 2026-10-27

### Added

* i18n — 空 `msgstr` を訳マップから埋める CLI [`tools/i18n/fill-empty-msgstr.php`](tools/i18n/fill-empty-msgstr.php) + [`tools/i18n/maps/`](tools/i18n/maps/) (コンパニオン文言など)

### Fixed

* i18n (Gutenberg) — `languages/s2j-webinar-ja.po` の Gutenberg 訳を復元し、JED ([`723569…`](languages/s2j-webinar-ja-723569a0885fd39d6ab6fb899e406bf0.json)) を再生成 (Document Settings の日本語表示)
* i18n ツール — `fill-empty-msgstr.php` の CLI 引数を `$_SERVER['argv']` 経由に変更 (IDE / 静的解析の `$argc` 未定義警告を解消)

### Changed

* 仕様 — Gutenberg i18n (`makei18n` / 空 `msgstr` / `update-po` / fill ツール)、**`-settings.js`** 命名、コンパニオン案内 — [`admin_ui_spec.md`](docs/admin_ui_spec.md)、[`oauth_and_settings_spec.md`](docs/oauth_and_settings_spec.md)、[`status.md`](docs/status.md)、[`README.md`](README.md)。[wp-plugin-spec §2.3](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/I18N_AND_A11Y_TEMPLATE.md#23-gutenberg--管理-js-wp_set_script_translations) と Survey / Source QR 側ドキュメントを同期

## 0.0.1 - 2026-10-26

### Added

* コンパニオン — [`CompanionStatus.php`](includes/Companion/CompanionStatus.php) (Source QR / Survey の installed、active 判定)。**Settings → S2J Webinar** に任意プラグイン案内 ([`SettingsPage.php`](includes/Admin/SettingsPage.php)、[README.md](README.md))

### Fixed

* Gutenberg — Document Settings パネルを **postId / postType 確定後** にだけ描画 (`useEntityProp` 前ガード)。GatherPress v0.36.x で設定サイドバー展開時の React クラッシュ回避 ([`src/gutenberg/index.tsx`](src/gutenberg/index.tsx))
* Gutenberg ビルド — `@wordpress/block-editor` → **`wp.blockEditor`** ([`vite.config.ts`](vite.config.ts))、依存 **`wp-block-editor`** ([`AssetLoader.php`](includes/Admin/AssetLoader.php)) — CoverArt `MediaUpload` 実行時 undefined 修正
* Gutenberg — **`getEditedEntityRecord`** 同期までロード表示。CoverArt / フライヤー — `core` **`getMedia`** 未提供時ガード
* Source QR — [`Handoff::is_companion_active()`](includes/SourceQr/Handoff.php) を `CompanionStatus` に集約
* 統合テスト — [`IntegrationTestCase`](tests/Integration/IntegrationTestCase.php) (PHPUnit v11 `checkRequirements` 非推奨警告の抑制)。[`RestRoutesTest`](tests/Integration/RestRoutesTest.php) / [`GatherPressEventTestCase`](tests/Integration/GatherPressEventTestCase.php) が継承
* TypeScript — `@wordpress/block-editor` / `@wordpress/data` shim 分割 ([`src/types/shims/`](src/types/shims/)、[`tsconfig.json`](tsconfig.json))
* i18n — `makejson` を **purge デフォルト**に変更 (`makejson:keep` = `--no-purge` のみ)。管理 JS 出力を **`s2j-webinar-settings.js`** に改名 (`-admin.js` の `make-json` 誤マッチ回避)。孤児 JED 削除、PO/POT/JED 更新。[wp-plugin-spec §2.3](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/I18N_AND_A11Y_TEMPLATE.md#23-gutenberg--管理-js-wp_set_script_translations)
* i18n (Gutenberg) — **`languages/*-ja.po` の Gutenberg 訳欠落**で JED がほぼ空になり Document Settings が英語表示だった問題を修正 (`wp i18n update-po` + 訳復元、`makejson` で JED 再生成)。Survey / Source QR も同様に Gutenberg 向け **ja.po / JED** を整備

### Changed

* **PHP 下限 v8.3** — [WordPress 動作環境](https://ja.wordpress.org/about/requirements/) に合わせ [`composer.json`](composer.json) (`>=8.3` / platform **8.3.0**)、[`s2j-webinar.php`](s2j-webinar.php) / [`readme.txt`](readme.txt)、[`studio/smoke-test-blueprint.json`](studio/smoke-test-blueprint.json)、[`.wp-env.json`](.wp-env.json)、[`composer.lock`](composer.lock)
* **CI** — Actions PHP **8.3** ([`phpunit.yml`](.github/workflows/phpunit.yml)、[`dist-zip.yml`](.github/workflows/dist-zip.yml))。lint workflows — [`composer validate`](.github/workflows/docs-lint.yml) (PHPUnit は **`test:unit:php:unit-only`**。`phpunit:wp-env` 非使用、Setup Node 先行)
* **npm (dev)** — `@wordpress/*` メジャー、`vite` / `eslint` / `rollup` / `@s2j/docs-linter` 等 ([`package.json`](package.json))
* Vite — 管理ビルド target を **`settings`** に (`npm` スクリプトは `build:*:admin` のまま、`endsWith(':admin')` で判定)
* `.gitignore` — JED (`languages/*-{32hex}.json`) はコミット対象である旨を明記
* ドキュメント — [overview.md](docs/overview.md)、[plugin_spec.md](docs/plugin_spec.md)、[status.md](docs/status.md)、[admin_ui_spec.md](docs/admin_ui_spec.md)、[README.md](README.md)、[studio/README.md](studio/README.md)、[test-results.md](docs/test-results.md)

## 0.0.1 - 2026-10-19

### Added

* **P2b** — `GET /s2j-webinar/v1/events/{id}/tracking-sources` ([`TrackingSourcesController.php`](includes/Rest/TrackingSourcesController.php)、[`TrackingSourcesImport.php`](includes/SourceQr/TrackingSourcesImport.php)) — Service build/map + [`WebinarHttpExecutor`](includes/Http/WebinarHttpExecutor.php) モック可。OAuth 未接続 **503** `oauth_disconnected`
* **P1** — CoverArt / フライヤー — メタ [`PostMetaRegistrar`](includes/Meta/PostMetaRegistrar.php)、検証 [`OperationalMaterialMeta.php`](includes/Meta/OperationalMaterialMeta.php)、Gutenberg `MediaUpload` ([`src/gutenberg/index.tsx`](src/gutenberg/index.tsx))
* Test — 統合 **14** 件 ([`TrackingSourcesRestTest.php`](tests/Integration/TrackingSourcesRestTest.php)、[`OperationalMaterialMetaRestTest.php`](tests/Integration/OperationalMaterialMetaRestTest.php))。**3a+3b** = **22 + 14** (`npm run test:unit:php:local`、wp-env 不要)
* ローカル3b — [`bin/patch-wp-tests-phpunit11.php`](bin/patch-wp-tests-phpunit11.php) ([`bin/phpunit-local.sh`](bin/phpunit-local.sh) から実行。PHPUnit v11 + WP テスト lib)
* Smoke — [`tests/smoke-local.sh`](tests/smoke-local.sh) に `tracking-sources` ルート。`.env.tests.example` — Studio **: 8888** 向け `WP_TESTS_GATHERPRESS_DIR` / `STUDIO_WP_PATH` 例

### Changed

* CoverArt — MIME / サイズ検証を **`update_post_metadata`** に移行 (コア REST 経由のパネル保存を含む)
* i18n — 取り込み REST 失敗文を **`%s` + [`WebinarProvider::label()`](includes/Provider/WebinarProvider.php)** に統一 (msgid から Zoom 直書き除去)。CoverArt / フライヤー UI 文言 — POT / [`languages/s2j-webinar-ja.po`](languages/s2j-webinar-ja.po)
* ドキュメント — 運用 **P1 + P2b** 完了に合わせ [status.md](docs/status.md)、[coverart_flyer_spec.md](docs/coverart_flyer_spec.md)、[source_qr_tracking_rest_spec.md](docs/source_qr_tracking_rest_spec.md)、[test-results.md](docs/test-results.md)、[cross_plugin_remaining_matrix.md](docs/cross_plugin_remaining_matrix.md) 等を更新

## 0.0.1 - 2026-10-18

### Added

* REST — `GET /s2j-webinar/v1/events/{id}/tracking-source-definitions` (P2a、Zoom デフォルト `web` / `sns` / `other` + `s2j_webinar_tracking_source_definitions` フィルター)
* Source QR handoff — Gutenberg「Edit source tracking / QR」→ `doAction( 's2j_webinar.open_source_qr', postId )` ([`Handoff.php`](includes/SourceQr/Handoff.php)、[`TrackingSourceDefinitions.php`](includes/SourceQr/TrackingSourceDefinitions.php))
* Gutenberg — **Source tracking / QR** 行 (`_s2j_webinar_source_qr` サマリー、Source QR 未有効時 Notice)
* [`AssetLoader.php`](includes/Admin/AssetLoader.php) — `s2jWebinarConfig.sourceQrActive` / `sourceQrOpenHook`
* Test — [`TrackingSourceDefinitionsRestTest.php`](tests/Integration/TrackingSourceDefinitionsRestTest.php) (統合 **8** 件)。[`tests/smoke-local.sh`](tests/smoke-local.sh) — 定義 REST ルート

### Fixed

* TypeScript — Gutenberg 向け `@wordpress/edit-post` / `@wordpress/editor` / `@wordpress/data` shim (`src/types/shims/`、`tsconfig.json` paths)
* Gutenberg ビルド — エディター bundle に `wp-hooks` 依存 (handoff `doAction`)

* [`OnlineEventLinkWriter`](includes/GatherPress/OnlineEventLinkWriter.php) — GatherPress `Event::set_online( true, $join_url )` (1引数のみだと link 空で公開欄 meta が更新されない)
* Gutenberg — 登壇者 `_s2j_webinar_panelists` を `editPost({ meta })` 経由で REST 保存 (`useEntityProp` はスカラ meta のみ)
* Gutenberg ビルド — Vite JSX shim: `key` を `props.key` に (`panelist-0` 等の DOM 露出)
* Studio Blueprint — wp.org `gatherpress-alpha` step 削除 (404)

### Changed

* ドキュメント — P2a / handoff 仕様 §実装、[status.md](docs/status.md) / [test-results.md](docs/test-results.md) **E7** Pass (2026-10-19、Studio `:8888` post **8**、P2a 本番ルート + Source QR `catalogReady` / Save draft / Apply。証跡 Source QR [studio-2026-1019-e7-real-p2a](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/screenshots/studio-2026-1019-e7-real-p2a/) **01–09**)、[cross_plugin_remaining_matrix.md](docs/cross_plugin_remaining_matrix.md) 3リポジトリ同期

* Gutenberg — 登壇者行 (Panelist N / Remove panelist、[`gutenberg.scss`](src/styles/gutenberg.scss))
* i18n — 登壇者 UI 文言 (`languages/s2j-webinar-ja.po` / `.mo` / JED)
* Studio — Alpha 手動での導入手順 ([studio/README.md](studio/README.md))
* ドキュメント — Gutenberg 保存経路、Blueprint **2026-10-18** Pass ([admin_ui_spec.md](docs/admin_ui_spec.md)、[status.md](docs/status.md)、[test-results.md](docs/test-results.md)、[cross_plugin_remaining_matrix.md](docs/cross_plugin_remaining_matrix.md) 他)

## 0.0.1 - 2026-10-17

### Added

* テスト — `WebinarServicePipelineTest` (Marketplace 不要の Service validate/plan/build)、`SyncRestServiceIntegrationTest` (REST sync + モック HTTP)。ローカル通し **unit: 22 + integration: 6** (`npm run test:unit:php:local`)
* ローカル PHPUnit (wp-env 非必須): `bin/phpunit-local.sh`、`bin/install-wp-tests.sh`、`phpunit.unit.xml.dist`、`tests/bootstrap.integration.php`、`.env.tests.example` (`tests/bootstrap.wp-env.php` 廃止)
* `.gitignore` — ローカル `.env.tests` (MySQL / `WP_TESTS_GATHERPRESS_DIR`)
* dev — `yoast/phpunit-polyfills`。静的解析 — `phpcs.xml.dist` / `phpcs.wpcs.xml.dist` / `phpcs.wpcs-includes.xml.dist` / `phpstan.neon.dist`、`phpstan-bootstrap.php`、`composer lint:php`
* npm — `makemo` / `makejson` / `makei18n`、`postbuild:production:gutenberg` (本番 Gutenberg ビルド後に JED 再生成)

### Changed

* Gutenberg — Document Settings に **S2J Webinar** トップレベルパネル (`PluginDocumentSettingPanel`、GatherPress Fill から変更)。見出し **S2J Webinar**、フィールド見出しを `h3.s2j-webinar-field__title` に統一。レコーディング `SelectControl` は `PanelRow` 外 (日本語ラベル崩れ回避)
* Gutenberg ビルド — Vite で `@wordpress/edit-post` → global `wp.editPost`、minify 後も `PluginDocumentSettingPanel` を `s2jWebinarDocumentSettings.panelComponent` 経由で参照 (`vite.config.ts` / `src/gutenberg/index.tsx`)。エディター bundle 依存に `wp-edit-post`
* `composer test` / `npm run test:unit:php:unit-only` — 純ユニットのみ。MySQL 統合は `npm run test:unit:php:local`。GitHub Actions PHPUnit は段階 **3a** (`unit-only`) のみ
* `package.json` — MediaLibrary Date Corrector 同型の PHPUnit npm スクリプト (`pretest:unit:php:local` で `composer install`)
* **`includes/`** — PSR-12整形 (`phpcbf`)。WPCS は bootstrap PHP + `includes` i18n / Security 分割
* `composer.json` — `test:integration` / `test:all` / `lint:php`
* PHPStan — `ServiceBridge::build_request()`、GatherPress / WP スタブ (`stubs/gatherpress.php`、`stubs/wordpress/…` 等)
* ドキュメント — [admin_ui_spec.md](docs/admin_ui_spec.md) / [gatherpress_boundary_spec.md](docs/gatherpress_boundary_spec.md) (パネル配置)、[operational_material_index.md](docs/operational_material_index.md) (Marketplace 不要な、実装の優先順位)、[README.md](README.md) / [test-results.md](docs/test-results.md) / [status.md](docs/status.md) (段階3a/3b、**22+6**、段階3b [x] 2026-10-17)
* ドキュメント — [test-results.md](docs/test-results.md) 段階1〜2手動 checklist Pass (2026-10-17。Local / Studio。OAuth Connect は checklist 項目6として SKIP)。[status.md](docs/status.md) §品質・スモーク 段階1–2 [x] (残: Service §プラグイン相互 `[x]`)
* ドキュメント — [operational_material_index.md](docs/operational_material_index.md) §実装優先順、[status.md](docs/status.md) §運用素材 優先表 (CoverArt → tracking 定義/取り込み REST → 配配メールは v0.0.2対象外)
* ドキュメント — [status.md](docs/status.md) / [test-results.md](docs/test-results.md) を [Source QR](https://github.com/stein2nd/s2j-webinar-source-qr) 同型 (§推奨テスト順、Docker なし表、品質 §分割、外部結合 **E7**、`lint:php` Pass)。[provider_setup_guides.md](docs/provider_setup_guides.md) 見出しを `S2J Webinar - …` に統一
* i18n — Gutenberg パネル **S2J Webinar** に合わせ `languages/*-{hash}.json` / `s2j-webinar-ja.mo` を再生成 (旧 msgid `Webinar` を廃止)

### Fixed

* PHPUnit **9.6** (WP テスト lib × PHPUnit v10非互換)。`phpunit.*.xml.dist` から v10専用 `cacheDirectory` 削除
* **GatherPress 日時** — `EventSchedule` を現行 GP (`GatherPress\Core\Event\Event`、`gatherpress_datetime_*` / JSON)
* **REST 統合** — `gatherpress_events`、`PUT` 更新、`PostMetaRegistrar` の post type 登録と tearDown 後の再登録 (`GatherPressEventTestCase`)
* **公開リンク** — GP `Event\Event` に `set_online` がない環境は `gatherpress_online_event_link` メタにフォールバック
* **設定 → S2J Webinar** — 保存時 `$_POST` を `sanitize_text_field` 経由 (WPCS)
* **統合テスト** — 日時 fixture を `gatherpress_datetime_*` メタ直書き (GP datetimes テーブル非依存)
* IDE — Intelephense の `DateTimeZone` (`stubs/php-internal.php`、`.vscode` `intelephense.stubs` に `date`)

## 0.0.1 - 2026-10-15

### Fixed

* Gutenberg イベント編集 — `_s2j_webinar_status` / `_s2j_webinar_last_error` の REST `auth_callback` を `can_edit_post_meta` に変更 (Source QR 結合テストで判明。`show_in_rest` + `__return_false` により下書き保存が失敗)
* [`Constants`](includes/Constants.php) — `OPTION_SHOW_GP_SETTINGS_TAB` を誤った `@return` docblock 配下から他 option 定数と同列に移動

### Changed

* `dependencies` — `react` / `react-dom` 19.3.1
* devDependencies を更新 (`ncu` / `npm install`。`@wordpress/env` **10→11**、`@s2j/docs-linter` 1.0.29、`@typescript-eslint/*` 8.71.2、`rollup` 4.64.7ほか)。`package-lock.json` 同期
* textlint の許可パターンに `#status-*` アンカー ID を追加

### セキュリティ (開発依存 - upstream 待ち)

最終確認: **2026-10-15** — `npm audit`: **23** 件 (moderate: 13/high: 8/critical: 2)、`npm audit --omit=dev`: **0**。配布 zip に npm `node_modules` は含めない (ビルド済み `dist/` を同梱)。`npm audit fix --force` は採用しない (破壊的ダウングレード)。

依存更新 (`ncu` / `npm install`) のたびに上記2コマンドを再実行し、件数と下記の解消状況をこの節で更新する。

* **`@wordpress/env`** (`wp-env` / `npm run test:unit:php`): transitive **`simple-git` / `@simple-git/argv-parser`** (critical、[GHSA-v5rq-49vh-5v5c](https://github.com/advisories/GHSA-v5rq-49vh-5v5c))、**`qs`** (moderate)、**`@wp-playground/cli`** 経由。実行は開発、CI のみ。**待ち**: [WordPress `@wordpress/env`](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) / Playground CLI の依存更新 (npm の自動 fix は `@wordpress/env@3` 提案のため不可)
* **`@s2j/docs-linter`** (`npm run lint:docs`): **`textlint-rule-prh` / `prh`**、プリセット (`textlint-rule-preset-wp-docs-ja` 等)、**`sprintf-js`** (moderate、npm 上 fix なし)。**待ち**: [docs-linter](https://github.com/stein2nd/s2j-docs-linter) および textlint プリセット側
* **`stylelint`** (`npm run lint:scss`): **`braces` / `micromatch` / `fast-glob` / `globby`** (high)。npm の自動 fix は stylelint v7ダウングレード提案のため不可。**待ち**: [stylelint/stylelint](https://github.com/stylelint/stylelint) の transitive 更新
* **`vite-plugin-static-copy`** (`npm run build:*` / `dist`): 同梱 **chokidar** 経由の **`braces`** (high)。npm の自動 fix は v0系ダウングレード提案のため不可。**待ち**: [vite-plugin-static-copy](https://github.com/sapphi-red/vite-plugin-static-copy) の chokidar 更新

## 0.0.1 - 2026-10-14

### Added

* [docs/provider_setup_guides.md](docs/provider_setup_guides.md) — GatherPress → Webinar と Zoom Marketplace の対応手順
* GatherPress **設定 → Webinar** タブ — **Provider setup guide** 外部リンク (`s2j_webinar_provider_setup_guide_url`)

### Fixed

* GatherPress **設定 → Webinar** —「Save settings」を `admin-post.php` 保存に変更 (`options.php` POST 時の wp_die 回避。**設定 → S2J Webinar** は Settings API のまま)
* **Connect Zoom** — `allowed_redirect_hosts` に `zoom.us` を追加 (`wp_safe_redirect` がダッシュボードにフォールバックしていた問題)
* OAuth **Redirect URI** — `localhost` を `127.0.0.1` に正規化、`s2j_webinar_oauth_redirect_uri` フィルター (ngrok / ステージング用)

### Changed

* ドキュメント — GP タブ `admin-post` 保存・ Connect / callback 経路、`redirect_uri` 正規化とフィルター、外部結合ゲート E1– E6を [oauth_and_settings_spec.md](docs/oauth_and_settings_spec.md)、[admin_ui_spec.md](docs/admin_ui_spec.md)、[data_dictionary.md](docs/data_dictionary.md)、[test-results.md](docs/test-results.md) 等に反映

* ドキュメント — 段階1–2で Webhook / OAuth Connect を後回しにできる旨 ([test-results.md](docs/test-results.md)、[webhook_spec.md](docs/webhook_spec.md)、[oauth_and_settings_spec.md](docs/oauth_and_settings_spec.md)、[studio/README.md](studio/README.md))

## 0.0.1 - 2026-10-13

### Added

* WordPress プラグイン初版スケルトン: `s2j-webinar.php`、`includes/` (設定、OAuth、メタ登録、レコード組立/dirty、`SyncRunner`、同期 REST)、`composer.json` + `s2j/webinar-service`
* 管理 UI: Vite + React (`dist/`)。サイト設定、GatherPress イベントパネル Fill (同期ボタン)
* テスト3段階: [docs/test-results.md](docs/test-results.md)、`tests/smoke-local.sh` (GatherPress 必須)、`.wp-env.json`、`studio/README.md` (Blueprint 未作成)
* `readme.txt`、`.textlintignore`、`phpunit.xml.dist`、TypeScript (`vite-env.d.ts`、`@wordpress/plugins` シム)

### Changed

* 配布 zip: `vendor/`・`languages/` を同梱。CI `dist-zip` で `composer install --no-dev` を先行
* PHPUnit CI: `npm run test:unit:php:local` (`composer test`)。`docs/status.md` に実装進捗を反映

## 0.0.1 - 2026-10-12

### Added

* 配布 zip: `tools/dist/make-dist-zip.mjs` (`npm run dist:zip`)。GitHub Actions で tag `v*` 時にビルド、zip アーティファクト
* GitHub Actions: `main` / PR で PHPUnit

### Changed

* 運用素材の仕様を `docs/` 正本に反映 (`operational_material_index.md`、`glossary_operational.md`、`coverart_flyer_spec.md`、`source_qr_handoff_spec.md`、`source_qr_tracking_definitions_rest_spec.md`、`source_qr_tracking_rest_spec.md`)。Source QR 向け REST は定義一覧と取り込み GET を分割。`specs.md` / `status.md` を更新、`docs_mod/v0.0.2/` を撤去し起草は [docs/archive/mod-v0-0-2-operational-material/](./docs/archive/mod-v0-0-2-operational-material/) に freeze

## 0.0.1 - 2026-10-11

### Added

* v0.0.2実装仕様の分割 (`docs_mod/v0.0.2/`): `glossary.md`、`coverart_flyer_spec.md` (draft 昇格)、`source_qr_handoff_spec.md` (パネル handoff、任意 GET 取り込み REST 最小契約)

### Changed

* v0.0.2索引: `coverart_flyer_draft` / `source_tracking_qr_draft` は参照メモ。Source QR と Service 取り込み (`tracking_import_spec`) は各リポジトリ `docs_mod` を正本候補にリンク
* 開発依存: `@wordpress/*`、`@typescript-eslint/*`、`vite`、`rollup` 等をマイナー更新

* v0.0.2起草 (`docs_mod/v0.0.2/`): プロバイダ一般化 (初実装 `zoom`、取り込みは `s2j-webinar-service` Adapter)。コンパニオン `s2j-webinar-source-qr` とメタ `_s2j_webinar_source_qr`、Webinar 側 handoff (token 正本・同期列に QR 文書を載せない) を合意
* `zoom` Source Tracking: リンク作成 REST 不可、`GET …/tracking_sources` 可。v0.0.2は接続先ポータル登録 + 手入力または Webinar 経由 GET 取り込み + Source QR で PNG 保存 (`s2j-webinar-source-qr-service` は見送り)
* CoverArt / フライヤー起草: 未登録は `delete_post_meta`、許可 MIME と上限 (画像5MiB / フライヤー20MiB)、検証失敗時はメタを前値のまま

## 0.0.1 - 2026-10-10

### Added

* v0.0.2運用素材の改訂案 (`docs_mod/v0.0.2/`): CoverArt / フライヤー (メディア紐付け・イベントパネル)、UTM 別 Source Tracking + QR (コンパニオン・プラグイン境界、OAuth 再利用方針)
* `docs_mod/README.md` に v0.0.2索引。見出しを「S2J Webinar - 起草用」に統一

### Changed

* OAuth HTTP をサービス契約に追随: Webinar REST は Bearer、token/refresh は Basic + フル URL の `path`、body は form-urlencoded
* `update` は `dirty` または `intend_retry` (error+id)。`last_panelists` は希望の登壇者列を丸ごと即時永続化し、列中断時も成功した op までの希望列を残す
* 同期手順1を「Survey は context の `survey_document` のみ」に修正。Authorization の粒度を oauth_and_settings に集約
* 確定仕様の表記を「とき→場合」にそろえた

## 0.0.1 - 2026-10-09

* 確定した仕様キットを `docs_mod/` から `docs/` に移した。`docs_mod/` は改訂案と進行中イニシアチブの起草用として残す。
* `README.md` の案内を [docs/specs.md](docs/specs.md) に切り替えた。
* `docs/` の Source of Truth とガバナンスを確定正本向けに更新し、`docs_mod/README.md` を起草用の説明に差し替えた。

### Added

* `docs_mod/` を Survey / Alliance / webinar-service に倣い分割起草した (索引、Why / What / How、ガバナンス、archive)
* メタ ↔ WebinarRecord、`dirty` / `synced`、`last_sent` / `last_panelists`、OAuth、同期実行、Webhook、Survey 受け渡しの境界を記録した
* `README.md` に仕様キット、依存 (`GatherPress` / `s2j/webinar-service`)、開発コマンド (`npm install` / `lint:docs`) を案内した

### Changed

* `docs_mod/specs.md`: メタキー ↔ WebinarRecord 対応表の正本は本プラグインであると明記。`dirty` 判定は `_s2j_webinar_last_sent` で本プラグインが行う。OAuth に `webinar:update:survey` を追加
* プロバイダはサービス側アダプタで差し替え可能とする。初版は `zoom` のみ。管理画面での選択は、実装済みが増えた場合に出す
* 参加登録のデフォルトは必須・自動承認 (`0`) にそろえる (サービス record_spec と同じ。2026-10-05記載の「不要」は破棄)
* `docs_mod/` 監査 BP を反映: アンインストールは所有キーの明示リスト (`_s2j_webinar_survey` 除外)、delete で `last_panelists` / セッション処理、get は `join_url` 反映可・`start_url` 非永続、`webinar.ended` は公開リンク非空、メタ保存は明示の投稿更新、`last_sent` は create/update map 成功直後、`intend_get` は「Zoom で開く」のみ、Webhook はプラグイン REST、Survey 添付は通常同期、用語を統一
* `docs_mod/` 再監査 BP を反映: 再取得は「Zoom で開く」と同義、`duration_minutes` は floor、`last_*` は map 成功直後に永続、`intend_retry` を admin UI に明記、get 写像範囲を固定、メタは個別キー、登壇者1人以上、トークン暗号化は後続
* `docs_mod/` 第3監査 BP を反映: get は書き込みと別経路 (`webinar_id` のみゲート)、メタ REST はパネル編集キーのみ、`join_url` 成功時は必須反映、登壇者0は UI でブロック、concept に即時永続の注記、`webinar_uuid` と `ready` 表記を統一
* `docs_mod/` 第4監査 BP を反映: get は `build_webinar_request( 'get', … )` のみ (`intend_get` plan 不使用)、REST 除外はカスタム REST / WP-CLI 等 (コア投稿 REST は許可)、登壇者 `[]` は投稿保存可、空の `join_url` 応答では既存を消さない、FOP を初出展開、配配メール (ラクス) と明記
* `docs_mod/` 第5監査 BP を反映: create / update の空 `join_url` も既存維持 (delete のみ空化)、usage の `intend_get` 例は非採用と明記、登壇者「1人以上」は同期ゲート、API 名は `build_webinar_request` に統一、`status.md` 後続に配配メール (ラクス)
* `docs_mod/` 第6監査 BP を反映: create / update の空 `webinar_uuid` も既存維持 (delete のみ空化)、`status.md` の get 完了条件にメタ、`sync_execution_spec` 冒頭コメントの API 名を `build_webinar_request` に統一
* `docs_mod/` 第7監査 BP を反映: `concept.md` 操作表と `specs.md` 読み方のパイプライン表記を正本 (`build_webinar_request` / get の uuid 非破壊) にそろえた
* `docs_mod/` 第8監査 BP を反映: `status.md` の get 完了条件に `webinar_uuid` 非空時のメタ更新を含めた

## 0.0.1 - 2026-10-08

* 確定前の仕様と変更履歴の表記をそろえた。「とき」を「場合」または「際」に、「次の」を「下記の」に直し、OAuth はユーザー管理アプリケーションと書く。

## 0.0.1 - 2026-10-07

* セッション中の Q&A は、作成で `settings.question_and_answer` の一式を送る。`enable` = `true`、`allow_anonymous_questions` = `true`、`answer_questions` = `only` である。コメントと upvote は送らない。
* HD は `settings.hd_video` = `false`、出席者の参加時認証は `settings.meeting_authentication` = `false` である。パネリスト認証とチャットのデフォルト対象は送らない。
* アンケート添付は `PATCH /webinars/{webinarId}/survey` で行う。写像はサービスが持つ。
* 初版の設問 `type` は `single` / `multiple` / `short_answer` / `long_answer` / `rating_scale` である。画像、スキップロジック、マッチング、ランク順、空欄に記入する、は初版以降の検討である。

## 0.0.1 - 2026-10-06

* 新規登録は二段階とする。ウェビナー ID は作成応答の `id` であり、発行は購読しない。その ID で、スケジュール後のタブの初期値を追加リクエストする。
* 参加登録のデフォルトは必須・自動承認とする。録画のデフォルトはクラウドである。トピックは200文字、説明は2000文字までとする。パスコードは送らない。
* オーディオはコンピューター、ホストとパネリストのカメラは off である。セッション中の Q&A は、使う、匿名可、出席者に見せるのは回答済みだけ、を一式で送る。
* 定員を超えそうな場合は、ライブストリームを別途検討する旨をパネルに出す。配信 URL とストリームキーは送らない。
* アンケート設問は S2J Webinar Survey が作る。本プラグインは、作成直後に使ってよい文書をアンケートとして付ける。

## 0.0.1 - 2026-10-05

* 確定前の仕様に、GatherPress の日時キー、所要時間、終日 (暦日数に1440分を掛けた所要時間) を記録した。
* OAuth はユーザー管理アプリケーションとし、アクセス・トークンは期限まで使い回す。戻り口は `admin-post.php` の固定パスである。
* イベントメタの接頭辞は `_s2j_webinar_` とする。参加登録はイベントごとのラジオで、デフォルトは不要である。
* 参加 URL の公開欄は `gatherpress_online_event_link` とし、開始と終了は `webinar.started` と `webinar.ended` で受ける。
* Zoom の削除はパネルの明示操作だけである。登壇者は外した人ごとに削除し、開始 URL は押した際に再取得する。
* セッション中の Q&A は初版では送らない。作成で省略してアカウント設定を継ぐのは、Zoom が公式に挙げた7項目だけである。
* Composer は Packagist の `s2j/webinar-service` だけを参照する。QR は後続の別プラグインが持つ。

## 0.0.1 - 2026-10-04

* 開発依存の `@s2j/docs-linter` を v1.0.27に更新した。
* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘は残す。配布物には含まれない。

## 0.0.1 - 2026-10-03

* `fsevents` と `@parcel/watcher` のインストールスクリプトを許可し、開発時のファイル監視でネイティブ実装を使えるようにした。
* 開発依存の `braces@3.0.3` に、深くネストしたパターンで Node.js プロセスが終了する指摘 (GHSA-vfj7-8cjw-p6xm) がある。修正版は未公開で、配布物には含まれない。
