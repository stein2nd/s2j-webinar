# S2J Webinar - 運用素材 (CoverArt / フライヤー / UTM 別 QR)

コア仕様の正本は [specs.md](./specs.md) です。本ページは **運用素材** の索引です (2026-10-12に `docs/` 正本に反映)。

## プロバイダ (製品方針)

S2J Webinar エコシステムは **接続先 (プロバイダ) を差し替え可能** な汎用プロダクトです ([principles.md](./principles.md) §6)。

* **v0.0.1 / v0.0.2初実装:** 最初の対応プロバイダは **`zoom`**。接続先選択 UI は出さない。
* **拡張:** コントリビューターが Service の Adapter と (必要なら) プラグイン UI を追加できる。使わない接続先の空実装は置かない。
* **運用素材:** CoverArt / フライヤーはプロバイダ非依存。Source Tracking + QR は **プロバイダ共通 UI + 接続先別取り込み**。API 調査節は **v0.0.2初実装 `zoom` 向けメモ** です。

## スコープ (合意済みの方向)

* 混在方針
    * CoverArt / フライヤーは **接続先 HTTP 不要** のため本プラグインのイベントパネルに載せる。
    * QR / UTM は **メンテナンス用 UI が重い** ためコンパニオン・プラグイン S2J Webinar Source QR に分離する (S2J Webinar Survey と同型の「別 UI → 文書メタ → 呼び出し元にサマリー」)。

