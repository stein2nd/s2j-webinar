# S2J Webinar - Source QR 受け渡し仕様

用語は [glossary_operational.md](./glossary_operational.md) を参照します。索引は [operational_material_index.md](./operational_material_index.md) です。

コンパニオン本体 (UI、永続化、QR 生成) の正本は [S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr) の [`docs/`](https://github.com/stein2nd/s2j-webinar-source-qr/tree/main/docs) です。

## 設計意図 (ゴール)

S2J Webinar Source QR が保存した文書を、**本プラグインのイベントパネルから参照できる境界** だけを固定します。QR 編集 UI、符号化、メタの書き込みは持ちません (Survey handoff と同型。[survey_handoff_spec.md](./survey_handoff_spec.md))。

## 非対象

* メンテナンス UI (4ステップ)、QR プレビュー / PNG 保存 (Source QR プラグイン)
* `_s2j_webinar_source_qr` への書き込み
* 接続先 Source Tracking の写像 (s2j-webinar-service プロバイダ Adapter)
* CoverArt / フライヤー ([coverart_flyer_spec.md](./coverart_flyer_spec.md))

## 所有

| 対象 | 所有者 |
| --- | --- |
| `_s2j_webinar_source_qr` | S2J Webinar Source QR |
| `_s2j_webinar_id` / `_s2j_webinar_provider` 等 | 本プラグイン ([data_dictionary.md](./data_dictionary.md)) |
| 取り込み GET の写像・検証 | s2j-webinar-service (プロバイダ Adapter) |
| 取り込み HTTP (OAuth 付き) | 本プラグイン (token 正本。Source QR は **読まない**) |
| パネル入口・サマリー表示 | 本プラグイン |

## パネル表示 (handoff)

| 項目 | 内容 |
| --- | --- |
| 入口 | 「ソース追跡 / QR を編集」等 (i18n)。Source QR のメンテナンス UI を開く |
| サマリー | メタ文書の `status` (`draft` / `ready`)、チャネル数、`updated_at` (あれば) |
| 編集 UI | **持たない** (Survey の設問 UI を持たないのと同型) |
| Source QR 未インストール | 短い案内を出してよい (任意) |
| Webinar 無効 | Source QR 単体の編集は可能 (Survey と同型) |

## 読み取り条件

本プラグインがサマリーに載せるのは、下記を満たす場合です。

1. メタ `_s2j_webinar_source_qr` が存在する (Source QR が保存済み)。
2. 表示は **メタ文書** を読む (パネル作業コピーではない)。
3. 本プラグインはメタを **書き戻さない**。

`draft` でもサマリーは出してかまいません (「未反映」等の文案は Source QR / 本プラグインで i18n)。

## 同期パイプラインとの関係

* **載せない:** `plan_webinar_operations` / `dirty` / create ・ update ボディに Source QR 文書や QR PNG を含めない (Survey の `attach_survey` とは別経路。集客素材であり Webinar ボディではない)。
* CoverArt / フライヤーも同様に同期対象外 ([coverart_flyer_spec.md](./coverart_flyer_spec.md))。

## Source QR 向け REST (v0.0.2)

Source QR は OAuth / token option を **直接読みません**。接続先 HTTP と **ソース定義一覧** は **本プラグイン REST** が担います。

### ソース定義一覧 GET (行の正本)

| 項目 | 内容 |
| --- | --- |
| 用途 | Source QR がチャネル行 `id` / `label` を組み立てる **正本** |
| 契約 | [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md) |
| 前提 | イベント `_s2j_webinar_id` が **非空** (未連携は **400** `tracking_webinar_id_empty`) |
| Source QR | 200かつ `sources.length >= 1` で `catalogReady` true ([Source QR admin_ui_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/admin_ui_spec.md)) |

### 任意: 取り込み GET (v0.0.2 `zoom`)

接続先の registration URL 一覧を OAuth 付きで取得します (Source QR は token を読まない)。

#### 責務分界

| 層 | 役割 |
| --- | --- |
| Source QR UI | ボタン押下 → 本プラグイン REST を呼ぶ。返却された正規化行をパネル文書に反映 |
| 本プラグイン REST | 権限、`post_id`、イベントの `_s2j_webinar_id` / `provider` を検証。HTTP は既存 OAuth アダプタ |
| s2j-webinar-service | GET 応答 → `items[]` 写像 ([tracking_import_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/tracking_import_spec.md)) |

#### REST 契約

* 定義: [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md)
* 取り込み: [source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md)

**v0.0.2:** 取り込み REST が未実装でも、定義 REST + 手入力 URL + QR で Source QR 初版を完了可。取り込みのみ省略も可 ([operational_material_index.md](./operational_material_index.md))。

## 表示上の注意

* 不足コードや接続先生 JSON を画面に出さない。
* 文書 `status` とレコード `_s2j_webinar_status` を混同しない ([glossary_operational.md](./glossary_operational.md))。

## アンインストール

| 操作 | `_s2j_webinar_source_qr` | QR 用 PNG メディア |
| --- | --- | --- |
| 本プラグイン アンインストール | **消さない** (Source QR 所有。明示リストから除外) | 触らない |
| Source QR アンインストール | Source QR が削除 (本プラグインは触らない) | **削除しない** (成果物として残す) |

## 関連

* 定義 REST: [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md)
* 取り込み REST: [source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md)
* コンパニオン永続化: [persistence_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/persistence_spec.md)
* 文書形: [data_dictionary.md (Source QR)](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/data_dictionary.md)
* 接続先調査 (`zoom`): [operational_material_index.md](./operational_material_index.md)

## 改訂履歴 (v0.0.2)

| 日付 | 内容 |
| --- | --- |
| 2026-10-11 | Survey handoff 同型で初版起草 |
| 2026-10-11 | REST 詳細を [source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md) に分離 |
| 2026-10-12 | 定義 REST を [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md) に分離。Source QR 正本 `docs/` にリンク更新 |
| 2026-10-12 | 正本 `docs/source_qr_handoff_spec.md` に反映 |
