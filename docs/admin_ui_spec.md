# S2J Webinar - 管理 UI 仕様

## 設計意図 (ゴール)

管理者が接続を済ませ、営業担当者がイベント編集パネルだけで Webinar の登録・更新・削除・「プロバイダー名付きで開く」を終えられるようにします (初版プロバイダーは `zoom`)。

## サイト設定

正本の項目と秘密扱いは [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) です。

| 画面 | 内容 |
| --- | --- |
| **設定 → S2J Webinar** | GatherPress 依存状態 (plugin / event post type / alpha) と Webinar タブへのリンク (有効時)。GatherPress 設定内 Webinar タブの表示 ON/OFF。**Webinar provider** (初版は Zoom ラベルのみ。将来コンボ)。Save settings。フッター注記 (ブロックエディター)。 |
| **GatherPress 設定 → Webinar** | クライアント ID/秘密鍵、リダイレクト URI、Webhook 秘密鍵/URL、Connection status (各ガイド付き)。Save settings。**Connect {provider}** (msgid `Connect %s`。Disconnect は接続時)。GatherPress 状態は出さない。 |

GatherPress → Webinar タブの認証・Webhook 項目には用途ガイド (description) を付けます。ガイド中のプロバイダー名は `%s` msgid + [`WebinarProvider`](../includes/Provider/WebinarProvider.php) の表示名とします。

## イベント編集パネル

| 項目 | 内容 |
| --- | --- |
| 見出し | Webinar (実装は i18n。msgid は英語基本) |
| 置き場 | GatherPress イベント編集。Slot `EventPluginDocumentSettings` の Fill |
| Survey | 別プラグインのパネルと同居。設問 UI は持たない |
| 権限 | そのイベントを編集できるユーザー |

### パネルに出す項目 (初版)

* 接続状態の要約 (未接続なら `%s is not connected…` + 設定への誘導。Client 入力は出さない)
* 録画方式 (`none` / `cloud` / `local`)。デフォルト表示はクラウド
* 参加登録 (ラジオ: 必須・自動承認 / 必須・手動承認 / 不要)。デフォルトは必須・自動承認 (`0`)
* 登壇者 (Panelist) の列 (氏名・メール。追加・削除・並べ替え。**同期には1人以上** (投稿保存では `[]` 可。詳細は下記)。0人のまま「同期」は無効または追加を促す。1人目が質問メール送信先である旨の補足)
* レコード status の表示 (`not_created` / `synced` / `dirty` / `error`)。生コードではなく適切なラベル文 (i18n)
* `last_error` がある場合の適切なメッセージ文 (トークンを含まない)
* 操作ボタン:
  * 同期 (作成・更新・登壇者差分・`ready` なら `attach_survey`。`error` かつ id 非空なら `intend_retry`。ラベルは状態に応じて変えてよい。アンケート専用ボタンは持たない)
  * **Open in %s** — `build_webinar_request( 'get', … )` 直呼びの get 専用経路。`%s` は `providerLabel`。再取得と同義。別の「再取得」ボタンは置かない
  * **Delete from %s** — 確認 `Remove this webinar from %s?` 必須
* 定員を超えそうな場合は、ライブストリームを別途検討する旨の案内 (配信 URL / ストリームキーは送らない・入力しない)

### パネルに出さない項目 (初版)

* 接続先プロバイダの選択 UI (サイト **設定 → S2J Webinar** で slug 表示のみ。イベントパネルには出さない)
* パスコード、チャットデフォルト、継承7項目の個別編集
* Q&A / HD / 認証の個別トグル (作成時はサービス材料の固定値)
* Survey 設問の編集 (Survey プラグイン)
* CoverArt / QR / フライヤー

### タイトル・説明・日時

* タイトル (`topic`) と概要 (`agenda`) は、投稿タイトルと抜粋を使う。パネルに二重入力しない。
* 日時は GatherPress の日時 UI を使う。パネルに開始・終了の二重入力しない。

## 保存と同期のトリガー

Survey プラグインの永続化にそろえます。**パネル編集キー**だけを `show_in_rest` 付きで `register_post_meta` し、**GatherPress イベント編集からの明示の投稿「更新」** の `save_post` でサーバーが正規化してメタに書きます。キー区分の正本は [data_dictionary.md](./data_dictionary.md)。クライアントのレコード `status` は信頼しません。1オブジェクトにまとめる形は初版に入れません。

除外 (メタの正規化書き込みも Zoom HTTP もしない): autosave、リビジョン、Quick Edit、一括編集、WP-CLI、**本プラグイン独自のカスタム REST によるメタ保存** (初版)。  
**許可:** エディター経由のコア投稿更新 REST (パネル編集キーの `show_in_rest` 搬送) + `save_post` でのサーバー正規化。

**Zoom HTTP は明示ボタンのみ** です。投稿保存では Zoom に送りません。

登壇者0人: 投稿の明示更新では `[]` をメタに残してよい (下書き途中)。「同期」は UI で無効化または追加を促す。サーバー到達時の最終判定はサービスの `panelists_empty` (安全網)。

| 操作 | 動き |
| --- | --- |
| 投稿の明示更新 | パネル入力をイベントメタに保存 (登壇者 `[]` 可)。**自動では Zoom に送らない** |
| 「同期」ボタン | 書き込みパイプライン ([sync_execution_spec.md](./sync_execution_spec.md))。`intend_get` は付けない。status が `error` かつ id 非空なら `intend_retry` |
| **Open in {provider}** (msgid `Open in %s`) | `build_webinar_request( 'get', … )` のみ (`webinar_id` 非空ゲート)。`start_url` を保存せず開く。応答 `join_url` / `webinar_uuid` 非空の場合だけメタ (と `join_url` なら公開欄) を更新。空なら既存を消さない。失敗時は `last_error` 可 |
| **Delete from {provider}** (msgid `Delete from %s`) | 確認後 `intend_delete` |

## 不足・失敗の表示

* サービスの不足コード / 例外メッセージから、適切なメッセージ文を表示する (国際化関数を経由)。
* 画面に生の deficiency code やスタックを出さない。
* 書き込み同期で不足がある場合は HTTP しない。「Open in %s」は topic 等の不足ではとめない。

## i18n

* PHP / JS への日本語直書きスクリプトにしない。msgid は英語基本。プロバイダー名は msgid に含めず **`%s` / `%1$s` プレースホルダ** + 実行時の表示名 ([`WebinarProvider`](../includes/Provider/WebinarProvider.php) / Gutenberg の `providerLabel`)。
* PHP の `__()` 等の第2引数 (text domain) は **`'s2j-webinar'` 文字列リテラル** とする (`wp i18n make-pot` が `Constants::TEXT_DOMAIN` だけでは抽出しないため)。
* Gutenberg: `wp_set_script_translations` + `languages/*.json` (PO から `wp i18n make-json`)。
* 管理 UI の Client / 接続フォームは **PHP 描画のみ** (GatherPress タブに admin JS フッターを二重表示しない)。
* 仕様書中の日本語は製品文案・説明であり、実装ソースではない。

## 関連

* 原則: [principles.md](./principles.md)
* データ辞書: [data_dictionary.md](./data_dictionary.md)
* GatherPress: [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)
