# 📧 Mailer - Sistema Profissional de Email Marketing

Sistema completo de email marketing desenvolvido em **CodeIgniter 4** com **PHP 8.1+**, integração com **Amazon SES**, validação automática de DNS, sistema de filas e tracking avançado.

🔗 **Repositório**: https://github.com/ariellcannal/Mailer

---

## ✨ Funcionalidades Implementadas

### ✅ Core Libraries
- **AWS SES Service** - Envio completo de emails com suporte a HTML, tags, verificação de domínios e consulta de limites
- **DNS Validator** - Validação automática de SPF, DKIM, DMARC e MX com geração de instruções
- **Queue Manager** - Sistema de filas com throttling inteligente para envio em massa

### ✅ Controllers
- **DashboardController** - Dashboard principal com estatísticas e gráficos
- **TrackController** - Tracking de aberturas (pixel transparente) e cliques (redirecionamento)

### ✅ Models Completos
- ContactModel - Gestão de contatos com classificação de qualidade (1-5)
- CampaignModel - Gestão de campanhas
- MessageModel - Gestão de mensagens
- MessageSendModel - Controle de envios
- MessageOpenModel - Registro de aberturas
- MessageClickModel - Registro de cliques
- SenderModel - Gestão de remetentes
- OptoutModel - Gestão de descadastramentos

### ✅ Interface Responsiva (Bootstrap 5)
- Layout principal com sidebar responsivo
- Dashboard com cards estatísticos e gráficos Chart.js
- Design profissional com gradientes e animações
- Mobile-friendly com menu toggle
- Integração com Font Awesome e Alertify.js

### ✅ Sistema de Tracking
- **Pixel de abertura** - Tracking transparente de aberturas
- **Redirecionamento de cliques** - Tracking de todos os links
- **Opt-out pages** - Páginas de descadastramento responsivas
- **Webview** - Visualização de emails no navegador
- **Atualização automática de métricas** - Contadores em tempo real

### ✅ Sistema de Filas
- Processamento assíncrono de envios
- Throttling configurável (emails/segundo)
- Personalização automática de conteúdo ({{nome}}, {{email}})
- Substituição automática de links por tracking
- Inserção automática de pixel de abertura
- Gestão de links especiais (opt-out, webview)

### ✅ Banco de Dados
- 20 tabelas relacionadas
- Índices otimizados para performance
- Sistema completo de tracking granular
- Suporte a campos personalizados

---

## 📋 Requisitos

- **PHP**: 8.1 ou superior
- **MySQL**: 5.7+ ou MariaDB 10.3+
- **Composer**: 2.0+
- **Extensões PHP**: mbstring, intl, json, mysqlnd, xml, curl, gd, zip, bcmath

---

## 🚀 Instalação Rápida

### 1. Clonar Repositório

```bash
git clone https://github.com/ariellcannal/Mailer.git
cd Mailer
```

### 2. Instalar Dependências

```bash
composer install
```

### 3. Configurar Banco de Dados

```bash
# Criar banco
mysql -u root -p -e "CREATE DATABASE mailer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar schema
mysql -u root -p mailer < database_schema.sql
```

### 4. Configurar Ambiente

Renomeie o arquivo `env` para `.env` e configure:

```ini
# URL Base
app.baseURL = 'http://localhost:8080/'

# Banco de Dados
database.default.hostname = localhost
database.default.database = mailer
database.default.username = root
database.default.password = sua_senha

# Throttling (emails por segundo)
app.throttleRate = 14

# Google OAuth
google.clientId = SEU_CLIENT_ID
google.clientSecret = SEU_CLIENT_SECRET
google.redirectUri = 'http://localhost:8080/auth/google/callback'

# AWS SES
aws.ses.region = us-east-1
aws.ses.accessKey = SUA_ACCESS_KEY
aws.ses.secretKey = SUA_SECRET_KEY
```

Gere a chave de encriptação:

```bash
php spark key:generate
```

### 5. Configurar Permissões

```bash
chmod -R 777 writable/
```

### 6. Iniciar Servidor

**Desenvolvimento:**

```bash
php spark serve
```

Acesse: **http://localhost:8080**

---

## 🔄 Processamento de Filas

Para processar a fila de envios, execute via CLI ou configure um cron job:

```bash
# Processar fila manualmente
php spark queue/process

# Cron job (a cada minuto)
* * * * * cd /caminho/para/Mailer && php spark queue/process >> /dev/null 2>&1
```

---

## 📚 Uso das Funcionalidades

### Enviar Email via AWS SES

