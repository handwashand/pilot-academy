# Pilot Academy — Guia do administrador

Guia para criar cursos, gerenciar o teste final e emitir certificados.

## 1. Entrar e se localizar

Abra o endereço do site e adicione `/admin`. Entre com o e-mail e a senha de administrador.

Você chega ao **Painel**, com resumo de estudantes, progresso, cursos publicados e certificados emitidos. O menu à esquerda leva a cada área da academia.

Altere seu nome, e-mail ou senha pelo menu da conta, no canto superior direito, em **Perfil**.

## 2. O menu em resumo

| Item | Para que serve |
|---|---|
| **Painel** | Indicadores gerais, progresso por empresa e estudantes parados. |
| **Content → Courses** | Criar cursos e ativar teste final e certificado. |
| **Content → Lessons** | Adicionar vídeo, texto e perguntas. |
| **Content → Products** | Produtos ou módulos e seus responsáveis. |
| **Content → Media Items** | Biblioteca de imagens reutilizáveis. |
| **People → Users** | Contas, funções, progresso e certificados. |
| **People → Companies** | Empresas parceiras e seus estudantes. |
| **Results → Certificates** | Baixar, reenviar, revogar ou restaurar certificados. |
| **Results → Final quiz health** | Ver se o teste final está fácil ou difícil demais. |
| **Docs → Guide** | Este guia. |
| **Docs → What's new** | Mudanças recentes e notas de versão. |

Os estudantes têm o próprio guia em **Ajuda**, o botão **?** na barra superior.

## 3. Início rápido: de curso vazio a certificado

O caminho é sempre: **criar curso → adicionar aulas → ativar teste final → publicar → estudante passa → recebe certificado**.

### Etapa 1 · Criar um curso

Em **Courses**, clique em **New course**. Preencha título, descrição curta, duração opcional, nível e produto/módulo. Novos cursos começam como **Draft**.

### Etapa 2 · Adicionar aulas

Em **Lessons**, crie uma aula, escolha o curso, adicione vídeo, texto, duração, transcrição e perguntas. A aula precisa de uma verificação de conhecimento para que o estudante consiga concluí-la.

Ordene as aulas na aba **Lessons** do curso. Mover uma aula existente a remove do curso anterior; para copiar material, use **Duplicate** no curso.

### Etapa 3 · Ativar o teste final

No curso, ative **Final quiz & certificate**, defina nota mínima, perguntas por tentativa e máximo de tentativas. Depois adicione perguntas em **Final questions**.

### Etapa 4 · Publicar o curso

Na lista de cursos, use **Publish**. Um curso precisa de pelo menos uma aula publicada. **Unpublish** oculta o curso sem apagar conteúdo, progresso ou certificados.

### Etapa 5 · Testar como estudante

Abra o site público, conclua as aulas e confirme que o teste final desbloqueia.

### Etapa 6 · O certificado

Ao passar, o certificado é gerado automaticamente em PDF com nome, curso, data, número único e QR code.

## 4. Como configurar

Edite perguntas do teste final na aba **Final questions** do curso. Uma pergunta vinda de uma aula é a mesma pergunta; editá-la muda também a aula.

Envie um fundo de certificado em **Final quiz & certificate → Certificate background**. Use imagem A4 paisagem ou deixe em branco para usar o modelo integrado.

Duplique cursos pela linha do curso quando quiser reaproveitar a estrutura. A cópia é um rascunho independente e não copia progresso nem certificados.

Para que um responsável de produto crie treinamento, dê a ele a função **Creator** e marque seus produtos em **Users**. Ele verá apenas cursos e aulas desses produtos.

Para adicionar empresa e estudante, crie a empresa em **Companies** e depois crie o usuário com empresa e função.

## 5. Como acompanhar

Use **Users** para filtrar por função e abrir o progresso de um estudante.

O **Painel** mostra conteúdo com problemas, indicadores, progresso por empresa, estudantes parados, aulas difíceis, atividade e cursos mais abertos.

Em **Student feedback**, dentro do curso, leia comentários privados dos estudantes.

Em **Students who have gone quiet**, use **Send reminder**. Ninguém recebe dois lembretes dentro de 7 dias.

Use **Ctrl+K** ou **⌘K** para buscar cursos, aulas e pessoas rapidamente.

Em **Final quiz health**, acompanhe aprovação na primeira tentativa e dias até o certificado.

Em **What's new**, veja mudanças por versão ou baixe um PDF.

Em **Certificates**, filtre por curso, empresa ou status. A página pública `/certificates/{number}` verifica um certificado.

## 6. O que fazer se…

| Situação | O que fazer |
|---|---|
| O estudante não recebeu o e-mail | Reenvie em **Certificates**. |
| Um certificado saiu por engano | Use **Revoke**. |
| O PDF está vazio ou falha | Use **Regenerate PDF** e baixe novamente. |
| Precisa de relatório | Exporte CSV em **Certificates** ou **Users**. |
| Acabaram as tentativas | Aumente ou limpe o máximo de tentativas do curso. |
| Estudantes não acham um curso | Verifique se está **Published**. |
| Falta uma aula | Verifique se curso e aula estão publicados. |
| Um creator não vê o curso | Confira o **Product / module** do curso. |
| Precisa tirar do ar | Use **Unpublish**. |

## 7. Perguntas comuns

**Certificados expiram?** Não.

**O estudante pode refazer o teste final depois de passar?** Não.

**De onde vem o nome do certificado?** Do nome digitado pelo estudante antes do teste final.

**E se o e-mail não estiver configurado?** O certificado ainda é criado e pode ser baixado.

**Posso mudar a nota mínima?** Sim, por curso.

**Quantos certificados por estudante em um curso?** Um certificado válido.

**Posso testar o final sem concluir as aulas?** Sim, administradores podem pré-visualizar e depois revogar o certificado de teste.

## 8. Termos usados aqui

| Termo | Significado |
|---|---|
| Teste final | Prova do curso inteiro. |
| Banco de perguntas | Conjunto de perguntas do teste final. |
| Nota mínima | Percentual necessário para passar. |
| Tentativa | Uma vez fazendo o teste. |
| Certificado | PDF que prova a aprovação. |
| Revogar | Invalidar um certificado. |
| Parceiro | Empresa à qual os estudantes pertencem. |
| Admin | Gerencia toda a plataforma. |
| Creator | Responsável que cria treinamento para seus produtos. |
| Learner | Estudante que faz cursos. |
| Draft | Não visível para estudantes. |
| Published | Visível no site do estudante. |
| Archived | Retirado, mas preservado. |
