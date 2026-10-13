# テスト戦略と結果記録

本プラグインの検証方針と実行記録です。環境の役割 (Local / Studio / PHPUnit) の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このプラグイン固有の手順と結果** です。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリの状態 |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (結合スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` + 手動チェックリスト |
| **2** | テストサイト構成の再現 (WP / PHP、**GatherPress 同梱**) | **WordPress Studio**、Blueprints | [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) (S2J Webinar は手動配置) |
| **3** | PHP 自動テスト、PR 時のゲート | PHPUnit、GitHub Actions | `composer test` (純 PHPUnit)。WP 統合テストは wp-env 拡張予定 |

段階1と2は「実際の WordPress サイト」、段階3の現状は **WordPress を起動しない** ユニットテストです。Local 開発 DB を PHPUnit が直接書き換える必要はありません。

**依存:** 段階1・2では **[GatherPress](https://github.com/stein2nd/gatherpress)** (フォーク可) を **有効化必須** です。wp.org 最新系では **GatherPress alpha** も同時に有効化する必要があります (GatherPress 本体が要求)。本プラグイン単体ではイベント post type / Slot が成立しません。

---

## 第1段階: Local (結合スモーク)

日常の **Local 等の開発サイト** の `wp-content/plugins/s2j-webinar` に置いた状態で確認します (サイト名・パスは環境依存のため、手順では固定しません)。

| # | 確認項目 | 期待 |
| --- | --- | --- |
| 1 | `composer install` + `npm run build:production` | `vendor/` と `dist/js/s2j-webinar-*.js` が存在 |
| 2 | GatherPress 有効化 | エラーなく有効 |
| 3 | S2J Webinar 有効化 | エラーなく有効 (`s2j/webinar-service` 同梱) |
| 4 | 管理メニュー **S2J Webinar** | 設定画面が開く。クライアント ID / 秘密鍵保存可 |
| 5 | GatherPress **イベント** 編集 (ブロックエディター) | パネル **Webinar** (Fill) が表示。未接続時は警告 |
| 6 | Zoom 接続 (任意・テストアカウント) | OAuth コールバック後「connected」通知 |
| 7 | イベント保存 | パネルメタ (`_s2j_webinar_*`) が REST 経由で保存される |
| 8 | **同期** ボタン | REST `POST /wp-json/s2j-webinar/v1/events/{id}/sync` が 422/200 を返す (Zoom 未設定時は失敗も可。ルート到達を確認) |

**自動 (段階1の一部):** Local の **Site Shell** では、まず **本プラグインのディレクトリ** (`wp-content/plugins/s2j-webinar`) に `cd` してから実行します。Site Shell の初期カレントは多くの場合 WordPress の `public` だけで、そこには `test:smoke:local` がありません (`npm error Missing script`)。

```bash
cd wp-content/plugins/s2j-webinar
export WP_PATH="/path/to/your/site/app/public"   # wp-config.php があるディレクトリ
npm run test:smoke:local
# 同等: WP_PATH="$WP_PATH" bash tests/smoke-local.sh
```

GatherPress + 本プラグイン有効化、REST ルート、Service 関数到達を確認します。Gutenberg UI・OAuth は手動項目として残します。

---

## 第2段階: WordPress Studio (再現可能な検証環境)

[WordPress Studio](https://developer.wordpress.com/docs/developer-tools/studio/) を、Local とは別系統の **検証用サイト工場** として使います。

* **Local:** いつもの開発・手動スモーク (段階1)
* **Studio:** 同じ手順で立ち上がる **テスト専用サイト** (WP / PHP 差、**GatherPress + 本プラグイン** の事前配置)

### 想定する使い方 (Blueprint 完成後)

1. Studio → Upload Blueprint → [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) ([Blueprints](https://developer.wordpress.com/docs/developer-tools/studio/blueprints/) 形式)。GatherPress は Blueprint 内で有効化。
2. 生成サイトに **`npm run build:production` 済み** + `composer install --no-dev` 済みの本プラグインを symlink / コピーし、`wp plugin activate s2j-webinar`。
3. 管理画面を開き mu-plugin シード (下書きイベント1件) を走らせる。
4. (GatherPress は Blueprint 済み。フォーク利用時は [studio/README.md](../studio/README.md) 参照。)
5. 段階1チェックリスト + `WP_PATH='<WordPress ルート>' npm run test:smoke:local` (Studio のサイト表示名や OS 上のフォルダ名は手順に含めない)。
6. 結果を下記「最新結果」に記録 (環境列は「WordPress Studio (Blueprint)」など抽象名でよい)。

詳細メモ: [studio/README.md](../studio/README.md)

---

## 第3段階: PHPUnit

### 3a. 純 PHPUnit (現状・CI)

WordPress を起動せず、`s2j/webinar-service` 連携とアダプタの純ロジックを検証します。

```bash
composer install
composer test
# または
npm run test:unit:php:local
```

期待: `tests/` 内のテストが Pass (件数は `phpunit.xml.dist` 参照)。GitHub Actions (`.github/workflows/phpunit.yml`) でも同コマンドを実行します。

### 3b. wp-env 上の PHPUnit (拡張予定)

`WP_UnitTestCase` を使うメタ登録・REST・GatherPress 境界のテストを追加する場合:

1. `npm install` (`@wordpress/env` 同梱)
2. `.wp-env.json` で本プラグイン + GatherPress を指定
3. `npm run phpunit:wp-env`

初回は Docker 上で wp-env が WordPress テストライブラリを用意します。Local の開発 DB とは別です。

---

## 最新結果

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-14 | 1 | Local (`blockpluginbase`) | PASS | `npm run test:smoke:local`（プラグインルート + 自動 `WP_PATH`）。手動 checklist は [第1段階](#第1段階-local-結合スモーク) 参照 |
| 2026-10-14 | 2 | WordPress Studio (`webinar-smoke`) | PASS | プラグイン `~/Studio/webinar-smoke/wp-content/plugins/s2j-webinar` から `npm run test:smoke:local`（`WP_PATH` → Studio サイトルート）。wp-cli v2.12の PHP Deprecated (react/promise) は出力のみ・判定に影響なし。手動 checklist は [第1段階](#第1段階-local-結合スモーク) 参照 |
| 2026-10-13 | 3 | CI / ローカル | PASS | `composer test` (ServiceBridge, StatusCalculator) |
| 2026-10-14 | 3a | ローカル | PASS | 純 PHPUnit: 18 件 (OAuth refresh, SyncRunner, Get/Delete, Webhook 署名) |
| 2026-10-14 | 3b | wp-env | (未実行) | 統合 4 件 — `npm run test:unit:php` (Docker 要) |

実行後、日付、Pass/Fail、メモを追記してください。
