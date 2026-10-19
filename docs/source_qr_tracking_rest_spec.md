# S2J Webinar - Source Tracking 取り込み REST

S2J Webinar 仕様 v0.0.2 (運用素材) です。handoff 全体は [source_qr_handoff_spec.md](./source_qr_handoff_spec.md)。ソース定義一覧は [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md)。写像は [s2j-webinar-service / tracking_import_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/tracking_import_spec.md)。

## 設計意図 (ゴール)

[S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr) の管理 UI から、**OAuth token を渡さず** 接続先のソース追跡リンク一覧を取得できる **本プラグイン専用 REST** を定義します。

* 呼び出し: Source QR → **本 REST** → Service (`build` / `map`) → Zoom GET → 正規化 `items[]` を返す。
* [同期実行仕様](./sync_execution_spec.md) の `plan` / `build_webinar_request` 列には **載せない** (集客素材用の読み取り専用経路)。

## 非対象

* `_s2j_webinar_source_qr` の読み書き (Source QR 所有)
* Webinar レコード `status` / `dirty` / `last_error` の更新 (本 REST では **触らない**。OAuth 失敗時も同期用 `last_error` には載せない)
* 接続先へのリンク **作成** (v0.0.2 `zoom`: ポータル運用。[operational_material_index.md](./operational_material_index.md))
* 匿名・外部システム向け公開 API (Cookie + REST nonce の編集者のみ)

## エンドポイント (初版)

初版は **GET のみ**。POST で取り込み結果をプッシュする形は採用しません (Source QR がパネル文書にマージする)。

| 項目 | 値 |
| --- | --- |
| Namespace | `s2j-webinar/v1` (Webhook 等と同じ命名。実装で既存定数に合わせてよい) |
| Method | `GET` |
| Route | `/events/(?P<post_id>[\d]+)/tracking-sources` |
| 完全 URL 例 | `/wp-json/s2j-webinar/v1/events/123/tracking-sources` |

## 認可

Source QR プラグインは **本プラグインが有効** であることを UI で前提とします。無効時は REST 自体が404になりえます (WordPress 標準)。

