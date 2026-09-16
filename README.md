# SysCampus — HTML + CSS + JavaScript + PHP + MySQL

Arquitetura separada:
- HTML: páginas da interface na raiz (`index.html`, `dashboard.html`, etc.)
- CSS: `assets/css/styles.css`
- JavaScript: `assets/js/app.js`
- Imagens PNG: `assets/img/`
- PHP: `php/` (backend, autenticação, CRUD e acesso ao MySQL)
- Banco: `database/schema.sql`

## Instalação no XAMPP
1. Extraia a pasta `syscampus` em `C:\xampp\htdocs\`.
2. Mantenha o MySQL existente na porta 3306 ativo; o MySQL do XAMPP pode permanecer parado.
3. Edite `config.php` e informe a senha do seu usuário MySQL.
4. Abra `http://localhost/syscampus/`.
5. Não é necessário Node.js.

Credenciais de demonstração (se a tabela users estiver vazia):
- Usuário: aluno@lumenveritas.edu.br / 123456
- Administrador: admin@lumenveritas.edu.br / admin123

Observação: como o HTML é estático e o PHP fica separado, o JavaScript chama scripts PHP de backend para autenticação e operações no MySQL. Não há Node.js nem arquivos `.php` misturados com o HTML.


Atualização: a homepage possui áreas de login distintas para usuário e administrador. O PHP valida o perfil solicitado no servidor; usuários comuns não podem entrar pela área administrativa e administradores não entram pela área de usuário.


## Instalação local corrigida
1. Coloque esta pasta `syscampus` diretamente em `C:\xampp\htdocs\syscampus`.
2. Inicie apenas o Apache do XAMPP. O MySQL usado pelo projeto é o MySQL Server 8.0 em `127.0.0.1:3306`.
3. Abra `http://localhost/syscampus/`.
4. O banco esperado é `syscampus`.

## Recuperação e redefinição de senha

O login possui o fluxo "Esqueceu sua senha?" integrado ao PHP e MySQL. O envio é feito pelo PHPMailer via SMTP.

### Instalação do PHPMailer

Na pasta raiz do projeto execute:

```bash
composer install
```

Isso cria a pasta `vendor/`.

### Configuração SMTP

Edite `mail.config.php`:

```php
return [
  'host' => 'smtp.gmail.com',
  'port' => 587,
  'encryption' => 'tls',
  'username' => 'SEU_EMAIL_GMAIL',
  'password' => 'SUA_SENHA_DE_APLICATIVO',
  'from_email' => 'SEU_EMAIL_GMAIL',
  'from_name' => 'SysCampus — Lumen Veritas'
];
```

Para Gmail, utilize uma **senha de aplicativo**, e não a senha normal da conta.

### Banco de dados

Se o banco já existir, execute:

```text
database/migration_password_reset.sql
```

Se o banco estiver sendo criado do zero, `database/schema.sql` já contém os campos de recuperação.

O token é gerado com `random_bytes`, expira em 1 hora, não revela ao solicitante se o e-mail está cadastrado e é invalidado após a redefinição.
