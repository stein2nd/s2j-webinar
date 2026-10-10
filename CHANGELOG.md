# S2J Webinar - CHANGELOG

## unreleased

## 0.0.1 - 2026-10-10

### Changed

* OAuth HTTP をサービス契約に追随: Webinar REST は Bearer、token/refresh は Basic + フル URL の `path`、body は form-urlencoded
* `update` は `dirty` または `intend_retry` (error+id)。`last_panelists` は希望の登壇者列を丸ごと即時永続化し、列中断時も成功した op までの希望列を残す
* 同期手順1を「Survey は context の `survey_document` のみ」に修正。Authorization の粒度を oauth_and_settings に集約
* 確定仕様の表記を「とき→場合」にそろえた

## 0.0.1 - 2026-10-09

* 確定した仕様キットを `docs_mod/` から `docs/` に移した。`docs_mod/` は改訂案と進行中イニシアチブの起草用として残す。
* `README.md` の案内を [docs/specs.md](docs/specs.md) に切り替えた。
* `docs/` の Source of Truth とガバナンスを確定正本向けに更新し、`docs_mod/README.md` を起草用の説明に差し替えた。

### Added

* `docs_mod/` を Survey / Alliance / webinar-service に倣い分割起草した (索引、Why / What / How、ガバナンス、archive)
* メタ ↔ WebinarRecord、`dirty` / `synced`、`last_sent` / `last_panelists`、OAuth、同期実行、Webhook、Survey 受け渡しの境界を記録した
* `README.md` に仕様キット、依存 (`GatherPress` / `s2j/webinar-service`)、開発コマンド (`npm install` / `lint:docs`) を案内した

### Changed

* `docs_mod/specs.md`: メタキー ↔ WebinarRecord 対応表の正本は本プラグインであると明記。`dirty` 判定は `_s2j_webinar_last_sent` で本プラグインが行う。OAuth に `webinar:update:survey` を追加
* プロバイダはサービス側アダプタで差し替え可能とする。初版は `zoom` のみ。管理画面での選択は、実装済みが増えた場合に出す
* 参加登録のデフォルトは必須・自動承認 (`0`) にそろえる (サービス record_spec と同じ。2026-10-05記載の「不要」は破棄)
* `docs_mod/` 監査 BP を反映: アンインストールは所有キーの明示リスト (`_s2j_webinar_survey` 除外)、delete で `last_panelists` / セッション処理、get は `join_url` 反映可・`start_url` 非永続、`webinar.ended` は公開リンク非空、メタ保存は明示の投稿更新、`last_sent` は create/update map 成功直後、`intend_get` は「Zoom で開く」のみ、Webhook はプラグイン REST、Survey 添付は通常同期、用語を統一
* `docs_mod/` 再監査 BP を反映: 再取得は「Zoom で開く」と同義、`duration_minutes` は floor、`last_*` は map 成功直後に永続、`intend_retry` を admin UI に明記、get 写像範囲を固定、メタは個別キー、登壇者1人以上、トークン暗号化は後続
* `docs_mod/` 第3監査 BP を反映: get は書き込みと別経路 (`webinar_id` のみゲート)、メタ REST はパネル編集キーのみ、`join_url` 成功時は必須反映、登壇者0は UI でブロック、concept に即時永続の注記、`webinar_uuid` と `ready` 表記を統一
* `docs_mod/` 第4監査 BP を反映: get は `build_webinar_request( 'get', … )` のみ (`intend_get` plan 不使用)、REST 除外はカスタム REST / WP-CLI 等 (コア投稿 REST は許可)、登壇者 `[]` は投稿保存可、空の `join_url` 応答では既存を消さない、FOP を初出展開、配配メール (ラクス) と明記
* `docs_mod/` 第5監査 BP を反映: create / update の空 `join_url` も既存維持 (delete のみ空化)、usage の `intend_get` 例は非採用と明記、登壇者「1人以上」は同期ゲート、API 名は `build_webinar_request` に統一、`status.md` 後続に配配メール (ラクス)
* `docs_mod/` 第6監査 BP を反映: create / update の空 `webinar_uuid` も既存維持 (delete のみ空化)、`status.md` の get 完了条件にメタ、`sync_execution_spec` 冒頭コメントの API 名を `build_webinar_request` に統一
* `docs_mod/` 第7監査 BP を反映: `concept.md` 操作表と `specs.md` 読み方のパイプライン表記を正本 (`build_webinar_request` / get の uuid 非破壊) にそろえた
* `docs_mod/` 第8監査 BP を反映: `status.md` の get 完了条件に `webinar_uuid` 非空時のメタ更新を含めた

