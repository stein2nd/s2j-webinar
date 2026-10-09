# S2J Webinar

GatherPress のイベント編集画面から Zoom Webinar を作成・更新する WordPress プラグインです。判断の正は [S2J Webinar Service](https://github.com/stein2nd/s2j-webinar-service) にあります。

## 仕様

確定前の仕様キットは [docs_mod/specs.md](docs_mod/specs.md) です。合意のあと `docs/` に移行します。

## 依存

* WordPress と [GatherPress](https://github.com/stein2nd/gatherpress) (フォーク可。本プラグインは Slot のみ使用)
* Composer: `s2j/webinar-service` (Packagist の名前だけ。`repositories` に VCS / パスは書かない)

## 開発

```bash
npm install
npm run lint:docs
```
