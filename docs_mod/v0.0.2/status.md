<!--
目的：「v0.0.2 起草イニシアチブの進捗」
-->

# S2J Webinar - 運用素材 (実装状況)

本ページは **docs_mod/v0.0.2** のレビュー・合意・正本反映の進捗です。機能実装の % ではありません。

製品方針: **汎用プロダクト + 初実装プロバイダ `zoom` + Adapter 拡張** ([README.md](./README.md)、[source_tracking_qr_draft.md](./source_tracking_qr_draft.md))。CoverArt / フライヤーはプロバイダ非依存。

**コンパニオン:** [S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr) — 本リポジトリ (S2J Webinar) とは **別プラグイン・別 Git**。handoff 起草は当 `docs_mod/`、プラグイン正本は同リポジトリ `docs/` (未作成)。

最終更新: 2026-10-11

## 仕様 v0.0.1

| 項目 | 状態 |
| --- | --- |
| 正本 `docs/` | **fix** (本イニシアチブでは改変しない) |

## 起草 (v0.0.2)

| 項目 | 状態 | メモ |
| --- | --- | --- |
| [README.md](./README.md) 索引・混在・プロバイダ | 合意済 | 調査節は `zoom` 向けメモ |
| [coverart_flyer_draft.md](./coverart_flyer_draft.md) | 合意済 | 未登録、MIME、サイズ。接続先 HTTP 不要。キー名は正本反映時に確定 |
| [source_tracking_qr_draft.md](./source_tracking_qr_draft.md) | 合意済 | プロバイダ抽象。v0.0.2取り込みは `zoom` のみ。手入力 QR は共通 |
| 接続先 Source Tracking 調査 (`zoom`) | 完了 | [README.md](./README.md) 調査結果。他プロバイダは追加時に同形式。実装時に API 再確認 |
| [s2j-webinar-source-qr](https://github.com/stein2nd/s2j-webinar-source-qr) リポジトリ | 作成済 | スラッグ `s2j-webinar-source-qr`。実装・`docs/` はこれから |
| `docs/` への反映 | 未着手 | 合意後 |

## 合意済み (旧「レビューで決めたいこと」)

1. **スラッグ / メタ:** プラグイン `s2j-webinar-source-qr` (表示名 S2J Webinar Source QR)。イベントメタ `_s2j_webinar_source_qr` (Survey の `_s2j_webinar_survey` と同型)。
2. **アンインストール:** **プラグインの所有物** (メタ・サイト option) だけ削除。**QR 用 PNG 等のメディア** は成果物として残す (Survey が Zoom 上アンケートを触らないのと同じ分離)。
3. **接続先 HTTP (v0.0.2 `zoom`):** Survey は **Zoom / OAuth / token に一切触れない** ([persistence_spec](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/persistence_spec.md) 受け渡しはメタ直読みのみ)。Source QR も **token option を直接読まない**。**任意 GET 取り込み** は S2J Webinar 側の HTTP アダプタ経由 (内部 REST 等。契約は実装時に `docs/` 化)。手入力 QR のみなら Source QR 単体で可。
4. **Composer (v0.0.2):** **`s2j-webinar-source-qr-service` は作らない。** Source Tracking **取り込み** の写像・接続先由来の検証は **[s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service)** の **プロバイダ Adapter** に載せる (汎用プロダクトの拡張点を Service レジストリに一本化。[principles.md](../../docs/principles.md) §6)。QR 符号化、UI、文書メタは **S2J Webinar Source QR** プラグイン。専用 Service は [下記メモ](#composer-構成メモ) 2行目の条件を満たした将来のみ検討。

## 合意後の完了条件

* 起草内容が `docs/` に分割反映され、`docs/specs.md` にリンクが増えている。
* `plugin_spec.md` の「非目標 (初版)」が v0.0.1 / v0.0.2 / 別途 (配配メール) に整理されている。
* QR / Source Tracking 正本に **プロバイダ拡張** と **初実装 `zoom`** が書かれている (Teams / Meet 等は後続)。
* 本 `docs_mod/v0.0.2/` は archive または削除 (governance §5)。
* [s2j-webinar-source-qr](https://github.com/stein2nd/s2j-webinar-source-qr) に handoff 反映後、双方の `docs/` が [README.md](./README.md) の関係節と一致している。

## Composer 構成メモ

QR ピクセル生成は WordPress 依存のため **Composer 純関数には載せない** (起草どおり)。

| パターン | v0.0.2 | 向くケース |
| --- | --- | --- |
| **Source QR プラグイン + s2j-webinar-service** | **採用** | 取り込み写像・接続先検証は Service の **プロバイダ Adapter** (v0.0.2: `zoom`)。QR 符号化と UI と `_s2j_webinar_source_qr` はプラグイン |
| **+ s2j-webinar-source-qr-service** | 見送り | 文書検証が survey-service 級に肥大化、または複数消費者が同じ QR 文書規約を共有するとき (Survey / survey-service と同型) |
