<!--
目的：「README と docs の整合、用語、archive 運用」の明文化
-->

# S2J Webinar - ドキュメンテーション・ガバナンス

本ドキュメントは、ユーザー向け説明と仕様の **整合ルール** を定義します。

共通の要約は [wp-plugin-spec / SPECS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md) §4です。ひな型は [DOCUMENTATION_GOVERNANCE_TEMPLATE.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/DOCUMENTATION_GOVERNANCE_TEMPLATE.md) です。

## 設計意図 (ゴール)

README と `docs/` の説明・命名が、実装とサービス契約からずれないようにします。「正本はどれか」「完了したら何を freeze するか」を、毎回説明しなくてよくします。

## 1. Source of Truth

| 対象 | 正本 |
| --- | --- |
| 検証・操作計画・材料・写像および OAuth 材料形 | Webinar Service の `docs/core/` |
| 公開 PHP API (Composer) | Webinar Service の `docs/interfaces/php_api_spec.md` |
| メタキー、option、メタ ↔ レコード、dirty 判定 | 本 `docs/` (`data_dictionary.md` 等) |
| 画面、HTTP 実行、Webhook、i18n | 本 `docs/` の分割仕様 |
| 最短手順・インストール | ルート `README.md` |
| 統合の見取り図 | `docs/plugin_spec.md` (要約。細部の正本ではない) |
| 索引 | `docs/specs.md` |

矛盾時は **規則・契約の正本** (計算はサービス側、メタ・画面は本 `docs/`) を直し、README、要約、usage を追随させます。メタキー名をサービス仕様に書き戻しません。

## 2. 用語

* コード名・キー名は [data_dictionary.md](../data_dictionary.md) と Webinar Service の contracts に従う。
* 画面の表示文言は、「適切なメッセージ文を表示する」と書き、国際化関数の経由を前提とする。
* レコードの `status` (`not_created` / `synced` / `dirty` / `error`) と Survey 文書の `status` (`draft` / `ready`) とセッション `phase` を混同しない。
* `dirty` の業務判定は本プラグイン。サービスは推測し直さない。
* Survey の `answer_kind` と Zoom の `type` を混同しない。写像は Webinar Service。
* 「パネル」は本プラグインのイベント編集 Fill。「パネル文書」は Survey 側用語であり、本プラグインでは使わない。
* UI および仕様の見出しは「登壇者」。キー・操作名・サービス用語は `panelists` / Panelist。初出で「登壇者 (Panelist)」と対応づける。
* Composer の repository 種別コードは `` `path` `` と書く。日本語の「パス」は URL やファイルの説明に限定する。
* アンインストール対象は所有キーの明示リスト。`_s2j_webinar_*` ワイルドカードと `_s2j_webinar_survey` を混同しない。
* レコードキーは `webinar_uuid` (略記 `uuid` 禁止)。Survey の status 値は `` `ready` `` / `` `draft` ``。
* 公開 API 名は `build_webinar_request` を使う。短記 `build( … )` は使わない。
* 「Zoom で開く」は `build_webinar_request( 'get', … )` 直呼び。`plan( intend_get )` は使わない (usage の `intend_get` 例は採用しない)。パネル編集キーだけ `show_in_rest`。
* 配配メールはラクス社のサービス名である。初出は「配配メール (ラクス)」と書く。
* FOP は初出で Functional Object-Oriented Programming と展開する。

## 3. Lint

* `@s2j/docs-linter` を SoT とする。
* ローカルと CI で同じ `npm run lint:docs` を使う。
* 対象は `README.md`、`CHANGELOG.md`、および `docs/` / `docs_mod/` 配下の仕様 Markdown である。

## 4. 分割ルール

* 新規仕様は [specs.md](../specs.md) のレイヤーに分類する。
* 必要になるまで、他製品にある層 (OpenAPI / SRE 等) を増やさない。
* Zoom パス・ボディの複製を本キットに置かない。サービスにリンクする。

## 5. 改訂フロー (`docs_mod` → `docs`)

* 確定仕様の正本は `docs/` である。
* 大きな改訂案は `docs_mod/` で起草し、レビュー・合意のあと `docs/` に反映する。
* 依存リポジトリからのリンクは、可能な限り `docs/` を指すように保つ。
* 小さな typo は `docs/` を直接直してよい。

## 6. イニシアチブ証跡 (archive)

実装・改修の区切りごとに、人が読める合格証跡を残します。機械成果物 (カバレッジ HTML 等) とは別です。

### 6.1. 命名

`<slug>` は短い kebab-case とします。SemVer はフォルダー名に入れません。

| 種類 | フォルダー |
| --- | --- |
| 実装イニシアチブ | `docs/archive/impl-<slug>/` (起草中は `docs_mod/` に三点) |
| 改修イニシアチブ | `docs/archive/mod-<slug>/` |
| 仕様リライトの旧正本 | `docs/archive/spec-<slug>/` |

### 6.2. 三点セット

| ファイル | 書くこと |
| --- | --- |
| `modification.md` | 目的、スコープ内外、タスク表、完了定義 |
| `status.md` | 進捗サマリー、完了条件のチェック |
| `test-results.md` | 仕様条件 ID ごとの PASS / WARN / FAIL |

### 6.3. ライフサイクル

索引は [archive/README.md](../archive/README.md) です。

1. 開始 … `docs_mod/` に三点セット
2. 作業 … 合意した仕様は都度 `docs/` に反映する
3. フリーズ … `docs/archive/…` にコピーして固定 (FAIL なし、CHANGELOG に一行)
4. フリーズ後 … archive は原則変更しない
5. 片付け … `docs_mod/` の三点は削除してよい

## 7. `docs/archive/README.md` に置くもの

* 本ガバナンスへのリンク (規則の正本はこちら)
* 命名の一行要約 (`impl-` / `mod-` / 仕様リライト)
* 完了イニシアチブの索引表 (日付・フォルダー・種別・一言)

機械成果物は、archive に置かない。

## 8. `docs_mod/README.md` に置くもの

* 確定正本は `docs/` であること
* 起草中の仕様ドラフトと、進行中イニシアチブ三点の置き場であること
* 完了後は archive に freeze し、三点は削除してよいこと
* 詳細へのリンク (本ファイルと `docs/archive/README.md`)
