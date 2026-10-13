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

## サイト設定画面 (2画面)

正本の画面分担は [admin_ui_spec.md](./admin_ui_spec.md) です。Client / Webhook / 接続の **入力と保存** は GatherPress 側タブに集約します。

| 画面 | メニュー / タブ | 権限 | 内容 |
| --- | --- | --- | --- |
| **設定 → S2J Webinar** | WordPress `add_options_page` | `manage_options` | GatherPress 依存状態 (plugin / event post type / alpha)。GatherPress 内 **Webinar タブを表示するか** (`s2j_webinar_show_gatherpress_settings_tab`。OFF なら GP からタブ非表示)。**Webinar provider** (初版は `zoom` ラベルのみ表示。将来コンボ)。Save settings。フッター注記 (ブロックエディター)。 |
| **GatherPress 設定 → Webinar** | `gatherpress_sub_pages` (`webinar_settings`)。Tools / Alpha 付近 | `manage_options` | タブ先頭 **Provider setup guide** リンク。クライアント ID/秘密鍵、リダイレクト URI、Webhook 秘密鍵/URL、Connection status (各 description)。Save settings (**`admin-post.php`** — 下記)。**Connect %s** / Disconnect (**`admin-post.php`**)。GatherPress 依存ブロックは **出さない**。 |

* サイト全体のプロバイダー slug / 表示名の正本は [`WebinarProvider`](../includes/Provider/WebinarProvider.php) (`s2j_webinar_provider` option。未設定時 `zoom`)。
* 接続ボタン・通知・ガイド文の msgid は **プロバイダー名を `%s` に埋め込む** (例: `Connect %s`、`%s account connected.`)。初版の slug は `zoom` のみ。
* GatherPress タブ OFF 中も **設定 → S2J Webinar** からタブを再度 ON にできる。

### 設定保存・Connect の HTTP 経路 (実装) {#oauth-http-routes}

GatherPress 設定は `edit.php?post_type=gatherpress_event` 配下のため、**GatherPress → Webinar** タブの Save を `wp-admin/options.php` に POST すると、環境によって `user_can_access_admin_page()` で wp_die となる。**当タブの資格情報保存は `admin-post.php` 専用** とする。**設定 → S2J Webinar** は従来どおり Settings API (`options.php`、option グループ `s2j_webinar_settings`)。

| 操作 | 入口 | action / グループ | 処理 |
| --- | --- | --- | --- |
| GP → Webinar **Save settings** | `admin-post.php` | `s2j_webinar_save_gatherpress_settings` ([`Constants::ADMIN_POST_SAVE_GP_SETTINGS`](../includes/Constants.php)) | [`SettingsPage::handle_gatherpress_tab_save()`](../includes/Admin/SettingsPage.php)。nonce `s2j_webinar_settings-options`。対象 option: Client ID / Secret / Webhook secret。成功時 **GatherPress → Webinar タブ** に `settings-updated` 付きリダイレクト |
| **設定 → S2J Webinar** Save | `options.php` | `s2j_webinar_settings` | GP タブ表示 ON/OFF 等 |
| **Connect %s** | `admin-post.php` (GET + nonce) | `s2j_webinar_oauth_connect` | [`OAuthFlow::handle_connect()`](../includes/OAuth/OAuthFlow.php) → `wp_safe_redirect( zoom 認可 URL )` |
| **Disconnect** | `admin-post.php` | `s2j_webinar_oauth_disconnect` | トークン option 削除 → 設定画面にリダイレクト |
| OAuth **コールバック** | `admin-post.php` (GET) | `s2j_webinar_oauth_callback` ([`Constants::OAUTH_CALLBACK_ACTION`](../includes/Constants.php)) | 認可コード交換 → **`admin.php?page=s2j-webinar`** に通知付きリダイレクト (GP タブではない) |

* 認可 URL へのリダイレクト前に **`allowed_redirect_hosts` に `zoom.us` を追加** する (`OAuthFlow::register`)。未追加時は `wp_safe_redirect` が外部 URL を拒否し管理ダッシュボードに落ちる。
* **Provider setup guide** の既定 URL は [`Constants::DOCS_PROVIDER_SETUP_GUIDE_URL`](../includes/Constants.php)。`s2j_webinar_provider_setup_guide_url` で差し替え。空文字ならリンク非表示。

### 秘密の扱い

* Client Secret / Webhook 秘密 / トークンは配布物に埋め込まない。
* 保存後のシークレットは再表示しない (プレースホルダのみ)。
* ログ・`last_error`・画面メッセージにトークンを出さない。

## リダイレクト URI

* 戻り口は `admin-post.php` の **固定アクション** とする (例: `admin-post.php?action=s2j_webinar_oauth_callback`)。
* 実際の絶対 URL を Zoom アプリケーションの Redirect URL に登録する。サイト設定の option には持たない。
* 組み立ては [`TokenStore::redirect_uri()`](../includes/OAuth/TokenStore.php): `admin_url( 'admin-post.php?action=' . OAUTH_CALLBACK_ACTION )` → **`localhost` を `127.0.0.1` に正規化** → フィルター **`s2j_webinar_oauth_redirect_uri`**。表示・認可 URL・トークン交換の `redirect_uri` はいずれもこの戻り値。
* 開発サイトが `http://localhost:…` の場合、Zoom はホスト名 `localhost` を拒否する (4700)。上記正規化後も **`http://127.0.0.1:<port>` は Marketplace で 4700 / Allow list *Wrong URL format* になり得る** (HTTPS + FQDN 要求)。手順は [provider_setup_guides.md](./provider_setup_guides.md) と [test-results.md](./test-results.md)。段階1–2では Connect 省略可 — 外部結合 [E1](./status.md#status-external)。
* 認可開始時、サービス `build_oauth_authorize_url` (相当) に `client_id` / `redirect_uri` 等を渡す。

## トークンライフサイクル

1. 管理者が「接続」→ 認可 URL にリダイレクト。
2. コールバックで認可コードを受け、サービス材料でトークン交換を HTTP 実行。
3. access / refresh / `expires_at` を option に保存。接続ユーザーのメールが取れたら表示用に保存。
4. API 呼び出し前に期限を見て、必要ならリフレッシュ材料で更新する。
5. アクセス・トークンは **期限まで使い回す** (毎回発行しない)。
6. 「切断」でトークン系 option を消し、クライアント ID / 秘密鍵は残してよい (再接続用)。

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
* **Local / Studio (HTTP):** Zoom は HTTPS 必須のため Event Subscriptions は登録しない。段階1–2では Webhook 項目は **未入力でよい** ([webhook_spec.md](./webhook_spec.md#local--段階12-webhook-は後回し))。

## アンインストール

* 本プラグインのサイト option (データ辞書の明示リスト) をすべて削除する。イベントメタの処理は [data_dictionary.md](./data_dictionary.md) のアンインストール節。
* Zoom アプリケーション側の認可取り消しは初版の必須としない (切断ボタンでトークン削除済みを推奨)。

## 関連

* Marketplace 操作手順: [provider_setup_guides.md](./provider_setup_guides.md)
* option キー: [data_dictionary.md](./data_dictionary.md)
* 同期時のトークン使用: [sync_execution_spec.md](./sync_execution_spec.md)
