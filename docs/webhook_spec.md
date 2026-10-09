<!--
目的：「Zoom Webhook 受け口」の明文化
-->

# S2J Webinar - Webhook 仕様

## 設計意図 (ゴール)

Zoom から開催開始・終了を受け、セッション状態を固定します。検証・操作計画は使いません。公開リンクの出し分けは GatherPress に任せます。

## 非対象

* Zoom イベント名の追加購読 (初版は下記2つのみ)
* 出席者一覧・録画完了の取り込み
* `admin-post` / カスタム rewrite による Webhook 受け口 (初版はプラグイン REST のみ)

## エンドポイント

署名検証に失敗したら `4xx` で終了し、メタを変えません。

| 項目 | 方針 |
| --- | --- |
| URL | 本プラグインの REST ルート (絶対 URL をサイト設定に表示)。OAuth コールバックの `admin-post` とは別 |
| 認証 | Zoom の署名 (Webhook Secret)。秘密は [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) |
| 権限 | 署名検証に成功したリクエストのみ処理。Cookie 認証に頼らない |

## 購読イベント (初版)

対象イベントの解決は、ペイロードの webinar `id` と `_s2j_webinar_id` の一致で行います。見つからなければ `2xx` 相当で無視してかまいません (再送対策。エラーログはトークンなし)。

| Zoom イベント | 本プラグインの動き |
| --- | --- |
| `webinar.started` | `_s2j_webinar_session.phase` ← `started`。`join_url` があれば公開欄 (`gatherpress_online_event_link`) を維持 / 再設定 (`Event::set_online` 等) |
| `webinar.ended` | `phase` ← `ended` のみ。**公開欄の参加 URL は空にしない** (終了後の表示抑止は GatherPress の `maybe_get_online_event_link` 等に任せる)。公開欄を空にするのは Zoom 削除成功時のみ ([gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)) |

## セッションメタ

形は [data_dictionary.md](./data_dictionary.md) の `_s2j_webinar_session` です。

* `phase` のライフサイクル: メタ未作成、`webinar_id` 空、delete 成功後は `idle` (またはメタ削除 = `idle` と同義)。`started` / `ended` は本 Webhook のみが書く。
* レコード status (`synced` 等) は Webhook では変えない。
* `start_url` は扱わない。

## エンドポイント URL Challenge

Zoom の URL 検証 (challenge-response) が必要な場合は、署名または公式手順に従い応答します。手順の細部は Zoom 公式に従い、秘密をログに出しません。

## 関連

* 公開リンク: [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)
* 設定: [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)
