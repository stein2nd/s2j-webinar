<!--
目的：「メタキー、option、メタ ↔ WebinarRecord」の明文化
-->

# S2J Webinar - データ辞書

本ドキュメントは、本プラグインが永続化する **キーと意味**、および **メタ ↔ WebinarRecord** の対応を定義します。
レコードフィールドの意味・不足コードの正本は Webinar Service です。

## 非対象

* Zoom API のフィールド名とパス (サービス `docs/core/request_spec.md`)
* 検証条件の列挙 (サービス `docs/core/validation_spec.md`)
* Survey 文書のフィールド (Survey Service / Survey プラグイン)

## 用語

| 用語 | 意味 |
| --- | --- |
| WebinarRecord | サービスに渡す計算用レコード。形は [record_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/record_spec.md) |
| 本体項目 | topic / 日時 (start_at、timezone、duration_minutes) / agenda / auto_recording / approval_type。`dirty` 判定の対象 |
| 前回送付スナップショット | `_s2j_webinar_last_sent`。直近の成功した create / update で送った本体項目 |
| レコード status | `not_created` / `synced` / `dirty` / `error`。Survey 文書の `draft` / `ready` と混同しない |
| セッション状態 | `_s2j_webinar_session.phase`。レコード status とは別 |
| 登壇者 (Panelist) | UI、仕様の見出しは「登壇者」。キー、op、サービス用語は `panelists` / Panelist。初出で対応づける |

## イベントメタ (本プラグイン所有)

接頭辞は `_s2j_webinar_` です。いずれも保護メタとします。**個別キーごと** に `register_post_meta` します。1オブジェクトにまとめる形は初版に入れません (詳細は [admin_ui_spec.md](./admin_ui_spec.md))。意味の正本は本表です。

### パネル編集キー (`show_in_rest`)

`show_in_rest` = true、`auth_callback` は `edit_post` 相当。投稿更新でパネルから載せる。

| キー | 型 | 説明 |
| --- | --- | --- |
| `_s2j_webinar_provider` | string | 接続先 ID。初版は常に `zoom`。未設定時はレコード組立で `zoom` |
| `_s2j_webinar_auto_recording` | string | `none` / `cloud` / `local`。未設定時はレコード組立で `cloud` |
| `_s2j_webinar_approval_type` | string\|int | `0` / `1` / `2`。未設定時はレコード組立で `0` (必須・自動承認) |
| `_s2j_webinar_panelists` | array | 登壇者 `{ name, email }[]`。順序あり。投稿保存では `[]` 可 (下書き)。同期時は1人以上 (UI + サービス)。1人目が質問メール送信先 |

### サーバー管理キー (`show_in_rest` しない)

同期、Webhook、get 写像が書く。クライアントからの REST 書き込み対象にしない。

| キー | 型 | 説明 |
| --- | --- | --- |
| `_s2j_webinar_id` | string | Zoom webinar ID。未作成は空 |
| `_s2j_webinar_uuid` | string | Zoom UUID (`webinar_uuid`)。空可。create / update / get で写像・応答が空のときは既存を消さない (空にするのは delete 成功時のみ) |
| `_s2j_webinar_join_url` | string | 参加 URL。空可。公開欄への写し元。create / update / get で写像・応答が空のときは既存を消さない (空にするのは delete 成功時のみ) |
| `_s2j_webinar_status` | string | レコード status |
| `_s2j_webinar_last_error` | string | 直近失敗文。トークンを入れない |
| `_s2j_webinar_last_sent` | object | 前回送付スナップショット (本体項目のみ。下記) |
| `_s2j_webinar_last_panelists` | array | 前回 Zoom に送った登壇者 `{ name, email }[]`。差分の `previous_panelists` 用。本体の `last_sent` に混ぜない |
| `_s2j_webinar_session` | object | `phase` (`idle` / `started` / `ended`) と任意の受信時刻 |

### セッション `phase` のライフサイクル

| 状態 | いつ |
| --- | --- |
| `idle` (またはメタ未作成) | 初期、`webinar_id` 空、Zoom 削除成功後 |
| `started` | Webhook `webinar.started` のみ |
| `ended` | Webhook `webinar.ended` のみ |

### `_s2j_webinar_last_sent` の形

create / update の **map 成功直後**に、送った本体項目を `update_post_meta` で永続化します (列の登壇者 / survey 成否を待たない。列末の一括書き戻しに頼らない)。登壇者・アンケートは含めません。登壇者差分の成功直後は `_s2j_webinar_last_panelists` を同様に即時永続化します。

* `topic`
* `agenda`
* `start_at`
* `timezone`
* `duration_minutes`
* `auto_recording`
* `approval_type`

### メタに持たないもの

* `start_url` (揮発。保存しない)
* Survey 文書本体 (`_s2j_webinar_survey` は Survey プラグインの所有)
* Zoom パス / ボディの複製

## メタ ↔ WebinarRecord

組立時に本プラグインが埋める対応です。サービスは左列の WP キー名を知りません。

