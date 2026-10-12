# S2J Webinar - 運用素材の用語集

本ファイルは **運用素材** (CoverArt / フライヤー / Source QR handoff) の仕様で共通する用語です。索引は [operational_material_index.md](./operational_material_index.md) です。

## 製品・パッケージ

| 用語 | 意味 |
| --- | --- |
| **S2J Webinar** | 本リポジトリの WordPress プラグイン。OAuth、接続先同期、CoverArt / フライヤー、Survey handoff、Source QR handoff |
| **S2J Webinar Source QR** | コンパニオン・プラグイン (`s2j-webinar-source-qr`)。ソース追跡 URL と QR 文書の UI ・永続化。製品・仕様版は **v0.0.1** (Webinar 仕様 v0.0.2とは別)。和文説明では **ソース QR** と書いてよい |
| **接続先 / プロバイダ** | ウェビナー API の実装先。文書・メタでは `provider` (v0.0.2初実装は `zoom`) |
| **s2j-webinar-service** | Composer ライブラリ。Webinar 同期と Source Tracking **取り込み** の写像 (Adapter) |

## 流入経路 (チャネル)

| 用語 | 意味 |
| --- | --- |
| **チャネル行** | Source QR 文書 `channels[]` の1行。`id` は **S2J Webinar ソース定義** の `source_name` と同一キー |
| **ソース定義一覧** | 本プラグイン REST `GET …/tracking-source-definitions` の `sources[]` ([source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md))。行の **件数・順序・ラベル** の正本。固定 id 列挙は **本リポジトリに置かない** |
| **Source Tracking** | 接続先が提供する「ソース名付き登録 URL」の機能 (Zoom: ソース追跡リンク) |
| **`source_name`** | 接続先 API / ポータル上のソース名 (Zoom GET `tracking_sources`)。チャネル行 `id` および定義 REST の `source_name` との **完全一致** で取り込み時に自動割り当て |
| **`registration_url`** | メタ文書・チャネル行が持つ **登録用 URL** (接続先発行)。QR に埋め込む文字列。Zoom では GET 応答の `tracking_url` を Adapter がここに写す |
| **UTM (独自)** | v0.0.2では **付け足さない**。見出しの「UTM 別」は運用上の呼び名であり、WordPress がクエリーを改変しない |

## 文書・保存 (Source QR。Survey と同型)

| 用語 | 意味 |
| --- | --- |
| **パネル文書** | メンテナンス UI 上の作業コピー (未保存可) |
| **メタ文書** | `_s2j_webinar_source_qr` に保存された正規化済み文書 (`status` 含む) |
| **文書 `status`** | `draft` または `ready`。Source QR 文書専用 |
| **レコード `status`** | `_s2j_webinar_status` 等、Webinar 同期レコードの状態。**文書 `status` と混同しない** |

## CoverArt / フライヤー

| 用語 | 意味 |
| --- | --- |
| **未登録** | メタ不存在、`0`、非正整数、存在しない attachment → いずれも未登録 (Survey の空 JSON とは別) |
| **attachment ID** | WordPress メディアの投稿 ID。CoverArt / フライヤー / QR PNG で使用 |

## 操作・パイプライン

| 用語 | 意味 |
| --- | --- |
| **明示の投稿「更新」** | GatherPress イベント編集からの通常保存 (Survey / v0.0.1の Webinar パネルと同じ除外規則) |
| **同期** | 接続先 Webinar 本体の create / update パイプライン。CoverArt / フライヤー / Source QR 文書は **載せない** |
| **取り込み** | 接続先 GET で既存 tracking URL 一覧を取得し、チャネル行に割り当てる (v0.0.2 `zoom` のみ UI) |
| **手入力** | ポータルからコピーした URL をチャネル行に貼る。取り込み未実装でも v0.0.2完了可 |

## 関連

* CoverArt / フライヤー: [coverart_flyer_spec.md](./coverart_flyer_spec.md)
* Webinar handoff: [source_qr_handoff_spec.md](./source_qr_handoff_spec.md)
* Source QR 永続化: [s2j-webinar-source-qr / persistence_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/persistence_spec.md)
