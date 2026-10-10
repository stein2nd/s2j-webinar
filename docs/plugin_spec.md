# S2J Webinar - プラグイン仕様 (統合見取り図)

統合見取り図として `docs/` に置きます。細部の正本は分割仕様 ([specs.md](./specs.md) の一覧) です。計算の正本は [Webinar Service の docs/](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/specs.md) です。記録日は2026-10-09です。

## 概要

本プラグインは、GatherPress のイベント編集画面から Zoom Webinar を作成・更新・削除し、「Zoom で開く」(`get`) するための WordPress コンパニオンです。管理画面のプラグイン名は「S2J Webinar」です。

スラッグは `s2j-webinar` です。ライセンスは GPL-3.0-or-later です。テキストドメインは `s2j-webinar` です。

両立したいのは、下記の2つです。

* 営業担当者が、イベントと同じ編集画面から Webinar を登録・更新できる。
* Zoom REST の規則を WordPress に埋め込まず、[S2J Webinar Service](https://github.com/stein2nd/s2j-webinar-service) の純関数に閉じる。

## 位置付け

| 層 | 名称 | 役割 |
| --- | --- | --- |
| イベント UI | [stein2nd/gatherpress](https://github.com/stein2nd/gatherpress) | イベントの編集画面。本プラグインのコードはフォークに置かない |
| 呼び出し側 | **本プラグイン** | OAuth、HTTP、メタ、画面、Webhook、メタ ↔ WebinarRecord、`dirty` / `synced` 判定 |
| 計算 | [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) | 検証、操作計画、リクエスト材料、応答写像。WordPress を知らない |
| 設問 | [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey) | 設問の検査と保存。本プラグインは `ready` 文書の添付だけ |

## 目的

初版で、運営者がイベント編集画面から、下記を終えることです。

* サイトに Zoom を1アカウント接続する。
* 録画方式・参加登録・登壇者をイベントごとに編集し、メタに保存する。
* 新規登録・更新・削除・「Zoom で開く」(`get`。再取得と同義) をパネルから行う。
* 参加 URL を GatherPress の公開欄に反映する。
* Webhook で開催中 / 終了を反映する。
* Survey の `ready` 文書があれば `attach_survey` で付ける (写像はサービス)。

## 非目標 (初版)

* 検証・操作計画・パス / ボディ / 応答写像の規則本体 (サービス)
* Zoom 作成画面の全項目再現
* 出席・録画ファイル・効果測定の取り込み、registrant 登録
* Zoom 手修正の双方向同期
* CoverArt、QR、フライヤー、配配メール (ラクス) (後続)
* Teams / Meet 等 (初版は Zoom 固定。選択 UI は実装済みが増えた場合)
* GatherPress フォーク本体の改変、WordPress.org への掲載
* 設問の検査・助言 (Survey / Survey Service)

## サービスとの境界

| 本プラグイン | サービス |
| --- | --- |
| メタと GatherPress から WebinarRecord を組み立てる | レコードのキー意味を定義する (WP キー名は知らない) |
| `_s2j_webinar_last_sent` と比較して `dirty` / `synced` を付ける | 渡された `status` を推測し直さない |
| `validate` → 不足なら適切なメッセージ文。HTTP しない | 不足コード列を返す |
| `plan` → 各 op で `build_webinar_request` → 種別ごとの `Authorization` 付き HTTP → `map` | 材料と写像 |
| 「Zoom で開く」で `start_url` を開く (保存しない)。create / update / get いずれも `join_url` / `webinar_uuid` 非空ならメタ (と `join_url` なら公開欄) を更新。空なら既存を消さない | `map` の揮発 `start_url` とレコード写像 |
| Survey の `_s2j_webinar_survey` が `ready` なら context に載せる | `attach_survey` 材料 |

詳細: [sync_execution_spec.md](./sync_execution_spec.md)、[data_dictionary.md](./data_dictionary.md)、[survey_handoff_spec.md](./survey_handoff_spec.md)。

## サイト設定・画面・同期および Webhook

要約のみです。正本は分割仕様です。

* OAuth とサイト設定: [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)
* イベントパネル: [admin_ui_spec.md](./admin_ui_spec.md)
* GatherPress 境界: [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)
* 同期実行: [sync_execution_spec.md](./sync_execution_spec.md)
* Webhook: [webhook_spec.md](./webhook_spec.md)
* キー一覧: [data_dictionary.md](./data_dictionary.md)

## 設計方針

[kis-wordpress エコシステム仕様](https://github.com/stein2nd/kis-wordpress/blob/main/docs_mod/specs.md) および [wp-plugin-spec](https://github.com/stein2nd/wp-plugin-spec) に従います。ドメイン判断はサービス側の純関数です。本プラグインはアダプタです。詳細は [principles.md](./principles.md) です。

Composer で `s2j/webinar-service` を require します。参照は [S2J Slug Generater](https://github.com/stein2nd/s2j-slug-generater) が `s2j/similarity-service` を Packagist の名前だけで require するのと同じです。`repositories` に `VCS` も `path` も書きません。

プラグイン無効化の場合は、メタとサイト設定を残します。プラグインをアンインストールの場合は、本プラグイン所有のイベントメタ (明示リスト。`_s2j_webinar_survey` は除外) と本プラグインのサイト option だけを消します。ワイルドカード `_s2j_webinar_*` では消しません。Zoom 上の Webinar は変更しません (削除 API は呼ばない)。所有キーの正本は [data_dictionary.md](./data_dictionary.md) です。

## 関連リポジトリ

| 名称 | 種別 | 役割 |
| --- | --- | --- |
| **本プラグイン** | WP プラグイン | OAuth、HTTP、メタ、画面、Webhook |
| [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) | Composer | 検証、計画、材料、写像 |
| [s2j-webinar-survey](https://github.com/stein2nd/s2j-webinar-survey) | WP プラグイン | 設問の編集と `ready` 保存 |
| [s2j-webinar-survey-service](https://github.com/stein2nd/s2j-webinar-survey-service) | Composer | 設問の検査・助言 |
| [stein2nd/gatherpress](https://github.com/stein2nd/gatherpress) | WP プラグイン (フォーク) | イベントの編集画面 |
| [kis-wordpress](https://github.com/stein2nd/kis-wordpress) | モノレポ | サイト専用プラグイン群 |
| [kis2026_base](https://github.com/stein2nd/kis2026_base) | テーマ | 見た目。本機能の画面は持たない |

## 実装順

1. プラグインの骨格、サイト設定、OAuth、トークン保存。
2. イベントパネル (Slot Fill)、メタ保存、レコード組立。
3. validate → plan → `build_webinar_request` → HTTP → map (作成・更新)。参加 URL の公開欄反映。
4. 削除・「Zoom で開く」(`get`)、登壇者 (Panelist) 差分。
5. Webhook (`webinar.started` / `webinar.ended`)。
6. Survey `ready` の `attach_survey`。
7. CoverArt / QR 等は後続。

## 採用した方針 (要約)

* 本プラグインはアダプタ。計算規則はサービス。メタキー名は本仕様だけに置く。
* サイトに Zoom は1アカウント。初版の接続先 UI は Zoom 固定。
* 参加登録のデフォルトは必須・自動承認 (`0`)。録画のデフォルトは `cloud`。
* `dirty` は本体項目の変更のみ。登壇者だけ・アンケートだけは `synced` のまま。`error`+id の「同期」は `intend_retry` (サービスは dirty と同計画)。
* 削除はパネルの明示操作だけ。ゴミ箱移動では delete を呼ばない。
* `start_url` は保存しない。「Zoom で開く」は `build_webinar_request( 'get', … )` 直呼び。create / update / get いずれも `join_url` / `webinar_uuid` 非空ならメタ (と `join_url` なら公開欄) を更新 (空なら既存を消さない。空にするのは delete 成功時のみ)。
* パネルは Slot `EventPluginDocumentSettings` の Fill のみ。メタ保存は明示の投稿更新 + `save_post` (Survey に倣う)。Zoom HTTP は明示ボタンのみ。Authorization の種別は [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)。
* Survey 未完了を create の条件にしない。`ready` は context の `survey_document` のみ (レコードに載せない)。通常の「同期」が `attach_survey` する (専用ボタンなし)。
* Webhook `webinar.ended` では公開リンクを空にしない。空にするのは Zoom 削除成功時のみ。
* アンインストールは本プラグイン所有キーの明示リスト + サイト option のみ。`_s2j_webinar_survey` は触らない。Zoom は変更しない。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-09 | `docs_mod/` に分割起草。Survey / Alliance / webinar-service に倣う索引と境界を記録 |
| 2026-10-09 | 確定正本を `docs/` に移行した |
| 2026-10-09 | 監査 BP: アンインストール明示リスト、delete で last_panelists/セッション処理、get と join_url、ended 非空、保存経路、last_sent タイミング、intend_get、Webhook REST、用語、と記録 |
| 2026-10-09 | 再監査 BP: 再取得は Zoom で開くと同義、duration floor、last_* 即時永続、intend_retry UI、get 写像範囲、個別メタ、登壇者1人以上、トークン暗号化は後続、と記録 |
| 2026-10-09 | 第3監査 BP: get 専用経路、メタ REST 範囲、join_url 必須反映、登壇者0は UI ブロック、concept 図注記、webinar_uuid と ready 表記、と記録 |
| 2026-10-09 | 第4監査 BP: get は build のみ、REST 除外の切り分け、登壇者 [] は投稿保存可、空 join_url は既存維持、FOP 展開、配配メール (ラクス)、と記録 |
| 2026-10-09 | 第5監査 BP: create/update の空 join_url も既存維持、intend_get は usage 非採用と明記、登壇者必須は同期ゲート、API 名は build_webinar_request、status 後続に配配メール (ラクス)、と記録 |
| 2026-10-09 | 第6監査 BP: create/update の空 webinar_uuid も既存維持、status の get 完了条件にメタ、冒頭コメントの API 名統一、と記録 |
| 2026-10-09 | 第7監査 BP: concept 操作表と specs 読み方のパイプライン表記を正本にそろえた、と記録 |
| 2026-10-09 | 第8監査 BP: status の get 完了条件に webinar_uuid を含めた、と記録 |
| 2026-10-10 | OAuth は REST=Bearer / token・refresh=Basic+フル URL、update は intend_retry も、last_panelists は希望列丸ごと、Survey は context のみ、と記録 |
