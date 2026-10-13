# WordPress Studio (第2段階テスト環境)

本ディレクトリは、**WordPress Studio の Blueprint** で検証用サイトを再現するための置き場です (共通方針: [wp-plugin-spec SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) 段階2)。

## 命名・パス (公開ドキュメント向け)

* **Studio のサイト表示名** は任意である (開発者ごとに付けるだけ。リポジトリの手順では、固定名を前提にしない)。
* **WordPress ルート** は環境ごとに異なる。自動スモークでは環境変数 **`WP_PATH`** に、`wp-config.php` があるディレクトリを指定する (Local / Studio / その他 WP-CLI 可能なサイトで共通)。
* Studio で `WP_PATH` が不明な場合は、そのサイト用シェルで `wp core version` が通るカレント、または Studio が示す WordPress ルートを使う。ホームディレクトリ配下の具体パスは、ドキュメントに書かない。

## ファイル

| ファイル | 内容 |
| --- | --- |
| [`smoke-test-blueprint.json`](./smoke-test-blueprint.json) | Studio / Playground 形式 (PHP v8.2、WP latest、**GatherPress + GatherPress alpha**、Query Monitor、Plugin Check、シード mu-plugin) |
| [`mu-plugin-smoke-seed.php`](./mu-plugin-smoke-seed.php) | Blueprint の `writeFile` に埋め込む mu-plugin の **ソース** (JSON 内 `data` と同期) |

## GatherPress について

Blueprint では **WordPress.org の GatherPress** と **GatherPress alpha** を続けて `installPlugin` します。開発中の GatherPress (wp.org 最新 / beta 系) は **alpha コンパニオンとの同時有効化** を要求するため、段階2スモークでも **両方 enabled** を前提にします (zip のフォルダ名が正しく、Studio 上で安定するため wp.org 経由)。

本番開発で **[stein2nd/gatherpress](https://github.com/stein2nd/gatherpress)** フォークを使う場合は、Blueprint 適用後にフォーク zip を `wp-content/plugins/gatherpress` に差し替えてください。`EventPluginDocumentSettings` 等の差分確認が目的なら、フォーク専用 Blueprint に `installPlugin` の `url` を差し替える運用でもかまいません。

## S2J Webinar は Blueprint に含めない

MLDC と同様、`vendor/` と `dist/` を Git 管理外とするため、**本プラグインは Blueprint 後に手動配置** します。

```bash
# リポジトリルート
composer install --no-dev
npm ci && npm run build:production
# Studio サイトの wp-content/plugins/s2j-webinar に symlink または rsync
wp plugin activate s2j-webinar
```

## 手順

1. Studio → **Add site** → **Upload a Blueprint** → [`smoke-test-blueprint.json`](./smoke-test-blueprint.json)。
2. 上記のとおり **ビルド済み** 本プラグインを `wp-content/plugins/s2j-webinar` に配置し、`wp plugin activate s2j-webinar`。
3. 管理画面を一度開く (mu-plugin が **GatherPress + S2J Webinar 両方 active** の場合、下書きイベントを1件追加)。
4. **S2J Webinar** 設定画面 (Blueprint の `landingPage`) と GatherPress イベント編集で [test-results.md](../docs/test-results.md) 段階1チェックリストを確認 (**Webhook URL / secret は未設定** — Zoom は HTTPS 必須。段階2では後回し)。
5. リポジトリルートで `WP_PATH='<WordPress ルート>' npm run test:smoke:local` (`WP_PATH` は段階1の Local でも同じ意味)。
6. 結果を `docs/test-results.md` の「最新結果」に記録。

Local の開発サイトと Studio の検証サイトを分けておくと、WP / PHP バージョン差の確認が Local の作業を壊しにくくなります。

## フォーク GatherPress 用 install 差し替え (任意)

`smoke-test-blueprint.json` の GatherPress ステップを、次の形に差し替える案です (GitHub zip は展開後フォルダ名に注意)。

```json
{
	"step": "installPlugin",
	"pluginData": {
		"resource": "url",
		"url": "https://github.com/stein2nd/gatherpress/archive/refs/heads/main.zip"
	},
	"options": {
		"activate": true
	}
}
```

Studio で有効化に失敗した場合は、zip 内ディレクトリを `gatherpress` にリネームしてから再配置してください。
