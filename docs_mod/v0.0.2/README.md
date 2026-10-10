<!--
目的：「実装仕様 v0.0.2 (運用素材) の起草索引と前提」
-->

# S2J Webinar - 運用素材 (CoverArt / フライヤー / UTM 別 QR)

確定仕様 **v0.0.1** の正本は [`../../docs/`](../../docs/) です。本フォルダーは **v0.0.2向けの改訂案** だけを置きます。合意後に `docs/` に反映し、本起草は片付けてかまいません ([documentation_governance.md](../../docs/governance/documentation_governance.md) §5)。

## スコープ (合意済みの方向)

* 混在方針
    * CoverArt / フライヤーは **Zoom HTTP 不要** のため本プラグインのイベントパネルに載せる。
    * QR / UTM は **メンテナンス用 UI が重い** ためコンパニオン・プラグインに分離する (S2J Webinar Survey と同型の「別 UI → 文書メタ → 呼び出し元にサマリー」)。

| 機能 | パッケージ | v0.0.1 | v0.0.2 |
| --- | --- | --- | --- |
| Zoom OAuth、同期、Webhook、Survey handoff | **S2J Webinar** (本プラグイン) | 正本 (`docs/`) | 変更なし (fix) |
| CoverArt (制作済み画像) | **S2J Webinar** パネル拡張 | 非目標 (後続) | 起草中 |
| フライヤー (制作済み PDF) | **S2J Webinar** パネル拡張 | 非目標 (後続) | 起草中 |
| UTM 別 Source Tracking + QR | **コンパニオン・プラグイン** (Survey 型) | 非目標 (後続) | 起草中 |
| 配配メール (ラクス) 連携・集客可視化 | — | 非目標 | **対象外** (別途検討) |

## 起草ファイル

| ファイル | 内容 |
| --- | --- |
| [coverart_flyer_draft.md](./coverart_flyer_draft.md) | メディア紐付け、パネル UI、非目標 (WP 内制作) |
| [source_tracking_qr_draft.md](./source_tracking_qr_draft.md) | コンパニオン・プラグイン、4ステップ UI、Service / プラグイン境界 |
| [status.md](./status.md) | レビュー・合意・反映の進捗 |

## v0.0.2の位置付け (実装順)

v0.0.1コア (OAuth → 同期 → Webhook → Survey `attach_survey`) **完了後**、同じマイルストーン内の **運用素材** として v0.0.2を実装します。

1. CoverArt / フライヤー (本プラグイン。API 調査不要)
2. UTM 別 QR (コンパニオン + Webinar Service 拡張。**Zoom Source Tracking API の可否が Go/No-Go**)

## 未決事項 (QR 着手前の、Zoom API 調査チェックリスト)

* 管理画面の「招待状」→「ソース追跡リンク」でできる操作が、公開 REST で再現できるかを確認する。
* 結果は [source_tracking_qr_draft.md](./source_tracking_qr_draft.md) の「フォールバック」節に反映する。

| # | 確認項目 | 結果 (TBD) |
| --- | --- | --- |
| 1 | 既存 Webinar に対する tracking link の **一覧取得** | |
| 2 | チャネル (例: `web` / `sns` / `other`) ごとの **作成・更新** | |
| 3 | 登録 URL の **安定した識別子** (再生成時の扱い) | |
| 4 | OAuth スコープ・エンドポイントが本サイトの Zoom アプリで利用可能か | |

**NG 時のフォールバック (起草):** 運営者が Zoom 管理画面でコピーした URL をチャネルごとに手入力 → コンパニオンは **QR 生成とプレビュー・メディア保存** のみ担当。Source Tracking の **構築** は Service / HTTP 対象外。

## 関連リポジトリ (v0.0.2想定)

コンパニオン・プラグインの **正式スラッグ・リポジトリ名** は、実装着手前に確定します (本 README の仮称は起草用)。

| 名称 | v0.0.2での役割 |
| --- | --- |
| **S2J Webinar** | CoverArt / フライヤー UI とメタ。QR プラグインへの入口 (リンク・サマリー表示) |
| **s2j-webinar-service** | Source Tracking URL 取得の **材料・写像・検証** (API が使える場合) |
| **S2J Webinar Source QR** (仮称) | UTM / チャネル編集モーダル、QR プレビュー、文書メタ、PNG 永続化 |
| **S2J Webinar Survey** | UX 参照 (別 Fill / 別メタ、Webinar は handoff のみ) |

## 反映時に触る正本 (予定)

合意後、おおむね次を同じ PR / 改訂バッチで更新します。

* `docs/plugin_spec.md` — 非目標の分割、実装順、関連リポジトリ表
* `docs/overview.md` / `docs/status.md`
* `docs/admin_ui_spec.md` — CoverArt / フライヤーをパネルに載せる。QR は「プラグインに」入口のみ
* `docs/data_dictionary.md` — 新メタキー (確定後)
* 新規: `docs/coverart_flyer_spec.md`、`docs/source_qr_handoff_spec.md` (名称 TBD)
* `CHANGELOG.md` — QR を「別プラグイン」に持つ方針の明文化 (v0.0.1記録との整合)
* コンパニオン側 `docs/` (新リポジトリ)

## 改訂履歴 (起草)

| 日付 | 内容 |
| --- | --- |
| 2026-10-10 | v0.0.2運用素材の索引、混在方針、API チェックリストを起草 |
