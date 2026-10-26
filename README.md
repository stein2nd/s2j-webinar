# S2J Webinar

GatherPress のイベント編集画面から Webinar を作成・更新する WordPress プラグインです。判断の正は [S2J Webinar Service](https://github.com/stein2nd/s2j-webinar-service) にあります。

## Installation {#installation}

1. [GatherPress](https://github.com/stein2nd/gatherpress) を有効化します (wp.org 版では **GatherPress Alpha** が必要な場合があります)。
2. 本プラグインを `wp-content/plugins/s2j-webinar/` に配置し、**Plugins** 画面で有効化します (リリース zip または `git clone`)。
3. **Settings → S2J Webinar** で GatherPress / 連携状態を確認します。

WordPress プラグインディレクトリ (wp.org) への掲載を予定しています。掲載後は **Plugins → Add New** からのインストールも可能になります。

**任意コンパニオン** (本プラグイン有効化が前提):

| プラグイン | 用途 |
| --- | --- |
| [S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr#installation) | ソース別 registration URL + QR PNG |
| [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey#installation) | ウェビナー後アンケート |

**Settings → S2J Webinar** に、上記コンパニオンのインストール / 有効化案内が表示されます (両方有効のときは非表示)。

## 仕様

確定仕様の索引は [docs/specs.md](docs/specs.md) です。大きな改訂案は [docs_mod/](docs_mod/) で起草します。

## 依存

* **PHP v8.3+**、**WordPress v6.3+** ([WordPress 動作環境](https://ja.wordpress.org/about/requirements/))
* [GatherPress](https://github.com/stein2nd/gatherpress) (フォーク可。本プラグインは Slot のみ使用)
* Composer: `s2j/webinar-service` (Packagist の名前だけ。`repositories` に VCS / パスは書かない)

## Contributing

### ウェビナープロバイダ (初版と拡張)

* **初版 (v0.0.1)** で接続・同期の対象とするのは、[Zoom 開発者向け API](https://www.zoom.com/ja/blog/build-valuable-experiences-zooms-developer-platform/) を利用する **Zoom** のみです。サイト設定の Webinar provider 表示と UI 文言の `%s` は [`WebinarProvider`](includes/Provider/WebinarProvider.php) に集約しています。
* 下記のプラットフォーム向けアダプタ (OAuth / Webhook / HTTP 実行と、必要なら [Webinar Service](https://github.com/stein2nd/s2j-webinar-service) 側の拡張) は、**コントリビューターによる設計・実装を歓迎** します。着手前に Issue または Discussion で方針を共有してください。
  * [Microsoft Teams 開発者向け API](https://learn.microsoft.com/en-us/microsoftteams/platform/)
  * [Google Meet 開発者向け API](https://developers.google.com/workspace/meet/api/guides/overview?hl=ja)
  * [Livestorm 開発者向け API](https://developers.livestorm.co/docs/introduction)
  * [GoTo Webinar 開発者向け API](https://developer.goto.com/GoToWebinarV2/)
  * [ON24 開発者向け API](https://apidoc.on24.com/home)
  * [WebinarKit 開発者向け API](https://documenter.getpostman.com/view/22597176/Uzs435mo)
  * [WebinarGeek 開発者向け API](https://webinargeek.docs.apiary.io/#)

境界とキー命名の正本は [docs/specs.md](docs/specs.md) および [docs/plugin_spec.md](docs/plugin_spec.md) です。

## 開発

```bash
composer install
npm install
npm run lint:docs
npm run build:production   # dist/ (管理画面・ブロックエディター JS)
npm run dist               # release/s2j-webinar-<version>.zip
composer test
npm run makepot          # languages/s2j-webinar.pot
npm run makei18n         # .mo + JED (Gutenberg サイドバー翻訳に JED 必須)
```

**日本語 (Gutenberg):** `makepot` のあと `wp i18n update-po languages/s2j-webinar.pot languages/s2j-webinar-ja.po` で PO をそろえ、**`msgstr` を埋めてから** `npm run makei18n`。空 `msgstr` は JED に載りません ([admin_ui_spec.md](docs/admin_ui_spec.md#i18n)、[wp-plugin-spec §2.3](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/I18N_AND_A11Y_TEMPLATE.md#23-gutenberg--管理-js-wp_set_script_translations))。補助: [`tools/i18n/fill-empty-msgstr.php`](tools/i18n/fill-empty-msgstr.php)。

### WP-CLI と PHP 8.5

`makepot` など WP-CLI 経由のコマンドでは、**phar に同梱された依存ライブラリ** 由来の `PHP Deprecated` が PHP v8.5環境で出ることがあります。POT 生成は通常どおり成功し、**本プラグインのコードが原因ではありません**。上流は [WP-CLI の PHP 8.5 互換](https://github.com/wp-cli/wp-cli/issues/6157) で対応中です。警告を減らすには `wp cli update --nightly` など CLI の更新を検討してください。

## テスト (3段階)

| 段階 | コマンド / 参照 |
| --- | --- |
| 1: Local 結合 | プラグインルートで `WP_PATH='<WordPress public>' npm run test:smoke:local` — [docs/test-results.md](docs/test-results.md) |
| 2: WordPress Studio | GatherPress 必須。[`studio/smoke-test-blueprint.json`](studio/smoke-test-blueprint.json) — [studio/README.md](studio/README.md) |
| 3a: PHPUnit (unit) | `composer test` または `npm run test:unit:php:unit-only` |
| 3b: PHPUnit (integration) | `.env.tests` 設定後 `npm run test:unit:php:local` (MySQL + 初回 svn)。任意: `npm run test:unit:php` (wp-env) |

`readme.txt` は textlint 対象外 (`.textlintignore`) です。
