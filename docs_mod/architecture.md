<!--
目的：「フォルダー構成、主要ファイル、技術スタック、責務」の明文化
-->

# S2J Webinar - アーキテクチャー

## 設計意図 (ゴール)

OAuth、HTTP、メタ、画面を WordPress 側に閉じ、計算を `s2j/webinar-service` に任せます。GatherPress フォークは改変しません。

## 設計方針

* 本プラグインはアダプタ。FOP (Functional Object-Oriented Programming) の計算はサービス。
* フック登録、設定、メタ、`wp_remote_*` は本プラグイン。
* クラスを使ってよいのはこの境界 (画面、HTTP、永続化)。

## レイヤー構成

| 層 | 責務 | 非責務 |
| --- | --- | --- |
| Admin UI (React) | 設定・イベントパネル | Zoom REST 規則 |
| PHP アダプタ | OAuth、HTTP、メタ、Webhook | 検証・差分の本体 |
| Webinar Service | 純関数 | I/O、WP |
| GatherPress | イベント UI、公開リンク | Webinar メタ |

```mermaid
flowchart TD
  U["担当者"] --> P["イベントパネル / 設定"]
  P --> A["本プラグイン PHP"]
  A --> S["s2j/webinar-service"]
  A -->|"Authorization + HTTP"| Z["Zoom REST"]
  A --> M["イベントメタ / option"]
  G["GatherPress"] -.->|"Slot / 日時 / set_online"| A
  SV["S2J Webinar Survey"] -.->|"`ready` 文書"| A
  Z -->|"Webhook"| A
```

## フォルダー構成 (想定)

```plaintext
s2j-webinar/
├── README.md
├── CHANGELOG.md
├── composer.json
├── package.json
├── s2j-webinar.php
├── docs/                 # 合意後の確定仕様
├── docs_mod/             # いまの起草キット
├── includes/             # PHP (設定、OAuth、HTTP、メタ、Webhook)
├── src/                  # 管理画面・パネル用 TS/TSX
├── build/                # ビルド成果
└── languages/
```

## 技術スタック

| 項目 | 方針 |
| --- | --- |
| PHP | WordPress プラグイン。Composer で `s2j/webinar-service` |
| JS | Gutenberg / `@wordpress/*`、Vite ビルド |
| ドキュメント lint | `@s2j/docs-linter` / `npm run lint:docs` |

## 関連

* 原則: [principles.md](./principles.md)
* 実行: [sync_execution_spec.md](./sync_execution_spec.md)
* サービス: [architecture.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/architecture.md)
