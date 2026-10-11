<!--
目的：「UTM 別 Source Tracking + QR (v0.0.2) の起草仕様」
-->

# S2J Webinar - 運用素材 (UTM 別 Source Tracking + QR)

**本ドキュメントの置き場:** [S2J Webinar](https://github.com/stein2nd/s2j-webinar) リポジトリ (`docs_mod/v0.0.2/`)。**コンパニオン本体** の仕様正本は [s2j-webinar-source-qr](https://github.com/stein2nd/s2j-webinar-source-qr) の `docs/` に置く (Survey / [survey_handoff_spec.md](../../docs/survey_handoff_spec.md) と同型)。

## S2J Webinar Source QR との関係

| 項目 | 内容 |
| --- | --- |
| コンパニオン | **[S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr)** — スラッグ `s2j-webinar-source-qr`、表示名 S2J Webinar Source QR |
| 本プラグイン | **[S2J Webinar](https://github.com/stein2nd/s2j-webinar)** — OAuth、接続先同期、イベントパネル、CoverArt / フライヤー (v0.0.2) |
| UX 参照 | [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey) — 別 Fill ・別メタ・本プラグインは handoff のみ |

### 所有 (メタと責務)

| 対象 | 所有者 |
| --- | --- |
| `_s2j_webinar_source_qr` (文書メタ) | **S2J Webinar Source QR** |
| `_s2j_webinar_id` / `_s2j_webinar_provider` 等 | **S2J Webinar** ([data_dictionary.md](../../docs/data_dictionary.md)) |
| 取り込み GET の写像・検証 | **s2j-webinar-service** (プロバイダ Adapter) |
| 取り込み HTTP (OAuth 付き) | **S2J Webinar** (token 正本。Source QR は読まない) |
| QR 符号化・ PNG ・メンテナンス UI | **S2J Webinar Source QR** |

### handoff (本プラグイン側)

* イベントパネルに **入口** (ソース追跡 / QR) と **サマリー** (`ready`、チャネル数等) のみ。QR 編集 UI は持たない。
* `_s2j_webinar_source_qr` は **読み取りのみ** (サマリー表示)。**書き戻さない** (Survey の `_s2j_webinar_survey` と同型)。
* 接続先 **「同期」** パイプラインには QR 文書を **載せない** (Survey の `attach_survey` とは別。集客素材であり Webinar ボディではない)。
* Source QR 未インストール時は短い案内を出してよい (任意)。Webinar 無効でも Source QR 単体の編集は可能 (Survey と同型)。
* 任意 **GET 取り込み** を実装する場合、HTTP は **S2J Webinar 経由** (内部 REST 等)。Source QR 向け bridge の契約は双方の `docs/` に書く。

### ドキュメント正本 (合意後)

| 内容 | 正本リポジトリ |
| --- | --- |
| Source QR の UI、永続化、QR 生成 | `s2j-webinar-source-qr` / `docs/` |
| 本プラグインからの handoff、パネル入口 | `s2j-webinar` / `docs/source_qr_handoff_spec.md` (名称 TBD) |
| 取り込み写像 (Adapter) | `s2j-webinar-service` / `docs/` |

## 設計意図 (ゴール)

接続先ウェビナー (プロバイダ) が発行する **視聴登録用 URL** を、流入経路 (チャネル。例: `web` / `sns` / `other`) ごとに扱い、**チャネルごとに QR コード PNG** を生成・プレビュー・保存します。

接続先の管理画面で行う「ソース追跡リンク」メンテナンスに近い UX を、GatherPress イベント編集から開ける **メンテナンス用ダイアログ** で提供します (S2J Webinar Survey の設問メンテナンスと **同型**)。

**基本方針:** QR に埋め込む URL は、接続先が発行した registration / tracking URL を **加工しません** (クエリーの付け足しや独自 UTM の上書きをしない)。

## プロバイダ (拡張方針)

本機能は **プロバイダ非依存** の UI と文書メタを持ち、取り込み API ・ポータル手順は **プロバイダごと** に差し替えます ([principles.md](../../docs/principles.md) §6に倣う)。

接続先の **選択 UI** は、v0.0.2では出しません (本プラグイン v0.0.1と同じ)。

| 層 | 方針 |
| --- | --- |
| v0.0.2実装 | **最初の対応プロバイダは `zoom` のみ**。イベントの `_s2j_webinar_provider` (未設定時 `zoom`) に一致するときだけ取り込み UI を出す |
| 拡張 | コントリビューターが Service の Adapter + (必要なら) Source QR プラグインのプロバイダ別 UI 片を追加できる形にする。使わない接続先の空実装は置かない |
| 共通 | チャネル行の `registration_url`、QR 符号化・合成、文書 `draft` / `ready` はプロバイダ共通 |
| 可変 | リンク **作成** (API / ポータル)、**GET 取り込み** の有無、外部 Webinar ID の意味 |

## 非目標

* WordPress 独自の流入 analytics 基盤 (接続先 Source Tracking の代替)。
* 配配メール (ラクス) の配信・効果測定。
* CoverArt / フライヤー (本プラグイン。[coverart_flyer_draft.md](./coverart_flyer_draft.md))。
* registrant 登録、出席取り込み (v0.0.1非目標のまま)。
* 公開 API / ポータルによるソース追跡リンク **新規作成** の WordPress 再現 (プロバイダごとに可否が異なる。v0.0.2では [zoom の調査結果](./README.md) に従う)。
* v0.0.2以外のプロバイダ実装 (Teams / Meet 等)。

## パッケージ分割

CHANGELOG (v0.0.1) の「QR は後続の **別プラグイン** が持つ」方針と整合します。

| 層 | 所有者 | 役割 |
| --- | --- | --- |
| イベントパネル入口 | **S2J Webinar** | 「ソース追跡 / QR を編集」リンク、**サマリー** (チャネル数、最終更新、ready 可否)。設問 UI を持たないのと同様に QR 編集 UI は持たない |
| メンテナンス UI + 文書メタ | **Source QR プラグイン** | モーダル (または専用 Fill)、下書き編集、プレビュー、PNG 保存 |
| tracking URL の材料・写像 | **s2j-webinar-service** | プロバイダ Adapter ごとの写像・検証。**リンク作成** は Service 対象外 (取り込み GET のみ) |
| OAuth + 接続先 HTTP | **S2J Webinar** (既存) | **token / OAuth option は Webinar のみが正本**。Survey と同様 Source QR は **読まない**。GET 取り込みは Webinar 経由の HTTP (内部 REST 等) |
| QR マトリックス + ロゴ合成 (PNG バイト) | **Source QR プラグイン** | プレビュー: ブラウザ (Canvas / data URL)。永続化: PHP (GD 等) |
| QR 符号化ライブラリ | **プラグイン (JS + PHP)** | ドメイン判断 (色 hex、辺 px 範囲) のみ Service 可 |

## 画面フロー (4ステップ)

コンパニオン・プラグインのメンテナンスダイアログ (呼び出し元: イベント ID、イベントメタの接続先 Webinar ID `_s2j_webinar_id` と `provider`)。

### 1. チャネルと registration URL

* **入力:** チャネル一覧 (初期セット案: `web` / `sns` / `other`。ラベルは i18n。v0.0.2では固定3でも可)。WordPress から **接続先に新規トラッキングリンクを API 作成することはしない** (プロバイダが許す場合でも v0.0.2スコープ外)。
* **接続先ポータル (運用):** プロバイダの管理画面でソース名を登録し、URL を発行する (手順はプロバイダ別。v0.0.2は [zoom](./README.md))。
* **取り込み (任意・プロバイダ依存):** 外部 Webinar ID があるとき、対応 Adapter 経由で既存トラッキング URL 一覧を取得し、チャネル行に割り当てる。未対応プロバイダではボタンを出さない。
* **手入力 (常に可):** 接続先ポータルからコピー & ペーストした URL をチャネルごとに貼る。取り込みなしでも v0.0.2完了可。

#### v0.0.2初実装 (プロバイダ `zoom`)

* 取り込み: `GET /v2/webinars/{webinarId}/tracking_sources` ([README.md](./README.md))。`source_name` / `tracking_url` を行にマッチまたは一覧表示。
* リンク作成: Zoom Web ポータル「招待状」→ ソース追跡のみ (REST 作成不可)。
* **後日検討:** 繰り返しウェビナーと GET 応答の実例差。GatherPress **1イベント = 1Webinar** 想定のため v0.0.2では扱わない。

### 2. QR 見た目パラメータ (チャネル共通 or チャネル別)

起草では **チャネル共通** から始め、必要なら v0.0.2.x でチャネル別に拡張します。

各チャネル行には **構築済み URL** (1で確定) を表示します。

| パラメータ | 説明 |
| --- | --- |
| 中央アイコン | メディアライブラリの attachment ID (正方形推奨。未指定可) |
| モジュール色 | QR の「黒」相当 (hex)。背景は白固定で開始可 |
| 出力サイズ | 正方形辺 px (上限は Service またはプラグインで clamp) |

### 3. 「QR コード生成」

* **その場プレビュー必須:** チャネル (UTM) 別に QR を一覧表示。別途ダウンロードしなくても視認できる。
* **実装:** クライアント側 QR 符号化 + 任意で中央アイコン合成 (Canvas)。サーバー往復は不要。
* **永続 PNG はこの時点では必須にしない** (4で保存)。

### 4. 「反映」または「保存」

* 文書メタを **`ready`** にし、チャネルごとの PNG をメディアライブラリに登録 (attachment ID を文書に保持)。
* ダイアログを閉じ、**S2J Webinar パネル** のサマリーが更新される (チャネル数、`ready`、最終更新の時刻 など)。
* 本プラグインの **「同期」** パイプライン (接続先 Webinar 本体) には **載せない** (Survey の `attach_survey` とは別。QR は集客素材であり Webinar ボディではない)。

## 文書メタ (プラグイン所有・起草)

Survey の `_s2j_webinar_survey` と同様、**プラグインが所有** するイベントメタ1キー (オブジェクト JSON) を想定します。キー名は正本反映前に確定します (案: `_s2j_webinar_source_qr`)。

**S2J Webinar プラグイン** は文書を **書き戻しません**。読み取りはサマリー表示のみです (handoff 正本は将来 `docs/source_qr_handoff_spec.md`)。

| フィールド (案) | 説明 |
| --- | --- |
| `status` | `draft` / `ready` (Survey と同型。Webinar レコード status と混同しない) |
| `provider` | 省略時はイベント `_s2j_webinar_provider` (空なら `zoom`)。取り込み Adapter の選択に使う |
| `channels` | `{ id, label?, registration_url, qr_attachment_id? }[]` |
| `style` | `{ icon_attachment_id, foreground_hex, size_px }` |
| `updated_at` | 日時文字列 (任意。形式はプラグインで固定) |

## Service 境界 (s2j-webinar-service)

プロバイダごとに `validate` → (取り込み時) tracking 用材料 → HTTP (Webinar プラグイン・アダプタ) → `map`、または **tracking 専用 PHP API** を Service に追加 (正本は webinar-service 側で起草)。Survey handoff と同型の列挙にする。

### 載せる

* 接続先 API 応答から **チャネル別 registration URL** への写像 (プロバイダ Adapter)。
* URL 列・色・サイズの **検証** (不足コード)。未知 `provider` は deficiencies。

### 載せない

* PNG / SVG バイト生成、GD 操作。
* WordPress attachment、メディアアップロード。

## ライブラリ

| 用途 | 層 | 備考 |
| --- | --- | --- |
| QR 符号化 (プレビュー) | JS (npm) | 管理画面バンドルのみ |
| QR 符号化 + 合成 (保存) | PHP | プラグイン `includes/`。Composer 可 |
| 接続先 Source Tracking 取り込み | — | プロバイダ Adapter + Webinar HTTP。v0.0.2は zoom のみ ([README.md](./README.md)) |

## 前提・ゲート

| 前提 | 内容 |
| --- | --- |
| `_s2j_webinar_id` | 取り込みには接続先 Webinar 作成済み。空でも **手入力 URL + QR** は可 (取り込みは無効) |
| `provider` | v0.0.2取り込み UI は `zoom` のみ。他は手入力のみ |
| リンク作成 | 接続先ポータル (v0.0.2zoom: REST 作成不可。[README.md](./README.md)) |
| GET 取り込み | プロバイダ Adapter + scope 実装時。未実装でも手入力で v0.0.2完了可 |

## アンインストール

**プラグインのアンインストール** と **プラグインが作った成果物 (メディア)** は分離する (Survey が Zoom 上リソースを触らないのと同型)。

* **Source QR プラグイン:** 所有メタ (案 `_s2j_webinar_source_qr`) とサイト option のみ削除。QR 用 PNG attachment は **削除しない** (メディアライブラリに残す)。
* **S2J Webinar プラグイン:** Source QR のメタは触らない (Survey と同型)。

## テスト観点 (起草)

* 手入力 URL のみで QR プレビューから PNG のメディア保存まで通す。
* API モックでチャネル URL が埋まり、QR がチャネル数分生成される。
* `_s2j_webinar_id` 空で取り込みが無効でも手入力 QR は通る。
* `provider` が `zoom` 以外のとき取り込み UI が出ない (手入力は可)。
* Webinar `dirty` / 同期 plan に QR 文書が混入しない。

## 改訂履歴 (起草)

| 日付 | 内容 |
| --- | --- |
| 2026-10-10 | コンパニオン・プラグイン、4ステップ UI、境界、メタ案を起草 |
| 2026-10-11 | 作成 REST 不可、ポータル + 手入力 / GET 取り込み、繰り返し Webinar 非対象 |
| 2026-10-11 | プロバイダ抽象 (zoom 初実装・拡張可能) を明文化 |
| 2026-10-11 | S2J Webinar Source QR との関係 (所有・ handoff ・正本) を明記 |