| 機能 | パッケージ | v0.0.1 | v0.0.2 |
| --- | --- | --- | --- |
| OAuth、接続先同期、Webhook、Survey handoff | **S2J Webinar** (本プラグイン) | 正本 (`docs/`)。初実装 `zoom` | 変更なし (fix) |
| CoverArt (制作済み画像) | **S2J Webinar** パネル拡張 | 非目標 (後続) | 仕様確定 ([coverart_flyer_spec.md](./coverart_flyer_spec.md)) |
| フライヤー (制作済み PDF) | **S2J Webinar** パネル拡張 | 非目標 (後続) | 仕様確定 ([coverart_flyer_spec.md](./coverart_flyer_spec.md)) |
| UTM 別 Source Tracking + QR | **[S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr)** (`s2j-webinar-source-qr`) | 非目標 (後続) | 仕様確定 (コンパニオン [`docs/`](https://github.com/stein2nd/s2j-webinar-source-qr/tree/main/docs)) |
| 配配メール (ラクス) 連携・集客可視化 | — | 非目標 | **対象外** (別途検討) |

## S2J Webinar Source QR との関係

v0.0.2の QR / Source Tracking は **本プラグイン (S2J Webinar) には実装せず**、コンパニオン **[S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr)** に置きます ([S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey) + [survey_handoff_spec.md](./survey_handoff_spec.md) と同型)。

CoverArt / フライヤーは **本プラグインのみ** です。Source QR はインストール任意 (未インストール時はパネル案内のみ)。

| 役割 | S2J Webinar (本リポジトリ) | S2J Webinar Source QR |
| --- | --- | --- |
| GatherPress イベントパネル | CoverArt / フライヤー、OAuth、同期、Survey 入口 | (Fill 同居可) メンテナンス UI ・ QR 生成 |
| イベントメタ | `_s2j_webinar_*` (Webinar 本体)。**`_s2j_webinar_source_qr` は触らない** | **`_s2j_webinar_source_qr` を所有** |
| 接続先 HTTP | OAuth ・同期・定義 / (任意) 取り込み GET の **唯一の token 正本** | 手入力 QR は単体可。定義 / 取り込みは **Webinar 経由** |
| 仕様ドキュメント | 本 `docs/` ([下表](#仕様ファイル-v020)) | [`docs/`](https://github.com/stein2nd/s2j-webinar-source-qr/tree/main/docs) (**プラグイン v0.0.1** 正本) |

## 仕様ファイル (v0.0.2)

コンパニオン実装仕様: [s2j-webinar-source-qr / docs/](https://github.com/stein2nd/s2j-webinar-source-qr/tree/main/docs)

| ファイル | 内容 |
| --- | --- |
| [glossary_operational.md](./glossary_operational.md) | v0.0.2用語・表記 |
| [coverart_flyer_spec.md](./coverart_flyer_spec.md) | CoverArt / フライヤー **実装仕様** (本プラグイン) |
| [source_qr_handoff_spec.md](./source_qr_handoff_spec.md) | Source QR **handoff** (本プラグインのみ) |
| [source_qr_tracking_definitions_rest_spec.md](./source_qr_tracking_definitions_rest_spec.md) | ソース定義一覧 **REST** (Source QR 行の正本) |
| [source_qr_tracking_rest_spec.md](./source_qr_tracking_rest_spec.md) | Source Tracking **取り込み REST** (Source QR 向け GET) |
| 起草証跡 | [archive/mod-v0-0-2-operational-material/](./archive/mod-v0-0-2-operational-material/) |

## v0.0.2の位置付け (実装順)

v0.0.1コア (OAuth → 同期 → Webhook → Survey `attach_survey`) **完了後**、同じマイルストーン内の **運用素材** として v0.0.2を実装します。

1. CoverArt / フライヤー (本プラグイン。API 調査不要)
2. UTM 別 QR (コンパニオン + Webinar Service の **プロバイダ Adapter**。**リンク作成は接続先ポータル**、WP は取り込み・手入力・ QR。v0.0.2取り込みは `zoom` のみ)

## 接続先 Source Tracking 調査 (初実装 `zoom`)

プロバイダごとに API / ポータル可否が異なります。下記は **プロバイダ `zoom` 限定** の調査結果です (2026-10-11)。他プロバイダ追加時は同形式の節を足します。  
API は更新され得るため、**実装着手時に [Zoom Meeting API](https://developers.zoom.us/docs/api/meetings/) で再確認** します。

### `zoom`: ソース名付きでリンクを **作成** できるか

> このウェビナーに対して、このソース名用のソース追跡リンクを作成してくれ

**公開 REST では不可** です。作成用エンドポイントは Marketplace 参照になく、Zoom Developer Forum でも **API から tracking source を追加する方法はない** と回答されています。  
ソース名を入力して URL を自動生成する操作は **Zoom Web ポータル** のみです ([Creating webinar registration tracking links](https://support.zoom.com/hc/en/article?id=zm_kb&sysparm_article=KB0065543))。

| # | 確認項目 (`zoom`) | 結果 |
| --- | --- | --- |
| 1 | 既存 Webinar の tracking link **一覧取得** | **可** — `GET /v2/webinars/{webinarId}/tracking_sources` ([API 参照](https://marketplace.zoom.us/docs/api-reference/zoom-api/methods/#operation/getTrackingSources)) |
| 2 | ソース名 (例: `web` / `sns` / `other`) 指定での **作成・更新** | **不可** — POST 等なし ([Forum 120777](https://devforum.zoom.us/t/api-to-create-tracking-sources-for-a-webinar/120777)、[83105](https://devforum.zoom.us/t/can-i-add-tracking-sources-via-the-api-after-creating-a-webinar/83105)) |
| 3 | 登録 URL / 識別子 | ポータルで作成後、GET 応答の `id` / `source_name` / `tracking_url` を WordPress に保存。URL は **加工しない** |
| 4 | OAuth スコープ | GET 用 scope を Marketplace で確定 (**実装時 TBD**) |

**v0.0.2運用 (プロバイダ共通 + `zoom` 初実装):**

1. S2J Webinar で **接続先 Webinar** を作成・同期する (v0.0.2: `zoom`)。
2. ソース追跡リンクは **接続先ポータル** で登録する (v0.0.2 `zoom`: 招待状タブ、最大50、URL は接続先が生成)。
3. WordPress では **手入力** または (任意・`zoom` のみ) **GET 取り込み** で `tracking_url` をチャネルに紐付ける。
4. コンパニオンで QR プレビュー・ PNG 保存。

`invite_links` API (`zoom`) はソース追跡リンクの代替になりません ([83105](https://devforum.zoom.us/t/can-i-add-tracking-sources-via-the-api-after-creating-a-webinar/83105))。

詳細 UI は [S2J Webinar Source QR / admin_ui_spec.md](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/admin_ui_spec.md) です (handoff は [source_qr_handoff_spec.md](./source_qr_handoff_spec.md))。

### `zoom` のみ後日検討

* **繰り返しウェビナー** と `GET …/tracking_sources` の実例差 (集計不一致の報告等)。GatherPress **1イベント = 1Webinar** 想定のため v0.0.2では扱いません。

## 関連リポジトリ (v0.0.2想定)

| 名称 | v0.0.2での役割 |
| --- | --- |
| **[S2J Webinar](https://github.com/stein2nd/s2j-webinar)** (本リポジトリ) | コア + CoverArt / フライヤー。Source QR への **handoff** (入口・サマリー・定義 / 任意 GET HTTP) |
| **[S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr)** | UTM / チャネル UI、QR、メタ `_s2j_webinar_source_qr` |
| **[s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service)** | Webinar 同期 + Source Tracking **取り込み** Adapter (v0.0.2: `zoom`) |
| **[S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey)** | コンパニオン UX の参照モデル (本プラグインは Survey handoff のみ) |

## 実装反映時に追随する正本 (未着手)

仕様は `docs/` に確定済みです。コード実装 PR では、おおむね次も同バッチで更新します。

* [plugin_spec.md](./plugin_spec.md) — 非目標の分割、実装順、関連リポジトリ表
* [overview.md](./overview.md) / [status.md](./status.md)
* [admin_ui_spec.md](./admin_ui_spec.md) — CoverArt / フライヤーをパネルに載せる。QR は「プラグインに」入口のみ
* [data_dictionary.md](./data_dictionary.md) — CoverArt / フライヤー メタキー

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-10 | v0.0.2運用素材の索引、混在方針、API チェックリストを起草 |
| 2026-10-11 | Source Tracking: 作成 REST 不可・ GET 可、v0.0.2運用4段、繰り返し Webinar は後日 |
| 2026-10-11 | プロバイダ方針 (zoom 初実装・拡張可能) を索引に明文化 |
| 2026-10-11 | S2J Webinar Source QR との関係節を追加 |
| 2026-10-12 | 定義 REST 索引、Source QR `docs/` リンク、進捗ラベル統一 |
| 2026-10-12 | 正本 `docs/operational_material_index.md` に反映 |
