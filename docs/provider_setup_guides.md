# S2J Webinar - プロバイダー設定ガイド (GatherPress 設定 → Webinar)

**GatherPress 設定 → Webinar** タブの各項目について、接続先 (Marketplace / 開発者コンソール) で **どこから値を取得し、どこに貼るか** をまとめます。画面ラベルの `%s` は [`WebinarProvider`](../includes/Provider/WebinarProvider.php) の表示名 (初版は Zoom) に対応します。

| プロバイダー | 状態 | ガイド |
| --- | --- | --- |
| **Zoom** | 初版 | 下記 [Zoom (General app)](#zoom-general-app) |
| Teams / Meet / その他 | 未実装 | [README Contributing](../README.md#ウェビナープロバイダ-初版と拡張) — 同形式のガイドを追加予定 |

秘密の正本・HTTP 契約は [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)。Local / Studio では [test-results.md](./test-results.md) の **Webhook / OAuth Connect 後回し** も参照してください。

WordPress 管理画面 **GatherPress 設定 → Webinar** タブ先頭の **Provider setup guide** から本ページ (既定 URL) を開きます。フォークや社内 Wiki に向ける場合は PHP フィルター `s2j_webinar_provider_setup_guide_url` を使います。

---

## Zoom (General app)

### 前提

* [Zoom Marketplace](https://marketplace.zoom.us/) に、**Developer 権限** のあるアカウント (または相当する個人アカウント) でログインする。
* アプリ種別は **General app** (Server-to-Server ではない)。初版は **User-managed** (個人 Connect) を想定。
* WordPress 側は **Development** の Client ID / Client Secret を使う (Production は Marketplace 公開後)。

### Marketplace への入り方 (UI は変更されることがあります)

1. ログイン後、画面左下 **Developers** (またはトップの *Developers: Build on Zoom's platform*) を開く。
2. [Created apps](https://marketplace.zoom.us/user/build) → **Developer** 行の **＋** → **General app** → **Create**。
3. 以降、ビルドフローの **Basic Information** / **Features → Access** / **Scopes** を順に設定する。

### WordPress ↔ Zoom 対応表

| GatherPress → Webinar (WordPress) | Zoom Marketplace での操作 |
| --- | --- |
| **Client ID** | **Basic Information** (または **App Credentials**) → **Client ID** をコピーして貼る |
| **Client Secret** (秘密鍵) | 同画面の **Client Secret** (初回表示のみのことが多い) をコピーして貼る。**Client Secret ではない** |
| **Redirect URI** (表示のみ) | この **完全一致** の URL を **OAuth Redirect URL for OAuth** (Development) に登録。詳細は [リダイレクト URI](#リダイレクト-uri) |
| **Connect Zoom** | Client ID / Secret 保存後に実行 (OAuth 許可)。Local / Studio では [後回し可](./test-results.md) |
| **Webhook secret** | **Features → Access** → **Secret Token** をコピー (**Verification Token** は非推奨・使わない) |
| **Webhook URL** (表示のみ) | **Event Subscriptions** の **Event notification endpoint URL** に **完全一致** で貼る。HTTPS 必須 |

保存: WordPress **GatherPress → Webinar** タブでは **Save settings** → `admin-post.php` ([oauth_and_settings_spec.md](./oauth_and_settings_spec.md#oauth-http-routes))。Zoom 側は各画面の Save / Validate。

### リダイレクト URI

WordPress に表示される例:

`https://<サイト>/wp-admin/admin-post.php?action=s2j_webinar_oauth_callback`

* **OAuth Redirect URL for OAuth** に上記 **完全 URL** (`http(s)`・ホスト・ポート・パス・クエリまで一致)。
* **OAuth allow list** は Zoom UI が **HTTPS + FQDN** を要求する。`http://127.0.0.1:…` は *Wrong URL format* になりやすい。Allow list には **`https://<ステージングドメイン>`** などベース URL を入れ、Redirect URL 欄に callback 完全 URL を入れる運用が一般的 ([Zoom OAuth Information](https://developers.zoom.us/docs/build-flow/basic-info/oauth-info/))。
* 開発サイトが `localhost` の場合、プラグインは redirect を **`127.0.0.1` に正規化** するが、4700エラーが続く場合は **HTTPS ステージング / ngrok** が必要 ([test-results.md](./test-results.md))。

### Webhook (Event Subscriptions)

**Local / Studio (HTTP):** Zoom は *A secure URL using HTTPS is required* のため **未設定でよい** ([webhook_spec.md](./webhook_spec.md#local--段階12-webhook-は後回し))。HTTPS ステージングで実施。

HTTPS ステージングで設定する場合:

1. **Features → Access** → **Secret Token** → WordPress **Webhook secret**。
2. **Event Subscriptions** を **ON** → **Add New Event Subscription**。
3. **Choose a Method:** **Webhook** (WebSockets ではない)。
4. **Event notification endpoint URL:** WordPress **Webhook URL** をそのまま貼る。
5. イベント (初版): **`webinar.started`**、**`webinar.ended`** のみ ([webhook_spec.md](./webhook_spec.md))。
6. **Validate** で URL 検証 (WordPress に Webhook secret を先に保存しておく)。

### Scopes

**Scopes → Add Scopes** で、サービスが要求する粒度スコープを追加します。正本リストは [s2j-webinar-service ZoomProvider](https://github.com/stein2nd/s2j-webinar-service/blob/main/src/Core/Providers/ZoomProvider.php) の `OAUTH_SCOPES` です。

| 技術 ID (authorize URL) | Marketplace で探す目安 (日本語 UI の例) |
| --- | --- |
| `webinar:write:webinar` | Webinar — ユーザーのウェビナーを作成する |
| `webinar:read:webinar` | Webinar — ウェビナーを確認する |
| `webinar:update:webinar` | Webinar — ウェビナーを更新する |
| `webinar:delete:webinar` | Webinar — ウェビナーを削除する |
| `webinar:write:panelist` | Webinar — ウェビナーにパネリストを追加する |
| `webinar:delete:panelist` | Webinar — ウェビナーのパネリストを削除する |
| `webinar:update:survey` | Webinar — ウェビナーのアンケートを更新する |
| `user:read:user` | User — ユーザーを確認する |

UI 上のカテゴリ名 (Meetings / Webinar 等) は Zoom の改版で変わります。**上表の技術 ID と説明文** で選びます。`:admin` 末尾の管理者スコープは、初版では要求しません ([oauth_and_settings_spec.md](./oauth_and_settings_spec.md))。

### 作業順 (HTTPS ステージング)

1. Zoom で General app 作成 → Scopes 追加 → **Development** Client ID / Secret を控える。
2. Redirect URL (＋ allow list) を **HTTPS サイト** の Redirect URI に合わせる。
3. WordPress **GatherPress → Webinar** に Client ID / Secret を保存 → **Connect Zoom**。
4. (任意・HTTPS のみ) Secret Token / Event Subscriptions / Validate。
5. イベント編集パネルから **同期** を試す (外部結合 [E2](./status.md#status-external))。

### 関連

* [OAuth とサイト設定](./oauth_and_settings_spec.md)
* [Webhook 仕様](./webhook_spec.md)
* [管理 UI](./admin_ui_spec.md)