```php
use App\Libraries\AWS\SESService;

$ses = new SESService();

$result = $ses->sendEmail(
    from: 'contato@seudominio.com',
    fromName: 'Sua Empresa',
    to: 'destinatario@example.com',
    subject: 'Assunto do Email',
    htmlBody: '<h1>Olá!</h1><p>Conteúdo do email</p>',
    replyTo: 'resposta@seudominio.com'
);

if ($result['success']) {
    echo "Email enviado! ID: " . $result['messageId'];
}
```

### Validar DNS

```php
use App\Libraries\DNS\DNSValidator;

$validator = new DNSValidator();

// Validar tudo
$result = $validator->validateAll('seudominio.com');

// Gerar instruções
$instructions = $validator->generateDNSInstructions('seudominio.com', $dkimTokens);
```

### Adicionar à Fila

```php
use App\Libraries\Email\QueueManager;

$queue = new QueueManager();

$result = $queue->queueMessage(
    messageId: 1,
    contactIds: [1, 2, 3, 4, 5],
    resendNumber: 0
);

echo "Adicionados à fila: " . $result['queued'];
```

### Processar Fila

```php
$result = $queue->processQueue(batchSize: 100);

echo "Enviados: " . $result['sent'];
echo "Falhas: " . $result['failed'];
```

---

## 🎨 Interface

### Dashboard
- Estatísticas gerais (contatos, envios, aberturas, cliques)
- Gráficos interativos com Chart.js
- Limites AWS SES em tempo real
- Campanhas e mensagens recentes

### Tracking
- Pixel transparente 1x1 para aberturas
- Redirecionamento de links para tracking de cliques
- Páginas responsivas de opt-out
- Atualização automática de métricas e scores de qualidade

---

## 🔐 Configuração AWS SES

### 1. Verificar Domínio

1. Acesse https://console.aws.amazon.com/ses/
2. **Identidades** → **Criar identidade** → **Domínio**
3. Digite seu domínio
4. Copie os registros DNS

### 2. Configurar DNS

Use o DNSValidator para validar:

```php
$validator = new DNSValidator();
$result = $validator->validateAll('seudominio.com');

if (!$result['spf']['valid']) {
    echo "Configure SPF: " . $result['spf']['suggestion'];
}
```

### 3. Criar Credenciais IAM

1. Acesse https://console.aws.amazon.com/iam/
2. **Usuários** → **Adicionar usuário**
3. Permissões: **AmazonSESFullAccess**
4. Copie Access Key e Secret Key

---

## 📊 Estrutura do Projeto

```
Mailer/
├── app/
│   ├── Controllers/
│   │   ├── DashboardController.php  ✅
│   │   └── TrackController.php      ✅
│   ├── Models/
│   │   ├── ContactModel.php         ✅
│   │   ├── CampaignModel.php        ✅
│   │   ├── MessageModel.php         ✅
│   │   ├── MessageSendModel.php     ✅
│   │   ├── MessageOpenModel.php     ✅
│   │   ├── MessageClickModel.php    ✅
│   │   ├── SenderModel.php          ✅
│   │   └── OptoutModel.php          ✅
│   ├── Libraries/
│   │   ├── AWS/
│   │   │   └── SESService.php       ✅
│   │   ├── DNS/
│   │   │   └── DNSValidator.php     ✅
│   │   └── Email/
│   │       └── QueueManager.php     ✅
│   └── Views/
│       ├── layouts/
│       │   └── main.php             ✅
│       ├── dashboard/
│       │   └── index.php            ✅
│       └── tracking/
│           ├── optout_confirm.php   ✅
│           ├── optout_success.php   ✅
│           └── optout_already.php   ✅
├── database_schema.sql              ✅
└── README.md
```

---

## 🔄 Próximos Passos (A Implementar)

- [ ] Controllers restantes (Campaigns, Messages, Contacts, Senders, Templates)
- [ ] Views completas para todos os módulos
- [ ] Editor GrapesJS integrado
- [ ] Sistema de reenvios automáticos
- [ ] Autenticação Google OAuth + Passkeys
- [ ] Validação de opt-out link no HTML
- [ ] Gráficos avançados de análise
- [ ] API REST
- [ ] Webhooks SNS da AWS
- [ ] Testes automatizados

---

## 🛠️ Tecnologias

- **Backend**: CodeIgniter 4.6.3, PHP 8.1
- **Frontend**: Bootstrap 5, jQuery, Font Awesome
- **Gráficos**: Chart.js
- **Notificações**: Alertify.js
- **AWS**: aws/aws-sdk-php 3.359+
- **Planilhas**: phpoffice/phpspreadsheet 5.2+

---

## 📝 Licença

Uso pessoal.

---

## 👤 Autor

Sistema desenvolvido para uso profissional de email marketing.

---

**Mailer v1.1** - Sistema Profissional de Email Marketing  
✅ Core + Controllers + Views + Tracking implementados
