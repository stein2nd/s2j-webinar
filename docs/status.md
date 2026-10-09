<!--
目的：「実装状況サマリー」の明文化
-->

# S2J Webinar - 実装状況

本ページは、現状の実装状況を機能単位で一覧します。
索引は [specs.md](./specs.md) です。計算規則の正本は Webinar Service の `docs/` です。

最終更新: 2026-10-09

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [plugin_spec.md](./plugin_spec.md) — 統合見取り図
* [sync_execution_spec.md](./sync_execution_spec.md) / [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) / [admin_ui_spec.md](./admin_ui_spec.md)
* [governance/documentation_governance.md](./governance/documentation_governance.md) — ドキュメント運用
* [archive/README.md](./archive/README.md) — 完了イニシアチブ (まだなし)

## 機能一覧

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 |
| --- | --- | --- | --- |
| 仕様 (`docs/`) | 確定 | — | 大きな改訂は `docs_mod/` で起草し、合意後に本 `docs/` に反映 |
| Composer require (`s2j/webinar-service`) | 未実装 | 0 | Packagist 名のみ。`VCS`/`path` なし |
| サイト設定 + OAuth | 未実装 | 0 | [oauth_and_settings_spec.md](./oauth_and_settings_spec.md) |
| イベントパネル (Slot Fill) | 未実装 | 0 | [admin_ui_spec.md](./admin_ui_spec.md) / [gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md) |
| メタ ↔ WebinarRecord + dirty | 未実装 | 0 | [data_dictionary.md](./data_dictionary.md) |
| 同期 (create / update) | 未実装 | 0 | [sync_execution_spec.md](./sync_execution_spec.md) |
| 削除、「Zoom で開く」(`get`) | 未実装 | 0 | `build_webinar_request( 'get', … )` のみ。`join_url` / `webinar_uuid` 非空時のみメタ (と `join_url` なら公開欄) を更新 |
| 登壇者 (Panelist) 差分 | 未実装 | 0 | `_s2j_webinar_last_panelists`。delete 成功時に処理 |
| 公開リンク反映 | 未実装 | 0 | `gatherpress_online_event_link` / `Event::set_online`。ended では空にしない |
| Webhook started / ended | 未実装 | 0 | プラグイン REST のみ。[webhook_spec.md](./webhook_spec.md) |
| Survey `ready` → `attach_survey` | 未実装 | 0 | 通常の「同期」に含める。専用 UI なし。[survey_handoff_spec.md](./survey_handoff_spec.md) |
| アンインストール処理 | 未実装 | 0 | 所有メタの明示リスト + サイト option。`_s2j_webinar_survey` 除外。Zoom 非接触 |

## 補足

* CoverArt / QR / フライヤー / 配配メール (ラクス) は後続である。
* 初版の接続先 UI は Zoom 固定である。
