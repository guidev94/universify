# Tasks: Save e Mundos

| # | Tarefa | Depende | Feito quando |
|---|---|---|---|
| SV-T1 | Camada localStorage com try/catch | não | Quota tratada |
| SV-T2 | Criar/listar/deletar mundos | SV-T1 | Menu funcional |
| SV-T3 | Snapshot e restore completos | SV-T2 | Reload restaura tudo |
| SV-T4 | Diffs de blocos por chunk | Mundo | Construções persistem |
| SV-T5 | Autosave e flush ao sair | SV-T3 | Sem perda em 30 s |
| SV-T6 | Exportar/importar arquivo | SV-T3 | Round-trip idêntico |
| SV-T7 | Código de mundo | SV-T2 | Mesmo terreno em outro navegador |
| SV-T8 | Seed do dia | SV-T2 | Estável por 24 h |
| SV-T9 | Validação e migração | SV-T6 | Save inválido não quebra |
| SV-T10 | Tela de mundos e cards | SV-T2 | 12 mundos listados |
