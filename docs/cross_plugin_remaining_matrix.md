# S2J ウェビナー系 — 100% チェックリスト未完了の分類

最終更新: 2026-10-19

**配置:** 本ファイルは **S2J Webinar** リポジトリのコピーです。同一内容を [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/cross_plugin_remaining_matrix.md) / [S2J Webinar Source QR](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/cross_plugin_remaining_matrix.md) にも置き、`status.md` の `[ ]` 更新時に **3 ファイルを同期** してください。

各プラグインの **正本** はそれぞれの `docs/status.md` §100% 完了条件チェックリスト です。本ファイルは **横断の読み方** 用で、`[ ]` 行を次の3軸に振り分けた一覧です。

| 軸 | 意味 |
| --- | --- |
| **Local** | Local / Studio / CI (`localhost`、MySQL テスト DB、WP-CLI smoke 等) で完結。公衆 HTTPS 不要 |
| **Webinar 実装待ち** | **S2J Webinar** の機能・REST・hook が先 (例: **P2a** 定義 REST、handoff 契約)。実装後は多くが **Local** に移る |
| **HTTPS + Zoom** | 外部結合ゲート — Marketplace OAuth、実 Zoom API、Webhook、実 Zoom 上のデータ (登録 URL、Survey 添付等)。ステージング / トンネル想定 |

