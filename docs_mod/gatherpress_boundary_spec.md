<!--
目的：「GatherPress との境界 (日時・Slot・公開リンク・終日)」の明文化
-->

# S2J Webinar - GatherPress 境界仕様

## 設計意図 (ゴール)

イベントの日時・公開オンラインリンク・編集 Slot は GatherPress の所有のまま、本プラグインが読む・書く境界だけを固定します。フォーク本体は改変しません。

## 非対象

* GatherPress 内部のイベントテーブル実装
* RSVP、会場・トピック taxonomy
* Zoom REST 規則 (サービス)

## 編集パネルの足し方

| 項目 | 方針 |
| --- | --- |
| Slot | `EventPluginDocumentSettings` |
| 方式 | WordPress の Fill のみ。Slot 定義をフォークに足さない |
| 権限 | そのイベントを編集できるユーザー |
| Survey | Survey プラグインが別 Fill を載せる。本パネルと同居してよい |

## 日時の読み取り

本プラグインは、同期のたびに GatherPress が保持する開始・終了、timezone からレコードの日時を組み立てます。イベントメタに開始時刻の複製を持ちません。

| レコード | 組み立て |
| --- | --- |
| `timezone` | GatherPress のイベント timezone (IANA。UTC オフセット表記は GP の正規化に従い IANA 相当に) |
| `start_at` | 開始瞬間をタイムゾーン付きで表す (例: `2026-10-09T15:00:00+09:00`)。サービスの検証形に合わせる |
| `duration_minutes` | 終了 − 開始を秒以下切り捨て (floor) した整数分。1以上。0以下は不足。四捨五入しない |

読み取り API は、GatherPress が公開するイベント日時の取得手段を使います (`Event` 相当のヘルパ、または REST / メタ経由)。キー名が GP 側で変わった場合は、本境界仕様だけを直し、サービス仕様に WP キーを書き戻しません。

### 終日

終日イベントの場合:

* 開始は当該暦日の `00:00` (イベント timezone)
* 所要時間は **暦日数×1440** 分 (例: 1日終日→1440、2日終日→2880)
* 終日判定は GatherPress の終日フラグ / 表現に従う

## 公開参加 URL

本プラグインが公開ページのブロックを置き換えません。表示は GatherPress のオンラインイベントブロックに任せます。

| 項目 | 方針 |
| --- | --- |
| 公開欄のメタ | `gatherpress_online_event_link` |
| 書き込み契機 | create / update / get 写像で `join_url` が非空になった場合のみ (空なら既存メタ / 公開欄は触らない)。`webinar.started` で維持 / 再設定してよい ([webhook_spec.md](./webhook_spec.md)) |
| API | `Event::set_online` 相当 (GatherPress が提供するオンラインリンク設定)。生 `update_post_meta` だけに頼らず、GP の公開経路を優先する |
| 空にする場合 | **Zoom 削除成功後のみ。** create / update / get の空 `join_url` では消さない。`webinar.ended` でも空にしない (終了後の表示抑止は GatherPress に任せる) |

## イベント種別

初版は、GatherPress のイベント投稿 (または `gatherpress-event-date` を宣言する投稿タイプ) に対してパネルを出します。会場投稿には出しません。

## 削除・ゴミ箱

| 操作 | 本プラグイン |
| --- | --- |
| パネルの「Zoom から削除」 | `intend_delete` でサービスの delete を実行 |
| 投稿のゴミ箱移動・完全削除 | Zoom delete を **呼ばない**。メタは WP の投稿削除に従う (アンインストール時の一括処理は [plugin_spec.md](./plugin_spec.md)) |

## 関連

* データ辞書: [data_dictionary.md](./data_dictionary.md)
* 同期: [sync_execution_spec.md](./sync_execution_spec.md)
* Webhook: [webhook_spec.md](./webhook_spec.md)
