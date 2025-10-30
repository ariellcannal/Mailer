# Mailer - Sistema de Email Marketing

Sistema profissional de email marketing com CodeIgniter 4, AWS SES e validação DNS automática.

## Funcionalidades Implementadas

✅ AWS SES Service - Envio completo de emails
✅ DNS Validator - Validação automática SPF/DKIM/DMARC/MX  
✅ Contact Model - Gestão completa de contatos com import em massa
✅ Banco de dados completo (20 tabelas)
✅ Sistema de classificação de qualidade de contatos (1-5)

## Instalação Rápida

1. composer install
2. Criar banco: CREATE DATABASE mailer;
3. Importar: mysql -u root -p mailer < database_schema.sql
4. Configurar arquivo env (renomear para .env)
5. php spark key:generate
6. chmod -R 777 writable/
7. php spark serve

Acesse: http://localhost:8080

## Documentação

- INSTALLATION.md - Guia completo
- USER_GUIDE.md - Guia de uso
- DNS_CONFIGURATION.md - Configuração DNS

## Repositório

https://github.com/ariellcannal/Mailer
