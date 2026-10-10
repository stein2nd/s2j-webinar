<!--
目的：「CoverArt・フライヤー (v0.0.2) の起草仕様」
-->

# S2J Webinar - 運用素材 (CoverArt / フライヤー)

**所有者:** S2J Webinar (本プラグイン)。**正本反映前** の改訂案です。

## 設計意図 (ゴール)

制作 **済み** の CoverArt (画像) とフライヤー (PDF) を、WordPress メディアライブラリに載せ、GatherPress イベント (Webinar 対象投稿) に紐付けます。Zoom REST は呼びません。

## 非目標

* WordPress 内で CoverArt / フライヤーを **新規制作** する機能 (デザインツール、テンプレート PDF 生成等)。
* 配配メール (ラクス) との連携、メール経由の集客数の取り込み。
* QR コード生成 (コンパニオン・プラグイン。[source_tracking_qr_draft.md](./source_tracking_qr_draft.md))。

## ユースケース

初版 v0.0.2では **管理画面での登録・差し替え・解除** を完了条件とします。フロント表示は kis2026_base 等で必要になった時点で handoff します。

| やりたいこと | 画面 | 動き |
| --- | --- | --- |
| イベント用 CoverArt を登録する | GatherPress イベント編集 > S2J Webinar パネル | メディアライブラリから画像を選び、メタに attachment ID を保存 |
| フライヤー PDF を登録する | 同上 | PDF を選び、メタに attachment ID を保存 |
| 公開面で参照する | テーマ / ブロック (別イニシアチブ可) | メタの attachment から URL を解決 (本起草では **管理画面とメタ** まで) |

## UI (本プラグイン・イベントパネル)

Slot `EventPluginDocumentSettings` の既存 Fill を拡張します (Survey プラグインの Fill と **同居**)。

### CoverArt

* ラベル: CoverArt (表示文言は i18n)。
* コントロール: WordPress `MediaUpload` / `MediaUploadCheck` 相当 (画像 MIME に限定)。
* 選択後: サムネイルと「差し替え」「解除」。
* 未選択: プレースホルダと「メディアを選択」。

### フライヤー

* ラベル: フライヤー (PDF)。
* コントロール: PDF (`application/pdf`) に限定したメディア選択。
* 選択後: ファイル名リンク (新規タブでメディア URL)、「差し替え」「解除」。

### Zoom 同期との関係

* 投稿の明示「更新」でメタだけ保存。**「同期」ボタンでは Zoom に送らない。**
* CoverArt / フライヤーの変更は WebinarRecord の `dirty` 判定に **含めない** (本体項目のみの既存規則を維持)。

## 永続化 (起草キー)

個別 `register_post_meta`。1オブジェクトにまとめません (v0.0.1 [admin_ui_spec.md](../../docs/admin_ui_spec.md) に倣う)。

| キー (案) | 型 | `show_in_rest` | 説明 |
| --- | --- | --- | --- |
| `_s2j_webinar_cover_art_id` | int | true | CoverArt の `attachment_id`。0または未設定は未登録 |
| `_s2j_webinar_flyer_id` | int | true | フライヤー PDF の `attachment_id`。同上 |

**未決事項:** (確定前の検討事項)

* `0` と delete meta のどちらを「未登録」とするか (Survey 側の空表現に合わせる)。
* MIME / サイズのサーバー側検証 (PDF 以外を拒否する等)。

正本反映時は [data_dictionary.md](../../docs/data_dictionary.md) に移します。

## 保存経路

Survey / v0.0.1パネルと同じです。

* パネル編集キーは `show_in_rest` + GatherPress イベント編集からの明示の投稿「更新」+ `save_post` でサーバー正規化。
* autosave、Quick Edit、一括編集、本プラグイン独自 REST によるメタ保存 (v0.0.1方針) では正規化しない。

## Service 境界

**Webinar Service は、関与しません。**

検証は、本プラグインです (attachment が存在し、投稿に紐づくか、MIME が期待どおりか)。

## アンインストール

本プラグイン所有キーに `_s2j_webinar_cover_art_id` / `_s2j_webinar_flyer_id` を **明示リストに追加** します (ワイルドカード削除はしない)。

メディアライブラリの attachment 自体は削除しません (WordPress 標準のメディア寿命に従う)。

## テスト観点 (起草)

* attachment 選択 → 保存 → 再読み込みでサムネイルとファイル名が復元される。
* 解除 → メタが空になる。
* 同期パイプライン (validate / plan) が CoverArt / フライヤー欠如で止まらない。
* PDF 以外を選んだ場合のサーバー拒否 (方針確定後)。

## 改訂履歴 (起草)

| 日付 | 内容 |
| --- | --- |
| 2026-10-10 | 本プラグイン・パネル拡張、メタ案、非目標を起草 |
