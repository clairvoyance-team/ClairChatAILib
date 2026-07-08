# ClairChatAILib エージェント向けガイド

このファイルは、このリポジトリで作業するコーディングエージェント向けのクイックガイドです。

## 1) プロジェクト概要

- パッケージ: `clairvoyance/chat-ai-lib`
- 言語: PHP (`^8.2`)
- 種別: ライブラリ
- メイン名前空間: `Clair\Ai\`
- 主な対象: LLM（OpenAI / Gemini / local）向けチャット制御、プロンプト構築、ツール呼び出し、チャット履歴管理

## 2) 重要なパス

- ソースコード: `src/ChatAi/`
- テストコード: `tests/`
- テストブートストラップ: `tests/bootstrap.php`
- PHPUnit 設定: `phpunit.xml`
- 依存関係/設定ファイル: `composer.json`

機能別の主なソースディレクトリ:

- `src/ChatAi/LLM/`
- `src/ChatAi/Message/`
- `src/ChatAi/Prompt/`
- `src/ChatAi/Tool/`
- `src/ChatAi/ChatHistory/`
- `src/ChatAi/Exception/`

## 3) ローカルセットアップ

依存関係をインストール:

```bash
composer install
```

API 関連のテストを実行する場合は、`.env.testing` を作成し、以下のようなキーを設定してください:

- `OPENAI_API_KEY`
- `GEMINI_API_KEY`

`.env.testing` は `tests/bootstrap.php` で自動読み込みされます。

## 4) テストコマンド

すべてのテストを実行:

```bash
./vendor/bin/phpunit --testdox tests
```

API/local 系テストのみ実行:

```bash
./vendor/bin/phpunit --group local-only
```

API/local 系テストを除外して実行:

```bash
./vendor/bin/phpunit --testdox tests --exclude-group local-only
```

## 5) コーディング規約

- 既存の名前空間構成と PSR-4 マッピングを維持すること。
- 現行スタイル（型付きプロパティ/引数/戻り値、必要に応じた `readonly`）に合わせること。
- 新しい抽象化を導入する前に、既存パターンに沿うこと。
- 明示的な指示がない限り、後方互換性を維持すること。
- 必要性と妥当性がない限り、外部依存を追加しないこと。

## 6) テスト規約

- コード変更と同時にテストを追加または更新すること。
- テストは `tests/` 配下の対応する機能ディレクトリに配置すること。
- リポジトリで使われている PHPUnit 属性スタイル（`#[TestDox]`, `#[Group('local-only')]`）を使用すること。
- 外部 API にアクセスするテストには `local-only` を付与すること。

## 7) 変更チェックリスト

作業完了前に、以下を確認してください:

1. 変更に最も関係する PHPUnit テストを実行する。
2. 関連のないファイル変更が混ざっていないことを確認する。
3. 公開 API の変更が意図的であり、テストで担保されていることを確認する。
4. 振る舞い変更があれば、ドキュメント/サンプルとの整合性を保つ。

## 8) エージェント作業方針

- 最小限で焦点の合った差分を優先する。
- 指示がない限り、大規模なリファクタリングは避ける。
- 要件が不明確な場合は、簡潔な確認質問を 1 つだけ行う。


