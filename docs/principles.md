<!--
設計原則
-->

# S2J Webinar - 設計原則

## 原則

### 1. Source of Truth

* 計算規則の正本は Webinar Service の `docs/core/` である。
* メタキー、option、メタ ↔ WebinarRecord、dirty 判定の正本は本 `docs/` である。
* README は最短手順であり、契約と矛盾させない。

### 2. アダプタ

* 本プラグインは、WordPress / GatherPress / HTTP の外側である。
* ドメイン判断 (検証、plan、材料、写像) は、サービスに委譲する。
* パス・ボディ・不足コードの意味を、本プラグインで再定義しない。

### 3. 一方向同期

* WordPress → Zoom が正である。
* Zoom 側の手修正を検知してイベント項目を上書きしない (初版)。
* 「Zoom で開く」(`get`。再取得と同義) は表示用。`build_webinar_request( 'get', … )` 直呼び。タイトル・日時等を Zoom で上書きせず、レコードの `status` も変えない。create / update / get いずれも `join_url` / `webinar_uuid` 非空のときだけメタ (と `join_url` なら公開欄) を更新 (空なら既存を消さない。空にするのは delete 成功時のみ)。失敗時は `last_error` 可。`start_url` は保存しない (サービス result_spec)。

### 4. dirty / synced の所有

* **業務判定は、本プラグイン** (`_s2j_webinar_last_sent` との比較)。
* 本体項目 (topic / 日時 / 録画 / 参加登録等) が変わった場合だけ `dirty` にする。
* 登壇者だけ・アンケートだけの変更は `synced` のままでよい (サービスの差分 / `attach_survey`)。
* サービスは渡された `status` を推測し直さない。

### 5. 不足と例外

* レコード不足と未知 `provider` は、サービスの `deficiencies`。HTTP しない。
* プログラマー誤り (未知 op、必須 step 欠落等) は、サービスの例外。呼び出し側で握りつぶさない。
* 不足がある場合、書き込み用の `plan` を呼ばない。
* 「Zoom で開く」は書き込みパイプラインと分ける。ゲートは `webinar_id` 非空のみ。topic / 登壇者 / 日時の不足ではとめない (手順は [sync_execution_spec.md](./sync_execution_spec.md))。

### 6. プロバイダ

* 初版の接続先 UI は Zoom 固定。選択一覧は出さない。
* サービスは記述子 + Adapter 関数のレジストリ。実装済みが増えた場合は設定で選べる。
* 使わない接続先の空実装は置かない。

### 7. 秘密情報

* クライアント ID / シークレット、トークン、Webhook 秘密はサイト設定。配布物とログに出さない。
* 保存後のシークレットは再表示しない。
* `Authorization` は本プラグインが付け、サービス材料には含めない。

### 8. GatherPress 非改変

* フォークに本プラグインのコードを入れない。
* パネルは Slot `EventPluginDocumentSettings` の Fill のみ。
* メタ保存は明示の投稿更新。Zoom HTTP は明示ボタンのみ。

## 借用する原則

FOP (Functional Object-Oriented Programming) と Clean Coding を土台とします。[kis-wordpress エコシステム仕様](https://github.com/stein2nd/kis-wordpress/blob/main/docs_mod/specs.md) および [wp-plugin-spec](https://github.com/stein2nd/wp-plugin-spec) と同様です。

| 原則 | 本プラグインでの意味 |
| --- | --- |
| 依存の向きは外 → 内 | 画面と HTTP がサービスを呼ぶ。サービスは WordPress を呼ばない |
| 内側はビジネスルール | Webinar 項目の検証と次の操作はサービス |
| 外側は詳細 | OAuth、メタ、GatherPress、HTTP |
