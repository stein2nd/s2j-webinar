# S2J Webinar

GatherPress のイベント編集画面から Webinar を作成・更新する WordPress プラグインです。判断の正は [S2J Webinar Service](https://github.com/stein2nd/s2j-webinar-service) にあります。

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
```

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
