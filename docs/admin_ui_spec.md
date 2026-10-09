<!--
目的：「設定画面とイベントパネル」の明文化
-->

# S2J Webinar - 管理 UI 仕様

## 設計意図 (ゴール)

管理者が接続を済ませ、営業担当者がイベント編集パネルだけで Webinar の登録・更新・削除・「Zoom で開く」を終えられるようにします。

## サイト設定

正本の項目と秘密扱いは [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) です。メニュー見出しは「S2J Webinar」とします。

## イベント編集パネル

| 項目 | 内容 |
| --- | --- |
| 見出し | Webinar (実装は i18n。msgid は英語基本) |
| 置き場 | GatherPress イベント編集。Slot `EventPluginDocumentSettings` の Fill |
| Survey | 別プラグインのパネルと同居。設問 UI は持たない |
| 権限 | そのイベントを編集できるユーザー |

### パネルに出す項目 (初版)

* 接続状態の要約 (未接続なら設定画面への誘導。Client 入力は出さない)
* 録画方式 (`none` / `cloud` / `local`)。デフォルト表示はクラウド
* 参加登録 (ラジオ: 必須・自動承認 / 必須・手動承認 / 不要)。デフォルトは必須・自動承認 (`0`)
* 登壇者 (Panelist) の列 (氏名・メール。追加・削除・並べ替え。**同期には1人以上** (投稿保存では `[]` 可。詳細は下記)。0人のまま「同期」は無効または追加を促す。1人目が質問メール送信先である旨の補足)
* レコード status の表示 (`not_created` / `synced` / `dirty` / `error`)。生コードではなく適切なラベル文 (i18n)
* `last_error` がある場合の適切なメッセージ文 (トークンを含まない)
* 操作ボタン:
  * 同期 (作成・更新・登壇者差分・`ready` なら `attach_survey`。`error` かつ id 非空なら `intend_retry`。ラベルは状態に応じて変えてよい。アンケート専用ボタンは持たない)
  * Zoom で開く (`build_webinar_request( 'get', … )` 直呼びの get 専用経路。再取得と同義。別の「再取得」ボタンは置かない。書き込み用 validate / plan は通さない)
  * Zoom から削除 (確認ダイアログ必須)
* 定員を超えそうな場合は、ライブストリームを別途検討する旨の案内 (配信 URL / ストリームキーは送らない・入力しない)

### パネルに出さない項目 (初版)

* 接続先プロバイダの選択一覧
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
| 「Zoom で開く」 | `build_webinar_request( 'get', … )` のみ (`webinar_id` 非空ゲート)。`start_url` を保存せず開く。応答 `join_url` / `webinar_uuid` 非空のときだけメタ (と `join_url` なら公開欄) を更新。空なら既存を消さない。失敗時は `last_error` 可 |
| 「Zoom から削除」 | 確認後 `intend_delete` |

## 不足・失敗の表示

* サービスの不足コード / 例外メッセージから、適切なメッセージ文を表示する (国際化関数を経由)。
* 画面に生の deficiency code やスタックを出さない。
* 書き込み同期で不足がある場合は HTTP しない。「Zoom で開く」は topic 等の不足ではとめない。

## i18n

* PHP / JS への日本語直書き原稿にしない。msgid は英語基本。
* 仕様書中の日本語は製品文案・説明であり、実装ソースではない。

## 関連

* 原則: [principles.md](./principles.md)
* データ辞書: [data_dictionary.md](./data_dictionary.md)
* GatherPress: [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)
