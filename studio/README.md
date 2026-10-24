# WordPress Studio (第2段階テスト環境)

本ディレクトリは、**WordPress Studio の Blueprint** で検証用サイトを再現するための置き場です (共通方針: [wp-plugin-spec SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) 段階2)。

## 命名・パス (公開ドキュメント向け)

* **Studio のサイト表示名** は任意である (開発者ごとに付けるだけ。リポジトリの手順では、固定名を前提にしない)。
* **WordPress ルート** は環境ごとに異なる。自動スモークでは環境変数 **`WP_PATH`** に、`wp-config.php` があるディレクトリを指定する (Local / Studio / その他 WP-CLI 可能なサイトで共通)。
* Studio で `WP_PATH` が不明な場合は、そのサイト用シェルで `wp core version` が通るカレント、または Studio が示す WordPress ルートを使う。ホームディレクトリ配下の具体パスは、ドキュメントに書かない。

## ファイル

| ファイル | 内容 |
| --- | --- |
| [`smoke-test-blueprint.json`](./smoke-test-blueprint.json) | Studio / Playground 形式 (PHP v8.3、WP latest、**GatherPress (wp.org)**、Query Monitor、Plugin Check、シード mu-plugin。**GatherPress Alpha は Blueprint 外** — [GitHub 手動](#gatherpress-alpha-blueprint-後手動)) |
| [`mu-plugin-smoke-seed.php`](./mu-plugin-smoke-seed.php) | Blueprint の `writeFile` に埋め込む mu-plugin の **ソース** (JSON 内 `data` と同期) |

## GatherPress について

Blueprint では **WordPress.org の GatherPress 本体のみ** `installPlugin` します。wp.org 最新系 GatherPress は開発中のため、**GatherPress Alpha** コンパニオンとの **バージョン一致** が必要です (本体が admin 通知で案内)。

## GatherPress Alpha (Blueprint 後・手動) {#gatherpress-alpha-blueprint-後手動}

**GatherPress Alpha** は開発・互換テスト用で **[WordPress プラグインディレクトリには公開されていません](https://wordpress.org/plugins/search/gatherpress-alpha/)**。  
[GatherPress/gatherpress-alpha](https://github.com/GatherPress/gatherpress-alpha) の **Releases** から、**インストール済み GatherPress と同じバージョン** の `gatherpress-alpha.zip` を取得して有効化してください ([README 手順](https://github.com/GatherPress/gatherpress-alpha#how-to-determine-which-version-to-install))。

1. 管理画面 **プラグイン** で GatherPress の **バージョン番号** を確認 (Blueprint 直後は wp.org 最新。例: `0.36.0`)。
2. [gatherpress-alpha Releases](https://github.com/GatherPress/gatherpress-alpha/releases) で **同じバージョン** の Release を開き、Assets の **`gatherpress-alpha.zip`** をダウンロード。
3. **プラグイン → 新規追加 → アップロード** で zip を入れ、**GatherPress Alpha** を有効化 (`Requires Plugins: gatherpress` — 本体を先に有効のまま)。
4. 未適用の互換更新がある場合: **Events → Settings → Alpha** で **Apply Updates** (または `wp gatherpress alpha fix`)。

Local 開発サイトに既に `gatherpress-alpha` フォルダがある場合は、**同バージョン** のコピーを Studio サイトの `wp-content/plugins/gatherpress-alpha` に置いて `wp plugin activate gatherpress-alpha` でも構いません。

段階1–2の checklist **#2** は **GatherPress + Alpha 両方有効** を Pass 条件とします ([test-results.md](../docs/test-results.md))。

本番開発で **[stein2nd/gatherpress](https://github.com/stein2nd/gatherpress)** フォークを使う場合は、Blueprint 適用後にフォーク zip を `wp-content/plugins/gatherpress` に差し替え、Alpha も **フォーク GatherPress のバージョンに合わせた Release** を入れてください。

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
2. [GatherPress Alpha](#gatherpress-alpha-blueprint-後手動) を **GatherPress と同バージョン** でインストール・有効化。
3. 上記のとおり **ビルド済み** 本プラグインを `wp-content/plugins/s2j-webinar` に配置し、`wp plugin activate s2j-webinar`。
4. 管理画面を一度開く (mu-plugin が **GatherPress + S2J Webinar 両方 active** の場合、下書きイベントを1件追加)。
5. **S2J Webinar** 設定画面 (Blueprint の `landingPage`) と GatherPress イベント編集で [test-results.md](../docs/test-results.md) 段階1チェックリストを確認 (**Webhook URL / secret は未設定** — Zoom は HTTPS 必須。段階2では後回し)。
6. リポジトリルートで `WP_PATH='<WordPress ルート>' npm run test:smoke:local` (`WP_PATH` は段階1の Local でも同じ意味)。
7. 結果を `docs/test-results.md` の「最新結果」に記録。

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
