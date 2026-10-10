<!--
目的：「UTM 別 Source Tracking + QR (v0.0.2) の起草仕様」
-->

# S2J Webinar - 運用素材 (UTM 別 Source Tracking + QR)

**所有者:** コンパニオン WordPress プラグイン (仮称 **S2J Webinar Source QR**)。Zoom URL 材料は **s2j-webinar-service**。本ドキュメントは **S2J Webinar リポジトリ上の handoff 起草** です (プラグイン本体の `docs/` は別リポジトリ)。

## 設計意図 (ゴール)

Zoom Webinar の **視聴登録用 URL** を、流入経路 (UTM / Source Tracking チャネル。例: `web` / `sns` / `other`) ごとに扱い、**同一チャネルごとに QR コード PNG** を生成・プレビュー・保存します。

Zoom 管理画面の「招待状」→「ソース追跡リンク」メンテナンスに近い UX を、GatherPress イベント編集から開ける **メンテナンス用ダイアログ** で提供します (S2J Webinar Survey の設問メンテナンスと **同型**)。

**基本方針:** QR に埋め込む URL は、Zoom が返した tracking URL を **加工しません** (クエリーの付け足しや独自 UTM の上書きをしない)。

## 非目標

* WordPress 独自の流入 analytics 基盤 (Zoom Source Tracking の代替)。
* 配配メール (ラクス) の配信・効果測定。
* CoverArt / フライヤー (本プラグイン。[coverart_flyer_draft.md](./coverart_flyer_draft.md))。
* registrant 登録、出席取り込み (v0.0.1非目標のまま)。

## パッケージ分割

CHANGELOG (v0.0.1) の「QR は後続の **別プラグイン** が持つ」方針と整合します。

| 層 | 所有者 | 役割 |
| --- | --- | --- |
| イベントパネル入口 | **S2J Webinar** | 「ソース追跡 / QR を編集」リンク、**サマリー** (チャネル数、最終更新、ready 可否)。設問 UI を持たないのと同様に QR 編集 UI は持たない |
| メンテナンス UI + 文書メタ | **Source QR プラグイン** | モーダル (または専用 Fill)、下書き編集、プレビュー、PNG 保存 |
| tracking URL の材料・写像 | **s2j-webinar-service** | API 応答 → チャネル別 URL 列。検証 (URL 非空、サイズ上限等) |
| OAuth + Zoom HTTP | **S2J Webinar** (既存) | プラグインが Service 材料を得るため、**既存トークンと HTTP アダプタを再利用** するか、プラグインが同アカウント設定を読むかは実装時に1つに固定 (起草では **サイト設定共有・トークンは Webinar プラグインが正本** を推奨) |
| QR マトリックス + ロゴ合成 (PNG バイト) | **Source QR プラグイン** | プレビュー: ブラウザ (Canvas / data URL)。永続化: PHP (GD 等) |
| QR 符号化ライブラリ | **プラグイン (JS + PHP)** | ドメイン判断 (色 hex、辺 px 範囲) のみ Service 可 |

## 画面フロー (4ステップ)

コンパニオン・プラグインのメンテナンスダイアログ (呼び出し元: イベント ID、Zoom `webinar_id` が既知であること)。

### 1. チャネルと Source Tracking URL

* **入力:** チャネル一覧 (初期セット案: `web` / `sns` / `other`。ラベルは i18n。追加チャネルは v0.0.2では固定3でも可)。
* **構築 (API が使える場合):**「Zoom から取得」または「同期」で、Service 経由の HTTP によりチャネルごとの **登録 URL** を埋める。
* **フォールバック (API NG):** チャネルごとに URL を **手入力** (読み取り専用にしない)。Zoom 管理画面からのコピー & ペーストを想定。

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
* Webinar の Zoom **「同期」** パイプラインには **載せない** (Survey の `attach_survey` とは別。QR は集客素材であり Zoom webinar ボディではない)。

## 文書メタ (プラグイン所有・起草)

Survey の `_s2j_webinar_survey` と同様、**プラグインが所有** するイベントメタ1キー (オブジェクト JSON) を想定します。キー名は正本反映前に確定します (案: `_s2j_webinar_source_qr`)。

**S2J Webinar プラグイン** は文書を **書き戻しません**。読み取りはサマリー表示のみです (handoff 正本は将来 `docs/source_qr_handoff_spec.md`)。

| フィールド (案) | 説明 |
| --- | --- |
| `status` | `draft` / `ready` (Survey と同型。Webinar レコード status と混同しない) |
| `channels` | `{ id, label?, registration_url, qr_attachment_id? }[]` |
| `style` | `{ icon_attachment_id, foreground_hex, size_px }` |
| `updated_at` | 日時文字列 (任意。形式はプラグインで固定) |

## Service 境界 (s2j-webinar-service)

プラグインは `validate` → (必要なら) `build_webinar_request` 相当の **tracking 用 op** → HTTP (Webinar プラグイン・アダプタ経由) → `map` の形を Survey handoff に倣うか、**tracking 専用の小さな PHP API** を Service に追加します (正本は webinar-service 側で起草)。

### 載せる

* Zoom API 応答から **チャネル別 registration URL** を写像する純関数。
* URL 列・色・サイズの **検証** (不足コード)。

### 載せない

* PNG / SVG バイト生成、GD 操作。
* WordPress attachment、メディアアップロード。

## ライブラリ

| 用途 | 層 | 備考 |
| --- | --- | --- |
| QR 符号化 (プレビュー) | JS (npm) | 管理画面バンドルのみ |
| QR 符号化 + 合成 (保存) | PHP | プラグイン `includes/`。Composer 可 |
| Zoom Source Tracking | — | Service + Webinar HTTP。API 調査 ([README.md](./README.md)) 次第 |

## 前提・ゲート

| 前提 | 内容 |
| --- | --- |
| `webinar_id` | イベントに Zoom Webinar が作成済み (空ならダイアログは **説明のみ** または open 不可) |
| API Go | チェックリスト完了 + 写像実装 |
| API No-Go | 手入力 URL + ステップ2〜4のみ自動化 |

## S2J Webinar パネル (handoff 起草)

* パネルに **ソース追跡 / QR** 行を追加し、操作でコンパニオン UI を開く。
* Survey 未インストール時と同様、Source QR プラグイン未インストール時は **短い案内** (任意)。
* v0.0.2では Webinar 側から QR PNG を **直接ダウンロード** しなくてもよい (プラグイン・ダイアログ内で完結)。

## アンインストール

* **Source QR プラグイン:** 所有メタ (案 `_s2j_webinar_source_qr`) と、当該メタが指す QR 用 attachment を削除するかは **方針決定** (Survey に倣いメタのみ削除・メディア残し、を初期案)。
* **S2J Webinar プラグイン:** Source QR のメタは触らない (Survey と同型)。

## テスト観点 (起草)

* 手入力 URL のみで QR プレビューから PNG のメディア保存まで通す。
* API モックでチャネル URL が埋まり、QR がチャネル数分生成される。
* `webinar_id` 空で入口がブロックまたは案内される。
* Webinar `dirty` / 同期 plan に QR 文書が混入しない。

## 改訂履歴 (起草)

| 日付 | 内容 |
| --- | --- |
| 2026-10-10 | コンパニオン・プラグイン、4ステップ UI、境界、メタ案を起草 |
