<!--
目的：「validate → plan → build_webinar_request → HTTP → map」の実行契約
-->

# S2J Webinar - 同期実行の仕様

## 設計意図 (ゴール)

パネル操作から Zoom までの一連の実行順と、失敗時の中断・メタ書き戻しを固定します。列の意味と材料の正本はサービスです。

## 非対象

* パス / ボディ / 写像規則 (サービス `docs/core/`)
* OAuth 材料の中身 (サービス oauth_spec。トークン保存は [oauth_and_settings_spec.md](./oauth_and_settings_spec.md))

## 前提

* Composer パッケージ `s2j/webinar-service` を Packagist 名だけで require している。
* アクセス・トークンが有効 (必要ならリフレッシュ済み)。
* 呼び出しは UI → 本プラグイン PHP → Service。クライアントから Service を直接呼ばない。

## パイプライン (書き込み…「同期」/ 削除)

「Zoom で開く」は本パイプラインを使わない。下記 [get (「Zoom で開く」)](#get-zoom-で開く) を使う。

```text
1. メタ + GatherPress から WebinarRecord を組み立てる。
   Survey `ready` はレコードに載せない。context の survey_document のみ
   ([survey_handoff_spec.md](./survey_handoff_spec.md))
2. dirty / synced / not_created / error を本プラグインが付与する ([data_dictionary.md](./data_dictionary.md))
3. validate_webinar_record
4. deficiencies 非空 → 適切なメッセージ文。HTTP しない。
   レコード status を synced 等に進めない
5. plan_webinar_operations( record, context )
6. 各 step について:
   a. build_webinar_request( step.op, record, step )
   b. deficiencies 非空 → 中断
   c. 呼び出し種別に応じた Authorization を付けて HTTP
      (正本: [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)。本パイプラインは Webinar REST = Bearer)
   d. map_webinar_response( op, status, body, record )
   e. create / update の map 成功直後 →
      `_s2j_webinar_last_sent` を本体項目で update_post_meta (列の登壇者 / survey 成否を待たない)
   f. 登壇者追加・削除の map 成功直後 →
      `_s2j_webinar_last_panelists` を、その時点のレコード登壇者列
      (希望の最終形 = いまのパネル／レコード全体) で丸ごと update_post_meta。
      op 単位で Zoom 実体を再構築しない。列中断しても「成功した op までの希望列」が残る
   g. deficiencies 非空、または record.status === 'error' →
      残りの Webinar メタを書き戻して列中断
   h. record を写像結果で更新し、次の step
7. 列終了後 (または中断時)、webinar_id / status / last_error 等の
   残メタを書き戻す。join_url / webinar_uuid は get と同型:
   - join_url 非空 → メタと公開欄を更新。空 → 既存の join_url メタ / 公開欄は触らない
   - webinar_uuid 非空 → メタを更新。空 → 既存の webinar_uuid メタは触らない
   ([gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md))
   ※ last_sent / last_panelists は手順 e/f で既に永続化済み
   ※ join_url / webinar_uuid を空にするのは delete 成功時のみ (下記)
```

サービス側の使用例: [usage_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/interfaces/usage_spec.md)。  
本プラグインは usage の `intend_get` 例を採用しない。「Zoom で開く」は下記の `build_webinar_request( 'get', … )` 直呼びのみ。

## context の組み立て

| キー | 本プラグインの渡し方 |
| --- | --- |
| `previous_panelists` | `_s2j_webinar_last_panelists` (未設定時は空配列) |
| `intend_delete` | 「Zoom から削除」ボタンのみ |
| `intend_get` | **付けない。**「Zoom で開く」は `build_webinar_request( 'get', … )` 直呼び (下記)。通常の「同期」にも付けない |
| `intend_retry` | status が `error` かつ id 非空で、ユーザーが「同期」した場合 |
| `survey_document` | `_s2j_webinar_survey` が `ready` の場合のみ非空。それ以外は渡さない / null |
| `webinar_title` | レコードの `topic` (任意) |

`last_sent` (本体項目) と `last_panelists` (登壇者) は混ぜません。

## 操作別の注意

| 操作 | 本プラグイン |
| --- | --- |
| create | 実行順は create → (成功後) 登壇者追加 → (任意) `attach_survey`。追加失敗でも Webinar は削除しない |
| update | plan が返す場合だけ実行する (`dirty`、または `error` かつ id 非空で `intend_retry`。サービス operation_spec と同じ)。当該 op の map 成功直後に `last_sent` を永続化 |
| delete | パネル明示のみ。成功後: webinar_id / webinar_uuid / join_url を空、公開欄を空、`last_sent` と `last_panelists` を消す、セッションを `idle` (またはメタ削除)、status は `not_created` |
| get | 下記専用の経路。書き込みパイプラインと共用しない |
| 登壇者 (Panelist) | メール単位の remove。全員の一括削除 API は使わない (サービス規則) |
| attach_survey | [survey_handoff_spec.md](./survey_handoff_spec.md)。通常の「同期」に含める。別ボタンは持たない |

### get (「Zoom で開く」)

書き込み用の `validate` → `plan` は通しません (topic / 登壇者 / 日時の不足でとめない)。`plan(…, { intend_get: true })` は使いません (dirty 等で書き込み op が混ざるのを防ぐ)。ゲートは **`webinar_id` 非空** のみ。空なら適切なメッセージ文で終了し、HTTP しません。

1. webinar_id 非空を確認
2. `build_webinar_request( 'get', record, [] )` のみ
3. Webinar REST 用 Authorization (`Bearer`) を付けて HTTP ([oauth_and_settings_spec.md](./oauth_and_settings_spec.md))
4. `map_webinar_response( 'get', … )`
5. start_url をその場で開く (保存しない)
6. 成功時:
   - 応答の join_url が非空 → メタと公開欄を更新 (必須)
   - 応答の join_url が空 → 既存の join_url メタ / 公開欄は触らない
   - 応答に webinar_uuid があればメタを更新。なければ触らない
7. 失敗時: last_error を書いてよい。status / topic / 日時 / webinar_id は変えない

* `topic` / 日時 / レコード status を Zoom 値で上書きしない (サービス result_spec)。
* 再取得と同義。別ボタンは置かない。

空の `operations` (書き込み plan) は正常です (`none` という op はない)。ユーザーに「変更なし」旨を出してよい。

## HTTP 成功の扱い

* サービスの map が `2xx` を成功とみなす (正本は result_spec)。
* 本プラグインは、ステータス・コードとボディを map に渡し、独自に成功判定を二重定義しない。

## 例外

* サービスの `InvalidArgumentException` (未知 op、必須 step 欠落等) は握りつぶさない。ログ (トークンなし) と適切なメッセージ文。
* ネットワークエラーは `error` + `last_error` をメタに残して中断してよい。

## 関連

* 管理 UI: [admin_ui_spec.md](./admin_ui_spec.md)
* 原則: [principles.md](./principles.md)
