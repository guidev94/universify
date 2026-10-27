# Feature: Personagens

## Objetivo

Dar identidade ao explorador sem criar vantagem de gameplay, loja ou complexidade para crianças.

## Modelo

```js
{
  preset: 'lumi' | 'nino' | 'bia' | 'tico' | 'dara' | 'custom',
  custom: boolean,
  name: string,        // 1..16, sem <>; padrão "Lumi"
  skin: '#rrggbb',
  shirt: '#rrggbb',
  pants: '#rrggbb',
  hair: '#rrggbb',
  accessory: 'none' | 'leaf' | 'flower' | 'star' | 'glasses'
}
```

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| CHR-01 | Oferecer 9 skins/presets: Lumi, Nino, Bia, Tico, Dara, Sol, Maya, Kai, Luna | Must |
| CHR-02 | Permitir editar nome e 4 cores | Must |
| CHR-03 | Oferecer 30 swatches seguros e legíveis | Must |
| CHR-04 | Normalizar cor inválida para o padrão | Must |
| CHR-05 | Remover `<>` do nome e limitar a 16 caracteres | Must |
| CHR-06 | Persistir personagem em `lulicraft:settings` | Must |
| CHR-07 | Mostrar nome do personagem no cartão/foto | Should |
| CHR-08 | Mostrar avatar do personagem no multiplayer | Should |
| CHR-09 | Personagem é cosmético e não altera status | Must |
| CHR-10 | Prévia blocky funciona em desktop e toque | Must |

## Critérios de aceite

- Abrir personagem, escolher preset, trocar cor e salvar; recarregar mantém.
- `normalizeCharacter(null)` retorna o preset Lumi, não `custom`.
- Nome `<Dara>` vira `Dara`; cor `red` vira a cor padrão.
- Avatar aparece no cartão do mundo e na sala multiplayer.
- Nenhum erro de console ao abrir/fechar o modal.

## Fora de escopo

- Skins importadas, upload de imagem, roupas pagas.
- Vantagens, classes ou atributos.
- Editor 3D com partes livres.
