# Next Title TrimCount

タイトル・見出しブロックに**文字数**または**行数**で制限を追加する WordPress プラグイン。制限を超えた部分はフロントエンドで「…」に省略表示されます。

**Version: 1.4.0**

## 機能

- **対応ブロック**: `core/heading`（見出し）、`core/post-title`（投稿タイトル）、`feed-block/feed-item-title`（Feed Item Title）
- ブロックエディターのインスペクターパネルから最大文字数を設定
- 現在の文字数をリアルタイム表示（見出しブロック）
- 制限超過時に警告通知を表示（見出しブロック）
- クエリーループ内の投稿タイトルに一括適用可能
- HTML タグ構造を保持したままトリム処理（開いたタグを自動クローズ）
- フロントエンドのみで省略を適用（エディター内コンテンツは変更なし）
- **マルチバイト完全対応**: 日本語・絵文字・サロゲートペア文字を 1 文字として正確にカウント
- **行数制限**: CSS `line-clamp` による行数での省略（文字数制限と排他選択）

## 動作環境

- WordPress 6.0 以上
- PHP 7.4 以上

## インストール

1. `next-Title-TrimCount` フォルダーを `wp-content/plugins/` に配置
2. 管理画面 > プラグイン から「Next Title TrimCount」を有効化

## 使い方

1. ブロックエディターで見出し（H1〜H6）または投稿タイトルブロックを選択
2. 右サイドバー（インスペクター）に「文字数制限」パネルが表示される
3. 「最大文字数」に数値を入力（`0` で制限なし）
4. 制限を設定したページをフロントエンドで確認すると、超過分が「…」に省略される

## 開発

### 必要なツール

- Node.js
- npm

### セットアップ

```bash
npm install
```

### ビルド

```bash
# 本番ビルド
npm run build

# 開発ウォッチ
npm run start
```

### 配布用 ZIP 作成

```bash
npm run zip
```

`build/` 以外のソースファイルを除いた ZIP が親ディレクトリに生成されます。

## ファイル構成

```
next-Title-TrimCount/
├── build/
│   ├── index.js           # ビルド済みエディタースクリプト
│   └── index.asset.php    # 依存関係・バージョン情報
├── src/
│   └── index.js           # エディタースクリプトのソース
├── next-title-trim-count.php  # プラグイン本体
├── package.json
└── README.md
```

## 変更履歴

### 1.4.0
- `feed-block/feed-item-title`（Feed Item Title）ブロックに対応
- 文字数制限・行数制限ともに Feed ループ内の全アイテムに適用可能
- `<p>` タグ出力（level=0 設定時）にも対応

### 1.3.0
- `core/post-title` の `isLink` 有効時に line-clamp が効かない問題を修正
  - テーマが `<a>` に `display:inline-block` を付与している場合、`<a>` に直接 line-clamp を適用するよう変更
- スタイル注入ロジックを `inject_style` ヘルパー関数に整理

### 1.2.0
- 行数制限機能を追加（CSS `-webkit-line-clamp` / `line-clamp` によるフロントエンド処理）
- インスペクターパネルに「文字数 / 行数」切り替えラジオコントロールを追加
- `nextLineClamp` 属性を追加（`core/heading`・`core/post-title` 共通）

### 1.1.0
- JS 側の文字数カウントを Unicode コードポイント単位に修正（絵文字・サロゲートペア対応）
- PHP 側の `esc_html()` 二重エスケープを修正

### 1.0.0
- 初回リリース

## ライセンス

GPL-2.0-or-later
