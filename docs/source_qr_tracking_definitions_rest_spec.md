# S2J Webinar - Source Tracking ソース定義一覧 REST

S2J Webinar 仕様 v0.0.2 (運用素材) です。handoff 全体は [source_qr_handoff_spec.md](./source_qr_handoff_spec.md)。取り込み GET は [source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md)。

## 設計意図 (ゴール)

[S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr) の管理 UI が **チャネル行の正本** として参照する、ソース名と表示ラベルの一覧を **OAuth token を渡さず** 返す **本プラグイン専用 REST** を定義します。

* 接続先 GET (`tracking-sources`) とは **別経路**。Zoom 等は「登録前のソース名一覧 API」を提供しないため、定義は **本プラグイン (+拡張フィルター)** が解決する。
* Source QR の `catalogReady` は、本 REST の **200** かつ `sources.length >= 1` を含む ([Source QR admin_ui_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/admin_ui_spec.md))。

## 非対象

* `_s2j_webinar_source_qr` の読み書き (Source QR 所有)
* `registration_url` の取得 (取り込み REST の役割)
* Webinar レコード `status` / `dirty` / `last_error` の更新
* 匿名・外部システム向け公開 API (Cookie + REST nonce の編集者のみ)

## エンドポイント (初版)

| 項目 | 値 |
| --- | --- |
| Namespace | `s2j-webinar/v1` |
| Method | `GET` |
| Route | `/events/(?P<post_id>[\d]+)/tracking-source-definitions` |
| 完全 URL 例 | `/wp-json/s2j-webinar/v1/events/123/tracking-source-definitions` |

## 認可

[source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md) の **認可** 表と **同型** です (ログイン、GatherPress イベント、`edit_post`、OAuth 接続チェックは **不要** — 定義一覧は接続先 HTTP しない)。

| チェック | 内容 |
| --- | --- |
| ログイン | 必須 (REST Cookie 認証 + `X-WP-Nonce`) |
| 投稿 | `post_id` が存在し、GatherPress **イベント** であること |
| 権限 | `current_user_can( 'edit_post', $post_id )` |

## サーバー処理 (パイプライン)

同期列とは別の **読み取り専用** 列です。

1. post_id、権限、イベント post type を確認
2. `_s2j_webinar_id` (webinar_id) を読む
   * trim 後 **空** → REST エラー `tracking_webinar_id_empty` (400)。Webinar 未作成・未連携
3. `_s2j_webinar_provider` を読む (未設定は `zoom` として扱う)
4. イベントの `provider` に応じた **ソース定義一覧** を解決 (下記「定義の解決」)
5. `200` と `{ "sources": [ … ] }` を返す (`sources` は **空配列可**)

## 定義の解決 (v0.0.2)

| 層 | 役割 |
| --- | --- |
| 本プラグイン | プロバイダごとの **デフォルト一覧** (v0.0.2 `zoom`: サイト運用でポータルに登録するソース名セット) |
| 拡張 | `apply_filters( 's2j_webinar_tracking_source_definitions', $sources, $provider, $post_id )` で差し替え・追加 (順序は配列順 = Source QR の行順) |

各要素は、次の形に正規化してから返します。

| フィールド | 型 | 説明 |
| --- | --- | --- |
| `source_name` | string | 接続先ポータル上のソース名。**trim 後非空**。Source QR 行 `id` および取り込み `source_name` のキー |
| `label` | string | UI 表示用。空可 (Source QR は `id` を表示) |

**v0.0.2 `zoom` デフォルト (例):** 運用で `web` / `sns` / `other` をポータルに登録する想定の **初期デフォルト** です。固定仕様ではなく、フィルターでサイトごとに変更できます ([glossary_operational.md](./glossary_operational.md))。

## 成功レスポンス (200)

Content-Type: `application/json`

```json
{
  "sources": [
    { "source_name": "web", "label": "Web" },
    { "source_name": "sns", "label": "SNS" }
  ]
}
```

| フィールド | 型 | 説明 |
| --- | --- | --- |
| `sources` | array | 定義行。空配列可 (Source QR は `catalogReady` false、Import / 保存 disabled) |
| `sources[].source_name` | string | ソース名 |
| `sources[].label` | string | 表示ラベル |

## HTTP 応答 (失敗)

WordPress REST 慣習: `{ "code", "message", "data": { "status": N } }`。`message` は i18n 済みの短いユーザー向け文。

| HTTP | `code` | 条件 |
| --- | --- | --- |
| 403 | `rest_forbidden` | 権限不足 (コアに委譲可) |
| 404 | `rest_post_invalid_id` | 投稿不存在 (コアに委譲可) |
| 400 | `rest_invalid_post_type` | イベント post type でない |
| 400 | `tracking_webinar_id_empty` | `_s2j_webinar_id` が空 (Webinar 未連携) |

**Source QR 側:** 上記 **4xx** および空 `sources[]` は UI で **`catalogReady` false** とする。サーバー `draft` / `apply` は **503** (`catalog_unavailable`) に正規化 ([Source QR persistence_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/persistence_spec.md))。

## クライアント (Source QR)

| 項目 | 方針 |
| --- | --- |
| API | `@wordpress/api-fetch` |
| 呼び方 | `GET path: '/s2j-webinar/v1/events/' + postId + '/tracking-source-definitions'` |
| マージ | [Source QR data_dictionary.md — チャネル行の組み立て](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/data_dictionary.md) |

## v0.0.2での位置付け

* Source QR 初版 UI は **本 REST 成功 + 非空 `sources`** を編集モードの前提とする (厳格モード)。

## 実装 (本プラグイン)

| 層 | パス |
| --- | --- |
| REST ルート | [`includes/Rest/TrackingSourceDefinitionsController.php`](../includes/Rest/TrackingSourceDefinitionsController.php) — `Plugin.php` で register |
| 定義解決 | [`includes/SourceQr/TrackingSourceDefinitions.php`](../includes/SourceQr/TrackingSourceDefinitions.php) — プロバイダ別デフォルト + `s2j_webinar_tracking_source_definitions` |
| 認可 | [`SyncController::can_edit_event`](../includes/Rest/SyncController.php) と同型 (OAuth 不要) |
| テスト | [`tests/Integration/TrackingSourceDefinitionsRestTest.php`](../tests/Integration/TrackingSourceDefinitionsRestTest.php)、[`tests/smoke-local.sh`](../tests/smoke-local.sh) ルート確認 |

Studio / Local の **mu-plugin 定義 stub** は本ルートと競合するため、Webinar 本番ルート検証時は **無効化** する。

## 関連

* handoff: [source_qr_handoff_spec.md](./source_qr_handoff_spec.md)
* 取り込み REST: [source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md)
* Source QR: [admin_ui_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/admin_ui_spec.md)

## 改訂履歴 (v0.0.2)

| 日付 | 内容 |
| --- | --- |
| 2026-10-12 | 初版 (定義 GET、`_s2j_webinar_id` 必須、フィルター拡張) |
| 2026-10-12 | 正本 `docs/source_qr_tracking_definitions_rest_spec.md` に反映 |
| 2026-10-19 | §実装 — P2a 本番 REST (Controller / TrackingSourceDefinitions / Integration) |
