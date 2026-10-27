# Lulicraft: Game Design Document

## 1. Fantasia central

O mundo de **Lumi** era colorido até a **Aurora** adormecer. Agora as cores escapam em fragmentos. O jogador é um **Jardineiro de Lumi**: explora, coleta, constrói e acorda cristais para devolver cor e luz aos biomas.

Não há vilão. O "desafio" é a curiosidade: encontrar o próximo item raro, a próxima estrutura, o próximo bioma.

## 2. Loop principal (30 s)

```
Explorar → avistar algo brilhante → quebrar/coletar → celebrar descoberta
   ↑                                                        ↓
   └──────── construir, criar na forja, mostrar no álbum ←──┘
```

### Loop de sessão (15 min)

1. Acordar no mundo (spawn seguro).
2. Seguir a bússola de Lumi até um ponto de interesse.
3. Coletar recursos e 1 a 2 itens raros.
4. Craftar algo que abre novas possibilidades (picareta, botas, lâmpada).
5. Construir ou decorar um cantinho.
6. Salvar/fotografar/compartilhar.

### Loop de longo prazo

- Completar o álbum (20+ itens).
- Encontrar a Coroa de Lumi (1 por mundo).
- Descobrir os 5 biomas.
- Construir uma base em cada bioma.
- Completar desafios semanais.

## 3. Modos

| Modo | Regras | Para quem |
|---|---|---|
| **Aventura Cozy** | Recursos finitos, forja consome, voo bloqueado | Descoberta e construção com propósito |
| **Criativo** | Blocos infinitos, voo, forja grátis, sem drops | Construção livre e crianças menores |

Sem dano, morte, fome ou queda. Água permite nadar; no Criativo, voar.

## 4. Biomas

| Bioma | Paleta | Sensação | Itens-assinatura |
|---|---|---|---|
| **Prado Musgo** | verde-lima, amarelo | acolhedor, inicial | Flor Rosa, Mel, Fragmento |
| **Bosque Arco-Íris** | pastel multicolor | encantado | Semente Prismática, Cogumelo Estelar |
| **Deserto Coral** | coral, areia quente | vasto, misterioso | Cristal Solar, Coral |
| **Tundra Lumina** | azul-gelo, branco | silencioso, mágico | Gema Aurora, Gelo |
| **Costa de Nuvens** | turquesa, branco | leve, aberto | Pena de Nuvem, Bloco de Nuvem |

Cada bioma tem uma "assinatura sonora" (nota base diferente no ambiente) e partículas próprias.

## 5. Progressão

```
Início: mãos + 4 blocos de terra
  → quebrar árvore → tábuas → baú
  → achar Fragmento de Lumi → lâmpada
  → achar Cristal → picareta de cristal
  → achar Gema Aurora → picareta aurora (3×3)
  → achar Pena → botas de nuvem (salto duplo)
  → juntar 8 fragmentos + gema → Coração de Lumi (radar)
  → encontrar Coroa de Lumi (troféu mítico)
```

Sem árvore de habilidades. A progressão é **horizontal e por descoberta**.

## 6. Inventários

1. **Barra rápida** (9), construção.
2. **Mochila** (27), tudo.
3. **Bolsa de Sementes** (9), sementes e alimentos, auto-roteados.
4. **Baú** (27 por baú), armazenamento no mundo.
5. **Forja**, receitas.
6. **Álbum**, coleção, não armazena.

## 7. Raridade e recompensa

- Comum, Incomum, Raro, Épico, Lendário, Mítico.
- Pistas múltiplas: cor + estrelas + som + texto + partículas.
- Item raro: toast, som cristalino, partículas ao redor do jogador e registro no álbum.
- Mítico: cartão especial e efeito de aurora no céu.

## 8. Conquistas

| Conquista | Condição |
|---|---|
| Primeiro Brilho | Quebrar o primeiro bloco |
| Jardineiro | Colocar 20 blocos |
| Colecionador | Descobrir 10 itens |
| Caçador de Raros | Encontrar 3 itens raros |
| Arquiteto | Colocar 100 blocos |
| Explorador | Visitar 5 biomas |
| Forjador | Criar 5 itens |
| Coroado | Encontrar a Coroa de Lumi |

## 9. Desafio diário/semanal

- **Diário:** "Colete 5 Fragmentos de Lumi" no mundo do dia.
- **Semanal:** seed fixa + "encontre 3 itens raros" ou "construa 50 blocos".
- Recompensa: selo no cartão do mundo, sem moeda premium.

## 10. Áudio

- Notas pentatônicas por bioma; sinos para raros.
- Passos com variação de tom por bloco.
- Ambiente suave (vento, água, grilos) em loop gerado por WebAudio.
- Sem voz, sem letras, sem áudio agressivo.

## 11. Viralidade ética

- Seed do dia, código de mundo, cartão do mundo, modo foto, álbum.
- Zero upload automático, zero conta, zero analytics.
- Compartilhar é sempre uma ação explícita do jogador.
- Convite de sala é opt-in; não há chat nem lista pública de salas.

## 12. Personagens

- 9 skins/presets: Lumi, Nino, Bia, Tico, Dara, Sol, Maya, Kai e Luna.
- 4 skins de arma: Aurora, Pétala, Solar e Bolha.
- 5 armas mágicas sem dano: Lâmina de Pétalas, Varinha de Bolhas, Estilingue Estelar, Martelo Solar e Arco Aurora.
- Nome + 4 cores + acessório; tudo cosmético.
- Aparência aparece no cartão do mundo e nos avatares multiplayer.
- Criança pode começar como Lumi e personalizar depois, sem loja.

## 13. Mundos prontos e Kit de Raros

- 6 cards no menu: Jardim de Lumi, Vale Arco-Íris, Ilha das Nuvens, Deserto Coral, Noite de Aurora e Caixa de Raros.
- Dois mundos usam Kit de Raros; o checkbox também permite ativá-lo em qualquer mundo novo.
- Kit de Raros: 10 itens raros+ para uma primeira sessão cheia de descobertas.
- Kit é opcional; o início normal continua com o kit inicial cozy.

## 14. Multiplayer cozy

- Até 6 exploradores por sala, por convite de 5 caracteres ou link.
- Host mantém o mundo e retransmite blocos, baús, posições e patches.
- Sem chat, voz, contas, matchmaking público ou combate.
- Convidado entra com personagem próprio e starter kit.
- Se o host sair, a sala fecha e o convidado continua no mundo local.
- Segurança: convite não é senha; compartilhe só com pessoas de confiança.

## 15. Métricas de diversão

- "Uau" por minuto: ≥ 1 descoberta relevante a cada 3 min.
- Fricção zero: nenhuma ação essencial exige menu profundo.
- Rejogabilidade: seeds diferentes mudam biomas e estruturas.
