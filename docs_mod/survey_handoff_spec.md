<!--
目的：「Survey `ready` 文書の受け渡しと attach_survey」の明文化
-->

# S2J Webinar - Survey 受け渡し仕様

## 設計意図 (ゴール)

S2J Webinar Survey が保存した `ready` 文書を、Zoom アンケートとして付ける境界だけを固定します。設問の検査・助言は持ちません。

## 非対象

* 設問 UI、不足・助言、下書きコネクタ (Survey プラグイン / Survey Service)
* Zoom survey ボディ写像 (Webinar Service [survey_map_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/survey_map_spec.md))

## 所有

| 対象 | 所有者 |
| --- | --- |
| `_s2j_webinar_survey` | S2J Webinar Survey |
| `attach_survey` 材料・写像 | S2J Webinar Service |
| plan への載せ方、HTTP | 本プラグイン |

## 読み取り条件

本プラグインが `plan` の `survey_document` に載せるのは、下記をすべて満たす場合だけです。

1. メタ `_s2j_webinar_survey` が存在する。
2. その文書の `status` が **`ready`** である (保存済みメタ文書。パネル作業コピーではない)。
3. パネルの「同期」ボタンによる context 組み立て時である (アンケート専用の同期 UI は持たない)。

`draft` やメタ未作成の場合は `survey_document` を渡しません。Survey がないサイトでも create できます (survey 未完了を create の条件にしない)。

## 実行

* 入口は常に通常の「同期」→ `plan_webinar_operations` → 操作列の `attach_survey` → `build_webinar_request` → HTTP → `map`。
* 後から設問が `ready` になり、レコードが `synced` の場合も、同じ「同期」で `attach_survey` だけが計画されうる (サービス operation_spec)。専用ボタンは持たない。
* 本プラグインは Survey メタを書き戻さない。写像後に変えるのは Webinar 側メタだけである。

## 表示

* パネルに設問編集は出さない。
* Survey が `draft` の場合、「アンケートはまだ添付できない」旨の適切なメッセージ文を出してよい (任意。必須ではない)。
* 不足コードはサービス / Survey のコードを画面に生で出さない。

## アンインストール

* 本プラグインのアンインストールでは `_s2j_webinar_survey` を消さない (Survey プラグインの所有。アンインストール対象の明示リストからも除外する。[data_dictionary.md](./data_dictionary.md))。
* Zoom 上のアンケートは、本プラグインのアンインストールでは変更しない。

## 関連

* Survey 永続化: [persistence_spec.md](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/persistence_spec.md)
* 同期: [sync_execution_spec.md](./sync_execution_spec.md)
* サービス写像: [survey_map_spec.md](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs/core/survey_map_spec.md)