| チェック | 内容 |
| --- | --- |
| ログイン | 必須 (REST Cookie 認証 + `X-WP-Nonce`) |
| 投稿 | `post_id` が存在し、GatherPress **イベント** であること (`post_type_supports( …, 'gatherpress-event-date' )` 相当) |
| 権限 | `current_user_can( 'edit_post', $post_id )` |
| OAuth 接続 | サイト option に有効な access token (必要ならリフレッシュ)。未接続は [HTTP 応答](#http-応答-失敗) の `oauth_disconnected` |

## サーバー処理 (パイプライン)

同期列とは別の **読み取り専用** 列です。`validate_webinar_record` / `plan_webinar_operations` は **呼びません**。

1. post_id、権限、イベント post type を確認
2. `_s2j_webinar_id` (webinar_id) と `_s2j_webinar_provider` を読む
   * provider 未設定は `zoom` として扱う (レコード正規化と同型)
3. v0.0.2: provider が `zoom` 以外 → REST エラー `provider_import_not_supported` (501)。**プラグイン層** (取り込み UI は zoom のみ)
4. `build_tracking_sources_request( provider, webinar_id )` (Service)
   * 返却 `deficiencies` 非空 → [Service deficiency → REST](#service-deficiency--rest) に従い HTTP しない
5. トークンを解決 (有効期限内なら access、失効時は refresh)。失敗 → `oauth_disconnected`
6. 材料 `path` を API ベース URL に結合し、Bearer 付きで GET
   * Authorization 規則は [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) の Webinar REST 行
7. `map_tracking_sources_response( provider, status, body )` (Service)
8. 手順7の `deficiencies` を [Service deficiency → REST](#service-deficiency--rest) で解決
   * **`tracking_sources_empty` のみ** (単一要素) → **200**、`items: []` (接続先にリンク未登録)
   * それ以外で `deficiencies` 非空 → REST エラー
   * `deficiencies` 空 → **200** と `items` を返す

## Service deficiency → REST

[s2j-webinar-service / tracking_import_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/tracking_import_spec.md) の `deficiencies` を本 REST の HTTP / `code` に写す **正本** です。評価順は build (手順4) → map (手順7–8)。

| Service `deficiencies` (代表) | 発生 | HTTP | REST `code` | 備考 |
| --- | --- | --- | --- | --- |
| (空) | build / map | 200 | — | 成功 |
| `['tracking_sources_empty']` **のみ** | map | 200 | — | `items: []`。UI は空一覧または i18n |
| `tracking_webinar_id_empty` | build | 400 | `tracking_webinar_id_empty` | `_s2j_webinar_id` 空 |
| `provider_unsupported` | build / map | 501 | `provider_unsupported` | **Service** レジストリ未知 |
| `tracking_http_error` | map | 502 | `tracking_upstream_error` | 接続先非2xx |
| `tracking_body_invalid` | map | 502 | `tracking_upstream_error` | JSON / 形不正 |
| `provider_import_not_supported` | — | 501 | `provider_import_not_supported` | **プラグイン** v0.0.2: `zoom` 以外 (手順3。Service 未到達) |

**501の使い分け:** 手順3は **製品方針** (取り込み Adapter 未提供)。Service の `provider_unsupported` は **レジストリに provider 名がない** 場合。実装は **先に provider 正規化 → zoom 以外で501 (import)** → その後 Service を呼ぶ。

上記にない deficiency が返った場合は **502** `tracking_upstream_error` とし、ログにコードを残す (ユーザー向け `message` に列挙しない)。

## 成功レスポンス (200)

Content-Type: `application/json`

```json
{
  "items": [
    {
      "source_name": "web",
      "registration_url": "https://example.zoom.us/…",
      "external_id": "abc123"
    }
  ]
}
```

| フィールド | 型 | 説明 |
| --- | --- | --- |
| `items` | array | 正規化行。空配列可 ([glossary_operational.md](./glossary_operational.md) の `registration_url`) |
| `items[].source_name` | string | 接続先ソース名 (定義 REST の `source_name` と **完全一致** でマージ) |
| `items[].registration_url` | string | QR 用 URL (加工なし) |
| `items[].external_id` | string | 任意。Zoom `id` |

Zoom 生 JSON、token、`deficiencies` 生コードは **レスポンスに含めません**。

## HTTP 応答 (失敗)

WordPress REST 慣習に従い、`{ "code", "message", "data": { "status": N } }` 形とします。`message` は i18n 済みの **短い** ユーザー向け文です (生の Zoom body や deficiency 列挙を出さない)。

**プロバイダー表示名:** msgid に **接続先名を直書きしない**。[admin_ui_spec.md §i18n](./admin_ui_spec.md#i18n) と同型 — [`WebinarProvider::label()`](../includes/Provider/WebinarProvider.php) を `sprintf( __( '…%s…', 's2j-webinar' ), … )` の **`%s`** に渡す (サイト **設定 → S2J Webinar** の provider と一致)。代表例 ([`TrackingSourcesImport.php`](../includes/SourceQr/TrackingSourcesImport.php)):

| REST `code` | msgid (英語基本) |
| --- | --- |
| `oauth_disconnected` | `Connect %s in settings before loading tracking sources.` |
| `tracking_upstream_error` | `Tracking sources could not be loaded from %s. Try again later.` |

| HTTP | `code` | 発生層 | 条件 |
| --- | --- | --- | --- |
| 403 | `rest_forbidden` | コア | 権限不足 (コアに委譲可) |
| 404 | `rest_post_invalid_id` | コア | 投稿不存在 (コアに委譲可) |
| 400 | `rest_invalid_post_type` | プラグイン | イベント post type でない |
| 400 | `tracking_webinar_id_empty` | Service → プラグイン | `_s2j_webinar_id` が空 (Webinar 未作成) |
| 501 | `provider_import_not_supported` | **プラグイン** | v0.0.2: `zoom` 以外の provider (手順3) |
| 503 | `oauth_disconnected` | プラグイン | トークンなし / refresh 失敗 |
| 502 | `tracking_upstream_error` | Service → プラグイン | `tracking_http_error` または `tracking_body_invalid` (および未マップ deficiency) |
| 501 | `provider_unsupported` | **Service** | `provider_unsupported` (レジストリ未知) |

ログ (サーバー) には HTTP status、deficiency コード、post_id を残してかまいません。**token と応答 body 全文はログに出しません** ([oauth_and_settings_spec.md](./oauth_and_settings_spec.md))。

## クライアント (Source QR)

本プラグインのイベントパネル JS から呼ぶ必要は、**初版ではありません** (入口は Source QR のみ)。

| 項目 | 方針 |
| --- | --- |
| API | `@wordpress/api-fetch` (`apiFetch`) |
| 呼び方 | `GET path: '/s2j-webinar/v1/events/' + postId + '/tracking-sources'` |
| エラー | `code` から i18n メッセージ。詳細は [Source QR admin_ui_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/admin_ui_spec.md) |
| マージ | 返却 `items` を [Source QR persistence_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/persistence_spec.md) の規則でパネル文書に反映 |
| 保存 | マージ後は Source QR の `POST /s2j-webinar-source-qr/v1/events/{post_id}/document/draft` ([Source QR rest_api_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/rest_api_spec.md)) |

## OAuth スコープ (実装時)

`GET …/tracking_sources` に必要な Zoom スコープは **実装着手時に Marketplace で確定** します ([tracking_import_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/tracking_import_spec.md))。確定後、[oauth_and_settings_spec.md](./oauth_and_settings_spec.md) の要求スコープ一覧を v0.0.2改訂で更新します。

## v0.0.2での位置付け

* Source QR **取り込み UI** は本 REST 成功時に `items[]` をマージする (未 OAuth / 未 `_s2j_webinar_id` 時は spec どおり 4xx)。手入力 URL + QR のみでも初版 UI は完了可 ([source_qr_handoff_spec.md](./source_qr_handoff_spec.md))。

## 実装 (本プラグイン)

| 層 | パス |
| --- | --- |
| REST ルート | [`includes/Rest/TrackingSourcesController.php`](../includes/Rest/TrackingSourcesController.php) — `Plugin.php` で register |
| パイプライン | [`includes/SourceQr/TrackingSourcesImport.php`](../includes/SourceQr/TrackingSourcesImport.php) — Service `build_tracking_sources_request` / `map_tracking_sources_response` + [`WebinarHttpExecutor`](../includes/Http/WebinarHttpExecutor.php) |
| 認可 | [`SyncController::can_edit_event`](../includes/Rest/SyncController.php) と同型。OAuth は **503** `oauth_disconnected` (同期用 `last_error` は更新しない) |
| i18n | [`TrackingSourcesImport.php`](../includes/SourceQr/TrackingSourcesImport.php) — 失敗 `message` は [`WebinarProvider::label()`](../includes/Provider/WebinarProvider.php) + `%s` (上表) |
| テスト | [`tests/Integration/TrackingSourcesRestTest.php`](../tests/Integration/TrackingSourcesRestTest.php) (mock HTTP **200** / 未 OAuth **503** / 空一覧 **200**)、[`tests/smoke-local.sh`](../tests/smoke-local.sh) ルート確認 |

実 Zoom GET の Pass は [status.md §外部結合 E2](./status.md#status-external) (HTTPS)。Local 3b は Marketplace 不要のモック HTTP。

## 関連

* handoff: [source_qr_handoff_spec.md](./source_qr_handoff_spec.md)
* 定義 REST: [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md)
* Service: [tracking_import_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/tracking_import_spec.md)
* HTTP / Bearer: [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)
* 同期との分離: [sync_execution_spec.md](./sync_execution_spec.md)

## 改訂履歴 (v0.0.2)

| 日付 | 内容 |
| --- | --- |
| 2026-10-11 | 初版起草 (GET1本、パイプライン、HTTP マップ) |
| 2026-10-12 | deficiency → REST 統合表、501発生層、Source QR `docs/` リンク |
| 2026-10-12 | 正本 `docs/source_qr_tracking_rest_spec.md` に反映 |
| 2026-10-19 | §実装 — P2b 本番 REST (Controller / TrackingSourcesImport / Integration) |
| 2026-10-19 | §HTTP 応答 — 失敗 `message` のプロバイダー名は `%s` + `WebinarProvider::label()` (Zoom 直書き禁止) |