**Service リポジトリ** (`s2j-webinar-service` / `s2j-webinar-survey-service`) の `[ ]` は各 Service の `docs/status.md` が正本です。[status §品質](./status.md#status-quality) に「本プラグイン 100% 到達後に Service 側を [x]」があります。

**外部結合ゲート** (`E1` …) の Pass は各 `test-results.md` に記録し、多くの repo では **100% 分母外** です (実装％に含めない)。

---

## サマリ (件数)

| リポジトリ | Local | Webinar 実装待ち | HTTPS + Zoom | 備考 |
| --- | ---: | ---: | ---: | --- |
| [S2J Webinar Survey](#s2j-webinar-survey) | 0 | 0 | 0 (+ E3/E4 ゲート) | Local 未完了 9 件 **2026-10-19** 完了 (wp-env 分母外) |
| [S2J Webinar](#s2j-webinar) | 2 | 1 | 1 (+ E1–E4 ゲート) | P2a + handoff + **E7** **2026-10-19**。P2b 未。P3 は v0.0.2 対象外 |
| [S2J Webinar Source QR](#s2j-webinar-source-qr) | 10 | 5 | 3 (+ E1/E3 ゲート) | 任意・handoff 含む |

---

## S2J Webinar Survey

正本: [status.md §100%](../s2j-webinar-survey/docs/status.md#100-完了条件チェックリスト) / [test-results.md](../s2j-webinar-survey/docs/test-results.md) ([GitHub](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/status.md))

**Local 未完了 `[ ]`:** なし (**2026-10-19**)。メタ REST / 3b は `npm run test:unit:php:local` (MySQL) を正本とし、wp-env は採用しない。

| § | 備考 (完了) |
| --- | --- |
| 仕様 | `docs-lint` CI |
| Composer | `smoke-local.sh` — evaluate + REST ルート |
| 設定 / 永続化 / 下書き / アンインストール | Integration 9 本 + unit 6 |
| 品質 | 3a CI + 3b local + `lint:php` + test-results |

### ゲートのみ (チェックリスト `[ ]` ではない)

| # | 内容 | HTTPS + Zoom |
| --- | --- | :---: |
| E3 | Survey `ready` → Webinar `attach_survey` | ● (Webinar E2 後) |
| E4 | Zoom 上アンケート添付 | ● |

---

## S2J Webinar (+ Webinar Service)

正本: [status.md §100%](./status.md#100-完了条件チェックリスト) / [test-results.md](./test-results.md)

**Local 記録 (Pass 済 — [status §品質・スモーク](./status.md#status-quality) / [test-results §最新結果](./test-results.md#最新結果)):**

| 項目 | 記録日 | メモ |
| --- | --- | --- |
| 段階1 smoke + 手動 #1–8 | 2026-10-17 | Local + Studio (`webinar-smoke`)。OAuth #6 SKIP |
| Blueprint **ゼロから** + 手動 #1–8 | **2026-10-18** | 新規 Blueprint サイト。GP + Alpha (Releases)。#7 — `_s2j_webinar_panelists` 2名・スーパーリロード後も保持 |
| 段階3a+3b / `lint:php` | 2026-10-17–19 | **22 + 8** (定義 REST Integration 追加)、順0 |
| P2a 定義 REST + handoff (Webinar) | **2026-10-19** | コード + **E5** handoff + **E7** Pass (Studio post **8**、stub 無効、REST + **ブラウザ** 証跡 [Source QR studio-2026-1019-e7-real-p2a](../s2j-webinar-source-qr/docs/screenshots/studio-2026-1019-e7-real-p2a/)) |

**未完了 `[ ]` (分類):**

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| 品質 | Service §プラグイン — 本プラグイン 100% 後に Service [x] | ● | | プロセス (Service repo) |
| 品質 | Source QR 連携 — localhost `catalogReady` Pass 記録 (**E7**) | | | **2026-10-19** Pass ([test-results §最新結果](./test-results.md#最新結果)) |
| 品質 | **[E1–E4]** — OAuth / 実 API / Webhook / Survey attach | | | ● |
| 運用 P1 | CoverArt / フライヤー | ● | | HTTP なし |
| 運用 P2b | `GET …/tracking-sources` (取り込み) | | ● | P2a **実装済**。P2b 実 GET は E2 |
| 運用 P3 | 配配メール (ラクス) | — | — | status 100% 外 |

### 外部結合ゲート (詳細)

| # | 内容 | Local | HTTPS + Zoom |
| --- | --- | :---: | :---: |
| E1 | OAuth connect (Marketplace Redirect / Allow list) | 資格情報保存・試行まで可 | ● 成功確認 |
| E2 | create / update / get / delete 実 API | 未接続 422 等は Local | ● |
| E3 | Webhook started / ended | | ● |
| E4 | Survey `ready` → attach_survey 実 Zoom | Survey UI は Local | ● |
| E5 | 参加 URL 目視 | ● | |
| E7 | Source QR 定義 REST → `catalogReady` | **Pass 2026-10-19** (Studio post **8**) | |

---

## S2J Webinar Source QR

正本: [status.md §100%](../s2j-webinar-source-qr/docs/status.md#100-完了条件チェックリスト) / [test-results.md](../s2j-webinar-source-qr/docs/test-results.md) ([GitHub](https://github.com/stein2nd/s2j-webinar-source-qr/blob/main/docs/status.md))

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| REST | `sanitize_callback` — draft と同一カタログ厳格 | ● | | |
| REST | `save_post` 再正規化 — 任意 | ● | | |
| REST | meta sanitize / 経路ガード文書化 | ● | | |
| REST | 4xx 拡張 — 任意 | ● | | |
| 管理 UI | Apply **成功時** UI (Step4 ブラウザ記録) | | | **2026-10-19** P2a 実定義 post **8** — [Source QR 証跡](../s2j-webinar-source-qr/docs/screenshots/studio-2026-1019-e7-real-p2a/05-step4-apply-success-ready-ja.png) |
| 管理 UI | dirty / Notice `code` 分岐 | ● | | |
| 管理 UI | handoff から同一モーダル | | | **E5 Pass 2026-10-19** |
| 管理 UI | `catalogReady` true で Save draft 200 | | | **2026-10-19** 実 REST draft **200** |
| 管理 UI | Import → POST draft — 実 `tracking-sources` | | | ● E1/E3 |
| カタログ | 定義 REST 200 → `catalogReady` true (実 Webinar) | | | **2026-10-19** Pass |
| 取り込み | 実 Zoom 登録 URL → draft 永続化 | | | ● |
| handoff | open リスナ + 同一 Modal | | | **E5 Pass 2026-10-19** |
| handoff | Webinar から同一モーダル open (Test) | | | **E5 Pass 2026-10-19** |
| 品質 | Blueprint ゼロから Studio Pass 記録 | ● | | |
| 品質 | **E2** 定義 REST — localhost Pass 記録 | | | **Pass 2026-10-19** |
| 品質 | **E3** 取り込み | | | ● |
| 品質 | Webinar § Source QR — 定義 REST E2E | | | **E7 Pass 2026-10-19** |

### 外部結合ゲート (参照)

| # | 内容 | Local | HTTPS + Zoom |
| --- | --- | :---: | :---: |
| E1 | Webinar OAuth / `_s2j_webinar_id` | | ● |
| E2 | 定義 REST 200 + `sources.length >= 1` | **Pass 2026-10-19** | |
| E3 | 取り込み `tracking-sources` | | ● |
| E4 | Save draft / Apply (`catalogReady`) | **Pass 2026-10-18** (stub) + **2026-10-19** (P2a 実定義 UI) | |
| E5 | handoff 同一モーダル | **Pass 2026-10-19** | |
| E6 | QR PNG Apply E2E | ● (Integration PASS) | |

---

## 推奨の進め方 (依存順)

1. **Local 自動** — 各 repo [status §推奨テスト実施順](./status.md#status-test-order) 順0–2。
    * **Webinar** — 順0–3 Pass 済 + **P2a / handoff / E7 (2026-10-19)**。[test-results §最新結果](./test-results.md#最新結果)。
2. ~~**Local 連携記録** — stub 無効 → Source QR **E2** / Webinar **E7** / Save draft 200~~ — **2026-10-19** Pass。
3. **Webinar P1 / P2b / Source QR UI 残** — Local または HTTPS (P2b/E3)。
4. **HTTPS ステージング** — Webinar **E1 → E2 → E3**、続けて Survey **E3/E4**、Source QR **E3** (Import)。

---

## メンテナンス

* 各 repo の `status.md` で `[ ]` / `[x]` を更新したら、**3 リポジトリ** の `docs/cross_plugin_remaining_matrix.md` を同じ内容で揃える (相対リンク行のみ各 repo 向けに調整可)。
* 配置: `s2j-webinar/docs/` (本ファイル)、`s2j-webinar-survey/docs/`、`s2j-webinar-source-qr/docs/`。Local モノレポでは `wp-content/plugins/` 直下の兄弟パス。
