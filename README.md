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