## 0.0.1 - 2026-10-08

* 確定前の仕様と変更履歴の表記をそろえた。「とき」を「場合」または「際」に、「次の」を「下記の」に直し、OAuth はユーザー管理アプリケーションと書く。

## 0.0.1 - 2026-10-07

* セッション中の Q&A は、作成で `settings.question_and_answer` の一式を送る。`enable` = `true`、`allow_anonymous_questions` = `true`、`answer_questions` = `only` である。コメントと upvote は送らない。
* HD は `settings.hd_video` = `false`、出席者の参加時認証は `settings.meeting_authentication` = `false` である。パネリスト認証とチャットのデフォルト対象は送らない。
* アンケート添付は `PATCH /webinars/{webinarId}/survey` で行う。写像はサービスが持つ。
* 初版の設問 `type` は `single` / `multiple` / `short_answer` / `long_answer` / `rating_scale` である。画像、スキップロジック、マッチング、ランク順、空欄に記入する、は初版以降の検討である。

## 0.0.1 - 2026-10-06

* 新規登録は二段階とする。ウェビナー ID は作成応答の `id` であり、発行は購読しない。その ID で、スケジュール後のタブの初期値を追加リクエストする。
* 参加登録のデフォルトは必須・自動承認とする。録画のデフォルトはクラウドである。トピックは200文字、説明は2000文字までとする。パスコードは送らない。
* オーディオはコンピューター、ホストとパネリストのカメラは off である。セッション中の Q&A は、使う、匿名可、出席者に見せるのは回答済みだけ、を一式で送る。
* 定員を超えそうな場合は、ライブストリームを別途検討する旨をパネルに出す。配信 URL とストリームキーは送らない。
* アンケート設問は S2J Webinar Survey が作る。本プラグインは、作成直後に使ってよい文書をアンケートとして付ける。

## 0.0.1 - 2026-10-05

* 確定前の仕様に、GatherPress の日時キー、所要時間、終日 (暦日数に1440分を掛けた所要時間) を記録した。
* OAuth はユーザー管理アプリケーションとし、アクセス・トークンは期限まで使い回す。戻り口は `admin-post.php` の固定パスである。
* イベントメタの接頭辞は `_s2j_webinar_` とする。参加登録はイベントごとのラジオで、デフォルトは不要である。
* 参加 URL の公開欄は `gatherpress_online_event_link` とし、開始と終了は `webinar.started` と `webinar.ended` で受ける。
* Zoom の削除はパネルの明示操作だけである。登壇者は外した人ごとに削除し、開始 URL は押した際に再取得する。
* セッション中の Q&A は初版では送らない。作成で省略してアカウント設定を継ぐのは、Zoom が公式に挙げた7項目だけである。
* Composer は Packagist の `s2j/webinar-service` だけを参照する。QR は後続の別プラグインが持つ。

## 0.0.1 - 2026-10-04

* 開発依存の `@s2j/docs-linter` を v1.0.27に更新した。
* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘は残す。配布物には含まれない。

## 0.0.1 - 2026-10-03

* `fsevents` と `@parcel/watcher` のインストールスクリプトを許可し、開発時のファイル監視でネイティブ実装を使えるようにした。
* 開発依存の `braces@3.0.3` に、深くネストしたパターンで Node.js プロセスが終了する指摘 (GHSA-vfj7-8cjw-p6xm) がある。修正版は未公開で、配布物には含まれない。
