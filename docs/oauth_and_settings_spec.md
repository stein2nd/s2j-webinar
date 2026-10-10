<!--
目的：「OAuth 接続とサイト設定」の明文化
-->

# S2J Webinar - OAuth とサイト設定仕様

## 設計意図 (ゴール)

サイト管理者が Zoom を1アカウント接続し、トークンと Webhook 秘密を安全に保持できるようにします。
認可 URL / トークン交換の **材料** はサービス、受信・保存および HTTP は本プラグインです。

## 非対象

* スコープ文字列と材料形の規則本体 (サービス [oauth_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/oauth_spec.md))
* イベントごとの接続アカウント (初版はサイト1つ)

## アプリケーション種別

* Zoom の **ユーザー管理** OAuth アプリケーションである。
* 公開マーケットには出さず、サーバー間認証にもしない。
* スコープは本人用グラニュラーのみ (`:admin` 末尾は要求しない)。一覧の正本はサービス oauth_spec。

初版で要求するスコープには、アンケート添付用の `webinar:update:survey` を含めます。

## サイト設定画面

| 項目 | 内容 |
| --- | --- |
| メニュー見出し | S2J Webinar (製品名と同一でよい) |
| 権限 | `manage_options` のみ |
| 項目 | Client ID、Client Secret、接続状態、接続 / 切断、Webhook 秘密、Webhook URL の表示 |
| 接続先選択 | 初版は出さない (Zoom 固定) |

### 秘密の扱い

* Client Secret / Webhook 秘密 / トークンは配布物に埋め込まない。
* 保存後のシークレットは再表示しない (プレースホルダのみ)。
* ログ・`last_error`・画面メッセージにトークンを出さない。

## リダイレクト URI

* 戻り口は `admin-post.php` の **固定アクション** とする (例: `admin-post.php?action=s2j_webinar_oauth_callback`)。
* 実際の絶対 URL を Zoom アプリケーションの Redirect URL に登録する。サイト設定の option には持たない。
* 認可開始時、サービス `build_oauth_authorize_url` (相当) に `client_id` / `redirect_uri` 等を渡す。

## トークンライフサイクル

1. 管理者が「接続」→ 認可 URL にリダイレクト。
2. コールバックで認可コードを受け、サービス材料でトークン交換を HTTP 実行。
3. access / refresh / `expires_at` を option に保存。接続ユーザーのメールが取れたら表示用に保存。
4. API 呼び出し前に期限を見て、必要ならリフレッシュ材料で更新する。
5. アクセス・トークンは **期限まで使い回す** (毎回発行しない)。
6. 「切断」でトークン系 option を消し、Client ID / Secret は残してよい (再接続用)。

## HTTP と Authorization

材料に `Authorization` / Basic を含めない (サービス契約)。本プラグインが呼び出し種別に応じて付け、`wp_remote_*` する。正本の材料形はサービス [oauth_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/oauth_spec.md) / [request_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/request_spec.md)。

| 呼び出し | `path` の扱い | 認証ヘッダー | body |
| --- | --- | --- | --- |
| Webinar REST (`create` / `update` / `delete` / `get` / Panelist / `attach_survey`) | ホストなし。プラグインが API ベース (例: `api.zoom.us`) を前置 | `Authorization: Bearer {access_token}` | 材料の JSON (`Content-Type: application/json`) |
| OAuth `token` / `refresh` | 材料の **フル URL** (`https://zoom.us/oauth/token`) をそのまま使う。API ベースを前置しない | `Authorization: Basic` (`base64(client_id:client_secret)`)。**Bearer にしない** | 材料の object を `application/x-www-form-urlencoded` にエンコード |

* `path` が `https://` で始まる場合はそのまま使う。そうでなければ API ベースを前置する (サービス oauth_spec と同じ判定)。
* アクセス・トークンの Bearer は Webinar REST のみ。トークン交換・リフレッシュには使わない。

## Webhook 秘密

* サイト設定で保存する。
* エンドポイント URL を画面に表示し、Zoom 側に転記できるようにする。
* 検証手順の正本は [webhook_spec.md](./webhook_spec.md)。

## アンインストール

* 本プラグインのサイト option (データ辞書の明示リスト) をすべて削除する。イベントメタの処理は [data_dictionary.md](./data_dictionary.md) のアンインストール節。
* Zoom アプリケーション側の認可取り消しは初版の必須としない (切断ボタンでトークン削除済みを推奨)。

## 関連

* option キー: [data_dictionary.md](./data_dictionary.md)
* 同期時のトークン使用: [sync_execution_spec.md](./sync_execution_spec.md)
