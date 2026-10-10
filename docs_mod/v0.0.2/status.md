<!--
目的：「v0.0.2 起草イニシアチブの進捗」
-->

# S2J Webinar - 運用素材 (実装状況)

本ページは **docs_mod/v0.0.2** のレビュー・合意・正本反映の進捗です。機能実装の % ではありません。

最終更新: 2026-10-10

## 仕様 v0.0.1

| 項目 | 状態 |
| --- | --- |
| 正本 `docs/` | **fix** (本イニシアチブでは改変しない) |

## 起草 (v0.0.2)

| 項目 | 状態 | メモ |
| --- | --- | --- |
| [README.md](./README.md) 索引・混在方針 | 起草済 | |
| [coverart_flyer_draft.md](./coverart_flyer_draft.md) | 起草済 | キー名・ MIME 方針はレビュー待ち |
| [source_tracking_qr_draft.md](./source_tracking_qr_draft.md) | 起草済 | プラグイン仮称、メタキー、API フォールバックはレビュー待ち |
| Zoom Source Tracking API 調査 | 未着手 | README チェックリスト |
| コンパニオン・プラグイン・リポジトリ作成 | 未着手 | スラッグ確定後 |
| `docs/` への反映 | 未着手 | 合意後 |

## レビューで決めたいこと

1. CoverArt / フライヤーのメタキー名と「未登録」の表現 (`0` vs delete)。
2. Source QR プラグインの **正式スラッグ** とメタキー (案 `_s2j_webinar_source_qr`)。
3. QR 用 PNG attachment をアンインストール時に削除するか。
4. Zoom HTTP 再利用 (Webinar プラグイン経由の REST 内部 API vs プラグインが token option を読む)。

## 合意後の完了条件

* 起草内容が `docs/` に分割反映され、`docs/specs.md` にリンクが増えている。
* `plugin_spec.md` の「非目標 (初版)」が v0.0.1 / v0.0.2 / 別途 (配配メール) に整理されている。
* 本 `docs_mod/v0.0.2/` は archive または削除 (governance §5)。