| WebinarRecord | 取得元 |
| --- | --- |
| `provider` | `_s2j_webinar_provider`。空なら `zoom` |
| `topic` | 投稿タイトル (`post_title`)。空は検証で不足 |
| `agenda` | 投稿抜粋 (`post_excerpt`)。空可 |
| `start_at` | GatherPress 開始 + timezone から組み立て ([gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)) |
| `timezone` | GatherPress のイベント timezone |
| `duration_minutes` | 開始と終了の差を秒以下切り捨て (floor) した整数分。終日は暦日数×1440。1未満は検証で不足 |
| `auto_recording` | `_s2j_webinar_auto_recording`。空なら `cloud` |
| `approval_type` | `_s2j_webinar_approval_type`。空なら `0` |
| `panelists` | `_s2j_webinar_panelists`。空なら `[]` |
| `webinar_id` | `_s2j_webinar_id` |
| `webinar_uuid` | `_s2j_webinar_uuid` |
| `join_url` | `_s2j_webinar_join_url` |
| `status` | 組立直後に本プラグインが再計算 (下記 dirty 規則)。メタの値は初期入力 |
| `last_error` | `_s2j_webinar_last_error` |

写像後のレコードは、対応するメタに書き戻します。`start_url` は書き戻しません。`join_url` / `webinar_uuid` が空のときは既存メタを触らない (`join_url` が空なら公開欄も触らない。create / update / get 共通。空にするのは delete 成功時のみ)。

## dirty / synced の判定 (本プラグイン)

1. `webinar_id` が空:
   * `last_error` 非空、またはメタ status が `error` → `error`
   * それ以外 → `not_created`
2. `webinar_id` 非空:
   * 直近 map が失敗してメタ status が `error` → `error` を維持 (再同期で `intend_retry`)
   * `_s2j_webinar_last_sent` が空 → `dirty` (再送安全側。成功後は必ず埋める)
   * 本体項目が `last_sent` と異なる → `dirty`
   * 本体項目が `last_sent` と同じ → `synced`

**登壇者だけ・アンケートだけの変更では `dirty` にしません。** `synced` のまま `plan` に渡し、サービスの差分 / `attach_survey` に任せます。

比較前に、メール以外の文字列は trim してかまいません。`approval_type` は文字列 `"0"` と整数 `0` を同一とみなします。

## サイト option

Settings API の group / name は、本キーとそろえます。秘密は配布物とログに出しません。

| キー | 型 | 説明 |
| --- | --- | --- |
| `s2j_webinar_zoom_client_id` | string | Zoom アプリケーションの Client ID |
| `s2j_webinar_zoom_client_secret` | string | Client Secret。保存後は再表示しない |
| `s2j_webinar_zoom_access_token` | string | アクセス・トークン。WP option に保存し、配布物・ログ・画面に出さない。暗号化レイヤは初版の必須としない (後続可) |
| `s2j_webinar_zoom_refresh_token` | string | リフレッシュトークン |
| `s2j_webinar_zoom_token_expires_at` | int | UNIX 時刻。期限までアクセス・トークンを使い回す |
| `s2j_webinar_zoom_account_email` | string | 接続ユーザーの表示用メール (任意) |
| `s2j_webinar_webhook_secret` | string | Webhook 署名検証用。保存後は再表示しない |

OAuth の `redirect_uri` は option に持たず、`admin-post.php` の固定アクション URL を都度組み立てます ([oauth_and_settings_spec.md](./oauth_and_settings_spec.md))。

## Survey メタ (所有は Survey プラグイン)

| キー | 本プラグインの扱い |
| --- | --- |
| `_s2j_webinar_survey` | 読むだけ。`status === 'ready'` の場合に限り `plan` の `survey_document` に載せる。書き込まない。アンインストール対象外 |

## GatherPress メタ (所有は GatherPress)

| キー | 本プラグインの扱い |
| --- | --- |
| `gatherpress_datetime` 等 | 開始・終了、timezone、終日の読み取り ([gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)) |
| `gatherpress_online_event_link` | create / update / get で `join_url` 非空の場合のみ書き込み (`Event::set_online` 等)。空なら既存を触らない。`webinar.started` で維持 / 再設定可。`webinar.ended` では空にしない |

## アンインストールで消すもの (明示リスト)

ワイルドカード `_s2j_webinar_*` は使いません。

| 対象 | 消す |
| --- | --- |
| 本プラグイン所有のイベントメタ | 上表の `_s2j_webinar_provider` … `_s2j_webinar_session` の全キー |
| 本プラグインのサイト option | 上表の `s2j_webinar_zoom_*` / `s2j_webinar_webhook_secret` |
| `_s2j_webinar_survey` | **消さない** |
| GatherPress メタ | 消さない |
| Zoom 上の Webinar / アンケート | 変更しない |

## 一貫性ルール

* コード名は snake_case のまま扱い、画面に生コードを出さない。
* レコード status と Survey `status` とセッション `phase` を混同して書かない。
* 仕様文では「適切なメッセージ文を表示」と書く (i18n 経由。msgid は英語基本)。
* Composer の repository 種別コードは `` `path` `` と書く。日本語の「パス」は URL やファイルの説明に限定する。
* レコードキーは `webinar_uuid`。略記 `uuid` は使わない。メタキーは `_s2j_webinar_uuid`。
* Survey 文書の status 値は `` `ready` `` / `` `draft` `` とバッククオートする。
