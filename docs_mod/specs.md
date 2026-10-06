# S2J Webinar - プラグイン仕様 (ドラフト)

採用・合意前の設計メモとして `docs_mod/` に置き、確定後は `docs/SPEC.md` に移行します。状態はドラフトです。記録日は2026-10-03です。

計算の正は [S2J Webinar Service のサービス仕様](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs_mod/service_spec.md) です。本書は、WordPress 側の接続、保存、画面、HTTP の実行を定義します。

## 概要

本プラグインは、GatherPress のイベント編集画面から Zoom Webinar を作成・更新・削除・再取得するコンパニオンです。イベントの一覧と公開ページは GatherPress が持ちます。Webinar のリクエスト組立は S2J Webinar Service が持ちます。

スラッグは `s2j-webinar` です。ライセンスは GPL-3.0-or-later です。テキストドメインは `s2j-webinar` です。

## 位置付け

`kis-event-manager` の置き換え先は、次の3つです。

| 層 | 名称 | 役割 |
| --- | --- | --- |
| イベント UI | [stein2nd/gatherpress](https://github.com/stein2nd/gatherpress) | イベントの投稿、一覧、公開 URL。上流に追従する |
| 呼び出し側 | **本プラグイン** | OAuth、トークン、HTTP、イベントメタ、管理画面 |
| 計算 | [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) | 検証、次の操作、リクエスト材料。WordPress を知らない |

GatherPress のフォークには、本プラグインのコードを入れません。公開フックの外側からイベント編集画面にパネルを足します。

## 目的

初版で、営業担当者が WordPress のイベント編集画面から、下記を終えることです。

* Webinar 権限のある Zoom アカウントを、サイトに1つ接続する。接続ユーザーが、作成される Webinar のホストになる。
* イベントのタイトル、開始日時、所要時間、タイムゾーン、録画方式、登壇者から Webinar を新規登録する。
* 登壇者の1人目の氏名と社内メールアドレスを、質問メールの送信先として Zoom の登録連絡先に載せる。
* 開催日時や登壇者を変えたあと、Zoom に更新する。
* 参加 URL と Webinar ID を、そのイベントに保存する。

## 非目標 (初版)

* Zoom の作成画面の全項目を再現すること。
* 出席、滞在、投票、Q&A の中身、録画の視聴数を WordPress に保存すること。
* クラウド録画のファイルをメディアライブラリに保存すること。
* 申込者を Zoom の registrant として登録すること。
* Zoom 側の手修正を検知して、イベントの項目を上書きすること。
* CoverArt、QR、フライヤー PDF、配配メール文面。後続として本書の末尾に境界だけ置く。
* Teams、Meet、Webex。画面の接続先は Zoom 固定である。
* GatherPress フォーク本体の改変。
* WordPress.org への掲載。

## GatherPress との境界

本プラグインは、GatherPress が有効なときだけパネルを出します。無効なときは管理画面に案内を出し、Zoom は呼びません。

イベントのタイトルと公開 URL の読み取りは、GatherPress の投稿とメタからです。開始日時、終了日時、タイムゾーンは、保存時に正規化された読み取り専用のキーから読みます。編集画面が書く JSON の `gatherpress_datetime` と、GMT のキーは読みません。`Event::get_datetime()` は表示用のタイムゾーン・フィルターを通すため、Zoom に送る値には使いません。日時のコピーは、本プラグインのメタには持ちません。

| キー | 内容 |
| --- | --- |
| `gatherpress_datetime_start` | イベントのタイムゾーンでの開始。`Y-m-d H:i:s` |
| `gatherpress_datetime_end` | 同じ形式の終了 |
| `gatherpress_timezone` | IANA のタイムゾーン |

Zoom に渡す開始時刻は `gatherpress_datetime_start`、タイムゾーンは `gatherpress_timezone` です。所要時間のキーは GatherPress にないため、開始と終了の差を分にして、サービスに渡す `duration_minutes` にします。

終日 (`gatherpress_is_all_day`) も、初版から新規登録と更新をします。Zoom に終日のフラグはないため、GatherPress が保存した日の範囲を開始時刻と所要時間にします。開始は `gatherpress_datetime_start` (そのタイムゾーンの `0:00:00`) です。所要時間は、開始日と終了日を含む暦日の数に1440分を掛けた値です。1日なら1440分、2日なら2880分です。終了キーの `23:59:59` との差 (1439分) は使いません。パネルには終日であることを出します。

登壇者は GatherPress の機能には頼みません。本プラグインが、そのイベントのメタとして順序付きで保存します。

## サービスとの境界

本プラグインは、サービスが返した操作だけを Zoom に送ります。リクエストの中身 (パス、ボディ、登壇者の差分、質問メールの送信先) はサービスが決めます。

| 本プラグイン | サービス |
| --- | --- |
| イベントとメタから、Webinar レコードを組み立て、渡す | 検証し、次の操作とリクエスト材料を返す |
| `Authorization` を付けて HTTP を実行する | トークンを材料に含めない |
| ステータスとボディをサービスに戻す | 結果レコードを返す |
| 結果をイベントメタとサイト設定に書く | 保存しない |

クライアント ID、クライアント・シークレット、リフレッシュ・トークンはサイト設定です。リポジトリにコミットしません。サービスには実行のたびに渡します。アクセス・トークンもサイト設定です。Zoom への `Authorization` に付けるのは本プラグインであり、サービスには渡しません。

## 接続

設定画面は「設定 > S2J Webinar」です。操作できるのは `manage_options` を持つユーザーです。

画面に出すものは、次のとおりです。プロバイダ名の入力欄と選択一覧は出しません。

* Zoom のクライアント ID
* Zoom のクライアント・シークレット (保存後は値を再表示しない)
* Webhook の秘密トークン (保存後は値を再表示しない)
* 接続状態
* 接続中の Zoom ユーザー (メールアドレス)
* 「Zoom と接続」「再接続」「接続を解除」

「Zoom と接続」は、Zoom の OAuth 画面に遷移します。許可するのは、Webinar 権限のあるアカウントです。WordPress のログインユーザーとは別人でかまいません。現行のホストは `zoom3@…` です。

アプリは、ユーザー管理の OAuth アプリです。公開マーケットには出さず、サーバー間認証にもしません。扱うのは、許可した Zoom ユーザー本人の Webinar だけです。アプリ種別、リダイレクト URL、スコープは、WordPress の入力欄にはしません。

リダイレクトのパスは `wp-admin/admin-post.php?action=s2j_webinar_oauth_callback` です。サイトの URL と連結した完全な形を、Zoom のアプリに一字違わず登録します。認可コードはサーバーが受け取り、`manage_options` を持つログイン中の担当者だけがトークンを保存します。戻り口には `state` を付け、別の画面からの戻りを拒みます。クライアント・シークレットはブラウザに出しません。

スコープはグラニュラーで、本人用だけです。末尾が `:admin` のものは要求しません。

| 操作 | スコープ |
| --- | --- |
| 作成 | `webinar:write:webinar` |
| 取得 | `webinar:read:webinar` |
| 更新 | `webinar:update:webinar` |
| 削除 | `webinar:delete:webinar` |
| 登壇者の追加 | `webinar:write:panelist` |
| 登壇者の削除 | `webinar:delete:panelist` |
| 接続ユーザーのメール | `user:read:user` |

申込者の registrant、録画ファイル、他ユーザーの Webinar は要求しません。スコープを増やすと、Zoom は接続のやり直しを求めます。

開催の開始と終了は、Zoom の Webhook で受けます。購読するのは `webinar.started` と `webinar.ended` だけです。受け口のパスは `wp-json/s2j-webinar/v1/zoom/webhook` です。サイトの URL と連結した完全な形を、Zoom のアプリに登録します。秘密トークンはサイト設定です。保存後は再表示せず、ログと画面に出しません。署名を検証できない通知と、保存していない Webinar ID の通知は捨てます。公開ページを開くたびに、開催中かを Zoom に問い合わせません。

コールバックで認可コードをトークンに換え、アクセス・トークン、その期限、リフレッシュ・トークン、表示用のメールアドレスをサイト設定に保存します。アクセス・トークンは期限まで使い回します。呼ぶ直前に毎回リフレッシュしません。期限の直前だけリフレッシュし、判定には数十秒の余裕を見ます。重なったリフレッシュは直列にします。Zoom はリフレッシュのたびに新しいリフレッシュ・トークンを返し、古いほうを無効にします。返ったアクセス・トークンと新しいリフレッシュ・トークンは、同じ書き込みでサイト設定に上書きし、古いリフレッシュ・トークンは残しません。ログ、通知、画面にはトークンを出しません。イベント編集パネルは WordPress に操作を頼み、トークンを付けるのはサーバー側の HTTP です。

未接続のイベント編集画面では、新規登録を出さず、設定画面へのリンクを出します。

## イベントに保存するもの

1イベントにつき Webinar は1つです。保存先は、そのイベント投稿のメタです。接頭辞は `_s2j_webinar_` です。先頭の `_` により、投稿画面のカスタムフィールド一覧には出ません。読み書きするのは、本プラグインのパネルだけです。項目ごとに1キーです。登壇者の一覧と、直近に送った写しだけは、それぞれ1キーの配列です。クライアント ID、シークレット、トークン、QR の用語集は、この接頭辞には置きません。

| キー | 内容 |
| --- | --- |
| `_s2j_webinar_provider` | コードが `zoom` と書く。入力欄にはしない |
| `_s2j_webinar_webinar_id` | 未作成なら空 |
| `_s2j_webinar_uuid` | 空可 |
| `_s2j_webinar_join_url` | 空可。案内に使ってよい |
| `_s2j_webinar_status` | `not_created` / `synced` / `dirty` / `error` |
| `_s2j_webinar_last_error` | トークンは入れない |
| `_s2j_webinar_auto_recording` | `none` / `cloud` / `local`。デフォルトは `cloud` |
| `_s2j_webinar_approval_type` | `0` 必須・自動承認 / `1` 必須・手動承認 / `2` 不要。デフォルトは `0` |
| `_s2j_webinar_panelists` | 順序付きの配列。各要素は氏名とメール |
| `_s2j_webinar_last_sent` | サービスが `dirty` を判定するための写し。配列 |
| `_s2j_webinar_session` | 空は未開始。`live` は開催中。`ended` は終了。Webhook が書く |

開始 URL (`start_url`) は、ホストがその Webinar を始めるためのリンクです。参加 URL とは別です。通常のユーザーでは、取得から2時間で無効になります。接続する `zoom3@…` はこの2時間です。「Zoom で開く」は、押したときに `GET /webinars/{webinarId}` を呼び、返った `start_url` をその場で開きます。開始 URL は保存しません。期限の記録も持たず、2時間をタイマーやキャッシュにしません。スコープは `webinar:read:webinar` です。

参加登録は、そのイベントのパネルで選びます。コントロールはラジオで、選択肢は次の3つです。デフォルトは必須・自動承認です。`zoom3` の作成画面が登録必須だからです。イベントページを一つの参加 URL にする回は、不要を選びます。

| パネル | `settings.approval_type` |
| --- | --- |
| 必須・自動承認 | `0` |
| 必須・手動承認 | `1` |
| 不要 | `2` |

サービスは、選んだ値を作成と更新に含め、省略しません。省略すると、接続ユーザーのデフォルトが使われる可能性があります。不要では、イベントページの一つの参加 URL から入れます。Zoom は入室時に氏名とメールアドレスを尋ねます。必須では、その参加 URL は Zoom の登録ページに回され、承認された人が自分用の URL を受け取ります。申込者を registrant として WordPress から送ることは、初版の外です。必須を選んだときは、パネルにその旨を出します。

質問メールの送信先は、独立したメタにはしません。登壇者の1人目の氏名と社内メールアドレスです。サービスが `settings.contact_name` と `settings.contact_email` に載せます。1人目は社内の営業メンバーです。ホストの `zoom3@…` と、営業のファンクションアドレス (たとえば `business@…`) は、この欄にしません。ライセンスユーザーは、そのファンクションアドレスの受信者に入っていないためです。

セッション中の Q&A (`settings.question_and_answer`) は、作成リクエストで一式を送ります。`enable` = `true`、`allow_anonymous_questions` = `true`、`answer_questions` = `only` です。`enable` だけは送りません。コメントと upvote は送りません。パネルには出しません。質問メールの送信先とは別です。

トピックは200文字まで、説明は2000文字まで、です。パスコードは送らず、公開ページにも出しません。オーディオはコンピューター (`voip`) です。ホストとパネリストのカメラは off です。HD は `settings.hd_video` = `false` です。出席者の参加時認証は `settings.meeting_authentication` = `false` です。認証プロファイルの項目は送りません。パネリストの参加時認証は送りません。チャットのデフォルト対象は送りません。作成 API に一対一のフィールドがないためです。これらは作成リクエストで送ります。

作成で省略したとき、ユーザーのアカウント設定を継ぐと Zoom が公式にしているのは、2026年3月15日以降の7項目です。`password`、`add_watermark`、`add_audio_watermark`、`language_interpretation`、`sign_language_interpretation`、`panelist_authentication`、`allow_host_control_participant_mute_state`。これらはサービスが省略したままにし、本プラグインのメタには持ちません。更新で送るのも、サービスが持つ項目だけです。

## 画面 (イベント編集)

GatherPress は、イベント編集のドキュメント・サイドバーに Slot `EventPluginDocumentSettings` を開けています。本プラグインは、その Slot に Fill を登録して「オンラインウェビナー」のパネルを出します。フォークにボタンや画面は足しません。資料は [Slots & fills in GatherPress Admin UI](https://github.com/GatherPress/gatherpress/blob/develop/docs/developer/blocks/slot-fills/README.md) です。

下記はサンプルコードです。

```
import { Fill } from '@wordpress/components';

<Fill name="EventPluginDocumentSettings">
	{ /* 接続状態、登壇者、録画、参加登録、新規登録 */ }
</Fill>
```

公開ページの「オンラインイベントの URL」は、GatherPress の `gatherpress_online_event_link` です。新規登録の成功時と、参加 URL が変わった更新のときに、`Event::set_online( true, $join_url )` で書きます。メタだけは更新しません。このメソッドは、そのキーと会場タクソノミーの `online-event` タームを組で書き、すでに付いている実会場のタームは外しません。空の URL は渡しません。`_s2j_webinar_join_url` はパネル表示用の写しであり、公開ブロックは読みません。作成ウィザードそのものは、この Fill が持ちます。

GatherPress は、予定の終了日時 (開始に所要時間を足した時刻) を過ぎると、公開のリンクを空にします。参加予定の人にだけ出す判定も、同じ箇所にあります。Zoom は、ホストが「終了」を押すまで続きます。参加登録が自動承認のときは、予定を過ぎてからでも登録して入室できます。`_s2j_webinar_session` が `ended` でないイベントでは、`gatherpress_force_online_event_link` を true にし、予定の終了時刻ではリンクを隠しません。イベントページを開いた人に出します。`webinar.ended` を受けたときは、`gatherpress_online_event_link` を空にします。`online-event` タームは外しません。`_s2j_webinar_join_url` は残します。`webinar.started` を受けたときは、`_s2j_webinar_session` を `live` にし、保存してある参加 URL を再び `Event::set_online` で公開欄に戻します。

パネルの内容は、次のとおりです。

* 接続中の Zoom ユーザーと接続状態
* 状態 (未作成 / 同期済み / 未同期 / 失敗)
* 開催 (未開始 / 開催中 / 終了)
* 録画 (なし / クラウド / ローカル)。デフォルトはクラウド
* 参加登録。ラジオで、必須・自動承認 / 必須・手動承認 / 不要。デフォルトは必須・自動承認
* 登壇者。追加、削除、並べ替えができる。1人目を「質問メールの送信先」と表示する
* Webinar ID と参加 URL (作成後。表示のみ)
* 「Zoom Webinar を新規登録」「Zoom に更新」「Zoom Webinar を削除」「Zoom 情報を再取得」「Zoom で開く」

定員を超えそうなときは、ライブストリームを別途検討する旨をパネルに出します。配信 URL とストリームキーは送りません。

終日のイベントでも、新規登録と更新を出します。パネルには、終日として登録する旨を出します。

再取得は表示の更新です。タイトル、日時、登壇者、録画方式、参加登録を Zoom の値で上書きしません。

効果測定の数値は出しません。必要なら Zoom の当該 Webinar へのリンクだけを出します。リンク先は実装時に公式で確認します。

失敗したときは、直近の失敗をパネルに出します。トークンとクライアント・シークレットは出しません。

## 実行

新規登録は、同じ操作の中で二段階です。

1. イベントのタイトル、日時、タイムゾーン、録画方式、参加登録、登壇者と、保存済みの Webinar メタからレコードを作る。
2. サービスに渡す。
3. 返った操作が `none` なら HTTP しない。
4. 第一段階は `POST /users/me/webinars` です。スケジュール画面の入力を送ります。ウェビナー ID は、その応答の `id` です。発行の通知は購読しません。`webinar.created` は待ちません。
5. ID、UUID、参加 URL をメタに書きます。参加 URL は `_s2j_webinar_join_url` に保存し、同じ URL を `Event::set_online( true, $join_url )` で GatherPress に渡します。
6. 第二段階は、その ID をパスに含む追加リクエストです。スケジュール後のタブの初期値を、項目表にあるものだけ送ります。詳細タブへの書き込みはありません。
7. 応答をサービスに戻し、結果をメタに書きます。

第一段階が失敗したときは、ID を空のままにし、状態を `error` にします。第二段階が失敗したときは、Webinar は削除せず、ID を残して状態を `error` にします。パネルに失敗を出し、やり直しできます。両方成功で状態を `synced` にします。

更新は、状態が `dirty` のときだけ送ります。`dirty` になるのは、成功のあとでタイトル、日時、所要時間、タイムゾーン、録画方式、参加登録、登壇者のいずれかが変わったときです。判定はサービスが行います。

登壇者の追加と削除は、サービスの差分に従います。削除は、外した人ごとに `DELETE /webinars/{webinarId}/panelists/{panelistId}` を送り、`panelistId` にはそのメールを載せます。全員を消すパスは使いません。同じメールで氏名だけ変わったときは、その人を削除してから追加し直します。並び替えだけでは削除しません。1人目が変わったときは、Webinar の連絡先の更新も同じ操作に含めます。この削除は、予定されている Webinar への反映です。開催中の部屋の役割は、ホストが Zoom の画面で扱います。

削除は、パネルの「Zoom Webinar を削除」だけです。イベントをゴミ箱に移しても、完全削除しても、Zoom は削除しません。完全削除には、ゴミ箱から「完全に削除する」と、ゴミ箱に入ってから30日で起きる自動削除を含みます。中止するときは、完全削除の前にパネルから削除します。完全削除のあとにはパネルがないので、その時点ではもう送れません。出席とクラウド録画の正は Zoom であり、イベント投稿が消えても Zoom に残します。

## 後続 (初版の外)

Zoom 連携のあとで足す候補です。サービスには入れません。CoverArt と公開前チェックリストは、本プラグインに足します。QR は、別プラグインの QR コード・ジェネレータが持ちます。

* CoverArt はメディアライブラリの添付です。イベントの資産として参照します。
* QR は WordPress 内で作ります。qr.quel.jp は使いません。飛び先は GatherPress のイベント URL です。`utm_medium` は `qr`、`utm_campaign` はそのイベントのスラッグです。`utm_source` は、ユーザーが管理する用語のスラッグです。新規 QR のデフォルトはブランクで、ブランクのときはパラメータを付けません。用語集は階層がなく、タグの管理画面と同じです。初期データは空で、例は `flyer` / `email` / `web` です。一度 QR に使ったスラッグは変えません。この用語集は、本プラグインの設定にもメタ接頭辞にも置きません。見た目は色、中央のアイコン、SVG と PNG です。モジュールは四角に固定し、形状の選択は出しません。描画は Composer の `chillerlan/php-qrcode` (MIT) です。ロゴを載せるときは誤り訂正を H にします。中央で隠すのは、コードのおよそ2割まで、です。画面と UTM の組立は、ライブラリを直接呼びません。
* フライヤー PDF と配配メール用ヘッダーは、CoverArt と QR がそろってから検討します。配配メールへの送信は、本プラグインの外です。
* 公開前チェックリストは、パネル冒頭の表示です。
* 招待状のソース追跡は、QR の `utm_source` と同じ用語です。登録ページのバナーは、CoverArt ができてから、登録ページを使うときに出します。
* アンケートの設問は、[S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey) が作ります。仕様は [docs_mod/specs.md](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs_mod/specs.md) です。本プラグインは、作成直後に、使ってよいと判定された文書をアンケートとして付けます。設問の文面と助言は持ちません。
* 登壇者の Profile 台帳、メール本文の「最新情報」と「末尾の告知」、待機室の画像と動画、投票の流用、チャットのデフォルト対象、登録者数の Slack や LineWorks への通知は、後続です。連携タブの他製品接続は、本プラグインに入れません。

## 設計方針

[kis-wordpress エコシステム仕様](https://github.com/stein2nd/kis-wordpress/blob/main/docs_mod/specs.md) および [wp-plugin-spec](https://github.com/stein2nd/wp-plugin-spec) に従います。ドメインの判断はサービス側の純関数です。本プラグインはアダプタです。

フック登録、設定画面、メタの読み書き、`wp_remote_*` は本プラグインに置きます。クラスを使ってよいのは、この境界です。

| 借用する原則 | 本プラグインでの意味 |
| --- | --- |
| 依存の向きは外 → 内 | 画面と HTTP がサービスを呼ぶ。サービスは WordPress を呼ばない |
| 内側はビジネスルール | Webinar 項目の検証と次の操作はサービス |
| 外側は詳細 | OAuth、メタ、GatherPress、Zoom への HTTP |

Composer で `s2j/webinar-service` を require します。参照は [S2J Slug Generater](https://github.com/stein2nd/s2j-slug-generater) が [`s2j/similarity-service`](https://packagist.org/packages/s2j/similarity-service) を Packagist の名前だけで require するのと同じです。`repositories` に `VCS` も `path` も書きません。

## 関連リポジトリ

| 名称 | 種別 | 役割 |
| --- | --- | --- |
| **本プラグイン** | WP プラグイン | 接続、HTTP、メタ、画面 |
| [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) | Composer | 検証、次の操作、リクエスト材料 |
| [stein2nd/gatherpress](https://github.com/stein2nd/gatherpress) | WP プラグイン (フォーク) | イベント UI。本プラグインのコードは置かない |
| [kis-event-manager](https://github.com/yuki-530/kis-event-manager) | WP プラグイン | 現行。段階的に GatherPress と本プラグインに差し替える |
| [kis2026_base](https://github.com/stein2nd/kis2026_base) | テーマ | 見た目。イベントの正は GatherPress |

## 実装順

1. 本ドラフトの合意。
2. `zoom3` の作成画面の項目と Zoom API の突き合わせ (サービス仕様と同じ作業)。
3. プラグインの骨格、設定画面、OAuth の接続と解除。
4. イベント編集パネル。登壇者、録画方式、参加登録をメタに保存する。この段階では Zoom を呼ばない。
5. 新規登録と、ID および参加 URL の保存。
6. 更新、削除、再取得、登壇者の差分、質問メール送信先の更新。
7. CoverArt と公開前チェックリストは、別ドラフトで本プラグインに足す。QR は、別プラグインの後続ドラフトで扱う。

## 本ドラフトの提案

合意前の提案です。

* スラッグは `s2j-webinar`。ライセンスは GPL-3.0-or-later。
* イベント UI は GatherPress フォークのままにし、本プラグインはフックの外側に置く。
* サイトに接続する Zoom アカウントは1つ。ホストはその接続ユーザーである。
* 質問メールの送信先は登壇者の1人目であり、独立した宛先フィールドは持たない。
* 新規登録は二段階である。ウェビナー ID は作成応答の `id` であり、発行は購読しない。その ID で、スケジュール後のタブの初期値を追加リクエストする。
* 登壇者の削除は `DELETE /webinars/{webinarId}/panelists/{panelistId}` である。`panelistId` は外した人のメールである。全員削除は使わない。
* セッション中の Q&A は、作成で `settings.question_and_answer` の一式を送る。`enable` = `true`、`allow_anonymous_questions` = `true`、`answer_questions` = `only`。`enable` だけでは送らない。コメントと upvote は送らない。パネルには出さない。
* 参加登録はイベントごとにラジオで選ぶ。デフォルトは必須・自動承認であり、イベントページを一つの参加 URL にする回は不要を選ぶ。選んだ値は省略せず Zoom に送る。
* 録画のデフォルトはクラウドである。オーディオはコンピューター、ホストとパネリストのカメラは off、HD は `hd_video` = `false`、出席者の参加時認証は `meeting_authentication` = `false`、パスコードは送らない。チャットのデフォルト対象は送らない。
* 作成で省略してアカウント設定を継ぐのは、Zoom が公式に挙げた7項目だけである。本プラグインのメタには持たない。
* 開始 URL は保存しない。「Zoom で開く」は、押したときに `GET /webinars/{webinarId}` の `start_url` をその場で開く。通常ユーザーの期限は2時間であり、タイマーにはしない。
* 同期は WordPress から Zoom への一方向である。再取得は表示用である。
* イベントのゴミ箱移動と完全削除では Zoom を削除しない。Zoom を消すのはパネルの明示的な操作だけである。
* OAuth クライアントとトークンはサイト設定であり、配布物とログに含めない。アクセス・トークンは期限まで使い回し、期限の直前にだけリフレッシュする。
* OAuth アプリはユーザー管理である。スコープは本人用のグラニュラーだけを要求し、戻り口は `admin-post.php` の固定パスである。
* イベントメタの接頭辞は `_s2j_webinar_` である。プロバイダはコードが `zoom` と書き、管理画面では選ばせない。
* 参加 URL の公開欄は GatherPress の `gatherpress_online_event_link` であり、`Event::set_online` で書く。予定の終了時刻では隠さない。開始と終了は Zoom の Webhook で知る。
* 開始・終了・タイムゾーンは `gatherpress_datetime_start`、`gatherpress_datetime_end`、`gatherpress_timezone` から読む。所要時間は開始と終了の差 (分) である。終日も初版で登録し、所要時間は暦日数 ×1440分とする。
* QR は初版の外である。`utm_source` の用語集は、後続の QR コード・ジェネレータが持つ。モジュールは四角に固定する。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-03 | 初版ドラフト。リポジトリ作成に合わせ、接続、メタ、イベント編集パネル、HTTP 実行、GatherPress 非改変、質問メールは登壇者1人目、を記録 |
| 2026-10-03 | 編集パネルは GatherPress の Slot `EventPluginDocumentSettings` に Fill する。フォークにボタンは足さない |
| 2026-10-05 | Composer の参照を、S2J Slug Generater と同じく Packagist のパッケージ名だけにする、と記録 |
| 2026-10-05 | アクセス・トークンはサイト設定に置き、期限まで使い回す。期限の直前にリフレッシュし、新しいリフレッシュ・トークンと一緒に上書きする、と記録 |
| 2026-10-05 | 参加登録は不要とする。作成と更新で `settings.approval_type` に `2` を送り、省略しない、と記録 |
| 2026-10-05 | 参加登録はイベントのパネルでラジオ選択にする。デフォルトは不要。選んだ値を省略せず送る、と記録 |
| 2026-10-05 | ゴミ箱移動と完全削除では Zoom を削除しない。Zoom の削除はパネルの明示操作だけである、と記録 |
| 2026-10-05 | セッション中の Q&A は初版では送らない。接続ユーザーのデフォルトに任せる、と記録 |
| 2026-10-05 | QR の UTM は `utm_medium=qr`、`utm_campaign` はイベントのスラッグ、`utm_source` はユーザー管理でデフォルトブランク。用語集と描画は後続の QR コード・ジェネレータが持つ、と記録 |
| 2026-10-05 | イベントメタの接頭辞は `_s2j_webinar_` とする。プロバイダはコードが `zoom` と書き、管理画面では選ばせない、と記録 |
| 2026-10-05 | OAuth はユーザー管理アプリとする。戻り口は `admin-post.php` の固定パス、スコープは本人用のグラニュラーだけ、と記録 |
| 2026-10-05 | 参加 URL の公開欄は `gatherpress_online_event_link` とし、`Event::set_online` で書く、と記録 |
| 2026-10-05 | 公開の参加 URL は、予定の終了時刻では隠さない。Zoom の「終了」まで入室できるため、と記録 |
| 2026-10-05 | 開催の開始と終了は `webinar.started` と `webinar.ended` で受ける。終了通知で公開の参加 URL を空にする、と記録 |
| 2026-10-05 | 日時は `gatherpress_datetime_start`、`gatherpress_datetime_end`、`gatherpress_timezone` から読む。所要時間はその差 (分)。終日は新規登録と更新を拒む、と記録 |
| 2026-10-05 | 終日も初版で登録する。開始はそのタイムゾーンの `0:00`、所要時間は暦日数 ×1440分、と記録 |
| 2026-10-05 | QR のモジュールは四角に固定する。形状の選択は出さない、と記録 |
| 2026-10-05 | 登壇者の削除は `DELETE /webinars/{webinarId}/panelists/{panelistId}` とする。`panelistId` はメール。全員削除は使わない、と記録 |
| 2026-10-05 | 開始 URL は保存しない。「Zoom で開く」は押したときに `GET /webinars/{webinarId}` の `start_url` を開く。通常ユーザーの期限は2時間で、タイマーにはしない、と記録 |
| 2026-10-05 | 作成で省略してアカウント設定を継ぐのは、Zoom が公式に挙げた7項目だけとする。それ以外はフィールドごと送らず、更新はサービスが持つ項目だけ送る、と記録 |
| 2026-10-06 | 新規登録は二段階とする。ウェビナー ID は作成応答の `id` であり、発行は購読しない。その ID でタブの初期値を追加リクエストする、と記録 |
| 2026-10-06 | `zoom3` のスケジュール画面の項目を確定する。参加登録のデフォルトは必須・自動承認。録画のデフォルトはクラウド。トピックは200文字、説明は2000文字。パスコードは送らない、と記録 |
| 2026-10-06 | アンケート設問の仕様を [s2j-webinar-survey](https://github.com/stein2nd/s2j-webinar-survey) に分けた、と記録 |
| 2026-10-07 | Q&A は作成で `question_and_answer` 一式を送る。HD は `hd_video` = `false`。チャットのデフォルト対象は送らない、と記録 |
| 2026-10-07 | 出席者の参加時認証は `settings.meeting_authentication` = `false`。パネリスト認証と `enforce_login` は使わない、と記録 |
