# 社内研修 AWS Web site Handson

2026/02/07

## 前提条件

- 東京リージョン（ap-northeast-1）で実施する。（言語「日本語」）
- リソース、ログ等のKMS暗号化は行わない。
- ハンズオンで作成した AWS リソースは通常の料金が発生します。作成したリソースの削除を忘れずにお願いします。もし忘れてしまうと、想定外の料金が発生する可能性があります。

## 事前準備

アカウント作成後、最初にやっておきたいこと

- [料金設定](#料金設定)
- [IAMユーザ作成](#iamユーザ作成)

### 料金設定

コストを把握して、予算をオーバーしないように管理する。  
異常な請求が発生した場合に、気づけるようにする。

#### 料金設定 手順（予算管理アラート設定）

1. 「[請求とコスト管理コンソール](https://us-east-1.console.aws.amazon.com/costmanagement/home#/home)」 に移動
1. 左のサイドバーの [予算] をクリックし、 [予算を作成] をクリック
1. 以下を設定し、 [予算を作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 予算の設定 | テンプレートを使用（シンプル） | 研修用リソースを作成するため 予算の設定＝ゼロ支出予算は不可 |
    | テンプレート | 月次コスト予算 | - |
    | 予算名 | My Monthly Cost Budget | - |
    | 予算額 | 10 | 許容できる額を設定（ドル換算） |
    | Eメールの受信者 | <自身のメールアドレス> | - |

#### 料金設定 手順（IAMユーザ、ロールによる請求情報へのアクセス設定）

1. 右上のユーザ名をクリックし「アカウント」をクリック
1. 「IAM ユーザーおよびロールによる請求情報へのアクセス」の「編集」をクリック
1. 「IAM アクセスをアクティブ化」をチェックし「更新」をクリック

### IAMユーザ作成

以下の理由で日常の操作はルートユーザでなく別のIAMユーザを作成し使用する。 [IAM UserGuide](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_root-user.html)

- セキュリティリスクが高い
- 誤操作による影響が大きい
- 誰が何をしたか監査・トラッキングが困難
- ベストプラクティスに反する

#### IAMユーザ作成 手順

1. 「[IAMコンソール](https://us-east-1.console.aws.amazon.com/iam/home#/home)」 に移動
1. 左のサイドバーの [ユーザー] をクリックし、 [ユーザーの作成] をクリック
1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ユーザー名 | aws_training_user | - |
    | AWS マネジメントコンソールへのユーザーアクセスを提供する | チェックする | - |
    | コンソールパスワード | 自動生成されたパスワード | - |
    | ユーザーは次回のサインイン時に新しいパスワードを作成する必要があります | チェックしない | 研修のため再設定なしで操作できるようにしておく |

1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 許可のオプション | ポリシーを直接アタッチする | - |
    | 許可ポリシー | AdministratorAccess | - |

1. 入力内容を確認して、[ユーザーの作成] をクリック

> [!IMPORTANT]  
> 以降の操作は、ここで作成したIAMユーザで行うこと

## 概要（実施内容）

AWS マネジメントコンソールを使用してWebシステムの構築を行う。  
併せて運用・監視に利用するログの設定も行う。

### システム構成図

![whole_system.drawio](docs/whole_system.drawio.svg)

### 作成するもの

- [Amazon Virtual Private Cloud（VPC）](#amazon-virtual-private-cloudvpc)
- [Amazon Elastic Compute Cloud（EC2）](#amazon-elastic-compute-cloudec2)
- [Amazon Simple Storage Service（S3）](#amazon-simple-storage-services3)
- [Amazon Relational Database Service（RDS）](#amazon-relational-database-servicerds)
- [Elastic Load Balancing（ELB）+ AWS WAF（WAF）](#elastic-load-balancingelb-aws-wafwaf)
- [Amazon CloudFront（CloudFront）](#cloudfront)
- [動作確認用Webページ作成](#動作確認用webページ作成)
- [Amazon EC2 Auto Scaling](#amazon-ec2-auto-scaling)
- 後片づけ

> [!NOTE]  
> ドメインをRoute53で管理し、CloudFrontのディストリビューションのドメインに適用するのが正規の運用となるが、研修時間の都合上割愛する。

## Amazon Virtual Private Cloud（VPC）

システムに必要なネットワーク（VPC）を作成する。  
VPCの動作確認、トラブルシューティングのためのログ出力設定を行う。（VPC Flow Logs）

### VPC作成 手順

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [お使いのVPC] をクリックし、 [VPCを作成] をクリック
1. 以下を設定し、 [VPCを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 作成するリソース | VPCなど | - |
    | 名前タグの自動生成 | - [自動生成]をチェック</br>- aws-training | - |
    | IPv4 CIDR ブロック | 10.0.0.0/16 | - |
    | IPv6 CIDR ブロック | IPv6 CIDR ブロックなし | - |
    | テナンシー | デフォルト | - |
    | アベイラビリティゾーン (AZ) の数 | 2 | - |
    | AZのカスタマイズ | 1a, 1c | 別AZが指定されていることを確認 |
    | パブリックサブネットの数 | 0 | - |
    | プライベートサブネットの数 | 4 | 2AZ × 2Private Subnet |
    | NAT ゲートウェイ | リージョナル | [Regional NAT Gateway](https://docs.aws.amazon.com/vpc/latest/userguide/nat-gateways-regional.html) |
    | VPC エンドポイント | S3ゲートウエイ | - |
    | DNS オプション | - [DNS ホスト名を有効化]をチェック</br>- [DNS 解決を有効化]をチェック | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

### VPC Flow Logs設定

1. 「[CloudWatchコンソール](https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home)」 に移動
1. 左のサイドバーの [ロググループ] をクリックし、 [ロググループを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロググループ名 | /aws/vpc/aws-training-vpc-flow-logs | - |
    | 保持期間の設定 | 1週間（７日） | - |
    | ログクラス | スタンダード | - |
    | KMS キー ARN | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 「[IAMコンソール](https://us-east-1.console.aws.amazon.com/iam/home)」 に移動
1. 左のサイドバーの [ポリシー] をクリックし、 [ポリシーの作成] をクリック
1. ポリシーエディタを[JSON]にして以下を設定し、[次へ]をクリック

    ```json
    {
      "Version": "2012-10-17",
      "Statement": [{
        "Action": [
          "logs:CreateLogGroup",
          "logs:CreateLogStream",
          "logs:PutLogEvents",
          "logs:DescribeLogGroups",
          "logs:DescribeLogStreams"
        ],
        "Effect": "Allow",
        "Resource": "arn:aws:logs:ap-northeast-1:<AWSアカウントID>:log-group:/aws/vpc/aws-training-vpc-flow-logs:*"
      }]
    }
    ```

1. 以下を設定し、 [ポリシーの作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ポリシー名 | aws-training-vpc-flow-logs-policy | - |
    | 説明 | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 左のサイドバーの [ロール] をクリックし、 [ロールを作成] をクリック
1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 信頼されたエンティティタイプ | カスタム信頼ポリシー | - |

    カスタム信頼ポリシー

    ```json
    {
      "Version":"2012-10-17",
      "Statement": [{
        "Effect": "Allow",
        "Principal": {
          "Service": "vpc-flow-logs.amazonaws.com"
        },
        "Action": "sts:AssumeRole"
      }]
    }
    ```

1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 許可ポリシー | aws-training-vpc-flow-logs-policy | - |

1. 以下を設定し、 [ロールを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロール名 | aws-training-vpc-flow-logs-role | - |
    | 説明 | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [お使いのVPC] をクリックし、一覧から作成したVPCを選択
1. [フローログ]タブをクリックし、[フローログの作成]をクリック
1. 以下を設定し、 [フローログの作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 名前 | aws-training-vpc-flow-logs | - |
    | フィルター | すべて | - |
    | 最大集計間隔 | 10分 | - |
    | 送信先 | CloudWatch Logs に送信 | - |
    | 送信先ロググループ | /aws/vpc/aws-training-vpc-flow-logs | - |
    | サービスアクセス | 既存のサービスロールを使用 | - |
    | サービスロール | aws-training-vpc-flow-logs-role | - |
    | ログレコードの形式 | AWS のデフォルト形式 | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

## Amazon Elastic Compute Cloud（EC2）

VPCのPrivate Subnetに仮想サーバ（EC2）を作成する。  
SessionManagerからPrivate SubnetのEC2にアクセスできるよう設定する。

### EC2作成 手順

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [セキュリティグループを作成] をクリック
1. 以下を設定し、 [セキュリティグループを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | セキュリティグループ名 | aws-training-sg-ec2 | - |
    | 説明 | AWS Training VPC SecurityGroup For EC2 | - |
    | VPC | aws-training-vpc | - |
    | タグ(1) | - キー：Project</br>- 値：aws-training | - |
    | タグ(2) | - キー：Name</br>- 値：aws-training-sg-ec2 | - |

1. 「[EC2コンソール](https://ap-northeast-1.console.aws.amazon.com/ec2/home)」 に移動
1. 左のサイドバーの [インスタンス] をクリックし、 [インスタンスを起動] をクリック
1. 以下を設定し、 [インスタンスを起動] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 名前 | aws-training-ec2 | - |
    | Amazon マシンイメージ (AMI) | Amazon Linux 2023 | 最新を指定 |
    | アーキテクチャ | 64ビット（Arm） | - |
    | インスタンスタイプ | t4g.small | [無料トライアル](https://aws.amazon.com/jp/ec2/faqs/?nc1=h_ls#t4g-instances) |
    | キーペア名 | キーペアなしで続行 | SessionManagerを使用して接続するため不要 |
    | VPC | aws-training-vpc | - |
    | サブネット | aws-training-subnet-private1-ap-northeast-1a | private1を指定 |
    | パブリック IP の自動割り当て | 無効化 | - |
    | ファイアウォール (セキュリティグループ) | 既存のセキュリティグループを選択する | - |
    | 共通のセキュリティグループ | aws-training-sg-ec2 | - |
    | ストレージを設定 | 8 GiB gp3 | デフォルトのまま |

### SessionManagerからのEC2へのアクセス設定

1. 「[CloudWatchコンソール](https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home)」 に移動
1. 左のサイドバーの [ロググループ] をクリックし、 [ロググループを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロググループ名 | /aws/ssm/aws-training-session-manager-logs | - |
    | 保持期間の設定 | 1週間（７日） | - |
    | ログクラス | スタンダード | - |
    | KMS キー ARN | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 「[Systems Managerコンソール](https://ap-northeast-1.console.aws.amazon.com/systems-manager/home)」 に移動
1. 左のサイドバーの [セッションマネージャー] をクリックし、 [設定を行う] をクリック
1. 以下を設定し、 [保存] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | アイドルセッションタイムアウト | 20 | - |
    | 最大セッション時間 | チェックしない | - |
    | KMS暗号化 | チェックしない | - |
    | セッションのオペレーティングシステムユーザーを指定する | チェックしない | - |
    | CloudWatchログ記録 | チェックする | - |
    | ご希望のログ記録オプションを選択 | セッションログをストリーミング | - |
    | 暗号化を強制 | チェックしない | - |
    | Find Log Groups | /aws/ssm/aws-training-session-manager-logs | - |

1. 「[IAMコンソール](https://us-east-1.console.aws.amazon.com/iam/home)」 に移動
1. 左のサイドバーの [ポリシー] をクリックし、 [ポリシーの作成] をクリック
1. ポリシーエディタを[JSON]にして以下を設定し、[次へ]をクリック

    ```json
    {
        "Version": "2012-10-17",
        "Statement": [
            {
                "Sid": "VisualEditor0",
                "Effect": "Allow",
                "Action": "logs:DescribeLogStreams",
                "Resource": "arn:aws:logs:ap-northeast-1:<AWSアカウントID>:log-group:/aws/ssm/aws-training-session-manager-logs"
            },
            {
                "Sid": "VisualEditor1",
                "Effect": "Allow",
                "Action": [
                    "logs:CreateLogStream",
                    "logs:PutLogEvents"
                ],
                "Resource": "arn:aws:logs:ap-northeast-1:<AWSアカウントID>:log-group:/aws/ssm/aws-training-session-manager-logs:log-stream:*"
            },
            {
                "Sid": "VisualEditor2",
                "Effect": "Allow",
                "Action": "logs:DescribeLogGroups",
                "Resource": "*"
            }
        ]
    }
    ```

1. 以下を設定し、 [ポリシーの作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ポリシー名 | aws-training-session-manager-logs-policy | - |
    | 説明 | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 左のサイドバーの [ロール] をクリックし、 [ロールを作成] をクリック
1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 信頼されたエンティティタイプ | AWSのサービス | - |
    | サービスまたはユースケース | EC2 | - |

1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 許可ポリシー | - aws-training-session-manager-logs-policy</br>- AmazonSSMManagedInstanceCore | - |

1. 以下を設定し、 [ロールを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロール名 | aws-training-ec2-role | - |
    | 説明 | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 「[EC2コンソール](https://ap-northeast-1.console.aws.amazon.com/ec2/home)」 に移動
1. 左のサイドバーの [インスタンス] をクリック
1. 作成したインスタンスを選択し、[アクション]→[セキュリティ]→[IAMロールを変更]をクリック
1. 以下を設定し、 [IAMロールを更新] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | IAMロール | aws-training-ec2-role | - |

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [セキュリティグループを作成] をクリック
1. 以下を設定し、 [セキュリティグループを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | セキュリティグループ名 | aws-training-sg-endpoint | - |
    | 説明 | AWS Training VPC SecurityGroup For Endpoint | - |
    | VPC | aws-training-vpc | - |
    | タグ(1) | - キー：Project</br>- 値：aws-training | - |
    | タグ(2) | - キー：Name</br>- 値：aws-training-sg-endpoint | - |

    *インバウントルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTPS | TCP | 443 | 10.0.0.0/16 | - |

    *アウトバウンドルール*

    設定なし

1. 左のサイドバーの [エンドポイント] をクリックし、 [エンドポイントを作成] をクリック
1. 以下を設定し、 [エンドポイントを作成] をクリック（*４個エンドポイントを作成する*）

    | 項目名 | 設定値（１） | 設定値（２） | 設定値（３） | 設定値（４） | 備考 |
    | ---- | ---- | ---- | ---- | ---- | ---- |
    | 名前タグ | aws-training-endpoint-ssm | aws-training-endpoint-ssmmessages | aws-training-endpoint-ec2messages | aws-training-endpoint-logs | - |
    | タイプ | AWSのサービス | <- | <- | <- | - |
    | サービス | com.amazonaws.ap-northeast-1.ssm | com.amazonaws.ap-northeast-1.ssmmessages | com.amazonaws.ap-northeast-1.ec2messages | com.amazonaws.ap-northeast-1.logs | - |
    | VPC | aws-training-vpc | <- | <- | <- | - |
    | DNS名 | チェックする | <- | <- | <- | - |
    | DNS レコードの IP タイプ | IPv4 | <- | <- | <- | - |
    | サブネット | 2AZを選択しprivate1,2を指定。IPアドレスタイプはIPv4を指定。 | <- | <- | <- | - |
    | セキュリティグループ | aws-training-sg-endpoint | <- | <- | <- | - |
    | ポリシー | フルアクセス | <- | <- | <- | - |
    | タグ | - キー：Project</br>- 値：aws-training | <- | <- | <- | - |

1. 左のサイドバーの [セキュリティグループ] をクリックし、 [aws-training-sg-ec2] を選択
1. 以下を設定

    *インバウントルール*

    設定なし

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTPS | TCP | 443 | aws-training-sg-endpoint | - |

> [!NOTE]  
> インスタンスが表示されるまでには数分かかります

### EC2への接続確認

1. 「[Systems Managerコンソール](https://ap-northeast-1.console.aws.amazon.com/systems-manager/home)」 に移動
1. 左のサイドバーの [セッションマネージャー] をクリックし、 [セッションの開始] をクリック
1. 一覧から作成したEC2を選択し、[セッションを開始]をクリック
1. 以下コマンドを実行し、実行ログが出力されていることを確認

    ```bash
    > bash
    > cd
    > nslookup logs.ap-northeast-1.amazonaws.com
    ```

## Amazon Simple Storage Service（S3）

オブジェクトを保存するS3を作成する。  
（EC2からオブジェクトにアクセスを行う）

### S3作成 手順

1. 「[S3コンソール](https://ap-northeast-1.console.aws.amazon.com/s3/buckets)」 に移動
1. 左のサイドバーの [汎用バケット] をクリックし、 [バケットを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | バケットタイプ | 汎用 | - |
    | バケット名 | aws-training-s3-XXXXX | 一意になるよう設定 |
    | オブジェクト所有者 | ACL無効 | - |
    | パブリックアクセスをすべて ブロック | チェックする | - |
    | バケットのバージョニング | 有効にする | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |
    | 暗号化タイプ | SSE-S3 | - |
    | バケットキー | 有効にする | - |

### S3 「サーバーアクセスのログ記録」の有効化

1. 左のサイドバーの [汎用バケット] をクリックし、 [バケットを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | バケットタイプ | 汎用 | - |
    | バケット名 | aws-training-s3-access-log-XXXXX | 一意になるよう設定 |
    | オブジェクト所有者 | ACL無効 | - |
    | パブリックアクセスをすべて ブロック | チェックする | - |
    | バケットのバージョニング | 有効にする | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |
    | 暗号化タイプ | SSE-S3 | - |
    | バケットキー | 有効にする | - |

1. バケット一覧から[S3作成]で作成したバケットをクリック
1. [プロパティ]タブで、[サーバーアクセスのログ記録]の[編集]をクリック
1. 以下を設定し、 [変更の保存]をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | サーバーアクセスのログ記録 | 有効にする | - |
    | 送信先 | s3://aws-training-s3-access-log-xxxxx | - |
    | ログオブジェクトキーの形式 | [DestinationPrefix][SourceAccountId]/​[SourceRegion]/​[SourceBucket]/​[YYYY]/​[MM]/​[DD]/​[YYYY]-[MM]-[DD]-[hh]-[mm]-[ss]-[UniqueString] | - |
    | ログオブジェクトキーの形式で使用される日付のソース | S3 イベントの時刻 | - |

> [!NOTE]  
> コンソールから「サーバーアクセスのログ記録」の有効化を行った場合、S3サーバーアクセスログ用バケットのバケットポリシーが自動設定される。  
> 必要に応じて[ライフサイクル設定]を行う。

### S3アクセス設定 手順

1. 「[IAMコンソール](https://us-east-1.console.aws.amazon.com/iam/home)」 に移動
1. 左のサイドバーの [ポリシー] をクリックし、 [ポリシーの作成] をクリック
1. ポリシーエディタを[JSON]にして以下を設定し、[次へ]をクリック（XXXXXは、「S3作成 手順」で設定した値を設定）

    ```json
    {
        "Version": "2012-10-17",
        "Statement": [
            {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::aws-training-s3-XXXXX",
                "arn:aws:s3:::aws-training-s3-XXXXX/*"
            ]
            }
        ]
    }
    ```

1. 以下を設定し、 [ポリシーの作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ポリシー名 | aws-training-s3-access-policy | - |
    | 説明 | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 左のサイドバーの [ロール] をクリックし、 一覧から[aws-training-ec2-role] をクリック
1. [許可]タブの[許可を追加]→[ポリシーをアタッチ]をクリック
1. 以下を設定し、 [許可を追加] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 許可ポリシー | - aws-training-s3-access-policy | - |

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [aws-training-sg-ec2] を選択
1. 以下を設定（[com.amazonaws.ap-northeast-1.s3]の設定を追加）

    *インバウントルール*

    設定なし

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTPS | TCP | 443 | aws-training-sg-endpoint | - |
    | HTTPS | TCP | 443 | com.amazonaws.ap-northeast-1.s3 | - |

### S3への接続確認

> [!NOTE]  
> 「[S3コンソール](https://ap-northeast-1.console.aws.amazon.com/s3/buckets)」から、
> [aws-training-s3-XXXXX]バケットにこのリポジトリのS3フォルダのファイルをアップロードしておく

1. 「[Systems Managerコンソール](https://ap-northeast-1.console.aws.amazon.com/systems-manager/home)」 に移動
1. 左のサイドバーの [セッションマネージャー] をクリックし、 [セッションの開始] をクリック
1. 一覧から作成したEC2を選択し、[セッションを開始]をクリック
1. 以下コマンドを実行し、実行ログが出力されていることを確認

    ```bash
    > bash
    > cd
    > aws s3api list-objects-v2 --bucket aws-training-s3-XXXXX
    > aws s3api get-object --bucket aws-training-s3-XXXXX --key s3_access_test.txt ./s3_access_test.txt
    ```

## Amazon Relational Database Service（RDS）

データベース（RDS）を作成する。  
（EC2からRDSにアクセスを行う）

### RDS作成 手順

#### セキュリティグループを作成

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [セキュリティグループを作成] をクリック
1. 以下を設定し、 [セキュリティグループを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | セキュリティグループ名 | aws-training-sg-rds-mysql | - |
    | 説明 | AWS Training VPC SecurityGroup For RDS(MySQL) | - |
    | VPC | aws-training-vpc | - |
    | タグ(1) | - キー：Project</br>- 値：aws-training | - |
    | タグ(2) | - キー：Name</br>- 値：aws-training-sg-rds-mysql | - |

    *インバウントルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | MYSQL | TCP | 3306 | 10.0.0.0/16 | - |

    *アウトバウンドルール*

    設定なし

#### サブネットグループを作成

1. 「[Aurora and RDSコンソール](https://ap-northeast-1.console.aws.amazon.com/rds/home)」 に移動
1. 左のサイドバーの [サブネットグループ] をクリックし、 [DB サブネットグループを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 名前 | aws-training-db-subnet-group | - |
    | 説明 | AWS Training RDS(MySQL) Subnet Group | - |
    | VPC | aws-training-vpc | - |
    | アベイラビリティーゾーン | ap-northeast-1a, ap-northeast-1c | VPCで有効になっているAZ |
    | サブネット | aws-training-subnet-private3-ap-northeast-1a, aws-training-subnet-private4-ap-northeast-1c | private subnet3, 4を指定 |

#### パラメータグループを作成

1. 左のサイドバーの [パラメータグループ] をクリックし、 [パラメータグループの作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | パラメータグループ名 | aws-training-db-parameter-group | - |
    | 説明 | AWS Training RDS(MySQL) Parameter Group | - |
    | エンジンタイプ | MySQL Community | - |
    | パラメータグループファミリー | mysql8.4 | - |
    | タイプ | DB Parameter Group | - |

#### データベースを作成

1. 左のサイドバーの [データベース] をクリックし、 [データベースの作成] をクリック
1. 以下を設定し、 [データベースの作成] をクリック

    *データベース作成方法を選択*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | データベース作成方法を選択 | 標準作成 | - |

    *エンジンのオプション*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | エンジンタイプ | MySQL | - |
    | エンジンバージョン | MySQL 8.4.7 | - |

    *テンプレート*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | テンプレート | 開発/テスト | - |

    *可用性と耐久性*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 可用性と耐久性 | マルチ AZ DB インスタンスデプロイ (2 インスタンス) | - |

    *設定*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | DB インスタンス識別子 | aws-training | - |
    | マスターユーザー名 | admin | - |
    | 認証情報管理 | AWS Secrets Manager | - |
    | 暗号化キーを選択 | aws/secretsmanager | - |

    *インスタンスの設定*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | インスタンスの設定 | - バースト可能クラス (t クラスを含む)</br>- db.t4g.micro | - |

    *ストレージ*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ストレージタイプ | 汎用SSD(gp3) | - |
    | ストレージ割り当て | 20 | - |

    *接続*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | コンピューティングリソース | EC2 コンピューティングリソースに接続しない | - |
    | ネットワークタイプ | IPv4 | - |
    | Virtual Private Cloud | aws-training-vpc | - |
    | DB サブネットグループ | aws-training-db-subnet-group | - |
    | パブリックアクセス | なし | - |
    | VPC セキュリティグループ (ファイアウォール) | 既存の選択 | - |
    | 既存の VPC セキュリティグループ | aws-training-sg-rds-mysql | - |
    | 認証機関 | rds-ca-rsa2048-g1 | - |

    *タグ*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | タグ | - キー：Project</br>- 値：aws-training | - |

    *データベース認証*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | データベース認証 | パスワード認証 | - |

    *モニタリング*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | モニタリング | データベースインサイト - スタンダード | - |
    | 拡張モニタリングの有効化 | チェックする | - |
    | OS メトリクスの詳細度 | 60 秒 | - |
    | OS メトリクスのモニタリングの役割 | デフォルト | - |
    | ログのエクスポート | 全てチェックする | - |

    *追加設定*

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 最初のデータベース名 | aws_training_db | - |
    | DB パラメータグループ | aws-training-db-parameter-group | - |
    | オプショングループ | default:mysql-8-4 | - |

### EC2からRDSへの接続設定

1. 「[IAMコンソール](https://us-east-1.console.aws.amazon.com/iam/home)」 に移動
1. 左のサイドバーの [ロール] をクリックし、 一覧から[aws-training-ec2-role] をクリック
1. [許可]タブの[許可を追加]→[ポリシーをアタッチ]をクリック
1. 以下を設定し、 [許可を追加] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 許可ポリシー | - AWSSecretsManagerClientReadOnlyAccess | - |

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [エンドポイント] をクリックし、 [エンドポイントを作成] をクリック
1. 以下を設定し、 [エンドポイントを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 名前タグ | aws-training-endpoint-secretsmanager | - |
    | タイプ | AWSのサービス | - |
    | サービス | com.amazonaws.ap-northeast-1.secretsmanager | - |
    | VPC | aws-training-vpc | - |
    | DNS名 | チェックする | - |
    | DNS レコードの IP タイプ | IPv4 | - |
    | サブネット | 2AZを選択しprivate1,2を指定。IPアドレスタイプはIPv4を指定。 | - |
    | セキュリティグループ | aws-training-sg-endpoint | - |
    | ポリシー | フルアクセス | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 左のサイドバーの [セキュリティグループ] をクリックし、 [aws-training-sg-ec2] を選択
1. 以下を設定（[aws-training-sg-rds-mysql]の設定を追加）

    *インバウントルール*

    設定なし

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTPS | TCP | 443 | aws-training-sg-endpoint | - |
    | HTTPS | TCP | 443 | com.amazonaws.ap-northeast-1.s3 | - |
    | MySQL | TCP | 3306 | aws-training-sg-rds-mysql | - |

### EC2からRDSへの接続確認

1. 「[Systems Managerコンソール](https://ap-northeast-1.console.aws.amazon.com/systems-manager/home)」 に移動
1. 左のサイドバーの [セッションマネージャー] をクリックし、 [セッションの開始] をクリック
1. 一覧から作成したEC2を選択し、[セッションを開始]をクリック
1. 以下コマンドを実行し、実行ログが出力されていることを確認

    ```bash
    > bash
    > cd
    > sudo dnf install -y mariadb105
    > secret=$(aws secretsmanager get-secret-value --secret-id '<マスター認証情報 ARN>' --query 'SecretString' --output text)
    > USER=$(echo $secret | jq -r .username)
    > PASS=$(echo $secret | jq -r .password)
    > HOST="<エンドポイント>"
    > mysql -h "$HOST" -u "$USER" -p"$PASS" -D aws_training_db
    > #このリポジトリのRDSフォルダのsql.txtのSQLを変更後、実行
    ```

> [!NOTE]  
> マスター認証情報 ARN＝データベースの[設定]タブの[マスター認証情報 ARN]を設定  
> エンドポイント＝データベースの[接続とセキュリティ]タブの[エンドポイント]を設定

## Elastic Load Balancing（ELB）+ AWS WAF（WAF）

EC2の前段にELB（Application Load Balancer）を配置して負荷分散できるようにする。  
ウェブアプリケーションへの攻撃を防ぐためWAFを配置する。

### ELB（Application Load Balancer）, WAF作成 手順

#### ELB（Application Load Balancer）用セキュリティグループを作成

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [セキュリティグループを作成] をクリック
1. 以下を設定し、 [セキュリティグループを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | セキュリティグループ名 | aws-training-sg-alb | - |
    | 説明 | AWS Training VPC SecurityGroup For ALB | - |
    | VPC | aws-training-vpc | - |
    | タグ(1) | - キー：Project</br>- 値：aws-training | - |
    | タグ(2) | - キー：Name</br>- 値：aws-training-sg-alb | - |

    *インバウントルール*

    設定なし

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | 送信先 | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTP | TCP | 80 | aws-training-sg-ec2 | - |

#### ELB（Application Load Balancer）からの通信許可をEC2セキュリティグループに設定

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [aws-training-sg-ec2] を選択
1. 以下を設定（[インバウンドルール]の設定を追加）

    *インバウントルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTP | TCP | 80 | aws-training-sg-alb | - |

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTPS | TCP | 443 | aws-training-sg-endpoint | - |
    | HTTPS | TCP | 443 | com.amazonaws.ap-northeast-1.s3 | - |
    | MySQL | TCP | 3306 | aws-training-sg-rds-mysql | - |

#### ELB（Application Load Balancer）用ターゲットグループを作成

1. 「[EC2コンソール](https://ap-northeast-1.console.aws.amazon.com/ec2/home)」 に移動
1. 左のサイドバーの [ターゲットグループ] をクリックし、 [ターゲットグループの作成] をクリック
1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ターゲットの種類 | インスタンス | - |
    | ターゲットグループ名 | aws-training-alb-target-group | - |
    | プロトコル | HTTP | - |
    | ポート | 80 | - |
    | IPアドレスタイプ | IPv4 | - |
    | VPC | aws-training-vpc | - |
    | プロトコルバージョン | HTTP1 | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 起動しているEC2をターゲットに登録し、 [次へ] をクリック
1. [ターゲットグループの作成] をクリック

#### ELB（Application Load Balancer）, WAFを作成

1. 左のサイドバーの [ロードバランサー] をクリックし、 [ロードバランサーの作成] をクリック
1. [Application Load Balancer]の[作成]をクリック
1. 以下を設定し、 [ロードバランサーの作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロードバランサー名 | aws-training-alb | - |
    | スキーム | 内部 | - |
    | ロードバランサーのIPアドレスタイプ | IPv4 | - |
    | VPC | aws-training-vpc | - |
    | アベイラビリティーゾーンとサブネット | 有効にしたAZのプライベートサブネットを指定 | - Private1</br>- Private2 |
    | セキュリティグループ | aws-training-sg-alb | - |
    | プロトコル | HTTP | - |
    | ポート | 80 | - |
    | アクションのルーティング | ターゲットグループへ転送 | - |
    | ターゲットグループ | aws-training-alb-target-group | - |
    | リスナータグ | - キー：Project</br>- 値：aws-training | - |
    | ロードバランサータグ | - キー：project</br>- 値：aws-training | - |
    | アプリケーション層のセキュリティ保護 | - チェックする</br>- 事前定義済みのWAFを自動作成 | - |
    | ルールアクション | ブロック | - |
    | リソース名 | - [カスタム名]をチェックする</br>- aws-training-alb-waf | - |

#### ELB（Application Load Balancer） ログ出力設定

1. 「[S3コンソール](https://ap-northeast-1.console.aws.amazon.com/s3/buckets)」 に移動
1. 左のサイドバーの [汎用バケット] をクリックし、 [バケットを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | バケットタイプ | 汎用 | - |
    | バケット名 | aws-training-alb-access-log-XXXXX | 一意になるよう設定 |
    | オブジェクト所有者 | ACL無効 | - |
    | パブリックアクセスをすべて ブロック | チェックする | - |
    | バケットのバージョニング | 無効にする | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |
    | 暗号化タイプ | SSE-S3 | - |
    | バケットキー | 有効にする | - |

1. 作成したバケットを選択し[アクセス許可]タブのバケットポリシー[編集]をクリック
1. 以下を参考にポリシーを編集し、[変更の保存]をクリック

    ```json
    {
      "Version": "2012-10-17",
      "Statement": [{
        "Effect": "Allow",
        "Principal": {
          "Service": "logdelivery.elasticloadbalancing.amazonaws.com"
        },
        "Action": "s3:PutObject",
        "Resource": "arn:aws:s3:::<バケット名>/<ALB名>/AWSLogs/<AWSアカウントID>/*"
      }]
    }
    ```

1. 「[EC2コンソール](https://ap-northeast-1.console.aws.amazon.com/ec2/home)」 に移動
1. 左のサイドバーの [ロードバランサー] をクリックし、 作成したロードバランサを選択
1. [属性]タブで[編集]をクリック
1. 以下を設定し、 [変更内容の保存] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | アクセスログ | チェックする | - |
    | S3 URI | s3://aws-training-alb-access-log-xxxxx/aws-training-alb | - |

#### WAF ログ出力設定

1. 「[CloudWatchコンソール](https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home)」 に移動
1. 左のサイドバーの [ロググループ] をクリックし、 [ロググループを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロググループ名 | aws-waf-logs-aws-training | - |
    | 保持期間の設定 | 1週間（７日） | - |
    | ログクラス | スタンダード | - |
    | KMS キー ARN | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 「[WAFコンソール](https://ap-northeast-1.console.aws.amazon.com/wafv2-pro/protections)」 に移動
1. 左のサイドバーの [保護パック(ウェブACL)] をクリックし、 作成されたWAFをクリック
1. [ログ記録とサンプルリクエストを設定]をクリック
1. ログ記録の[有効化]→[ログ記録送信先]をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ログ記録送信先のタイプ | Amazon CloudWatch Logs | - |
    | Amazon CloudWatch Logs ロググループ | aws-waf-logs-aws-training | - |

## CloudFront

世界中に分散したエッジロケーションを利用し、ユーザーに近い場所からコンテンツを配信するため配置する。  
このシステムでは、インターネットからの通信をPrivate Subnetに配置したELB（Application Load Balancer）に連携する役割もある。  
「Free」プランの場合、[Origin type]に[VPC origin]を指定できないため[Pay as you go]で作成する。[参考](https://aws.amazon.com/jp/about-aws/whats-new/2025/11/aws-flat-rate-pricing-plans/)

### CloudFront作成 手順

#### CloudFrontを作成

1. 「[CloudFrontコンソール](https://us-east-1.console.aws.amazon.com/cloudfront/v4/home)」 に移動
1. 左のサイドバーの [VPCオリジン] をクリックし、 [VPCオリジンを作成] をクリック
1. 以下を設定し、 [VPCオリジンを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Name | aws-training-cloudfront-vpc-origin | - |
    | オリジンARN | 作成したALBのARN | - |
    | プロトコル | HTTPのみ | - |
    | HTTP port | 80 | - |

1. 「[CloudFrontコンソール](https://us-east-1.console.aws.amazon.com/cloudfront/v4/home)」 に移動
1. 左のサイドバーの [ディストリビューション] をクリックし、 [CloudFrontディストリビューションを作成] をクリック
1. 以下を設定し、 [Next] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Choose a plan | Pay as you go | - |

1. 以下を設定し、 [Next] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Distribution name | aws-training-cloudfront | - |
    | Distribution type | Single website or app | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

1. 以下を設定し、 [Next] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Origin type | VPCオリジン | - |
    | VPC origin | aws-training-cloudfront-vpc-origin | - |
    | オリジン設定 | Use recommended origin settings | - |
    | Cache settings | Use recommended cache settings tailored to serving Elastic Load Balancing content | - |

1. 以下を設定し、 [Next] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Web Application Firewall | セキュリティ保護を有効にしないでください | ALB作成時に作成済のため |

1. [Create distribution] をクリック

#### ELB（Application Load Balancer）からの通信許可をELB（Application Load Balancer）セキュリティグループに設定

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [aws-training-sg-alb] を選択
1. 以下を設定（[インバウンドルール]の設定を追加）

    *インバウントルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTP | TCP | 80 | CloudFront-VPCOrigins-Service-SG | - |

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | 送信先 | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTP | TCP | 80 | aws-training-sg-ec2 | - |

#### CloudFront ログ出力設定

1. 「[CloudWatchコンソール](https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home)」 に移動
1. 左のサイドバーの [ロググループ] をクリックし、 [ロググループを作成] をクリック
1. 以下を設定し、 [作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロググループ名 | aws-cloudfront-logs-aws-training | - |
    | 保持期間の設定 | 1週間（７日） | - |
    | ログクラス | スタンダード | - |
    | KMS キー ARN | - | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

> [!NOTE]  
> CloudWatch ロググループは、バージニアリージョンで作成する必要がある  

1. 「[CloudFrontコンソール](https://us-east-1.console.aws.amazon.com/cloudfront/v4/home)」 に移動
1. 左のサイドバーの [ディストリビューション] をクリックし、作成したCloudFrontを選択
1. [Logging]タブで[Add]→[Amazon CloudWatch Logs]をクリック
1. 以下を設定し、 [Submit] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Deliver to | Amazon CloudWatch Logs | - |
    | Destination log group | aws-cloudfront-logs-aws-training | - |

## 動作確認用Webページ作成

### EC2からのインターネット接続設定

1. 「[VPCコンソール](https://ap-northeast-1.console.aws.amazon.com/vpcconsole/home)」 に移動
1. 左のサイドバーの [セキュリティグループ] をクリックし、 [aws-training-sg-ec2] を選択
1. 以下を設定（[インバウンドルール]の設定を追加）

    *インバウントルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTP | TCP | 80 | aws-training-sg-alb | - |

    *アウトバウンドルール*

    | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
    | ---- | ---- | ---- | ---- | ---- |
    | HTTPS | TCP | 443 | aws-training-sg-endpoint | - |
    | HTTPS | TCP | 443 | com.amazonaws.ap-northeast-1.s3 | - |
    | MySQL | TCP | 3306 | aws-training-sg-rds-mysql | - |
    | すべてのTCP | TCP | 0 - 65535 | 0.0.0.0/0 | - |

### EC2設定

1. 「[Systems Managerコンソール](https://ap-northeast-1.console.aws.amazon.com/systems-manager/home)」 に移動
1. 左のサイドバーの [セッションマネージャー] をクリックし、 [セッションの開始] をクリック
1. 一覧から作成したEC2を選択し、[セッションを開始]をクリック
1. 以下コマンドを実行

    ```bash
    > bash
    > sudo dnf update -y
    > sudo dnf install -y httpd php8.4 php-mysqlnd php-pdo
    > cd /var/www/html/
    > sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    > sudo php composer-setup.php
    > sudo mv composer.phar /usr/local/bin/composer
    > sudo composer require aws/aws-sdk-php
    > sudo systemctl enable httpd
    > sudo systemctl start httpd
    ```

1. 以下コマンドを実行

    ```bash
    > sudo vi /etc/httpd/conf/httpd.conf
    ```

    viでhttpd.confの以下の箇所に「index.php」追加

    ```bash
    <IfModule dir_module>
        DirectoryIndex index.php index.html
    </IfModule>
    ```

1. 以下コマンドを実行

    ```bash
    > sudo vi index.php
    ```

    > [!NOTE]  
    > このリポジトリのphpフォルダのindex.phpの内容をコピー  
    > index.php内の<RDSのSecrets Manager ARN>、 <RDSのEndpoint> はコンソールから値を確認し設定

1. 以下コマンドを実行

    ```bash
    > sudo systemctl stop httpd
    > sudo systemctl start httpd
    ```

### S3設定（パブリックアクセスをすべて ブロック を解除）

1. 「[S3コンソール](https://ap-northeast-1.console.aws.amazon.com/s3/buckets)」 に移動
1. 左のサイドバーの [汎用バケット] をクリックし、 作成済の[aws-training-s3-XXXXX] をクリック
1. [アクセス許可]タブの[ブロックパブリックアクセス]の[編集]をクリック
1. [パブリックアクセスをすべて ブロック]のチェックをはずし、[変更の保存]をクリック
1. [アクセス許可]タブの[バケットポリシー]の[編集]をクリックし
1. ポリシーに以下を設定し[変更の保存]をっクリック

    ```json
    {
        "Version": "2012-10-17",
        "Statement": [
            {
                "Sid": "PublicReadGetObject",
                "Effect": "Allow",
                "Principal": "*",
                "Action": "s3:GetObject",
                "Resource": "arn:aws:s3:::aws-training-s3-XXXXX/*"
            }
        ]
    }
    ```

> [!NOTE]  
> ブラウザからCloudFrontのディストリビューションのURLにアクセス

## Amazon EC2 Auto Scaling

### ターゲットグループからEC2を登録解除

1. 「[EC2コンソール](https://ap-northeast-1.console.aws.amazon.com/ec2/home)」 に移動
1. 左のサイドバーの [ターゲットグループ] をクリックし、 [aws-training-alb-target-group] をクリック
1. [ターゲット]タブで登録済EC2を選択し、[登録解除]をクリック

### EC2停止

1. 左のサイドバーの [インスタンス] をクリック
1. [aws-training-ec2] をクリックし、 [インスタンスの状態]→[インスタンスを停止] をクリック

### EC2イメージを作成

1. [aws-training-ec2] をクリックし、 [アクション]→[イメージとテンプレート]→[イメージを作成] をクリック
1. 以下を設定し、 [イメージを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | イメージ名 | aws-training-ec2-image | - |
    | タグ | - キー：Project</br>- 値：aws-training | - |

### 起動テンプレートを作成

1. 左のサイドバーの [起動テンプレート] をクリックし、[起動テンプレートを作成]をクリック
1. 以下を設定し、 [起動テンプレートを作成] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 起動テンプレート名 | aws-training-ec2-launch-template | - |
    | Auto Scaling のガイダンス | チェックする | - |
    | テンプレートタグ | - キー：Project</br>- 値：aws-training | - |
    | アプリケーションおよび OS イメージ | aws-training-ec2-image | - |
    | インスタンスタイプ | t4g.small | - |
    | キーペア | 起動テンプレートの設定に含めない | - |
    | サブネット | 起動テンプレートの設定に含めない | - |
    | ファイアウォール (セキュリティグループ) | 既存のセキュリティグループを選択する | - |
    | 共通のセキュリティグループ | aws-training-sg-ec2 | - |
    | リソースタグ | - キー：Project</br>- 値：aws-training | - |
    | IAM インスタンスプロフィール | aws-training-ec2-role | - |

### Auto Scaling Groupを作成

1. 左のサイドバーの [Auto Scaling グループ] をクリックし、[Auto Scaling グループを作成する]をクリック
1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | Auto Scaling グループ名 | aws-training-ec2-auto-scaling-group | - |
    | 起動テンプレート | aws-training-ec2-launch-template | - |

1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | VPC | aws-training-vpc | - |
    | アベイラビリティーゾーンとサブネット | - aws-training-subnet-private1-ap-northeast-1a</br>- aws-training-subnet-private2-ap-northeast-1c | - |
    | アベイラビリティーゾーンのディストリビューション | バランシング（ベストエフォート） | - |

1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | ロードバランシング | 既存のロードバランサーにアタッチする | - |
    | アタッチするロードバランサーを選択 | ロードバランサーのターゲットグループから選択する | - |
    | 既存のロードバランサーターゲットグループ | aws-training-alb-target-group | - |
    | ヘルスチェック | Elastic Load Balancing のヘルスチェックをオンにする | - |

1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | 希望するキャパシティ | 2 | - |
    | 最小の希望する容量 | 2 | - |
    | 最大の希望する容量 | 4 | - |
    | 自動スケーリング | ターゲット追跡スケーリングポリシー | - |
    | ターゲット値 | 25 | - |

1. 何も設定せず、 [次へ] をクリック
1. 以下を設定し、 [次へ] をクリック

    | 項目名 | 設定値 | 備考 |
    | ---- | ---- | ---- |
    | タグ（１） | - キー：Project</br>- 値：aws-training | - |
    | タグ（２） | - キー：Name</br>- 値：aws-training-asg-ec2 | - |

1. [Auto Scaling グループを作成する]をクリック

## 後片づけ

1. CloudFront - ディストリビューション 無効 → 削除
    1. VPC オリジン 削除
1. Auto Scaling グループ 削除
    1. 起動テンプレート 削除
    1. AMI 登録解除
1. EC2 削除
1. RDS 削除
    1. サブネットグループ 削除
    1. パラメータグループ 削除
1. ロードバランサー 削除
    1. ターゲットグループ 削除
    1. WAF 保護パック（ウエブACL） 削除
1. VPC Endpoint 削除
1. Security Group 削除
1. NAT ゲートウエイ 削除
1. VPC 削除
1. S3 バケット 削除
1. CloudWatch Logs Group 削除（バージニアにも存在する）
1. IAM Role 削除
1. IAM Policy 削除
