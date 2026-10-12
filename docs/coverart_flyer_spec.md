# S2J Webinar - 運用素材 (CoverArt / フライヤー)

**所有者:** S2J Webinar (本プラグイン)。用語は [glossary_operational.md](./glossary_operational.md) を参照します。索引は [operational_material_index.md](./operational_material_index.md) です。

## 設計意図 (ゴール)

制作 **済み** の CoverArt (画像) とフライヤー (PDF) を、WordPress メディアライブラリに載せ、GatherPress イベント (Webinar 対象投稿) に紐付けます。接続先 REST は呼びません。

## 非目標

* WordPress 内で CoverArt / フライヤーを **新規制作** する機能 (デザインツール、テンプレート PDF 生成等)。
* 配配メール (ラクス) との連携、メール経由の集客数の取り込み。
* QR コード生成 ([S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr) および [source_qr_handoff_spec.md](./source_qr_handoff_spec.md))。

## ユースケース

初版 v0.0.2では **管理画面での登録・差し替え・解除** を完了条件とします。フロント表示は kis2026_base 等で必要になった時点で handoff します。

| やりたいこと | 画面 | 動き |
| --- | --- | --- |
| イベント用 CoverArt を登録する | GatherPress イベント編集 > S2J Webinar パネル | メディアライブラリから画像を選び、メタに attachment ID を保存 |
| フライヤー PDF を登録する | 同上 | PDF を選び、メタに attachment ID を保存 |
| 公開面で参照する | テーマ / ブロック (別イニシアチブ可) | メタの attachment から URL を解決 (本仕様では **管理画面とメタ** まで) |

## UI (本プラグイン・イベントパネル)

Slot `EventPluginDocumentSettings` の既存 Fill を拡張します (Survey プラグインの Fill と **同居**)。

### CoverArt

* ラベル: CoverArt (表示文言は i18n)。
* コントロール: WordPress `MediaUpload` / `MediaUploadCheck` 相当 ([CoverArt 採用](#coverart-採用) の MIME に限定)。
* 選択後: サムネイルと「差し替え」「解除」。
* 未選択: プレースホルダと「メディアを選択」。

### フライヤー

* ラベル: フライヤー (表示文言は i18n)。
* コントロール: [フライヤー 採用](#フライヤー-採用) の PDF のみ。PowerPoint (PPTX 等) は受け付けない。
* 案内 (任意): PowerPoint で作った場合は、PDF 出力してから登録する旨 (i18n)。
* 選択後: ファイル名リンク (新規タブでメディア URL)、「差し替え」「解除」。

### 接続先同期との関係

* 投稿の明示「更新」でメタだけ保存。**「同期」ボタンでは接続先に送らない。**
* CoverArt / フライヤーの変更は WebinarRecord の `dirty` 判定に **含めない** (本体項目のみの既存規則を維持)。

## 永続化

個別 `register_post_meta`。1オブジェクトにまとめません (v0.0.1 [admin_ui_spec.md](./admin_ui_spec.md) に倣う)。

キー定義の正本は実装反映時に [data_dictionary.md](./data_dictionary.md) に追記します。

| キー | 型 | `show_in_rest` | 説明 |
| --- | --- | --- | --- |
| `_s2j_webinar_cover_art_id` | int | true | CoverArt の `attachment_id`。正の整数のみ永続 ([未登録](#未登録の表現)) |
| `_s2j_webinar_flyer_id` | int | true | フライヤー PDF の `attachment_id`。同上 |

### 未登録の表現

WordPress のアイキャッチ (`_thumbnail_id`) に倣います。Survey 文書メタの空表現とはそろえません ([glossary_operational.md](./glossary_operational.md))。

* **読み取り:** メタ不存在、`0`、非正整数、存在しない attachment ID → いずれも **未登録**。
* **書き込み:** パネル「解除」および正規化で `0` / 負数 / 非数を受け取った場合は **`delete_post_meta`** する。永続するのは検証を通過した正の `attachment_id` のみ。

### CoverArt 採用

| 項目 | 方針 |
| --- | --- |
| 許可 MIME | `image/jpeg`, `image/png`, `image/webp`, `image/gif` |
| 初版で非許可 | `image/svg+xml` (XSS リスク。SVG が必要なら後続イニシアチブで別決) |
| UI | `allowedTypes` で画像に限定 (上記 MIME) |
| サーバー (`save_post`) | 対象が `attachment` であること。`get_post_mime_type()` が許可リストに含まれること |
| サイズ | WordPress / PHP のアップロード上限に加え、保存時 `filesize( get_attached_file( $id ) )` で **上限5MiB** |

### フライヤー 採用

| 項目 | 方針 |
| --- | --- |
| 許可 MIME | **`application/pdf` のみ** |
| PowerPoint | v0.0.2では非対応。営業は PDF エクスポート後にメディアに登録する運用 |
| UI | PDF のみ選択可能 |
| サーバー (`save_post`) | MIME が `application/pdf` であること。必要なら `wp_check_filetype()` で拡張子も確認 |
| サイズ | 保存時 **上限20MiB** |
| PDF ページ数 | 初版では検証しない |

### 検証失敗時

不正な attachment ID や MIME / サイズ超過では **メタを更新しません** (既存の ID を維持)。適切なメッセージ文を表示します (生の MIME やパスを出さない)。

UI で絞り込み、サーバーで必ず再検証します (REST 改ざん・古いメディアの付け替え対策)。

## 保存経路

Survey / v0.0.1パネルと同じです。

* パネル編集キーは `show_in_rest` + GatherPress イベント編集からの明示の投稿「更新」+ `save_post` でサーバー正規化。
* autosave、Quick Edit、一括編集、本プラグイン独自 REST によるメタ保存 (v0.0.1方針) では正規化しない。

## Service 境界

**s2j-webinar-service は関与しません。**

検証は本プラグインの `save_post` 正規化です ([CoverArt 採用](#coverart-採用)、[フライヤー 採用](#フライヤー-採用))。

## アンインストール

本プラグイン所有キーに `_s2j_webinar_cover_art_id` / `_s2j_webinar_flyer_id` を **明示リストに追加** します (ワイルドカード削除はしない)。

メディアライブラリの attachment 自体は削除しません (WordPress 標準のメディア寿命に従う)。

## テスト観点

* attachment 選択 → 保存 → 再読み込みでサムネイルとファイル名が復元される。
* 解除 → メタキーが削除され、未登録として読める。
* REST が `0` を送った場合 → 正規化でメタ削除。
* 同期パイプライン (validate / plan) が CoverArt / フライヤー欠如で止まらない。
* 不正 MIME / サイズ超過 → メタは前値のまま、メッセージ表示。
* CoverArt に PDF、フライヤーに画像をサーバー側で拒否。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-10 | 本プラグイン・パネル拡張、メタ案、非目標を起草 (`coverart_flyer_draft.md`) |
| 2026-10-11 | 実装仕様として `coverart_flyer_spec.md` に昇格。用語集参照を追加 |
| 2026-10-12 | 正本 `docs/coverart_flyer_spec.md` に反映 |
