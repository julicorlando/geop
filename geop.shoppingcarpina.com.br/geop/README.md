# Sistema de Ordens de Serviço - Shopping Carpina

Sistema web responsivo em **PHP + MySQL** para abertura, acompanhamento e encerramento de Ordens de Serviço da manutenção.

## Recursos

- Login com controle de perfil.
- Cadastro de usuários exclusivo para o perfil **TI**.
- Perfis: TI, Gestor e Agente.
- Gestor/TI abre OS, atribui agente, define prioridade e acompanha execução.
- Agente visualiza apenas suas OS, atualiza status, envia anexos e informa materiais.
- Histórico completo por OS.
- Upload de fotos/documentos.
- Relatórios por status, categoria e agente.
- Layout responsivo com Bootstrap.

## Requisitos

- PHP 8.0 ou superior.
- MySQL 5.7+ ou MariaDB 10+.
- Extensão PHP PDO MySQL habilitada.
- Servidor Apache, Nginx ou servidor embutido do PHP.

## Instalação local rápida

1. Extraia o ZIP.
2. Importe o banco:

```bash
mysql -u root -p < sql/schema.sql
```

3. Ajuste as credenciais em:

```text
config/database.php
```

4. Rode com o servidor embutido do PHP:

```bash
php -S localhost:8000 -t public
```

5. Acesse:

```text
http://localhost:8000
```

## Login inicial

```text
E-mail: ti@shoppingcarpina.com.br
Senha: 123456
```

Após o primeiro acesso, recomenda-se alterar a senha do usuário TI.

## Implantação em hospedagem compartilhada

1. Envie o conteúdo da pasta do projeto para a hospedagem.
2. Configure o domínio/subdomínio para apontar para a pasta `public`.
3. Crie um banco MySQL no painel da hospedagem.
4. Importe `sql/schema.sql` pelo phpMyAdmin.
5. Edite `config/database.php` com host, nome do banco, usuário e senha.
6. Garanta permissão de escrita na pasta:

```text
public/uploads
```

## Perfis e permissões

### TI

- Cadastra, edita, ativa e inativa usuários.
- Cadastra setores, locais e categorias.
- Visualiza todas as OS.
- Pode abrir e atualizar OS.

### Gestor

- Abre OS.
- Atribui agente.
- Atualiza status gerencial.
- Aprova/encerra OS.
- Visualiza relatórios.
- Não cadastra usuários.

### Agente

- Visualiza apenas OS atribuídas a ele.
- Atualiza andamento.
- Envia fotos/documentos.
- Registra materiais.
- Marca OS como concluída pelo agente.
- Não cadastra usuários.

## Estrutura de pastas

```text
config/database.php      Configuração do banco
includes/auth.php        Autenticação e permissões
includes/helpers.php     Funções auxiliares
includes/layout.php      Cabeçalho e rodapé
public/index.php         Dashboard
public/login.php         Login
public/ordens.php        Lista de OS
public/ordem_form.php    Nova OS
public/ordem_detalhe.php Detalhes e atualizações da OS
public/usuarios.php      Gestão de usuários pelo TI
public/cadastros.php     Setores, locais e categorias
public/relatorios.php    Relatórios
public/uploads           Arquivos enviados
sql/schema.sql           Banco de dados completo
```

## Observações importantes

- Este projeto é funcional e pronto para uso básico.
- Para produção, use HTTPS.
- Mantenha backups do banco e da pasta `public/uploads`.
- Altere a senha inicial do TI.
- O envio de e-mail/WhatsApp não está integrado neste pacote, mas a estrutura permite adicionar notificações depois.
