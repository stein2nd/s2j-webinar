# S2J Webinar - 仕様書の起点

本プロジェクトの仕様は、下記のドキュメントに分散して定義します。
各ドキュメントへの導線のみを提供し、詳細は個別ファイルに委譲します。

統合見取り図は [plugin_spec.md](./plugin_spec.md) です。構成は [S2J Webinar Survey の docs/](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/specs.md) と [S2J Alliance Manager の docs/](https://github.com/stein2nd/s2j-alliance-manager/blob/main/docs/specs.md) に倣い、本プラグインに必要な層だけを置いています。

**いまの置き場:** 確定仕様の正本は `docs/` です。大きな改訂案は `docs_mod/` で起草します。

## 共通仕様

* [SPECS.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md)

## 計算の正本 (Composer ライブラリ)

Webinar の検証・操作計画・リクエスト材料・応答写像の規則は、本プラグインには置きません。

* 索引: [s2j-webinar-service / docs/specs.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/specs.md)
* 統合見取り図: [service_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/service_spec.md)
* 公開 API: [php_api_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/interfaces/php_api_spec.md)
* 使用方法: [usage_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/interfaces/usage_spec.md)

## 読み方ガイド

### 推奨読み順 (初読)

1. **[concept.md](./concept.md)** — なぜ存在するか
2. **[overview.md](./overview.md)** — 責務と非対応
3. **[principles.md](./principles.md)** — SoT、境界、dirty / HTTP
4. **[architecture.md](./architecture.md)** — レイヤとフォルダー
5. **[data_dictionary.md](./data_dictionary.md)** — メタ、option、レコード対応
6. **[gatherpress_boundary_spec.md](./gatherpress_boundary_spec.md)** — 日時、公開リンク、Slot
7. **[oauth_and_settings_spec.md](./oauth_and_settings_spec.md)** — 接続とトークン
8. **[admin_ui_spec.md](./admin_ui_spec.md)** — 設定とイベントパネル
9. **[sync_execution_spec.md](./sync_execution_spec.md)** — plan → `build_webinar_request` → HTTP → map

### 役割別

#### 実装者 (本プラグイン)

1. [architecture.md](./architecture.md)
2. [data_dictionary.md](./data_dictionary.md)
3. [oauth_and_settings_spec.md](./oauth_and_settings_spec.md)
4. [admin_ui_spec.md](./admin_ui_spec.md)
5. [sync_execution_spec.md](./sync_execution_spec.md)
6. [webhook_spec.md](./webhook_spec.md)
7. [survey_handoff_spec.md](./survey_handoff_spec.md)
8. [status.md](./status.md)
9. [governance/documentation_governance.md](./governance/documentation_governance.md)

#### サービス側の実装者

1. [サービス docs/specs.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/specs.md)
2. 本プラグインの境界だけ [plugin_spec.md](./plugin_spec.md) の「サービスとの境界」と [data_dictionary.md](./data_dictionary.md)

## ドキュメント一覧

### Why

| ドキュメント | 内容 |
| --- | --- |
| [概要](./overview.md) | 基本情報、責務、非対応 |
| [コンセプト](./concept.md) | 背景、ユースケース、処理フロー |
| [アーキテクチャー](./architecture.md) | レイヤ、フォルダー、依存 |
| [設計原則](./principles.md) | FOP (Functional Object-Oriented Programming)、境界、SoT |
| [統合見取り図](./plugin_spec.md) | 要約。細部は分割仕様 |

### What (データと画面)

| ドキュメント | 内容 |
| --- | --- |
| [データ辞書](./data_dictionary.md) | メタキー、option、メタ ↔ WebinarRecord |
| [GatherPress 境界](./gatherpress_boundary_spec.md) | 日時キー、Slot、公開リンク、終日 |
| [OAuth とサイト設定](./oauth_and_settings_spec.md) | 接続、トークン、Webhook 秘密 |
| [管理 UI](./admin_ui_spec.md) | 設定画面、イベントパネル |

### How (実行)

| ドキュメント | 内容 |
| --- | --- |
| [同期実行](./sync_execution_spec.md) | validate → plan → `build_webinar_request` → HTTP → map、dirty、削除 |
| [Webhook](./webhook_spec.md) | `webinar.started` / `ended` |
| [Survey 受け渡し](./survey_handoff_spec.md) | `ready` 文書と `attach_survey` |

### 運用

| ドキュメント | 内容 |
| --- | --- |
| [実装状況](./status.md) | 機能単位の進捗 |
| [ドキュメンテーション・ガバナンス](./governance/documentation_governance.md) | SoT、用語、lint、`docs_mod` → `docs`、archive |
| [archive 索引](./archive/README.md) | 完了イニシアチブの凍結一覧 |

## Source of Truth

食い違う場合は、計算規則はサービス側を直し、本キットの境界記述を追随させます。メタキー名をサービス仕様に書き戻しません。

| 対象 | 正本 |
| --- | --- |
| 検証・操作計画・材料・写像および OAuth 材料形 | Webinar Service の `docs/core/` |
| 公開 PHP API (Composer) | Webinar Service の `docs/interfaces/php_api_spec.md` |
| メタキー、option、メタ ↔ レコード、dirty 判定 | 本 `docs/` ([data_dictionary.md](./data_dictionary.md) 等) |
| 画面、HTTP 実行、Webhook、i18n | 本 `docs/` の分割仕様 |
| ユーザー向け最短手順 | ルート `README.md` |

## 補足

* 本プラグインはアダプタである。GatherPress フォーク本体は改変しない。
* 設問の検査・助言は [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey) / Survey Service である。本プラグインは `ready` 文書の添付だけを担う。
