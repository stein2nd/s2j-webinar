<!--
目的：「プロジェクトの存在理由、概要、基本情報」の明文化
-->

# S2J Webinar - 概要

本ドキュメントは、本プラグインの **基本情報および前提理解** を目的とします。

## はじめに

本プラグインは、GatherPress のイベント編集画面から Zoom Webinar を作成・更新・削除し、「Zoom で開く」(`get`) するための WordPress コンパニオンです。イベントの一覧と公開ページは GatherPress が持ちます。検証・操作計画・リクエスト材料・応答写像は [S2J Webinar Service](https://github.com/stein2nd/s2j-webinar-service) が持ちます。

## 基本情報

| 項目 | 値 |
| --- | --- |
| 名称 | S2J Webinar |
| スラッグ | `s2j-webinar` |
| テキストドメイン | `s2j-webinar` |
| ライセンス | GPL-3.0-or-later |
| Composer 依存 | `s2j/webinar-service` (Packagist の名前のみ。`repositories` に `VCS` / `path` は書かない) |

## 提供機能

* Zoom OAuth 接続 (サイトに1アカウント)
* イベント編集パネルでの録画・参加登録・登壇者の編集とメタ保存
* サービス経由の新規登録・更新・削除・「Zoom で開く」(`get`。再取得と同義)
* 参加 URL の GatherPress 公開欄への反映
* Webhook による開催中 / 終了の反映
* Survey の `ready` 文書がある場合の `attach_survey` (写像はサービス)

## 責務

* OAuth、トークン保存、`Authorization` 付き HTTP
* イベントメタとサイト設定
* GatherPress Slot へのパネル、設定画面
* メタ ↔ WebinarRecord の組立と書き戻し、`dirty` / `synced` の業務判定
* Webhook 受け口と署名検証

## 非対応スコープ (Out of Scope)

* 検証・操作計画・パス / ボディ / 応答写像の規則本体 (サービス)
* Zoom 作成画面の全項目再現
* 出席・録画ファイル・効果測定の取り込み
* registrant 登録、Zoom 手修正の双方向同期
* CoverArt、QR、フライヤー、配配メール (ラクス) (後続。境界は [plugin_spec.md](./plugin_spec.md))
* Teams / Meet 等 (初版は Zoom 固定。選択 UI は実装済みが増えた場合)
* GatherPress フォーク本体の改変
* WordPress.org への掲載

## 関連ドキュメント

* 背景: [concept.md](./concept.md)
* 統合見取り図: [plugin_spec.md](./plugin_spec.md)
* 索引: [specs.md](./specs.md)
